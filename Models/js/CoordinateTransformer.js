/**
 * CoordinateTransformer Module
 * Handles coordinate system transformations using proj4.js
 * Supports Texas State Plane zones and UTM zones
 */

const CoordinateTransformer = (() => {
    // Projection definitions for Texas coordinate systems
    const PROJ_DEFINITIONS = {
        'NAD83(2011) TX State Plane North (4201)': {
            proj: '+proj=lcc +lat_1=36.11666666666667 +lat_2=34.65 +lat_0=34.00 +lon_0=-101.5 +x_0=2000000 +y_0=0 +datum=NAD83 +units=us-ft +no_defs',
            zone: 4201,
            unit: 'US Survey Feet',
            bounds: { minN: 3000000, maxN: 11000000, minE: 1500000, maxE: 3500000 }
        },
        'NAD83(2011) TX State Plane North Central (4202)': {
            proj: '+proj=lcc +lat_1=33.96666666666667 +lat_2=32.13333333333333 +lat_0=31.66666666666667 +lon_0=-98.5 +x_0=2000000 +y_0=0 +datum=NAD83 +units=us-ft +no_defs',
            zone: 4202,
            unit: 'US Survey Feet',
            bounds: { minN: 2800000, maxN: 10500000, minE: 1500000, maxE: 3500000 }
        },
        'NAD83(2011) TX State Plane Central (4203)': {
            proj: '+proj=lcc +lat_1=31.88333333333333 +lat_2=30.11666666666667 +lat_0=29.66666666666667 +lon_0=-100.3333333333333 +x_0=2000000 +y_0=0 +datum=NAD83 +units=us-ft +no_defs',
            zone: 4203,
            unit: 'US Survey Feet',
            bounds: { minN: 2600000, maxN: 10200000, minE: 2000000, maxE: 3500000 }
        },
        'NAD83(2011) TX State Plane South Central (4204)': {
            proj: '+proj=lcc +lat_1=30.28333333333333 +lat_2=28.38333333333333 +lat_0=27.83333333333333 +lon_0=-99 +x_0=2000000 +y_0=0 +datum=NAD83 +units=us-ft +no_defs',
            zone: 4204,
            unit: 'US Survey Feet',
            bounds: { minN: 2400000, maxN: 10000000, minE: 2000000, maxE: 3500000 }
        },
        'NAD83(2011) TX State Plane South (4205)': {
            proj: '+proj=lcc +lat_1=27.83333333333333 +lat_2=26.16666666666667 +lat_0=25.5 +lon_0=-98.5 +x_0=2000000 +y_0=0 +datum=NAD83 +units=us-ft +no_defs',
            zone: 4205,
            unit: 'US Survey Feet',
            bounds: { minN: 2200000, maxN: 9800000, minE: 2000000, maxE: 3500000 }
        },
        'NAD83(2011) UTM Zone 13N': {
            proj: '+proj=utm +zone=13 +datum=NAD83 +units=m +no_defs',
            zone: '13N',
            unit: 'Meters',
            bounds: { minN: 2500000, maxN: 4500000, minE: 200000, maxE: 900000 }
        },
        'NAD83(2011) UTM Zone 14N': {
            proj: '+proj=utm +zone=14 +datum=NAD83 +units=m +no_defs',
            zone: '14N',
            unit: 'Meters',
            bounds: { minN: 2500000, maxN: 4500000, minE: 200000, maxE: 900000 }
        },
        'NAD83(2011) UTM Zone 15N': {
            proj: '+proj=utm +zone=15 +datum=NAD83 +units=m +no_defs',
            zone: '15N',
            unit: 'Meters',
            bounds: { minN: 2500000, maxN: 4500000, minE: 200000, maxE: 900000 }
        }
    };

    const WGS84_PROJ = '+proj=longlat +datum=WGS84 +no_defs';
    const TEXAS_BOUNDS = { minLat: 25.8, maxLat: 36.5, minLon: -106.6, maxLon: -93.5 };

    /**
     * Transform coordinates from source system to WGS84
     * @param {string} coordSystem - Coordinate system name from PROJ_DEFINITIONS
     * @param {number} northing - Northing/Y coordinate
     * @param {number} easting - Easting/X coordinate
     * @returns {Object} {success, lat, lon, zone, message}
     */
    const transformToWGS84 = (coordSystem, northing, easting) => {
        const def = PROJ_DEFINITIONS[coordSystem];
        if (!def) {
            return { success: false, message: `Unknown coordinate system: ${coordSystem}` };
        }

        try {
            // Get proj4 from global scope (try multiple locations)
            let proj4Lib = null;

            if (typeof window !== 'undefined' && typeof window.proj4 === 'function') {
                proj4Lib = window.proj4;
            } else if (typeof proj4 !== 'undefined' && typeof proj4 === 'function') {
                proj4Lib = proj4;
            } else if (typeof global !== 'undefined' && typeof global.proj4 === 'function') {
                proj4Lib = global.proj4;
            }

            if (!proj4Lib) {
                // Return error but include diagnostic info
                const available = [];
                if (typeof window !== 'undefined') available.push('window');
                if (typeof global !== 'undefined') available.push('global');
                return {
                    success: false,
                    message: `proj4.js not available. Please ensure https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.10.2/proj4.min.js is loaded and accessible. (Checked: ${available.join(', ')})`
                };
            }

            const sourceProj = proj4Lib(def.proj);
            const targetProj = proj4Lib(WGS84_PROJ);
            // proj4.js returns [longitude, latitude] for geographic output
            const transformed = proj4Lib(sourceProj, targetProj, [easting, northing]);

            console.log(`Transform ${coordSystem}: input [E:${easting}, N:${northing}] -> output [${transformed[0]}, ${transformed[1]}]`);

            return {
                success: true,
                lat: transformed[1],  // Second value = latitude
                lon: transformed[0],  // First value = longitude
                zone: def.zone,
                unit: def.unit
            };
        } catch (err) {
            return { success: false, message: `Transformation error: ${err.message}` };
        }
    };

    /**
     * Validate if transformed coordinates are within reasonable bounds
     * @param {number} lat - Latitude
     * @param {number} lon - Longitude
     * @returns {Object} {valid, warning}
     */
    const validateCoordinates = (lat, lon) => {
        const warnings = [];

        if (lat < TEXAS_BOUNDS.minLat || lat > TEXAS_BOUNDS.maxLat) {
            warnings.push(`Latitude ${lat.toFixed(4)} outside typical Texas bounds (${TEXAS_BOUNDS.minLat}–${TEXAS_BOUNDS.maxLat}°N)`);
        }

        if (lon < TEXAS_BOUNDS.minLon || lon > TEXAS_BOUNDS.maxLon) {
            warnings.push(`Longitude ${lon.toFixed(4)} outside typical Texas bounds (${TEXAS_BOUNDS.minLon}–${TEXAS_BOUNDS.maxLon}°W)`);
        }

        return {
            valid: true,
            warning: warnings.length > 0 ? warnings.join('; ') : null
        };
    };

    /**
     * Get list of available coordinate systems
     * @returns {Array} Array of system names
     */
    const getAvailableSystems = () => Object.keys(PROJ_DEFINITIONS);

    /**
     * Get definition for a coordinate system
     * @param {string} coordSystem - System name
     * @returns {Object} System definition or null
     */
    const getSystemDefinition = (coordSystem) => PROJ_DEFINITIONS[coordSystem] || null;

    /**
     * Check if coordinates fall within expected bounds for a system
     * @param {number} northing - Northing value
     * @param {number} easting - Easting value
     * @param {string} coordSystem - Coordinate system name
     * @returns {Object} {withinBounds, message}
     */
    const validateSystemBounds = (northing, easting, coordSystem) => {
        const def = PROJ_DEFINITIONS[coordSystem];
        if (!def || !def.bounds) {
            return { withinBounds: true };
        }

        const { minN, maxN, minE, maxE } = def.bounds;
        const errors = [];

        if (northing < minN || northing > maxN) {
            errors.push(`Northing ${northing.toFixed(0)} outside expected range for ${coordSystem} (${minN}–${maxN})`);
        }

        if (easting < minE || easting > maxE) {
            errors.push(`Easting ${easting.toFixed(0)} outside expected range for ${coordSystem} (${minE}–${maxE})`);
        }

        return {
            withinBounds: errors.length === 0,
            message: errors.length > 0 ? errors.join('; ') : null
        };
    };

    // Public API
    return {
        transformToWGS84,
        validateCoordinates,
        getAvailableSystems,
        getSystemDefinition,
        validateSystemBounds,
        PROJ_DEFINITIONS,
        TEXAS_BOUNDS
    };
})();
