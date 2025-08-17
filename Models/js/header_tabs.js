// Header tabs functionality
(function() {
    // Get current page from URL
    const currentPage = window.location.pathname.split('/').pop().replace('.html', '') || 'index';

    console.log("I am in the header_tab.html");
    
    // Set active tab based on current page
    function setActiveTab() {
        const tabs = document.querySelectorAll('.tab-item');
        tabs.forEach(tab => {
            tab.classList.remove('active');
            const tabPage = tab.getAttribute('data-page');
            if (tabPage === currentPage) {
                tab.classList.add('active');
            }
        });
    }
    

    // Get session data via AJAX
    async function getSessionData() {
        try {
            const response = await fetch('Models/php/get_session.php');
            const sessionData = await response.json();
            
            if (sessionData.success) {
                updateUserSection(sessionData.data.isLoggedIn, sessionData.data.userName);
    
                console.log('Session data:', sessionData.data);
                return sessionData.data;
            }
        } catch (error) {
            console.error('Error getting session data:', error);
        }
        return null;
    }

    // Usage
    getSessionData().then(sessionData => {
        if (sessionData && sessionData.isLoggedIn) {
            console.log(`Welcome ${sessionData.username}!`);
            // Update UI based on session data
        }
    });

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
            loginLink.style.display = 'inline-flex';
            loginLink.innerText = 'Dashboard';
            userAvatar.style.display = 'inline-flex';
            userAvatar.textContent = userName.substring(0, 2).toUpperCase();
            userAvatar.title = userName;
        } else {
            loginLink.style.display = 'inline-flex';
            userAvatar.style.display = 'none';
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