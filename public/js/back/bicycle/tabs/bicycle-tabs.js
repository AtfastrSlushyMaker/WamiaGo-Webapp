/**
 * Bicycle Tabs JavaScript
 * Handles tab animations and tab-specific functionality
 */

class BicycleTabs {
    constructor() {
        this.init();
    }

    init() {
        this.initTabsSwitching();
        this.initTabAnimations();
        console.log('Bicycle Tabs initialized');
    }

    /**
     * Initialize tab switching functionality
     */
    initTabsSwitching() {
        const tabLinks = document.querySelectorAll('#bicycleManagementTabs .nav-link');

        tabLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                // Prevent default action
                e.preventDefault();

                // Get the tab name
                const tabName = link.getAttribute('data-tab');

                // Update URL without reloading the page
                const url = new URL(window.location);
                url.searchParams.set('tab', tabName);
                window.history.pushState({}, '', url);

                // Activate the tab
                this.activateTab(tabName);
            });
        });

        // Handle browser back/forward navigation
        window.addEventListener('popstate', () => {
            const url = new URL(window.location);
            const tabName = url.searchParams.get('tab') || 'rentals';
            this.activateTab(tabName);
        });

        // Initialize with current tab
        const url = new URL(window.location);
        const currentTab = url.searchParams.get('tab') || 'rentals';
        this.activateTab(currentTab);
    }

    /**
     * Activate a specific tab
     * @param {string} tabName - The name of the tab to activate
     */
    activateTab(tabName) {
        // Get all tab links and content
        const tabLinks = document.querySelectorAll('#bicycleManagementTabs .nav-link');
        const tabContents = document.querySelectorAll('.tab-pane');

        // Remove active class from all tabs and hide content
        tabLinks.forEach(tab => tab.classList.remove('active'));
        tabContents.forEach(content => {
            content.classList.remove('show', 'active');
        });

        // Add active class to selected tab and show content
        const activeTab = document.querySelector(`#bicycleManagementTabs .nav-link[data-tab="${tabName}"]`);
        const activeContent = document.querySelector(`#${tabName}Tab`);

        if (activeTab && activeContent) {
            activeTab.classList.add('active');
            activeContent.classList.add('show', 'active');

            // Apply animation to the tab content
            this.animateTab(activeContent);
        }
    }

    /**
     * Apply enter animation to tab content
     * @param {HTMLElement} tabContent - The tab content to animate
     */
    animateTab(tabContent) {
        // First remove any existing animation classes
        tabContent.classList.remove('fadeInUp');

        // Force a reflow to make sure the animation runs again
        void tabContent.offsetWidth;

        // Add the animation class
        tabContent.classList.add('fadeInUp');
    }

    /**
     * Initialize animations for tab content
     */
    initTabAnimations() {
        // Animate headers in tabs
        document.querySelectorAll('.bicycle-tab-header').forEach(header => {
            this.addScrollAnimation(header, 'fadeInUp');
        });

        // Animate statistics cards
        document.querySelectorAll('.stat-card').forEach((card, index) => {
            this.addScrollAnimation(card, 'fadeInUp', index * 100);
        });

        // Animate tables
        document.querySelectorAll('.table-responsive').forEach(table => {
            this.addScrollAnimation(table, 'fadeInUp', 300);
        });
    }

    /**
     * Add scroll-triggered animation to an element
     * @param {HTMLElement} element - Element to animate
     * @param {string} animationClass - Animation class to apply
     * @param {number} delay - Delay before animation starts in ms
     */
    addScrollAnimation(element, animationClass, delay = 0) {
        // Check if IntersectionObserver is available
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        // Add animation after delay
                        setTimeout(() => {
                            entry.target.classList.add(animationClass);
                        }, delay);

                        // Stop observing after animation
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.2
            });

            observer.observe(element);
        } else {
            // Fallback for browsers without IntersectionObserver
            setTimeout(() => {
                element.classList.add(animationClass);
            }, delay);
        }
    }
}

// Initialize the tabs when DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    new BicycleTabs();
});