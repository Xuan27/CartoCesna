/**
 * CSVParser Module
 * Parses PNEZD CSV format and transforms coordinates
 */

const CSVParser = (() => {
    // Column name aliases (case-insensitive matching)
    const COLUMN_ALIASES = {
        point: ['p', 'point', 'point_id', 'point#', 'pointid', 'pt_id', 'id'],
        northing: ['n', 'northing', 'north', 'yn'],
        easting: ['e', 'easting', 'east', 'xe'],
        elevation: ['z', 'elevation', 'elev', 'height', 'alt', 'altitude'],
        description: ['d', 'description', 'desc', 'name', 'note']
    };

    /**
     * Normalize column headers to lowercase and trim
     * @param {Array} headers - Raw header array from CSV
     * @returns {Array} Normalized headers
     */
    const normalizeHeaders = (headers) => {
        return headers.map(h => (h || '').toLowerCase().trim());
    };

    /**
     * Find column index by checking aliases
     * @param {Array} headers - Normalized headers
     * @param {string} columnType - One of: point, northing, easting, elevation, description
     * @returns {number} Column index or -1
     */
    const findColumnIndex = (headers, columnType) => {
        const aliases = COLUMN_ALIASES[columnType] || [];
        return headers.findIndex(h => aliases.includes(h));
    };

    /**
     * Validate CSV headers contain all required columns
     * @param {Array} headers - Normalized headers
     * @returns {Object} {valid, missingColumns}
     */
    const validateHeaders = (headers) => {
        const required = ['point', 'northing', 'easting', 'elevation', 'description'];
        const missing = [];

        required.forEach(col => {
            if (findColumnIndex(headers, col) === -1) {
                missing.push(col);
            }
        });

        return {
            valid: missing.length === 0,
            missingColumns: missing
        };
    };

    /**
     * Parse and transform a PNEZD CSV file
     * @param {File} file - CSV file object
     * @param {string} coordSystem - Coordinate system name
     * @returns {Promise} Resolves to {points, errors}
     */
    const parseFile = (file, coordSystem) => {
        return new Promise((resolve, reject) => {
            if (!file) {
                reject(new Error('No file provided'));
                return;
            }

            if (!coordSystem) {
                reject(new Error('Coordinate system not specified'));
                return;
            }

            const reader = new FileReader();

            reader.onload = (e) => {
                try {
                    const text = e.target.result;
                    const result = parseText(text, coordSystem);
                    resolve(result);
                } catch (err) {
                    reject(err);
                }
            };

            reader.onerror = () => {
                reject(new Error('Failed to read file'));
            };

            reader.readAsText(file);
        });
    };

    /**
     * Parse CSV text content (supports optional headers)
     * @param {string} text - CSV text content
     * @param {string} coordSystem - Coordinate system name
     * @returns {Object} {points, errors}
     */
    const parseText = (text, coordSystem) => {
        if (!CoordinateTransformer) {
            throw new Error('CoordinateTransformer not loaded');
        }

        const lines = text.split(/\r?\n/).map(l => l.trim()).filter(l => l);

        if (lines.length === 0) {
            throw new Error('CSV file is empty');
        }

        // Detect if first row is a header by checking if it contains column aliases
        let startRow = 0;
        let indices = {
            point: -1,
            northing: -1,
            easting: -1,
            elevation: -1,
            description: -1
        };

        const firstLineNormalized = normalizeHeaders(lines[0].split(','));
        const headerValidation = validateHeaders(firstLineNormalized);

        if (headerValidation.valid) {
            // First row is a header
            indices = {
                point: findColumnIndex(firstLineNormalized, 'point'),
                northing: findColumnIndex(firstLineNormalized, 'northing'),
                easting: findColumnIndex(firstLineNormalized, 'easting'),
                elevation: findColumnIndex(firstLineNormalized, 'elevation'),
                description: findColumnIndex(firstLineNormalized, 'description')
            };
            startRow = 1;
        } else {
            // No header row - assume fixed column order: P, N, E, Z, D
            // Try to auto-detect from first data row if it looks like coordinates
            const firstDataCols = lines[0].split(',').map(c => c.trim());
            if (firstDataCols.length >= 5) {
                // Check if columns 1, 2, 3 look numeric (N, E, Z)
                const col1Numeric = !isNaN(parseFloat(firstDataCols[1]));
                const col2Numeric = !isNaN(parseFloat(firstDataCols[2]));
                const col3Numeric = !isNaN(parseFloat(firstDataCols[3]));

                if (col1Numeric && col2Numeric && col3Numeric) {
                    // Assume order: P(0), N(1), E(2), Z(3), D(4)
                    indices = {
                        point: 0,
                        northing: 1,
                        easting: 2,
                        elevation: 3,
                        description: 4
                    };
                    startRow = 0;
                } else {
                    throw new Error('CSV format unclear. Expected columns: P (Point ID), N (Northing), E (Easting), Z (Elevation), D (Description)');
                }
            } else {
                throw new Error('CSV has fewer than 5 columns. Expected: P (Point ID), N (Northing), E (Easting), Z (Elevation), D (Description)');
            }
        }

        if (lines.length <= startRow) {
            throw new Error('No data rows found in CSV');
        }

        const points = [];
        const errors = [];

        // Parse data rows
        for (let i = startRow; i < lines.length; i++) {
            const cols = lines[i].split(',').map(c => c.trim());
            const rowNumber = i + 1;

            // Check for sufficient columns
            const maxIdx = Math.max(Object.values(indices));
            if (cols.length <= maxIdx) {
                errors.push({
                    row: rowNumber,
                    message: 'Insufficient columns in row'
                });
                continue;
            }

            const pointId = cols[indices.point];
            const northing = parseFloat(cols[indices.northing]);
            const easting = parseFloat(cols[indices.easting]);
            const elevation = parseFloat(cols[indices.elevation]);
            const description = cols[indices.description];

            // Validate numeric fields
            if (isNaN(northing) || isNaN(easting) || isNaN(elevation)) {
                errors.push({
                    row: rowNumber,
                    pointId,
                    message: 'Northing, Easting, or Elevation is not a valid number'
                });
                continue;
            }

            // Validate coordinate system bounds
            const boundsCheck = CoordinateTransformer.validateSystemBounds(northing, easting, coordSystem);
            if (!boundsCheck.withinBounds) {
                errors.push({
                    row: rowNumber,
                    pointId,
                    message: boundsCheck.message
                });
                continue;
            }

            // Transform to WGS84
            const transform = CoordinateTransformer.transformToWGS84(coordSystem, northing, easting);
            if (!transform.success) {
                errors.push({
                    row: rowNumber,
                    pointId,
                    message: transform.message
                });
                continue;
            }

            // Validate transformed coordinates
            const validation = CoordinateTransformer.validateCoordinates(transform.lat, transform.lon);

            points.push({
                row: rowNumber,
                pointId,
                northing,
                easting,
                elevation,
                description,
                latitude: transform.lat,
                longitude: transform.lon,
                sourceSystem: coordSystem,
                unit: transform.unit,
                warning: validation.warning || null
            });
        }

        return { points, errors };
    };

    /**
     * Format a point for database insertion
     * @param {Object} point - Point object from parseFile
     * @param {string} projectId - Project ID
     * @param {string} datumEpoch - Datum/Epoch string
     * @returns {Object} Formatted point for API
     */
    const formatForInsert = (point, projectId, datumEpoch) => {
        return {
            project_id: projectId,
            point_number: point.pointId,
            point_name: point.description,
            point_type: 'Control',
            status: 'Set',
            northing: point.northing,
            easting: point.easting,
            elevation: point.elevation,
            latitude: point.latitude,
            longitude: point.longitude,
            coordinate_system: point.sourceSystem,
            datum_epoch: datumEpoch,
            units: point.unit
        };
    };

    // Public API
    return {
        parseFile,
        parseText,
        validateHeaders,
        normalizeHeaders,
        findColumnIndex,
        formatForInsert,
        COLUMN_ALIASES
    };
})();
