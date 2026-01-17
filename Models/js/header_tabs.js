// Header tabs functionality
(function() {
    // Get current page from URL
    const currentPage = window.location.pathname.split('/').pop().replace('.html', '').replace('.php', '') || 'index';

    // Get base path from HeaderLoader if available, otherwise detect it
    function getBasePath() {
        if (window.HeaderLoader && typeof window.HeaderLoader.getBasePath === 'function') {
            return window.HeaderLoader.getBasePath();
        }
        // Fallback: detect from current path
        const path = window.location.pathname;
        const knownSubdirs = ['dashboards', 'Projects', 'Models', 'classes'];
        const segments = path.split('/').filter(Boolean);

        for (let i = segments.length - 1; i >= 0; i--) {
            if (knownSubdirs.includes(segments[i])) {
                return '/' + segments.slice(0, i).join('/') + '/';
            }
        }
        // Default to first segment as project root
        return segments.length > 0 ? '/' + segments[0] + '/' : '/';
    }

    // Set active tab based on current page
    function setActiveTab() {
        const tabs = document.querySelectorAll('.tab-item');
        tabs.forEach(tab => {
            tab.classList.remove('active');
            const tabPage = tab.getAttribute('data-page');
            if (tabPage === currentPage || (currentPage === 'index' && tabPage === 'home')) {
                tab.classList.add('active');
            }
        });
    }

    // Get session data via AJAX
    async function getSessionData() {
        try {
            const basePath = getBasePath();
            const response = await fetch(basePath + 'Models/php/get_session.php');

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const sessionData = await response.json();

            if (sessionData.success) {
                updateUserSection(sessionData.data.isLoggedIn, sessionData.data.userName);
                return sessionData.data;
            }
        } catch (error) {
            console.error('Error getting session data:', error);
        }
        return null;
    }

    // Initialize session data (only if HeaderLoader hasn't already done it)
    setTimeout(() => {
        // Wait a bit to see if HeaderLoader handles it
        if (!window.HeaderLoader || !window.HeaderLoader.sessionData) {
            getSessionData();
        }
    }, 500);

    // Initialize when DOM is loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setActiveTab);
    } else {
        setActiveTab();
    }
    
    // Optional: Handle user authentication state
    function updateUserSection(isLoggedIn, userName) {
        const loginLink = document.getElementById('login-link');
        const userAvatar = document.getElementById('user-avatar');

        if (isLoggedIn && userName) {
            if (loginLink) {
                loginLink.style.display = 'inline-flex';
                loginLink.innerText = 'Dashboard';
            }
            if (userAvatar) {
                userAvatar.style.display = 'inline-flex';
                userAvatar.textContent = userName.substring(0, 2).toUpperCase();
                userAvatar.title = userName;
            }
        } else {
            if (loginLink) {
                loginLink.style.display = 'inline-flex';
            }
            if (userAvatar) {
                userAvatar.style.display = 'none';
            }
        }
    }
    
    // Example: Check if user is logged in (you can customize this)
    //updateUserSection(sessionData.isLoggedIn, sessionData.userName);
    
    // Make functions available globally
    window.HeaderTabs = {
        setActiveTab,
        updateUserSection
    };
})();