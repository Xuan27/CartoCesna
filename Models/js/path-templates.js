/**
 * PathTemplates
 * Fetches and caches the project folder-path templates configured in the
 * Settings panel (survey_projects.php), so project-crud.js's new-project
 * auto-fill and field_data_qc.php's raw-data-path guess both read from one
 * place instead of each hardcoding its own copy of the same paths.
 */
const PathTemplates = (() => {
    let cache = null;
    let pending = null;

    async function get(forceRefresh = false) {
        if (cache && !forceRefresh) return cache;
        if (pending && !forceRefresh) return pending;

        pending = fetch('../../Models/php/path_templates_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_templates'
        })
            .then(r => r.json())
            .then(data => {
                cache = data.success ? data.templates : {};
                pending = null;
                return cache;
            })
            .catch(err => {
                console.error('Error loading path templates:', err);
                cache = {};
                pending = null;
                return cache;
            });

        return pending;
    }

    function fill(template, projectId) {
        return (template || '').split('[project_id]').join(projectId || '');
    }

    // Call after saving edits in the Settings panel so the next auto-fill
    // picks up the new values instead of a stale cached copy.
    function invalidate() {
        cache = null;
    }

    return { get, fill, invalidate };
})();
