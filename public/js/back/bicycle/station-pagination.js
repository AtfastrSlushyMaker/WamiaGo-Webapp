/**
 * WamiaGo - Station Pagination Handler
 * Handles AJAX pagination for station listings
 */

// Initialize pagination when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    setupAjaxPagination();
    setupPerPageSelect();
});

/**
 * Set up AJAX pagination for station listings
 */
function setupAjaxPagination() {
    // Select all pagination links with the ajax-page-link class
    document.querySelectorAll('.ajax-page-link').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            // Get the URL from the href attribute
            const url = this.getAttribute('href');

            // Show a loading indicator
            showLoadingIndicator();

            // Fetch the content from the URL with AJAX
            fetchPageContent(url);
        });
    });
}

/**
 * Set up per-page dropdown selector
 */
function setupPerPageSelect() {
    const perPageSelect = document.getElementById('perPageSelect');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function () {
            // Construct URL with the new per-page value
            const url = new URL(window.location.href);
            url.searchParams.set('perPage', this.value);
            url.searchParams.set('page', '1'); // Reset to first page

            // Show loading indicator
            showLoadingIndicator();

            // Fetch content using the new URL
            fetchPageContent(url.toString());
        });
    }
}

/**
 * Show loading indicator while content is being loaded
 */
function showLoadingIndicator() {
    const contentContainer = document.querySelector('.container-fluid.px-4');
    if (contentContainer) {
        // Create a loading overlay
        const overlay = document.createElement('div');
        overlay.className = 'position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center';
        overlay.style.backgroundColor = 'rgba(255, 255, 255, 0.7)';
        overlay.style.zIndex = '999';
        overlay.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Loading stations...</p>
            </div>
        `;

        // Add positioning to container if not already set
        const currentPosition = window.getComputedStyle(contentContainer).position;
        if (currentPosition === 'static') {
            contentContainer.style.position = 'relative';
        }

        // Add overlay to container
        contentContainer.appendChild(overlay);
    }
}

/**
 * Fetch page content using AJAX
 */
function fetchPageContent(url) {
    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Server responded with ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            // Update browser history
            window.history.pushState({}, '', url);

            // Parse the HTML
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // Find the main content container from the response
            const newContent = doc.querySelector('.container-fluid.px-4');

            // Update the content in the current page
            if (newContent && document.querySelector('.container-fluid.px-4')) {
                document.querySelector('.container-fluid.px-4').innerHTML = newContent.innerHTML;

                // Reinitialize components after content change
                reinitializeComponents();
            } else {
                // If we couldn't find the right containers, reload the page
                console.error('Could not find expected content containers for AJAX update');
                window.location.href = url;
            }
        })
        .catch(error => {
            console.error('Error loading page via AJAX:', error);
            // Fallback to regular navigation
            window.location.href = url;
        });
}

/**
 * Reinitialize all components after content change
 */
function reinitializeComponents() {
    console.log('Reinitializing components after AJAX content update');

    // Re-initialize the map if available
    if (window.stationMap && typeof window.stationMap.initializeMap === 'function') {
        window.stationMap.initializeMap();
    }

    // Re-setup pagination after content update
    setupAjaxPagination();
    setupPerPageSelect();

    // Re-attach event listeners to table search
    setupTableSearch();

    // Re-setup edit buttons
    setupEditButtons();

    // Emit custom event for other components to respond to
    window.dispatchEvent(new CustomEvent('stationContentUpdated'));
}

/**
 * Set up table search functionality
 */
function setupTableSearch() {
    const stationSearch = document.getElementById('stationSearch');
    const quickStationSearch = document.getElementById('quickStationSearch');

    if (stationSearch) {
        stationSearch.addEventListener('input', function () {
            filterTableRows(this.value.toLowerCase());
        });
    }

    if (quickStationSearch) {
        quickStationSearch.addEventListener('input', function () {
            filterListItems(this.value.toLowerCase());
        });
    }
}

/**
 * Filter table rows based on search term
 */
function filterTableRows(searchTerm) {
    document.querySelectorAll('.station-row').forEach(row => {
        const stationName = row.querySelector('.fw-medium')?.textContent.toLowerCase() || '';
        const stationAddress = row.querySelector('.text-muted')?.textContent.toLowerCase() || '';
        const match = stationName.includes(searchTerm) || stationAddress.includes(searchTerm);

        row.style.display = match ? '' : 'none';
    });
}

/**
 * Filter list items based on search term
 */
function filterListItems(searchTerm) {
    document.querySelectorAll('.station-list-item').forEach(item => {
        const stationName = item.querySelector('.station-name')?.textContent.toLowerCase() || '';
        const stationAddress = item.querySelector('.station-address')?.textContent.toLowerCase() || '';
        const match = stationName.includes(searchTerm) || stationAddress.includes(searchTerm);

        item.style.display = match ? '' : 'none';
    });
}

/**
 * Set up edit buttons
 */
function setupEditButtons() {
    document.querySelectorAll('.station-edit-btn').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const stationId = this.getAttribute('data-station-id');
            if (typeof window.initEditStationModal === 'function') {
                window.initEditStationModal(stationId);
            }
        });
    });
}

// Export functions for global use
window.stationPagination = {
    setupAjaxPagination,
    setupPerPageSelect,
    reinitializeComponents
};