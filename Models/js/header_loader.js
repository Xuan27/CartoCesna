/**
 * Header Loader - Loads and manages the reusable header component
 * This script should be included in every page that needs the header
 */

(function() {
    'use strict';
    
    // Configuration
    let CONFIG = {
        containerId: 'header-container',
        retryAttempts: 3,
        retryDelay: 1000,
        enableCache: true,
        cacheKey: 'header-tabs-cache',
        cacheExpiry: 1000 * 60 * 30 // 30 minutes
    };
    
    // Cache management
    const Cache = {
        set(key, data) {
            if (!CONFIG.enableCache) return;
            try {
                const cacheData = {
                    content: data,
                    timestamp: Date.now()
                };
                localStorage.setItem(key, JSON.stringify(cacheData));
            } catch (e) {
                console.warn('Failed to cache header content:', e);
            }
        },
        
        get(key) {
            if (!CONFIG.enableCache) return null;
            try {
                const cached = localStorage.getItem(key);
                if (!cached) return null;
                
                const cacheData = JSON.parse(cached);
                const isExpired = Date.now() - cacheData.timestamp > CONFIG.cacheExpiry;
                
                if (isExpired) {
                    localStorage.removeItem(key);
                    return null;
                }
                
                return cacheData.content;
            } catch (e) {
                console.warn('Failed to retrieve cached header:', e);
                return null;
            }
        },
        
        clear() {
            try {
                localStorage.removeItem(CONFIG.cacheKey);
            } catch (e) {
                console.warn('Failed to clear header cache:', e);
            }
        }
    };

        
    
    // Loading states
    const LoadingStates = {
        showLoading(container) {
            container.innerHTML = `
                <div style="
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                    background: white;
                    border-bottom: 1px solid #e9ecef;
                    color: #666;
                    font-family: 'Segoe UI', sans-serif;
                    font-size: 14px;
                ">
                    <div style="
                        display: inline-block;
                        width: 20px;
                        height: 20px;
                        border: 2px solid #f3f3f3;
                        border-top: 2px solid #667eea;
                        border-radius: 50%;
                        animation: spin 1s linear infinite;
                        margin-right: 10px;
                    "></div>
                    Loading header...
                    <style>
                        @keyframes spin {
                            0% { transform: rotate(0deg); }
                            100% { transform: rotate(360deg); }
                        }
                    </style>
                </div>
            `;
        },
        
        showError(container, message, retry) {
            container.innerHTML = `
                <div style="
                    padding: 20px;
                    background: #f8d7da;
                    color: #721c24;
                    border-bottom: 1px solid #f5c6cb;
                    text-align: center;
                    font-family: 'Segoe UI', sans-serif;
                    font-size: 14px;
                ">
                    <div style="margin-bottom: 10px;">
                        ⚠️ Failed to load header: ${message}
                    </div>
                    <button onclick="HeaderLoader.loadHeader()" style="
                        padding: 8px 16px;
                        background: #721c24;
                        color: white;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 12px;
                    ">
                        Retry
                    </button>
                </div>
            `;
        }
    };
    
    // Main header loader class
    class HeaderLoader {
        constructor() {
            this.currentAttempt = 0;
            this.container = null;
            this.sessionData = null;
            // Compute basePath dynamically from current location (first folder only)
            const pathParts = window.location.pathname.split('/');
            this.basePath = pathParts.length > 1 ? '/' + pathParts[1] + '/' : '/';
            this.loginUrl = this.basePath + 'login.php';
            // Set initial headerUrl dynamically
            CONFIG.headerUrl = this.basePath + 'Models/php/header_tabs.php';
        }
        
        async loadHeader(forceReload = false) {
            this.container = document.getElementById(CONFIG.containerId);
            if (!this.container) {
                console.error(`Header container with ID "${CONFIG.containerId}" not found`);
                return;
            }
            
            // Show loading state
            LoadingStates.showLoading(this.container);
            
            try {
                // Try to get from cache first (unless force reload)
                let headerContent = null;
                if (!forceReload) {
                    headerContent = Cache.get(CONFIG.cacheKey);
                }
                
                // If not in cache, fetch from server
                if (!headerContent) {
                    headerContent = await this.fetchHeader();
                    Cache.set(CONFIG.cacheKey, headerContent);
                }
                
                // Insert header content
                this.container.innerHTML = headerContent;
                
                // Initialize header functionality
                await this.initializeHeader();
                
                // Reset attempt counter
                this.currentAttempt = 0;
                
                console.log('Header loaded successfully');
                
            } catch (error) {
                console.error('Failed to load header:', error);
                this.handleLoadError(error);
            }
        }
        
        async fetchHeader() {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); // 10s timeout
            
            try {
                const response = await fetch(CONFIG.headerUrl, {
                    signal: controller.signal,
                    cache: 'no-cache',
                    headers: {
                        'Content-Type': 'text/html'
                    }
                });
                
                clearTimeout(timeoutId);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                return await response.text();
                
            } catch (error) {
                clearTimeout(timeoutId);
                if (error.name === 'AbortError') {
                    throw new Error('Request timeout');
                }
                throw error;
            }
        }
        
        async handleLoadError(error) {
            this.currentAttempt++;
            
            if (this.currentAttempt < CONFIG.retryAttempts) {
                console.log(`Retrying header load (${this.currentAttempt}/${CONFIG.retryAttempts})...`);
                setTimeout(() => {
                    this.loadHeader();
                }, CONFIG.retryDelay * this.currentAttempt);
            } else {
                LoadingStates.showError(this.container, error.message, () => this.loadHeader(true));
            }
        }
        
        async initializeHeader() {
            // Fetch session data
            await this.fetchSessionData();
            
            // Update all hrefs to absolute
            this.updateHrefsToAbsolute();
            
            // Set up event listeners for tab interactions
            this.setupTabEvents();
            
            // Set active tab based on current page
            this.setActiveTab();
            
            // Initialize user state
            if (this.sessionData) {
                this.initializeUserState(this.sessionData.isLoggedIn, this.sessionData.userName);
            }
            
            // Update home href
            this.updateHomeHref();
            
            // Emit custom event for external listeners
            document.dispatchEvent(new CustomEvent('headerLoaded', {
                detail: { container: this.container }
            }));
        }
        
        setupTabEvents() {
            const tabItems = this.container.querySelectorAll('.tab-item[href]');
            
            tabItems.forEach(tab => {
                tab.addEventListener('click', (e) => {
                    // Allow normal navigation for actual links
                    if (tab.hasAttribute('href') && !tab.getAttribute('href').startsWith('#')) {
                        return; // Let the browser handle navigation
                    }
                    
                    // Handle hash-based navigation
                    e.preventDefault();
                    const href = tab.getAttribute('href');
                    
                    // Update active state
                    this.updateActiveTab(tab);
                    
                    // Emit tab change event
                    document.dispatchEvent(new CustomEvent('headerTabChange', {
                        detail: {
                            href,
                            tab,
                            text: tab.textContent.trim()
                        }
                    }));
                });
            });
        }
        
        setActiveTab() {
            // Get current page from URL
            const currentPath = window.location.pathname;
            const currentPage = currentPath.split('/').pop().replace('.html', '') || 'index';
            
            // Find and activate the corresponding tab
            const tabs = this.container.querySelectorAll('.tab-item');
            tabs.forEach(tab => {
                tab.classList.remove('active');
                
                const tabPage = tab.getAttribute('data-page');
                const tabHref = tab.getAttribute('href');
                
                // Match by data-page attribute or href
                if (tabPage === currentPage || 
                    (tabHref && tabHref.includes(currentPage)) ||
                    (currentPage === 'index' && (tabPage === 'home' || (this.sessionData && tabHref === this.sessionData.rootPage)))) {
                    tab.classList.add('active');
                }
            });
        }
        
        updateActiveTab(activeTab) {
            const tabs = this.container.querySelectorAll('.tab-item');
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });
            activeTab.classList.add('active');
        }
        
        initializeUserState(isLoggedIn, userName) {
            // Check if user management functions exist and call them
            if (typeof window.HeaderTabs !== 'undefined' && 
                typeof window.HeaderTabs.updateUserSection === 'function') {
                
                window.HeaderTabs.updateUserSection(isLoggedIn, userName);
            }
        }
        
        async fetchSessionData() {
            try {
                const response = await fetch(this.basePath + 'Models/php/get_session.php');
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                const sessionData = await response.json();
                
                if (sessionData.success) {
                    this.sessionData = sessionData.data;
                    // Update basePath from rootPage
                    if (this.sessionData.rootPage) {
                        this.basePath = this.sessionData.rootPage.replace(/\/[^\/]*$/, '/');
                        this.loginUrl = this.sessionData.rootPage.replace(/\/[^\/]*$/, '/login.php');
                        CONFIG.headerUrl = this.basePath + 'Models/php/header_tabs.php';
                    }
                    console.log('Session data:', sessionData.data);
                    console.log('Base path set to:', this.basePath);
                }
            } catch (error) {
                console.error('Error fetching session data:', error);
            }
        }
        
        updateHomeHref() {
            if (this.sessionData && this.sessionData.rootPage) {
                const homeTab = this.container.querySelector('.tab-item[data-page="index"]');
                if (homeTab) {
                    homeTab.setAttribute('href', this.sessionData.rootPage);
                }
            }
        }
        
        updateHrefsToAbsolute() {
            const links = this.container.querySelectorAll('a[href], link[href]');
            links.forEach(link => {
                const href = link.getAttribute('href');
                if (href && !href.startsWith('http') && !href.startsWith('/')) {
                    link.setAttribute('href', this.basePath + href.replace(/^\.\//, ''));
                }
            });
        }
        
        getUserLoginState() {
            // This is a simple example - replace with your actual auth check
            try {
                return isLoggedIn === 'true';
            } catch (e) {
                return false;
            }
        }
        
        getUserName() {
            // This is a simple example - replace with your actual user data
            try {
                return userName || '';
            } catch (e) {
                return '';
            }
        }
        
        // Public methods for external use
        refreshHeader() {
            Cache.clear();
            this.loadHeader(true);
        }
        
        setUserLoginState(isLoggedIn, userName = '') {
            try {
                localStorage.setItem('userLoggedIn', isLoggedIn.toString());
                if (userName) {
                    localStorage.setItem('userName', userName);
                }
                
                // Update header if it's loaded
                if (this.container && window.HeaderTabs) {
                    window.HeaderTabs.updateUserSection(isLoggedIn, userName);
                }
            } catch (e) {
                console.warn('Failed to update user state:', e);
            }
        }
        
        logout() {
            this.setUserLoginState(false, '');
            
            // Optionally redirect to login page
            window.location.href = this.loginUrl;
        }
    }
    
    // Create global instance
    window.HeaderLoader = new HeaderLoader();
    
    // Auto-load header when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.HeaderLoader.loadHeader();
        });
    } else {
        // DOM is already ready
        window.HeaderLoader.loadHeader();
    }
    
    // Expose utility methods globally
    window.HeaderUtils = {
        refreshHeader: () => window.HeaderLoader.refreshHeader(),
        setUserLogin: (isLoggedIn, userName) => window.HeaderLoader.setUserLoginState(isLoggedIn, userName),
        logout: () => window.HeaderLoader.logout(),
        
        // Event listeners helpers
        onHeaderLoaded: (callback) => {
            document.addEventListener('headerLoaded', callback);
        },
        
        onTabChange: (callback) => {
            document.addEventListener('headerTabChange', callback);
        }
    };
    
})();