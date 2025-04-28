/**
 * WamiaGo - Station Pagination Handler
 * Handles AJAX pagination for bicycle stations list
 */

document.addEventListener('DOMContentLoaded', function () {
    // Initialize pagination when the document is loaded
    initPagination();

    // Special handling: force check for missing pagination after map loads
    setTimeout(checkForMissingPagination, 1000);
});

/**
 * Initialize pagination functionality
 */
function initPagination() {
    setupAjaxPagination();
    setupPerPageSelect();
}

/**
 * Setup Ajax pagination links
 */
function setupAjaxPagination() {
    // Target all pagination links with the ajax-page-link class
    document.querySelectorAll('.ajax-page-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            const page = this.getAttribute('data-page');
            fetchPageContent(url, { page });
        });
    });

    console.log('AJAX pagination links setup complete');
}

/**
 * Setup items per page selector
 */
function setupPerPageSelect() {
    const perPageSelect = document.getElementById('perPageSelect');

    if (perPageSelect) {
        perPageSelect.addEventListener('change', function () {
            const currentPath = window.location.pathname;
            const currentParams = new URLSearchParams(window.location.search);

            // Update perPage parameter
            currentParams.set('perPage', this.value);

            // Reset to first page when changing items per page
            currentParams.set('page', '1');

            const newUrl = `${currentPath}?${currentParams.toString()}`;
            fetchPageContent(newUrl, { perPage: this.value, page: 1 });
        });

        console.log('Per page select setup complete');
    }
}

/**
 * Check if pagination is missing and restore it if needed
 * This is particularly needed for the stations tab where the map might
 * have accidentally removed the pagination element
 */
function checkForMissingPagination() {
    // Check if we're on the stations tab and pagination might be missing
    const isStationsTab = window.location.search.includes('tab=stations');
    const stationsTab = document.getElementById('stationsTab');

    if (isStationsTab && stationsTab && stationsTab.classList.contains('active')) {
        const existingPagination = stationsTab.querySelector('.ajax-pagination');

        // If pagination is missing from the stations tab, we need to recreate it
        if (!existingPagination) {
            console.log('Pagination missing from stations tab, attempting to restore it');

            // Get the current URL to fetch pagination data
            const currentUrl = window.location.href;

            // Fetch the current page to extract pagination data
            fetch(currentUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    // Try to find pagination in the response
                    const paginationInResponse = doc.querySelector('.ajax-pagination');

                    if (paginationInResponse) {
                        // Find where to insert the pagination
                        const stationsContent = stationsTab.querySelector('.card-body') ||
                            stationsTab.querySelector('.tab-content') ||
                            stationsTab;

                        // Create a container for the pagination if needed
                        const paginationContainer = document.createElement('div');
                        paginationContainer.className = 'mt-4';
                        paginationContainer.innerHTML = paginationInResponse.outerHTML;

                        // Add the pagination to the bottom of the tab content
                        stationsContent.appendChild(paginationContainer);

                        // Reinitialize the pagination links
                        setupAjaxPagination();
                        setupPerPageSelect();

                        console.log('Successfully restored pagination for stations tab');
                    }
                })
                .catch(error => console.error('Error restoring pagination:', error));
        }
    }
}

/**
 * Fetch page content via AJAX
 */
function fetchPageContent(url, params = {}) {
    // Show loading indicator
    const contentArea = document.querySelector('#stationContentArea') ||
        document.querySelector('#bicycleContentArea') ||
        document.querySelector('#rentalContentArea') ||
        document.querySelector('.tab-content .active');

    if (contentArea) {
        // Create and show loading overlay
        const loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'loading-overlay';
        loadingOverlay.innerHTML = `
            <div class="d-flex justify-content-center align-items-center h-100">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        loadingOverlay.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        `;
        contentArea.style.position = 'relative';
        contentArea.appendChild(loadingOverlay);
    }

    console.log('Fetching page content:', url, params);

    // Make AJAX request
    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Server responded with ${response.status}: ${response.statusText}`);
            }
            return response.text();
        })
        .then(html => {
            // Update URL in browser history
            window.history.pushState({}, '', url);

            // Find the content to replace - this depends on your page structure
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // Find which tab is active
            const tabParam = new URLSearchParams(window.location.search).get('tab') || 'rentals';
            const tabContentSelector = `#${tabParam}TabContent`;
            const tabContent = doc.querySelector(tabContentSelector);

            if (tabContent) {
                // Update just the tab content
                document.querySelector(tabContentSelector).innerHTML = tabContent.innerHTML;
            } else {
                // Fallback: try to find any content area to update
                const contentArea = document.querySelector('#stationContentArea') ||
                    document.querySelector('#bicycleContentArea') ||
                    document.querySelector('#rentalContentArea') ||
                    document.querySelector('.tab-content .active');

                if (contentArea) {
                    // Try to find corresponding content in the fetched page
                    const newContent = doc.querySelector('.tab-content .active') ||
                        doc.querySelector('#stationContentArea') ||
                        doc.querySelector('#bicycleContentArea') ||
                        doc.querySelector('#rentalContentArea');

                    if (newContent) {
                        contentArea.innerHTML = newContent.innerHTML;
                    } else {
                        console.error('Could not find content to update in the fetched page.');
                    }
                } else {
                    console.error('Could not find content area to update on the current page.');
                }
            }

            // Reinitialize components on the refreshed content
            reinitializeComponents();
        })
        .catch(error => {
            console.error('Error fetching page content:', error);
            // Show error message
            if (contentArea) {
                const errorMessage = document.createElement('div');
                errorMessage.className = 'alert alert-danger m-3';
                errorMessage.innerHTML = `
                <h4><i class="fas fa-exclamation-circle me-2"></i> Error Loading Content</h4>
                <p>${error.message}</p>
                <button class="btn btn-sm btn-outline-danger" onclick="window.location.reload()">
                    <i class="fas fa-sync-alt me-1"></i> Reload Page
                </button>
            `;
                contentArea.appendChild(errorMessage);
            }
        })
        .finally(() => {
            // Remove loading overlay
            if (contentArea) {
                const overlay = contentArea.querySelector('.loading-overlay');
                if (overlay) {
                    overlay.remove();
                }
            }
        });
}

/**
 * Reinitialize components after AJAX content load
 */
function reinitializeComponents() {
    // Re-setup pagination on the new content
    setupAjaxPagination();
    setupPerPageSelect();

    // Check if pagination is missing and restore it if needed
    setTimeout(checkForMissingPagination, 500);

    // Reinitialize station map if it exists
    if (typeof window.stationMap !== 'undefined' && typeof window.stationMap.initializeMap === 'function') {
        console.log('Reinitializing station map after pagination');
        window.stationMap.initializeMap();
    }

    // Reinitialize modals
    setupLocateButtons();
    setupEditButtons();

    // Reinitialize tooltips and popovers if Bootstrap is used
    if (typeof bootstrap !== 'undefined') {
        const tooltips = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltips.map(function (tooltip) {
            return new bootstrap.Tooltip(tooltip);
        });

        const popovers = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popovers.map(function (popover) {
            return new bootstrap.Popover(popover);
        });
    }

    console.log('Components reinitialized after content load');
}

/**
 * Setup station locate buttons
 */
function setupLocateButtons() {
    document.querySelectorAll('.station-locate-btn, .view-on-map-btn').forEach(button => {
        button.addEventListener('click', function () {
            const stationId = parseInt(this.getAttribute('data-station-id'), 10);
            if (window.stationMap && typeof window.stationMap.locateStation === 'function') {
                window.stationMap.locateStation(stationId);
            }
        });
    });
}

/**
 * Setup station edit buttons
 */
function setupEditButtons() {
    document.querySelectorAll('.station-edit-btn').forEach(button => {
        button.addEventListener('click', function () {
            const stationId = parseInt(this.getAttribute('data-station-id'), 10);
            if (typeof window.initEditStationModal === 'function') {
                window.initEditStationModal(stationId);
            }
        });
    });
}

// Export function for global access
window.bicyclePagination = {
    init: initPagination,
    fetchPage: fetchPageContent,
    reinitializeComponents: reinitializeComponents
};