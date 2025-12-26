/**
 * Header Loader - Loads and manages the reusable header component
 * This script should be included in every page that needs the header
 * Automatically detects the correct base path regardless of server configuration
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
        cacheExpiry: 1000 * 60 * 30, // 30 minutes
        headerUrl: null // Will be set dynamically
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
        
        showError(container, message) {
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
                    <button onclick="HeaderLoader.loadHeader(true)" style="
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
    
    // Path utilities
    const PathUtils = {
        /**
         * Detects the base path of the application
         * Returns the path from the domain root to the application root
         */
        detectBasePath() {
            const scriptSrc = document.currentScript?.src || '';
            const currentPath = window.location.pathname;
            
            // Try to find the base path from the script src
            if (scriptSrc) {
                const scriptUrl = new URL(scriptSrc);
                const scriptPath = scriptUrl.pathname;
                
                // If script is in Models/js/, go up two levels
                if (scriptPath.includes('/Models/js/')) {
                    const baseFromScript = scriptPath.substring(0, scriptPath.indexOf('/Models/js/'));
                    if (baseFromScript) {
                        return baseFromScript + '/';
                    }
                }
            }
            
            // Try to detect from current page location
            // Look for common root indicators
            const pathSegments = currentPath.split('/').filter(Boolean);
            
            // If we're in a subdirectory like /dashboards/ or /Projects/
            const knownSubdirs = ['dashboards', 'Projects', 'Models', 'classes'];
            for (let i = pathSegments.length - 1; i >= 0; i--) {
                if (knownSubdirs.includes(pathSegments[i])) {
                    // The base path is everything before this subdirectory
                    const basePath = '/' + pathSegments.slice(0, i).join('/');
                    return basePath ? basePath + '/' : '/';
                }
            }
            
            // Default: check if we're in a subdirectory
            if (pathSegments.length > 1) {
                // Assume first segment is the project name
                return '/' + pathSegments[0] + '/';
            }
            
            // We're at the root
            return '/';
        },
        
        /**
         * Resolves a relative path to an absolute path based on the base path
         */
        resolve(basePath, relativePath) {
            // Remove leading ./ or /
            relativePath = relativePath.replace(/^\.?\//, '');
            
            // Ensure basePath ends with /
            if (!basePath.endsWith('/')) {
                basePath += '/';
            }
            
            return basePath + relativePath;
        },
        
        /**
         * Normalizes a path by removing double slashes and resolving ..
         */
        normalize(path) {
            // Remove double slashes
            path = path.replace(/\/+/g, '/');
            
            // Split into segments
            const segments = path.split('/');
            const normalized = [];
            
            for (const segment of segments) {
                if (segment === '..') {
                    normalized.pop();
                } else if (segment !== '.' && segment !== '') {
                    normalized.push(segment);
                }
            }
            
            // Reconstruct path
            let result = normalized.join('/');
            
            // Preserve leading slash
            if (path.startsWith('/')) {
                result = '/' + result;
            }
            
            // Preserve trailing slash if original had it
            if (path.endsWith('/') && !result.endsWith('/')) {
                result += '/';
            }
            
            return result;
        }
    };
    
    // Main header loader class
    class HeaderLoader {
        constructor() {
            this.currentAttempt = 0;
            this.container = null;
            this.sessionData = null;
            
            // Detect base path dynamically
            this.basePath = PathUtils.detectBasePath();
            console.log('Detected base path:', this.basePath);
            
            // Set dependent paths
            this.loginUrl = PathUtils.normalize(this.basePath + 'login.php');
            CONFIG.headerUrl = PathUtils.normalize(this.basePath + 'Models/php/header_tabs.php');
            this.sessionUrl = PathUtils.normalize(this.basePath + 'Models/php/get_session.php');
            
            console.log('Header URL:', CONFIG.headerUrl);
            console.log('Session URL:', this.sessionUrl);
            console.log('Login URL:', this.loginUrl);
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
                LoadingStates.showError(this.container, error.message);
            }
        }
        
        async initializeHeader() {
            // Fetch session data
            await this.fetchSessionData();
            
            // Update all hrefs to absolute paths
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
                detail: { container: this.container, basePath: this.basePath }
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
            const currentPage = currentPath.split('/').pop().replace('.php', '').replace('.html', '') || 'index';
            
            // Find and activate the corresponding tab
            const tabs = this.container.querySelectorAll('.tab-item');
            tabs.forEach(tab => {
                tab.classList.remove('active');
                
                const tabPage = tab.getAttribute('data-page');
                const tabHref = tab.getAttribute('href');
                
                // Match by data-page attribute or href
                if (tabPage === currentPage || 
                    (tabHref && (tabHref.includes(currentPage + '.php') || tabHref.includes(currentPage + '.html'))) ||
                    (currentPage === 'index' && (tabPage === 'home' || tabPage === 'index'))) {
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
                const response = await fetch(this.sessionUrl);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                const sessionData = await response.json();
                
                if (sessionData.success) {
                    this.sessionData = sessionData.data;
                    console.log('Session data:', this.sessionData);
                }
            } catch (error) {
                console.error('Error fetching session data:', error);
            }
        }
        
        updateHomeHref() {
            const homeTab = this.container.querySelector('.tab-item[data-page="index"]');
            if (homeTab) {
                homeTab.setAttribute('href', PathUtils.normalize(this.basePath + 'index.php'));
            }
        }
        
        updateHrefsToAbsolute() {
            const links = this.container.querySelectorAll('a[href], link[href]');
            links.forEach(link => {
                const href = link.getAttribute('href');
                
                // Skip external links, anchors, and already absolute paths
                if (!href || 
                    href.startsWith('http') || 
                    href.startsWith('//') || 
                    href.startsWith('#') ||
                    href.startsWith('mailto:') ||
                    href.startsWith('tel:')) {
                    return;
                }
                
                // Convert relative paths to absolute
                if (href.startsWith('./')) {
                    link.setAttribute('href', PathUtils.normalize(this.basePath + href.substring(2)));
                } else if (!href.startsWith('/')) {
                    link.setAttribute('href', PathUtils.normalize(this.basePath + href));
                }
            });
        }
        
        // Public methods for external use
        refreshHeader() {
            Cache.clear();
            this.loadHeader(true);
        }
        
        getBasePath() {
            return this.basePath;
        }
        
        resolvePath(relativePath) {
            return PathUtils.resolve(this.basePath, relativePath);
        }
        
        logout() {
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
        getBasePath: () => window.HeaderLoader.getBasePath(),
        resolvePath: (relativePath) => window.HeaderLoader.resolvePath(relativePath),
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