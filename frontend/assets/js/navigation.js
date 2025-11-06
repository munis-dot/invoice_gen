// assets/js/navigation.js
class NavigationManager {
    constructor() {
        this.currentModule = 'dashboard';
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadInitialModule();
    }

    bindEvents() {
        // Sidebar navigation
        document.addEventListener('click', (e) => {
            const navLink = e.target.closest('.nav-link[data-link]');
            if (navLink && !navLink.href.includes('logout.php')) {
                e.preventDefault();
                this.handleNavigation(navLink);
            }
        });

        // Mobile menu toggle
        const mobileToggle = document.getElementById('mobile-menu-toggle');
        if (mobileToggle) {
            mobileToggle.addEventListener('click', () => {
                document.getElementById('main-sidebar').classList.toggle('active');
            });
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            const sidebar = document.getElementById('main-sidebar');
            const toggle = document.getElementById('mobile-menu-toggle');
            
            if (sidebar && toggle && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });
    }

    handleNavigation(navLink) {
        // Update active state
        this.setActiveNavItem(navLink);
        
        // Get module to load
        const module = navLink.getAttribute('data-link');
        this.loadModule(module);
    }

    setActiveNavItem(activeLink) {
        // Remove active class from all nav items
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });

        // Add active class to clicked item and its parent
        activeLink.closest('.nav-item').classList.add('active');
    }

    async loadModule(module) {
        try {
            this.showLoading();
            
            // Update browser history
            window.history.pushState({ module }, '', `?module=${module}`);
            
            // Load module content
            const response = await fetch(`modules/${module}/index.php`);
            
            if (!response.ok) {
                throw new Error(`Failed to load module: ${module}`);
            }
            
            const content = await response.text();
            document.getElementById('app-content').innerHTML = content;
            this.currentModule = module;
            
            // Initialize module-specific scripts
            this.initializeModuleScripts(module);
            
        } catch (error) {
            console.error('Error loading module:', error);
            this.showError('Failed to load content. Please try again.');
        } finally {
            this.hideLoading();
        }
    }

    initializeModuleScripts(module) {
        // Load module-specific JavaScript
        const scriptPath = `assets/js/modules/${module}.js`;
        
        // Remove existing module script if any
        const existingScript = document.querySelector(`script[data-module="${module}"]`);
        if (existingScript) {
            existingScript.remove();
        }

        // Add new module script
        const script = document.createElement('script');
        script.src = scriptPath;
        script.setAttribute('data-module', module);
        script.onerror = () => console.warn(`Module script not found: ${scriptPath}`);
        document.body.appendChild(script);
    }

    loadInitialModule() {
        const urlParams = new URLSearchParams(window.location.search);
        const module = urlParams.get('module') || 'dashboard';
        this.loadModule(module);
    }

    showLoading() {
        // Create loading overlay
        const loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'loading-overlay';
        loadingOverlay.innerHTML = `
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Loading...</p>
            </div>
        `;
        document.getElementById('app-content').appendChild(loadingOverlay);
    }

    hideLoading() {
        const loadingOverlay = document.querySelector('.loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.remove();
        }
    }

    showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.innerHTML = `
            <div class="error-content">
                <i class="fas fa-exclamation-triangle"></i>
                <p>${message}</p>
                <button class="btn-retry" onclick="navigationManager.loadModule('dashboard')">Return to Dashboard</button>
            </div>
        `;
        document.getElementById('app-content').innerHTML = '';
        document.getElementById('app-content').appendChild(errorDiv);
    }
}

// Initialize navigation manager
const navigationManager = new NavigationManager();

// Handle browser back/forward buttons
window.addEventListener('popstate', (event) => {
    if (event.state && event.state.module) {
        navigationManager.loadModule(event.state.module);
    }
});