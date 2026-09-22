/**
 * Settings side panel (survey_projects.php): edits the project folder-path
 * templates that project-crud.js's new-project auto-fill and
 * field_data_qc.php's raw-data-path guess both read via PathTemplates.
 * Depends on globals defined elsewhere on the page: showToast, PathTemplates.
 */

const PATH_TEMPLATE_LABELS = {
    projectFolderLink:  { label: 'Project Folder',   hint: 'The project\'s top-level folder.' },
    surveyFolderLink:   { label: 'Survey Folder',    hint: 'Auto-fills the Survey Folder Link field.' },
    drawingFolderLink:  { label: 'Drawing Folder',   hint: 'Auto-fills the Drawing Folder Link field.' },
    contractLink:       { label: 'Contract Folder',  hint: 'Auto-fills the Contract Link field.' },
    qaQcFolderLink:     { label: 'QA/QC Folder',     hint: 'Auto-fills the QA/QC Folder Link field.' },
    researchFolderLink: { label: 'Research Folder',  hint: 'Auto-fills the Research Folder Link field.' },
    rawDataPathGuess:   { label: 'Raw Field Data Path (suggested default)', hint: 'Pre-fills a new QC session\'s raw data path — the crew can still edit it per session.' }
};

let settingsPanelTemplates = null;

function openSettingsPanel() {
    document.getElementById('settingsPanel').classList.add('active');
    loadPathTemplatesIntoPanel();
}

function closeSettingsPanel() {
    document.getElementById('settingsPanel').classList.remove('active');
}

async function loadPathTemplatesIntoPanel() {
    const container = document.getElementById('settingsTemplateFields');
    container.innerHTML = `
        <div style="text-align: center; padding: 2rem; color: var(--gray-400);">
            <i class="fas fa-spinner fa-spin" style="font-size: 1.5rem;"></i>
            <p>Loading templates...</p>
        </div>`;

    try {
        const formData = new FormData();
        formData.append('action', 'get_templates');
        const response = await fetch('../../Models/php/path_templates_api.php', { method: 'POST', body: formData });
        const data = await response.json();

        if (!data.success) {
            container.innerHTML = `<p style="color: var(--danger-color);">${data.message || 'Could not load templates'}</p>`;
            return;
        }

        settingsPanelTemplates = data.templates;

        container.innerHTML = Object.keys(PATH_TEMPLATE_LABELS).map(key => {
            const info = PATH_TEMPLATE_LABELS[key];
            const value = (data.templates[key] || '').replace(/"/g, '&quot;');
            const placeholder = ((data.defaults || {})[key] || '').replace(/"/g, '&quot;');
            return `
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label" for="tpl-${key}">${info.label}</label>
                    <input type="text" class="form-input" id="tpl-${key}" data-template-key="${key}"
                           value="${value}" placeholder="${placeholder}">
                    <p style="font-size: 0.75rem; color: var(--gray-400); margin-top: 0.25rem;">${info.hint}</p>
                </div>`;
        }).join('');
    } catch (error) {
        console.error('Error loading path templates:', error);
        container.innerHTML = '<p style="color: var(--danger-color);">Network error loading templates</p>';
    }
}

async function savePathTemplates() {
    const inputs = document.querySelectorAll('#settingsTemplateFields input[data-template-key]');
    const templates = {};
    inputs.forEach(input => {
        templates[input.dataset.templateKey] = input.value;
    });

    try {
        const formData = new FormData();
        formData.append('action', 'save_templates');
        formData.append('templates_json', JSON.stringify(templates));
        const response = await fetch('../../Models/php/path_templates_api.php', { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success) {
            showToast('Path templates saved', 'success');
            PathTemplates.invalidate();
            closeSettingsPanel();
        } else {
            showToast(data.message || 'Could not save templates', 'error');
        }
    } catch (error) {
        console.error('Error saving path templates:', error);
        showToast('Network error saving templates', 'error');
    }
}
