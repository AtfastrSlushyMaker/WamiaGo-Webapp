/**
 * WamiaGo - Global Search Handler
 * Handles search functionality across all bicycle management tabs
 */

document.addEventListener('DOMContentLoaded', function () {
    initGlobalSearch();
});

/**
 * Initialize search functionality across all tabs
 */
function initGlobalSearch() {
    setupSearchInputs();
    checkUrlSearchParam();
}

/**
 * Setup search input event handlers
 */
function setupSearchInputs() {
    // Setup for bicycle search
    setupTabSearch('bicycleSearchInput', 'bicycleSearchClearBtn', 'bicycles');

    // Setup for rental search
    setupTabSearch('rentalSearchInput', 'rentalSearchClearBtn', 'rentals');

    // Setup for station search
    setupTabSearch('stationSearchInput', 'stationSearchClearBtn', 'stations');
}

/**
 * Setup search functionality for a specific tab
 * @param {string} inputId - The ID of the search input element
 * @param {string} clearBtnId - The ID of the clear button element
 * @param {string} tabName - The name of the tab
 */
function setupTabSearch(inputId, clearBtnId, tabName) {
    const searchInput = document.getElementById(inputId);
    const clearBtn = document.getElementById(clearBtnId);

    if (!searchInput) return;    // Add input event for responsive client-side search without server requests
    let debounceTimer;
    searchInput.addEventListener('input', function () {
        // Show/hide clear button based on input content
        if (clearBtn) {
            clearBtn.style.display = this.value.trim() ? 'block' : 'none';
        }

        // Debounce the search to avoid too many DOM manipulations
        clearTimeout(debounceTimer);
        const searchTerm = this.value.trim();

        // If search is empty, clear the search completely
        if (searchTerm.length === 0) {
            clearGlobalSearch(tabName);
            return;
        }

        // Otherwise, debounce the client-side search (wait for 150ms after typing stops)
        debounceTimer = setTimeout(() => {
            performGlobalSearch(searchTerm, tabName);
        }, 150); // Reduced from 500ms for more responsive filtering
    });    // Also keep Enter key functionality for server-side search
    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(debounceTimer); // Clear any pending debounce
            const searchTerm = this.value.trim();

            if (searchTerm.length === 0) {
                clearGlobalSearch(tabName);
                return;
            }

            // Use the server-side search function
            performServerSearch(searchTerm, tabName);
        }
    });

    // Find and attach event listener to the search button if it exists
    const searchBtn = document.getElementById(`${tabName}SearchBtn`);
    if (searchBtn) {
        searchBtn.addEventListener('click', function () {
            const searchTerm = searchInput.value.trim();
            if (searchTerm.length === 0) {
                clearGlobalSearch(tabName);
                return;
            }

            // Trigger server-side search
            performServerSearch(searchTerm, tabName);
        });
    }

    // Clear search button functionality
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            clearBtn.style.display = 'none';
            clearGlobalSearch(tabName);
        });
    }
}

/**
 * Perform a global search across all data
 * @param {string} searchTerm - The search term
 * @param {string} tabName - The tab name
 */
function performGlobalSearch(searchTerm, tabName) {
    if (!searchTerm || searchTerm.length === 0) {
        clearGlobalSearch(tabName);
        return;
    }

    // First do client-side filtering for immediate feedback
    filterTableRows(searchTerm, tabName);
    highlightSearchTerms(searchTerm, tabName);
    showSearchResultCount(tabName);

    // Update URL without reloading the page
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('search', searchTerm);
    window.history.pushState({ search: searchTerm, tab: tabName }, '', currentUrl.toString());

    // Store search state
    storeSearchState(searchTerm, tabName);

    // Mark the tab as needing a refresh when cleared
    const tabElement = document.getElementById(`${tabName}Tab`);
    if (tabElement) {
        tabElement.setAttribute('data-needs-refresh', 'true');
    }

    // Only fetch from server when user presses Enter or clicks search button
    // This avoids the loading indicator on every keystroke
    const searchInput = document.getElementById(getSearchInputId(tabName));
    if (searchInput) {
        searchInput.setAttribute('data-needs-server-search', 'true');
    }
}

/**
 * Perform a server-side search by making an AJAX request
 * @param {string} searchTerm - The search term
 * @param {string} tabName - The tab name
 */
function performServerSearch(searchTerm, tabName) {
    if (!searchTerm || searchTerm.length === 0) {
        clearGlobalSearch(tabName);
        return;
    }

    // Show loading indicator for server request
    showLoadingOverlay(tabName);

    // Prepare URL with search parameter and reset to page 1
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('search', searchTerm);
    currentUrl.searchParams.set('page', '1'); // Always start at page 1 for a new search

    // Mark the tab as needing a refresh when cleared
    const tabElement = document.getElementById(`${tabName}Tab`);
    if (tabElement) {
        tabElement.setAttribute('data-needs-refresh', 'true');
    }

    // Fetch from server for complete results
    fetch(currentUrl.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }
            return response.text();
        })
        .then(html => {
            processSearchResults(html, searchTerm, tabName);
            const searchInput = document.getElementById(getSearchInputId(tabName));
            if (searchInput) {
                searchInput.removeAttribute('data-needs-server-search');
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            hideLoadingOverlay(tabName);
            showErrorMessage(tabName, error.message);
        });
}

/**
 * Process search results and update the UI
 * @param {string} html - The HTML response from the server
 * @param {string} searchTerm - The search term
 * @param {string} tabName - The tab name
 */
function processSearchResults(html, searchTerm, tabName) {
    // Parse HTML response
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');

    // Update table content based on the tab
    updateTabContent(doc, tabName);

    if (searchTerm && searchTerm.length > 0) {
        // Filter table to only show matching rows
        filterTableRows(searchTerm, tabName);

        // Highlight search term in results
        highlightSearchTerms(searchTerm, tabName);

        // Show result count
        showSearchResultCount(tabName);
    }

    // Reinitialize components
    reinitializeTabComponents(tabName);

    // Hide loading overlay
    hideLoadingOverlay(tabName);

    // Store search state
    storeSearchState(searchTerm, tabName);

    // Update pagination to include search parameter
    updatePaginationWithSearch(tabName, searchTerm);
}

/**
 * Update tab content with search results
 * @param {Document} doc - The parsed HTML document
 * @param {string} tabName - The tab name
 */
function updateTabContent(doc, tabName) {
    // Find the tab-specific table to update
    const tableSelector = getTabTableSelector(tabName);
    const paginationSelector = getTabPaginationSelector(tabName);

    // Get current table and the new table from the response
    const currentTable = document.querySelector(tableSelector);
    const newTable = doc.querySelector(tableSelector);

    // Update table body while preserving the table's classes and structure
    if (newTable && currentTable) {
        // Update table body content
        const newTableBody = newTable.querySelector('tbody');
        const currentTableBody = currentTable.querySelector('tbody');

        if (newTableBody && currentTableBody) {
            currentTableBody.innerHTML = newTableBody.innerHTML;
        }

        // Copy table classes to ensure styling is maintained
        currentTable.className = newTable.className;

        // Ensure table header styling is preserved
        const newTableHeader = newTable.querySelector('thead');
        const currentTableHeader = currentTable.querySelector('thead');

        if (newTableHeader && currentTableHeader) {
            currentTableHeader.className = newTableHeader.className;

            // Update header cells classes if needed
            const newHeaderCells = newTableHeader.querySelectorAll('th');
            const currentHeaderCells = currentTableHeader.querySelectorAll('th');

            if (newHeaderCells.length === currentHeaderCells.length) {
                for (let i = 0; i < newHeaderCells.length; i++) {
                    currentHeaderCells[i].className = newHeaderCells[i].className;
                }
            }
        }
    }

    // Update pagination
    const newPagination = doc.querySelector(paginationSelector);
    const currentPagination = document.querySelector(paginationSelector);

    if (newPagination && currentPagination) {
        currentPagination.innerHTML = newPagination.innerHTML;
    }
}

/**
 * Clear the global search and reset to default state
 * @param {string} tabName - The tab name
 */
function clearGlobalSearch(tabName) {
    // Reset search input
    const inputId = getSearchInputId(tabName);
    const searchInput = document.getElementById(inputId);

    if (searchInput) {
        searchInput.value = '';
        searchInput.removeAttribute('data-needs-server-search');
    }

    // Hide clear button
    const clearBtnId = `${tabName}SearchClearBtn`;
    const clearBtn = document.getElementById(clearBtnId);
    if (clearBtn) {
        clearBtn.style.display = 'none';
    }

    // Hide the search result count
    const resultCountElement = document.getElementById(`${tabName}SearchResultCount`);
    if (resultCountElement) {
        resultCountElement.style.display = 'none';
    }

    // Get the current URL
    const currentUrl = new URL(window.location.href);

    // Check if there was a search parameter or if we need to refresh anyway
    const hadSearchParam = currentUrl.searchParams.has('search');
    const needsRefresh = hadSearchParam || document.getElementById(`${tabName}Tab`).getAttribute('data-needs-refresh') === 'true';

    // Update URL without search parameter
    currentUrl.searchParams.delete('search');
    currentUrl.searchParams.set('page', '1'); // Reset to page 1
    window.history.pushState({ tab: tabName }, '', currentUrl.toString());

    // Always fetch from server to ensure proper formatting
    showLoadingOverlay(tabName);

    // Fetch results without search parameter
    fetch(currentUrl.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }
            return response.text();
        })
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // Update tab content
            updateTabContent(doc, tabName);

            // Remove any highlights
            removeHighlights(tabName);

            // Reinitialize components
            reinitializeTabComponents(tabName);

            // Clear search state
            clearSearchState(tabName);

            // Reset the needs-refresh flag
            const tabElement = document.getElementById(`${tabName}Tab`);
            if (tabElement) {
                tabElement.setAttribute('data-needs-refresh', 'false');
            }
        })
        .catch(error => {
            console.error('Error clearing search:', error);
        })
        .finally(() => {
            hideLoadingOverlay(tabName);
        });
}

/**
 * Show all rows in the table
 * @param {string} tabName - The tab name
 */
function showAllTableRows(tabName) {
    const tableSelector = getTabTableSelector(tabName);
    const table = document.querySelector(tableSelector);

    if (!table) return;

    // Show all rows
    const rows = table.querySelectorAll('tbody tr:not(.empty-search-results)');
    rows.forEach(row => row.style.display = '');

    // Remove any empty results message
    const emptyMessage = table.querySelector('.empty-search-results');
    if (emptyMessage) emptyMessage.remove();
}

/**
 * Remove highlights from the table
 * @param {string} tabName - The tab name
 */
function removeHighlights(tabName) {
    const tableSelector = getTabTableSelector(tabName);
    const table = document.querySelector(tableSelector);

    if (!table) return;

    // Remove mark tags
    const marks = table.querySelectorAll('mark');
    marks.forEach(mark => {
        const parent = mark.parentNode;
        if (parent) {
            parent.replaceChild(document.createTextNode(mark.textContent), mark);
            parent.normalize();
        }
    });

    // Restore original text from data attributes
    const cells = table.querySelectorAll('[data-original-text]');
    cells.forEach(cell => {
        cell.textContent = cell.getAttribute('data-original-text');
        cell.removeAttribute('data-original-text');
    });
}

/**
 * Check if the URL contains a search parameter and apply it
 */
function checkUrlSearchParam() {
    const urlParams = new URLSearchParams(window.location.search);
    const searchParam = urlParams.get('search');
    let tabParam = urlParams.get('tab');

    // Try to determine which tab is active based on URL path
    if (!tabParam) {
        const path = window.location.pathname;
        if (path.includes('bicycle')) tabParam = 'bicycles';
        else if (path.includes('rental')) tabParam = 'rentals';
        else if (path.includes('station')) tabParam = 'stations';
        else tabParam = 'rentals'; // Default
    }

    if (searchParam) {
        // Set the search input value
        const inputId = getSearchInputId(tabParam);
        const searchInput = document.getElementById(inputId);

        if (searchInput) {
            searchInput.value = searchParam;

            // Show clear button if it exists
            const clearBtnId = getClearButtonId(tabParam);
            const clearBtn = document.getElementById(clearBtnId);

            if (clearBtn) {
                clearBtn.style.display = 'block';
            }

            // Apply client-side filtering immediately
            filterTableRows(searchParam, tabParam);
            highlightSearchTerms(searchParam, tabParam);
            showSearchResultCount(tabParam);

            // Update pagination
            updatePaginationWithSearch(tabParam, searchParam);

            // Store search state
            storeSearchState(searchParam, tabParam);

            // Mark the tab as needing a refresh when cleared
            const tabElement = document.getElementById(`${tabParam}Tab`);
            if (tabElement) {
                tabElement.setAttribute('data-needs-refresh', 'true');
            }
        }
    }
}

/**
 * Filter table rows to only show matching ones
 * @param {string} searchTerm - The search term
 * @param {string} tabName - The tab name
 */
function filterTableRows(searchTerm, tabName) {
    if (!searchTerm) return;

    const tableSelector = getTabTableSelector(tabName);
    const table = document.querySelector(tableSelector);

    if (!table) return;

    const rows = table.querySelectorAll('tbody tr:not(.empty-search-results)');
    const terms = searchTerm.toLowerCase().split(' ').filter(term => term.length > 0);
    let matchCount = 0;

    // If no valid search terms (only spaces), show all rows and return
    if (terms.length === 0) {
        rows.forEach(row => row.style.display = '');

        // Remove any "No results" message
        const emptyMessage = table.querySelector('.empty-search-results');
        if (emptyMessage) emptyMessage.remove();

        table.dataset.matchCount = rows.length;
        return;
    }

    rows.forEach(row => {
        const rowText = row.textContent.toLowerCase();
        let rowMatches = false;

        // Check if row contains all search terms
        rowMatches = terms.every(term => {
            if (term.length < 2) return true; // Skip very short terms
            return rowText.includes(term);
        });

        // Show/hide row based on match
        if (rowMatches) {
            row.style.display = '';
            matchCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Handle case when no results are found
    if (matchCount === 0) {
        const tbody = table.querySelector('tbody');

        // Check if empty results message already exists
        let emptyMessage = tbody.querySelector('.empty-search-results');

        if (!emptyMessage) {
            const emptyRow = document.createElement('tr');
            emptyRow.className = 'empty-search-results';
            emptyRow.innerHTML = `
                <td colspan="20" class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-search fa-3x text-muted opacity-50"></i>
                    </div>
                    <h5>No matching results</h5>
                    <p class="text-muted mb-3">No records match your search criteria: "${searchTerm}"</p>
                    <button type="button" class="btn btn-outline-primary" id="clear${tabName}SearchBtn">
                        <i class="fas fa-times-circle me-2"></i>Clear Search
                    </button>
                </td>
            `;
            tbody.appendChild(emptyRow);

            // Add click handler to clear search button
            emptyRow.querySelector(`#clear${tabName}SearchBtn`).addEventListener('click', function () {
                clearGlobalSearch(tabName);
            });
        }
    } else {
        // Remove empty message if it exists and we have results
        const emptyMessage = table.querySelector('.empty-search-results');
        if (emptyMessage) {
            emptyMessage.remove();
        }
    }

    // Store match count in data attribute
    table.dataset.matchCount = matchCount;
}

/**
 * Show the count of search results
 * @param {string} tabName - The tab name
 */
function showSearchResultCount(tabName) {
    const tableSelector = getTabTableSelector(tabName);
    const table = document.querySelector(tableSelector);

    if (!table) return;

    const matchCount = parseInt(table.dataset.matchCount || 0);
    const searchInput = document.getElementById(getSearchInputId(tabName));
    const searchTerm = searchInput ? searchInput.value.trim() : '';

    // Find or create the results count element
    let resultCountElement = document.querySelector(`#${tabName}SearchResultCount`);

    if (!resultCountElement) {
        resultCountElement = document.createElement('div');
        resultCountElement.id = `${tabName}SearchResultCount`;
        resultCountElement.className = 'search-result-count mt-3 mb-2 ms-2';

        // Insert after the table
        if (table.nextElementSibling) {
            table.parentNode.insertBefore(resultCountElement, table.nextElementSibling);
        } else {
            table.parentNode.appendChild(resultCountElement);
        }
    }

    // Update the content based on results
    if (searchTerm && searchTerm.length > 0) {
        resultCountElement.innerHTML = matchCount > 0
            ? `<i class="fas fa-filter me-2"></i><strong>${matchCount}</strong> matching result${matchCount !== 1 ? 's' : ''} for "${searchTerm}"`
            : '';
        resultCountElement.style.display = matchCount > 0 ? 'block' : 'none';
    } else {
        resultCountElement.style.display = 'none';
    }
}

/**
 * Highlight search terms in the search results
 * @param {string} searchTerm - The search term
 * @param {string} tabName - The tab name
 */
function highlightSearchTerms(searchTerm, tabName) {
    if (!searchTerm) return;

    const tableSelector = getTabTableSelector(tabName);
    const table = document.querySelector(tableSelector);

    if (!table) return;

    // First, remove any existing highlights
    const highlightedCells = table.querySelectorAll('mark');
    highlightedCells.forEach(mark => {
        const parent = mark.parentNode;
        if (parent) {
            parent.replaceChild(document.createTextNode(mark.textContent), mark);
            // Normalize to merge adjacent text nodes
            parent.normalize();
        }
    });

    // Get only visible cells excluding action buttons
    const cells = table.querySelectorAll('tbody tr:not([style*="none"]):not(.empty-search-results) td:not(.actions)');
    const terms = searchTerm.toLowerCase().split(' ').filter(term => term.length >= 2);

    if (terms.length === 0) return;

    // Create a safe regex pattern from search terms
    const safeTerms = terms.map(term => escapeRegExp(term)).join('|');
    const regex = new RegExp(`(${safeTerms})`, 'gi');

    cells.forEach(cell => {
        // Store original text in a data attribute if not already stored
        if (!cell.hasAttribute('data-original-text')) {
            cell.setAttribute('data-original-text', cell.textContent);
        }

        const originalText = cell.getAttribute('data-original-text');

        // Only apply highlighting if the cell contains any search term
        if (terms.some(term => originalText.toLowerCase().includes(term))) {
            // Replace with highlighting
            cell.innerHTML = originalText.replace(regex, '<mark>$1</mark>');
        }
    });
}

/**
 * Escape special characters for regular expressions
 * @param {string} string - The string to escape
 * @returns {string} - The escaped string
 */
function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Store the current search state
 * @param {string} searchTerm - The search term
 * @param {string} tabName - The tab name
 */
function storeSearchState(searchTerm, tabName) {
    const inputId = getSearchInputId(tabName);
    const searchInput = document.getElementById(inputId);

    if (searchInput) {
        searchInput.setAttribute('data-current-search', searchTerm);
    }

    // Store in session storage for persistence
    sessionStorage.setItem(`${tabName}-search`, searchTerm);
}

/**
 * Clear the stored search state
 * @param {string} tabName - The tab name
 */
function clearSearchState(tabName) {
    const inputId = getSearchInputId(tabName);
    const searchInput = document.getElementById(inputId);

    if (searchInput) {
        searchInput.removeAttribute('data-current-search');
    }

    // Remove from session storage
    sessionStorage.removeItem(`${tabName}-search`);
}

/**
 * Show loading overlay for the specified tab
 * @param {string} tabName - The tab name
 */
function showLoadingOverlay(tabName) {
    const contentArea = document.querySelector(`#${tabName}TabContent`) ||
        document.querySelector(`#${tabName}Tab`);

    // Also show loading indicator in the search input itself
    const searchInputId = getSearchInputId(tabName);
    const searchInput = document.getElementById(searchInputId);

    if (searchInput) {
        // Add a loading class to the search input's parent
        const searchContainer = searchInput.closest('.input-group');
        if (searchContainer) {
            searchContainer.classList.add('search-loading');

            // Add or update the loading spinner in the input group
            let spinner = searchContainer.querySelector('.search-spinner');
            if (!spinner) {
                spinner = document.createElement('div');
                spinner.className = 'search-spinner position-absolute';
                spinner.innerHTML = '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Searching...</span></div>';
                spinner.style.cssText = 'right: 40px; top: 50%; transform: translateY(-50%); z-index: 5;';
                searchContainer.appendChild(spinner);
            } else {
                spinner.style.display = 'block';
            }
        }
    }

    if (!contentArea) return;

    // Create and show loading overlay if it doesn't exist
    let loadingOverlay = contentArea.querySelector('.global-search-loading');

    if (!loadingOverlay) {
        loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'global-search-loading position-absolute';
        loadingOverlay.style.cssText = `
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.7);
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
        `;
        loadingOverlay.innerHTML = `
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        `;

        // Set position relative on parent if not already set
        const currentPosition = window.getComputedStyle(contentArea).position;
        if (currentPosition === 'static') {
            contentArea.style.position = 'relative';
        }

        contentArea.appendChild(loadingOverlay);
    } else {
        loadingOverlay.style.display = 'flex';
    }
}

/**
 * Hide loading overlay for the specified tab
 * @param {string} tabName - The tab name
 */
function hideLoadingOverlay(tabName) {
    // Hide loading indicator in the search input
    const searchInputId = getSearchInputId(tabName);
    const searchInput = document.getElementById(searchInputId);

    if (searchInput) {
        // Remove loading class from the search input's parent
        const searchContainer = searchInput.closest('.input-group');
        if (searchContainer) {
            searchContainer.classList.remove('search-loading');

            // Hide the spinner
            const spinner = searchContainer.querySelector('.search-spinner');
            if (spinner) {
                spinner.style.display = 'none';
            }
        }
    }

    const contentArea = document.querySelector(`#${tabName}TabContent`) ||
        document.querySelector(`#${tabName}Tab`);

    if (!contentArea) return;

    const loadingOverlay = contentArea.querySelector('.global-search-loading');

    if (loadingOverlay) {
        loadingOverlay.style.display = 'none';
    }
}

/**
 * Show error message in the specified tab
 * @param {string} tabName - The tab name
 * @param {string} message - The error message
 */
function showErrorMessage(tabName, message) {
    const contentArea = document.querySelector(`#${tabName}TabContent`) ||
        document.querySelector(`#${tabName}Tab`);

    if (!contentArea) return;

    // Create error message element
    const errorElement = document.createElement('div');
    errorElement.className = 'alert alert-danger mt-3 mb-3 global-search-error';
    errorElement.innerHTML = `
        <strong><i class="fas fa-exclamation-circle"></i> Search Error:</strong> ${message}
        <button type="button" class="btn-close float-end" aria-label="Close"></button>
    `;

    // Add click handler to close button
    errorElement.querySelector('.btn-close').addEventListener('click', function () {
        errorElement.remove();
    });

    // Remove any existing error messages
    const existingError = contentArea.querySelector('.global-search-error');
    if (existingError) {
        existingError.remove();
    }

    // Insert error message
    contentArea.insertBefore(errorElement, contentArea.firstChild);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (errorElement.parentNode) {
            errorElement.remove();
        }
    }, 5000);
}

/**
 * Reinitialize components after search
 * @param {string} tabName - The tab name
 */
function reinitializeTabComponents(tabName) {
    // Setup AJAX pagination links
    setupSearchAwarePagination(tabName);

    // Reinitialize sort headers
    setupSortableHeaders(tabName);

    // Reinitialize tooltips
    if (typeof bootstrap !== 'undefined') {
        const tooltips = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltips.map(function (tooltip) {
            return new bootstrap.Tooltip(tooltip);
        });
    }

    // Tab-specific reinitializations
    if (tabName === 'stations' && typeof window.stationMap !== 'undefined') {
        if (typeof window.stationMap.initializeMap === 'function') {
            window.stationMap.initializeMap();
        }

        // Re-attach station locate buttons
        document.querySelectorAll('.station-locate-btn').forEach(button => {
            button.addEventListener('click', function () {
                const stationId = parseInt(this.getAttribute('data-station-id'), 10);
                if (window.stationMap && typeof window.stationMap.locateStation === 'function') {
                    window.stationMap.locateStation(stationId);
                }
            });
        });
    }
}

/**
 * Setup pagination that's aware of the current search term
 * @param {string} tabName - The tab name
 */
function setupSearchAwarePagination(tabName) {
    const paginationSelector = getTabPaginationSelector(tabName);
    const paginationLinks = document.querySelectorAll(`${paginationSelector} .page-link`);

    paginationLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            // Get the current search term
            const inputId = getSearchInputId(tabName);
            const searchInput = document.getElementById(inputId);
            const searchTerm = searchInput ? searchInput.value.trim() : '';

            // Get the URL from the link
            let url = new URL(this.href);

            // Add search parameter if there's a search term
            if (searchTerm) {
                url.searchParams.set('search', searchTerm);

                // Mark the tab as needing a refresh when search is cleared
                const tabElement = document.getElementById(`${tabName}Tab`);
                if (tabElement) {
                    tabElement.setAttribute('data-needs-refresh', 'true');
                }
            }

            // Show loading
            showLoadingOverlay(tabName);

            // Fetch the page content with search parameter
            fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Error ${response.status}: ${response.statusText}`);
                    }
                    return response.text();
                })
                .then(html => {
                    // Update URL in browser history
                    window.history.pushState({ search: searchTerm, tab: tabName, page: url.searchParams.get('page') }, '', url.toString());

                    // Parse HTML response
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    // Update table content based on the tab
                    updateTabContent(doc, tabName);

                    // Apply search filtering if there's a search term
                    if (searchTerm && searchTerm.length > 0) {
                        filterTableRows(searchTerm, tabName);
                        highlightSearchTerms(searchTerm, tabName);
                        showSearchResultCount(tabName);
                    }

                    // Update pagination with search parameter
                    updatePaginationWithSearch(tabName, searchTerm);

                    // Reinitialize components
                    reinitializeTabComponents(tabName);

                    // Hide loading overlay
                    hideLoadingOverlay(tabName);
                })
                .catch(error => {
                    console.error('Pagination error:', error);
                    hideLoadingOverlay(tabName);
                    showErrorMessage(tabName, error.message);
                });
        });
    });
}

/* Helper functions to get element selectors and IDs based on tab name */

function getSearchInputId(tabName) {
    const inputMappings = {
        'bicycles': 'bicycleSearchInput',
        'stations': 'stationSearchInput',
        'rentals': 'rentalSearchInput'
    };

    return inputMappings[tabName] || '';
}

function getClearButtonId(tabName) {
    const buttonMappings = {
        'bicycles': 'bicycleSearchClearBtn',
        'stations': 'stationSearchClearBtn',
        'rentals': 'rentalSearchClearBtn'
    };

    return buttonMappings[tabName] || '';
}

function getTabTableSelector(tabName) {
    const tableMappings = {
        'bicycles': '.bicycle-table',
        'stations': '.station-table',
        'rentals': '.rental-table'
    };

    return tableMappings[tabName] || '';
}

function getTabPaginationSelector(tabName) {
    return `.ajax-pagination[data-tab="${tabName}"]`;
}

/**
 * Update pagination links to include search parameters
 * @param {string} tabName - The tab name
 * @param {string} searchTerm - The search term
 */
function updatePaginationWithSearch(tabName, searchTerm) {
    const paginationSelector = getTabPaginationSelector(tabName);
    const paginationLinks = document.querySelectorAll(`${paginationSelector} .page-link`);

    paginationLinks.forEach(link => {
        const url = new URL(link.href);
        if (searchTerm && searchTerm.trim()) {
            url.searchParams.set('search', searchTerm.trim());
        } else {
            url.searchParams.delete('search');
        }
        link.href = url.toString();
    });
}

/**
 * Get the CSS selector for the content area of a specific tab
 * @param {string} tabName - The tab name
 * @returns {string} - The CSS selector for the content area
 */
function getTabContentSelector(tabName) {
    switch (tabName) {
        case 'bicycles':
            return '.bicycle-content';
        case 'rentals':
            return '.rental-content';
        case 'stations':
            return '.station-content';
        default:
            return '';
    }
}

/**
 * Setup sortable headers to work with the current search term
 * @param {string} tabName - The tab name
 */
function setupSortableHeaders(tabName) {
    const tableSelector = getTabTableSelector(tabName);
    const sortHeaders = document.querySelectorAll(`${tableSelector} thead th[data-sort]`);

    if (!sortHeaders.length) return;

    sortHeaders.forEach(header => {
        header.addEventListener('click', function () {
            // Get sort direction and field
            const sortField = this.getAttribute('data-sort');
            const currentDirection = this.getAttribute('data-direction') || 'asc';
            const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';

            // Get the current search term
            const inputId = getSearchInputId(tabName);
            const searchInput = document.getElementById(inputId);
            const searchTerm = searchInput ? searchInput.value.trim() : '';

            // Build the URL with sort parameters
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('sort', sortField);
            currentUrl.searchParams.set('direction', newDirection);

            // Keep search parameter if there's a search term
            if (searchTerm) {
                currentUrl.searchParams.set('search', searchTerm);
            } else {
                currentUrl.searchParams.delete('search');
            }

            // Show loading
            showLoadingOverlay(tabName);

            // Fetch the sorted results
            fetch(currentUrl.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Error ${response.status}: ${response.statusText}`);
                    }
                    return response.text();
                })
                .then(html => {
                    // Update URL without reloading
                    window.history.pushState({}, '', currentUrl.toString());

                    // Process results
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    // Update content
                    updateTabContent(doc, tabName);

                    // Apply filtering and highlighting if we have a search term
                    if (searchTerm) {
                        filterTableRows(searchTerm, tabName);
                        highlightSearchTerms(searchTerm, tabName);
                        showSearchResultCount(tabName);
                    }

                    // Update sort direction indicators on all headers
                    document.querySelectorAll(`${tableSelector} thead th[data-sort]`).forEach(h => {
                        h.removeAttribute('data-direction');
                        h.querySelector('.sort-indicator')?.remove();
                    });

                    // Update clicked header
                    this.setAttribute('data-direction', newDirection);

                    // Add sort indicator
                    let indicator = document.createElement('span');
                    indicator.className = `sort-indicator ms-2 ${newDirection === 'asc' ? 'fa-solid fa-sort-up' : 'fa-solid fa-sort-down'}`;
                    this.appendChild(indicator);

                    // Reinitialize components
                    reinitializeTabComponents(tabName);

                    // Hide loading
                    hideLoadingOverlay(tabName);
                })
                .catch(error => {
                    console.error('Sorting error:', error);
                    hideLoadingOverlay(tabName);
                    showErrorMessage(tabName, error.message);
                });
        });
    });
}

// Make functions accessible globally
window.bicycleSearch = {
    init: initGlobalSearch,
    perform: performGlobalSearch,
    clear: clearGlobalSearch
};
