/**
 * BulkImportUI Module
 * Handles UI interactions for bulk CSV import of control points
 * Dependencies: CoordinateTransformer, CSVParser
 */

const BulkImportUI = (() => {
    // Cache for validated points
    let cachedValidatedPoints = [];

    /**
     * Initialize bulk import UI with coordinate systems and datums
     * @param {Array} coordSystems - Array of available coordinate systems
     * @param {Array} datumEpochs - Array of available datums/epochs
     */
    const init = (coordSystems, datumEpochs) => {
        const coordSelect = document.getElementById('bulkCoordSystem');
        const datumSelect = document.getElementById('bulkDatum');

        if (!coordSelect || !datumSelect) {
            console.warn('Bulk import elements not found in DOM');
            return;
        }

        // Populate coordinate system dropdown
        coordSelect.innerHTML = '<option value="">— Select Coordinate System —</option>' +
            coordSystems
                .map(cs => `<option value="${escapeHtml(cs)}">${escapeHtml(cs)}</option>`)
                .join('');

        // Populate datum dropdown
        datumSelect.innerHTML = '<option value="">— Select Datum/Epoch —</option>' +
            datumEpochs
                .map(de => `<option value="${escapeHtml(de)}">${escapeHtml(de)}</option>`)
                .join('');

        // Show bulk import section if it exists
        const section = document.getElementById('bulkImportSection');
        if (section) {
            section.style.display = 'block';
        }
    };

    /**
     * Toggle bulk import panel visibility
     * @param {Event} event - Click event (optional)
     */
    const toggle = (event) => {
        if (event) event.preventDefault();

        const body = document.getElementById('bulkImportBody');
        const icon = document.getElementById('bulkToggleIcon');

        if (!body) return;

        const isHidden = body.style.display === 'none';
        body.style.display = isHidden ? 'block' : 'none';

        if (icon) {
            icon.style.transform = isHidden ? 'rotate(180deg)' : '';
        }
    };

    /**
     * Validate and preview CSV data
     */
    const validateAndPreview = async () => {
        const file = document.getElementById('bulkCsvFile')?.files[0];
        const coordSystem = document.getElementById('bulkCoordSystem')?.value;

        if (!file) {
            showNotification('Please select a CSV file', 'error');
            return;
        }

        if (!coordSystem) {
            showNotification('Please select a coordinate system', 'error');
            return;
        }

        showLoadingSpinner('Parsing and transforming CSV...');

        try {
            const { points, errors } = await CSVParser.parseFile(file, coordSystem);

            // Cache points for later import
            cachedValidatedPoints = points;

            displayPreview(points, errors);

            if (points.length > 0) {
                const importBtn = document.getElementById('importBtn');
                if (importBtn) {
                    importBtn.style.display = 'inline-block';
                }
                showNotification(`${points.length} point(s) ready for import`, 'success');
            } else {
                const importBtn = document.getElementById('importBtn');
                if (importBtn) {
                    importBtn.style.display = 'none';
                }
                showNotification('No valid points found in CSV', 'error');
            }
        } catch (err) {
            const importBtn = document.getElementById('importBtn');
            if (importBtn) {
                importBtn.style.display = 'none';
            }
            showNotification(err.message || 'Error parsing CSV', 'error');
            console.error('CSV parse error:', err);
        }

        showLoadingSpinner(null);
    };

    /**
     * Display validation preview table
     * @param {Array} points - Valid points
     * @param {Array} errors - Invalid rows
     */
    const displayPreview = (points, errors) => {
        const container = document.getElementById('transformationPreview');
        if (!container) return;

        let html = '';

        // Error section
        if (errors.length > 0) {
            html += `
                <div class="validation-errors">
                    <h4>
                        <i class="fas fa-exclamation-circle"></i>
                        ${errors.length} Row(s) Failed
                    </h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Row</th>
                                <th>Point ID</th>
                                <th>Error</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            errors.forEach(err => {
                html += `
                    <tr class="error-row">
                        <td>${err.row}</td>
                        <td>${err.pointId || '—'}</td>
                        <td>${err.message}</td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;
        }

        // Success section
        if (points.length > 0) {
            html += `
                <div class="transformation-success">
                    <h4>
                        <i class="fas fa-check-circle"></i>
                        ${points.length} Row(s) Ready to Import
                    </h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Point ID</th>
                                <th>Northing</th>
                                <th>Easting</th>
                                <th>Latitude</th>
                                <th>Longitude</th>
                                <th>Elevation</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            const displayCount = Math.min(15, points.length);
            for (let i = 0; i < displayCount; i++) {
                const p = points[i];
                const rowClass = p.warning ? 'warn-row' : 'ok-row';
                html += `
                    <tr class="${rowClass}">
                        <td><strong>${escapeHtml(p.pointId)}</strong></td>
                        <td>${p.northing.toFixed(2)}</td>
                        <td>${p.easting.toFixed(2)}</td>
                        <td>${p.latitude.toFixed(7)}</td>
                        <td>${p.longitude.toFixed(7)}</td>
                        <td>${p.elevation.toFixed(2)}</td>
                    </tr>
                `;
            }

            if (points.length > displayCount) {
                html += `
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 1rem; color: var(--gray-500);">
                            ... and ${points.length - displayCount} more point(s)
                        </td>
                    </tr>
                `;
            }

            html += `
                        </tbody>
                    </table>
                </div>
            `;
        }

        container.innerHTML = html;
    };

    /**
     * Perform bulk import
     * @param {string} apiUrl - API endpoint URL
     * @param {Function} onSuccess - Callback on success
     * @param {Function} onError - Callback on error
     */
    const performImport = async (apiUrl, onSuccess, onError) => {
        if (cachedValidatedPoints.length === 0) {
            showNotification('No points to import', 'error');
            return;
        }

        const projectId = document.getElementById('projectFilter')?.value;
        const datumEpoch = document.getElementById('bulkDatum')?.value;

        if (!projectId) {
            showNotification('Please select a project', 'error');
            return;
        }

        if (!datumEpoch) {
            showNotification('Please select a datum/epoch', 'error');
            return;
        }

        showLoadingSpinner('Importing points...');

        try {
            const formData = new FormData();
            formData.append('action', 'bulk_import');
            formData.append('project_id', projectId);
            formData.append('datum_epoch', datumEpoch);
            formData.append('points_json', JSON.stringify(
                cachedValidatedPoints.map(p => CSVParser.formatForInsert(p, projectId, datumEpoch))
            ));

            const response = await fetch(apiUrl, { method: 'POST', body: formData });
            const data = await response.json();

            if (data.success) {
                showNotification(`${data.imported_count} point(s) imported successfully!`);

                // Reset form
                const form = document.getElementById('bulkImportForm');
                if (form) form.reset();

                const preview = document.getElementById('transformationPreview');
                if (preview) preview.innerHTML = '';

                const importBtn = document.getElementById('importBtn');
                if (importBtn) importBtn.style.display = 'none';

                // Close panel
                toggle();

                // Clear cache
                cachedValidatedPoints = [];

                // Call success callback
                if (typeof onSuccess === 'function') {
                    onSuccess(data);
                }
            } else {
                const errorMsg = data.message || 'Import failed';
                showNotification(errorMsg, 'error');

                if (typeof onError === 'function') {
                    onError(data);
                }
            }
        } catch (err) {
            console.error('Import error:', err);
            showNotification('Network error during import', 'error');

            if (typeof onError === 'function') {
                onError({ error: err.message });
            }
        }

        showLoadingSpinner(null);
    };

    /**
     * Show notification toast (assumes global showToast function exists)
     * @param {string} message - Message to display
     * @param {string} type - 'success' or 'error'
     */
    const showNotification = (message, type = 'success') => {
        if (typeof showToast === 'function') {
            showToast(message, type);
        } else {
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    };

    /**
     * Show loading spinner (delegates to global function if exists)
     * @param {string|null} message - Message to display or null to hide
     */
    const showLoadingSpinner = (message) => {
        // Check if global showLoading exists (from control_points.php)
        if (typeof window.showLoading === 'function') {
            window.showLoading(message);
        } else {
            console.log(`[LOADING] ${message || 'Processing...'}`);
        }
    };

    /**
     * Escape HTML to prevent XSS
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    const escapeHtml = (text) => {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    /**
     * Get cached validated points
     * @returns {Array} Cached points
     */
    const getCachedPoints = () => cachedValidatedPoints;

    /**
     * Clear cached points
     */
    const clearCache = () => {
        cachedValidatedPoints = [];
    };

    // Public API
    return {
        init,
        toggle,
        validateAndPreview,
        performImport,
        getCachedPoints,
        clearCache,
        displayPreview
    };
})();
