// Small shared UI utilities for survey_projects.php: the toast
// notification, JSON export, sidebar collapse/expand (desktop + mobile),
// and the top-bar/header height CSS-variable sync used for sticky layout.
//
// Depends on globals defined by survey_projects.php's main script (loaded
// before this file): filteredProjects.

// Export data
function exportData() {
    const dataStr = JSON.stringify(filteredProjects, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    
    const link = document.createElement('a');
    link.href = URL.createObjectURL(dataBlob);
    link.download = `survey-projects-${new Date().toISOString().split('T')[0]}.json`;
    link.click();
    
    showToast('Project data exported successfully!');
}

const sidebar = document.getElementById('sidebar'); // Make sure your sidebar has id="sidebar"
const toggleButton = document.querySelector('.toggle-btn'); // or use getElementById('toggleBtn')
const main_content = document.querySelector('.main-content');

toggleButton.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    main_content.classList.toggle('collapsed');
});

mobileToggle.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
});


// Toggle sidebar (mobile)
function toggleSidebar() {
    sidebar.classList.toggle('open');
}

// Show toast notification with type support
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    const toastIcon = document.querySelector('.toast-icon');
    
    toastMessage.textContent = message;
    
    // Update icon and color based on type
    if (type === 'error') {
        toastIcon.className = 'fas fa-exclamation-circle toast-icon';
        toast.style.borderLeftColor = 'var(--danger-color)';
        toastIcon.style.color = 'var(--danger-color)';
    } else if (type === 'warning') {
        toastIcon.className = 'fas fa-exclamation-triangle toast-icon';
        toast.style.borderLeftColor = 'var(--warning-color)';
        toastIcon.style.color = 'var(--warning-color)';
    } else {
        toastIcon.className = 'fas fa-check-circle toast-icon';
        toast.style.borderLeftColor = 'var(--success-color)';
        toastIcon.style.color = 'var(--success-color)';
    }
    
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Keep content-header sticky offset in sync with top-bar height
function syncTopBarHeight() {
    const h = document.querySelector('.top-bar')?.offsetHeight || 73;
    document.documentElement.style.setProperty('--top-bar-height', h + 'px');
}
syncTopBarHeight();
window.addEventListener('resize', syncTopBarHeight);

// Keep sidebar/top-bar offset in sync with the shared header tabs height.
// The header scrolls with the page (not sticky here), so the offset tracks
// only its still-visible portion — once it scrolls off, the sidebar and
// sticky top-bar sit at the very top.
function syncHeaderHeight() {
    const total = document.querySelector('.header-tabs-container')?.offsetHeight || 0;
    const visible = Math.max(0, total - window.scrollY);
    document.documentElement.style.setProperty('--header-height', visible + 'px');
}
document.addEventListener('headerLoaded', syncHeaderHeight);
window.addEventListener('resize', syncHeaderHeight);
window.addEventListener('scroll', syncHeaderHeight, { passive: true });

