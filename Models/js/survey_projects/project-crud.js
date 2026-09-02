// Project create/edit/delete modal for survey_projects.php: the project
// form (save/update/delete), folder-path copy helpers, the Plus Code
// (Open Location Code) decoder, and the project-form auto-fill wiring.
//
// Depends on globals defined by survey_projects.php's main script (loaded
// before this file): allProjects, currentEditingProject, showToast,
// loadProjects, searchProjects, projectIdInput and the other project
// form DOM refs.

// Open create modal
function openCreateModal() {
    currentEditingProject = null;
    document.getElementById('modalTitle').textContent = 'Create New Project';
    document.getElementById('projectForm').reset();
    document.getElementById('projectModal').style.display = 'block';
}

// Edit project
function editProject(projectId) {
    const project = allProjects.find(p => p.projectId === projectId);
    if (!project) {
        alert('Project not found');
        return;
    }
    
    currentEditingProject = project;
    document.getElementById('modalTitle').textContent = 'Edit Project';
    
    // Populate form
    Object.keys(project).forEach(key => {
        const element = document.getElementById(key);
        if (!element) return;
        if (element.type === 'checkbox') {
            element.checked = !!parseInt(project[key]);
        } else {
            element.value = project[key] || '';
        }
    });

    // Show the modal
    document.getElementById('projectModal').style.display = 'block';
    
    // Change save button text to indicate editing
    const saveButton = document.querySelector('#projectModal .btn-primary');
    if (saveButton) {
        saveButton.textContent = 'Update Project';
        saveButton.onclick = saveProjectChanges;
    }
}

// Save project changes
function saveProjectChanges() {
    if (!currentEditingProject) {
        alert('No project selected for editing');
        return;
    }

    // Get form data
    const formData = new FormData();
    formData.append('action', 'update_project');
    formData.append('projectId', currentEditingProject.projectId);

    // Get all form fields
    const fieldIds = [
        'projectName', 'projectStatus', 'projectFolderLink', 'surveyFolderLink',
        'drawingFolderLink', 'contractLink', 'qaQcFolderLink', 'researchFolderLink',
        'fieldFolderLink', 'notes', 'modifiedBy', 'location', 'plus_code', 'scale_factor'
    ];
    // Checkbox fields (value is 0 or 1, not read via .value)
    formData.append('needs_monuments', document.getElementById('needs_monuments').checked ? '1' : '0');

    // Add form data
    fieldIds.forEach(fieldId => {
        const element = document.getElementById(fieldId);
        if (element && element.value !== undefined) {
            formData.append(fieldId, element.value);
        }
    });

    // Show loading state
    const saveButton = document.querySelector('#projectModal .btn-primary');
    const originalText = saveButton.textContent;
    saveButton.textContent = 'Updating...';
    saveButton.disabled = true;

    // Send update request
    fetch('../../Models/php/load_survey_project_notes.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the project in allProjects array
            const projectIndex = allProjects.findIndex(p => p.projectId === currentEditingProject.projectId);
            if (projectIndex !== -1) {
                allProjects[projectIndex] = data.project;
            }
            
            // Refresh the project display
            loadProjects();
            
            // Close the modal
            closeModal();
            
            // Show success message
            showToast('Project updated successfully!', 'success');
            
        } else {
            alert('Error updating project: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating project. Please try again.');
    })
    .finally(() => {
        // Restore button state
        saveButton.textContent = originalText;
        saveButton.disabled = false;
    });
}

// Close modal
function closeModal() {
    document.getElementById('projectModal').style.display = 'none';
    currentEditingProject = null;
}

// Save project
function saveProject(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    formData.append('action', 'add_project');
    
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.textContent = 'Saving...';
    submitButton.disabled = true;
    
    fetch('../../Models/php/save_survey_project_notes.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        submitButton.textContent = originalText;
        submitButton.disabled = false;
        
        if (data.success) {
            showToast('Project saved successfully!', 'success');
            form.reset();
            closeModal();
            loadProjects(); // Reload projects
        } else {
            showToast(data.message || 'Error saving project', 'error');
        }
    })
    .catch(error => {
        submitButton.textContent = originalText;
        submitButton.disabled = false;
        console.error('Error saving project:', error);
        showToast('Network error: Unable to save project. Please try again.', 'error');
    });
}

// Delete project
function deleteProject(projectId) {
    if (confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
        const index = allProjects.findIndex(p => p.projectId === projectId);
        if (index !== -1) {
            allProjects.splice(index, 1);
            showToast('Project deleted successfully!');
            searchProjects();
        }
    }
}

// Copy project ID
function copyFolderPath(folderPath, linkType) {
    navigator.clipboard.writeText(folderPath).then(() => {
        showToast(`${linkType} ": ${folderPath}" copied to clipboard!`);
    }).catch(() => {
        showToast(`"Failed to copy: ${linkType}"`);
    });
}

// Copy project ID to clipboard
function copyProjectId(projectId) {
    navigator.clipboard.writeText(projectId).then(() => {
        showToast(`Project ID "${projectId}" copied to clipboard!`);
    }).catch(() => {
        showToast('Failed to copy Project ID', 'error');
    });
}

// ── Google Plus Code (Open Location Code) decoding ─────────────────────────
// Pure client-side implementation of the OLC "full code" decode algorithm
// (https://github.com/google/open-location-code) - no API key required.
const OLC_ALPHABET = '23456789CFGHJMPQRVWX';
const OLC_SEPARATOR = '+';
const OLC_SEPARATOR_POSITION = 8;
const OLC_PAIR_RESOLUTIONS = [20.0, 1.0, 0.05, 0.0025, 0.000125];
const OLC_GRID_ROWS = 5;
const OLC_GRID_COLUMNS = 4;

// A "full" plus code (e.g. 87G8Q2PV+W3) can be decoded on its own. A "short"
// code (e.g. 2PVH+W3) is missing its leading digits and can only be resolved
// relative to a nearby reference location, so it isn't handled here.
function isFullPlusCode(code) {
    if (!code || code.indexOf(OLC_SEPARATOR) !== OLC_SEPARATOR_POSITION) return false;
    return code.replace(OLC_SEPARATOR, '').indexOf('0') === -1;
}

function decodePlusCode(code) {
    const clean = code.toUpperCase().replace(OLC_SEPARATOR, '');
    let latVal = 0, lngVal = 0;
    for (let i = 0; i * 2 < Math.min(clean.length, 10); i++) {
        latVal += OLC_ALPHABET.indexOf(clean[i * 2]) * OLC_PAIR_RESOLUTIONS[i];
    }
    for (let i = 0; i * 2 + 1 < Math.min(clean.length, 10); i++) {
        lngVal += OLC_ALPHABET.indexOf(clean[i * 2 + 1]) * OLC_PAIR_RESOLUTIONS[i];
    }
    let latResolution = OLC_PAIR_RESOLUTIONS[4];
    let lngResolution = OLC_PAIR_RESOLUTIONS[4];
    for (let i = 10; i < clean.length; i++) {
        const idx = OLC_ALPHABET.indexOf(clean[i]);
        if (idx < 0) break;
        const row = Math.floor(idx / OLC_GRID_COLUMNS);
        const col = idx % OLC_GRID_COLUMNS;
        latResolution /= OLC_GRID_ROWS;
        lngResolution /= OLC_GRID_COLUMNS;
        latVal += row * latResolution;
        lngVal += col * lngResolution;
    }
    return {
        lat: (latVal - 90) + latResolution / 2,
        lng: (lngVal - 180) + lngResolution / 2
    };
}

// Build a "City, County, State" string from a Nominatim address object
function formatCityCountyState(address) {
    const city = address.city || address.town || address.village || address.hamlet || '';
    const county = address.county || '';
    const state = address.state || '';
    return [city, county, state].filter(Boolean).join(', ');
}

// Fill the City/County/State field by looking up the entered Plus Code.
// Full codes are decoded locally then reverse-geocoded; short codes (which
// include a locality after the code, e.g. "2PVH+W3 Austin, Texas") skip the
// code math and just forward-geocode that locality text directly. Uses the
// free OpenStreetMap Nominatim API - no API key required.
async function lookupLocationFromPlusCode() {
    const plusCodeInput = document.getElementById('plus_code');
    const locationInput = document.getElementById('location');
    const raw = (plusCodeInput.value || '').trim();
    if (!raw) {
        showToast('Enter a Plus Code first', 'error');
        return;
    }

    const btn = document.getElementById('plusCodeLookupBtn');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    try {
        const spaceIndex = raw.indexOf(' ');
        let address;

        if (spaceIndex === -1) {
            if (!isFullPlusCode(raw)) {
                throw new Error('Short Plus Codes need a locality after the code, e.g. "2PVH+W3 Austin, Texas"');
            }
            const { lat, lng } = decodePlusCode(raw);
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&lat=${lat}&lon=${lng}`);
            const data = await response.json();
            if (!data || !data.address) throw new Error('Could not resolve that Plus Code to a location');
            address = data.address;
        } else {
            const locality = raw.substring(spaceIndex + 1).trim();
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&addressdetails=1&q=${encodeURIComponent(locality)}`);
            const results = await response.json();
            if (!results.length) throw new Error(`Could not find location "${locality}"`);
            address = results[0].address;
        }

        const formatted = formatCityCountyState(address);
        if (!formatted) throw new Error('Could not determine city/county/state for that Plus Code');
        locationInput.value = formatted;
        showToast('City/County/State filled from Plus Code', 'success');
    } catch (err) {
        console.error('Plus Code lookup failed:', err);
        showToast(err.message || 'Failed to look up location from Plus Code', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }
}

// Set up auto-fill functionality
function setupAutoFill() {
    // Function to update dependent fields based on project ID
    function updateDependentFields() {
        const projectId = projectIdInput.value.trim();
        
        if (projectId) {
            // Update all folder links
            projectFolderLinkInput.value = `N:\\\\${projectId}`;
            projectSurveyFolderLinkInput.value = `N:\\\\${projectId}\\\\05 Service Groups\\\\Survey`;
            projectDrawingFolderLinkInput.value = `N:\\\\${projectId}\\\\06 CAD\\\\DWG\\\\Survey C3D`;
            projectContractLinkInput.value = `N:\\\\${projectId}\\\\01 Administration\\\\Contracts`;
            projectQAQCLinkInput.value = `N:\\\\${projectId}\\\\07 QA-QC\\\\5 - Plan and Report Markups\\\\Land Surveying`;
            projectResearchLinkInput.value = `N:\\\\${projectId}\\\\09 Research\\\\Survey Research`;
            
            // Add auto-filled class
            [projectFolderLinkInput, projectSurveyFolderLinkInput, projectDrawingFolderLinkInput,
             projectContractLinkInput, projectQAQCLinkInput, projectResearchLinkInput].forEach(input => {
                input.classList.add('auto-filled');
            });
        } else {
            // Clear all fields
            [projectFolderLinkInput, projectSurveyFolderLinkInput, projectDrawingFolderLinkInput,
             projectContractLinkInput, projectQAQCLinkInput, projectResearchLinkInput].forEach(input => {
                input.value = '';
                input.classList.remove('auto-filled');
            });
        }
    }

    // Listen for changes to the project ID input
    projectIdInput.addEventListener('input', updateDependentFields);
    projectIdInput.addEventListener('paste', function() {
        setTimeout(updateDependentFields, 10);
    });

    // Allow manual editing of auto-filled fields
    [projectFolderLinkInput, projectSurveyFolderLinkInput, projectDrawingFolderLinkInput,
     projectContractLinkInput, projectQAQCLinkInput, projectResearchLinkInput].forEach(input => {
        input.addEventListener('focus', function() {
            this.classList.remove('auto-filled');
        });
    });
}

