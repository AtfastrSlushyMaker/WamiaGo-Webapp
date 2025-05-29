/**
 * Enhanced search functionality for WamiaGo User Management
 * - Instant search as you type
 * - Highlights matching text
 * - Works with both table and card views
 * - Shows result count
 */
document.addEventListener('DOMContentLoaded', function () {
    // Elements
    const searchInput = document.getElementById('user-search');
    const searchClear = document.getElementById('search-clear');
    const searchResultsCount = document.getElementById('search-results-count');
    const searchIndicator = document.getElementById('search-indicator');

    // Keep track of the current timeout for debouncing
    let searchTimeout;
    // Keep track of the search state
    let isSearching = false;

    // Initialization
    function initSearch() {
        if (!searchInput) return;

        // Event listeners
        searchInput.addEventListener('input', handleSearchInput);
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                clearSearch();
            }
        });

        if (searchClear) {
            searchClear.addEventListener('click', clearSearch);
        }

        // Initialize clear button state
        updateClearButtonVisibility();
    }

    function handleSearchInput(e) {
        const searchTerm = e.target.value.trim();

        // Update clear button visibility
        updateClearButtonVisibility();

        // Debounce the search to avoid excessive processing
        clearTimeout(searchTimeout);

        if (searchTerm.length === 0) {
            performClearSearch();
            return;
        }

        // Show loading indicator
        if (searchIndicator) {
            searchIndicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }

        // Set searching flag
        isSearching = true;

        searchTimeout = setTimeout(() => {
            performSearch(searchTerm);
        }, 300); // 300ms debounce
    }

    function updateClearButtonVisibility() {
        if (searchClear) {
            if (searchInput.value.length > 0) {
                searchClear.classList.add('visible');
            } else {
                searchClear.classList.remove('visible');
            }
        }
    }

    function clearSearch() {
        // Clear the search input
        searchInput.value = '';
        updateClearButtonVisibility();

        // Clear local search display
        performClearSearch();

        // Call the resetFilters function from admin-user-management.js
        if (typeof window.resetFilters === 'function') {
            window.resetFilters();
        }

        // Force reload the user data with the cleared filter
        if (typeof window.loadUsers === 'function') {
            console.log('Reloading users after search clear');
            window.loadUsers(1);
        }

        // Focus the search input after clearing
        searchInput.focus();
    }

    function performClearSearch() {
        // Reset searching flag
        isSearching = false;

        // Reset the search indicator
        if (searchIndicator) {
            searchIndicator.innerHTML = '<i class="fas fa-search"></i>';
        }

        // Hide the results count
        if (searchResultsCount) {
            searchResultsCount.classList.remove('visible');
        }

        // Get all content containers to reset
        resetHighlighting();

        // Show all rows in table view with an important flag
        const tableRows = document.querySelectorAll('#users-table-body tr');
        tableRows.forEach(row => {
            row.style.display = ''; // Clear the display style
            row.style.visibility = ''; // Clear visibility if it was set
            row.classList.remove('search-result-row');

            // Remove the data-filtered attribute to allow filters to be reapplied cleanly
            if (row.hasAttribute('data-filtered')) {
                row.removeAttribute('data-filtered');
            }
        });

        // Show all cards in card view with the same approach
        const cards = document.querySelectorAll('#users-card-container .card');
        cards.forEach(card => {
            card.style.display = ''; // Clear the display style
            card.style.visibility = ''; // Clear visibility if it was set
            card.classList.remove('search-result-card');

            // Remove the data-filtered attribute to allow filters to be reapplied cleanly
            if (card.hasAttribute('data-filtered')) {
                card.removeAttribute('data-filtered');
            }
        });

        // Emit an event to notify other components that search has been cleared
        window.dispatchEvent(new CustomEvent('userSearchCleared'));

        // Force reload the view to ensure proper display of all elements
        if (typeof window.displayUsers === 'function') {
            window.displayUsers();
        }

        // Update pagination state for both views
        if (typeof window.updatePagination === 'function') {
            window.updatePagination();
        }

        // If any global filter state handler exists, trigger it to reapply filters
        if (typeof window.applyFilters === 'function') {
            window.applyFilters();
        }
    }

    function resetHighlighting() {
        // Remove all highlight elements by replacing them with their text content
        document.querySelectorAll('.highlight-match').forEach(highlight => {
            const textNode = document.createTextNode(highlight.textContent);
            highlight.parentNode.replaceChild(textNode, highlight);
        });

        // Find all text nodes that may have been merged and normalize them
        document.querySelectorAll('#users-table-body, #users-card-container').forEach(container => {
            container.normalize();
        });
    }

    function performSearch(searchTerm) {
        // Reset searching flag
        isSearching = false;

        // Reset the search indicator
        if (searchIndicator) {
            searchIndicator.innerHTML = '<i class="fas fa-search"></i>';
        }

        // Reset highlighting from previous searches
        resetHighlighting();

        // Store the current search term in a global variable so it can be reapplied
        window.currentSearchTerm = searchTerm;

        // Prepare case-insensitive search term
        const searchRegex = new RegExp(escapeRegExp(searchTerm), 'gi');

        // Track the matched items
        let matchedRowCount = 0;
        let matchedCardCount = 0;

        // Search in table view
        const tableRows = document.querySelectorAll('#users-table-body tr');
        tableRows.forEach(row => {
            const textContent = row.textContent.toLowerCase();

            // Check if row matches
            if (textContent.includes(searchTerm.toLowerCase())) {
                row.style.display = '';
                row.classList.add('search-result-row');
                matchedRowCount++;

                // Highlight matching text in the row
                highlightMatches(row, searchRegex);
            } else {
                row.style.display = 'none';
                row.classList.remove('search-result-row');
            }
        });

        // Search in card view
        const cards = document.querySelectorAll('#users-card-container .card');
        cards.forEach(card => {
            const textContent = card.textContent.toLowerCase();

            // Check if card matches
            if (textContent.includes(searchTerm.toLowerCase())) {
                card.style.display = '';
                card.classList.add('search-result-card');
                matchedCardCount++;

                // Highlight matching text in the card
                highlightMatches(card, searchRegex);
            } else {
                card.style.display = 'none';
                card.classList.remove('search-result-card');
            }
        });

        // Update the results count
        const totalMatches = matchedRowCount || matchedCardCount; // Use row count if in table view, else card count
        if (searchResultsCount) {
            searchResultsCount.textContent = `${totalMatches} result${totalMatches !== 1 ? 's' : ''}`;
            searchResultsCount.classList.add('visible');
        }

        // Update pagination state if pagination function exists
        if (window.updatePagination) {
            window.updatePagination();
        }

        // Fire a custom event that other code might listen for
        window.dispatchEvent(new CustomEvent('userSearchCompleted', {
            detail: {
                searchTerm: searchTerm,
                matchCount: totalMatches
            }
        }));
    }

    function highlightMatches(element, regex) {
        // First ensure any existing highlights are removed in this element
        const existingHighlights = element.querySelectorAll('.highlight-match');
        existingHighlights.forEach(highlight => {
            const textNode = document.createTextNode(highlight.textContent);
            highlight.parentNode.replaceChild(textNode, highlight);
        });

        // Normalize the DOM to merge adjacent text nodes
        element.normalize();

        // Use TreeWalker to find all text nodes
        const walker = document.createTreeWalker(
            element,
            NodeFilter.SHOW_TEXT,
            {
                acceptNode: function (node) {
                    // Skip nodes in script and style elements or elements with special attributes
                    const parent = node.parentNode;
                    if (!parent ||
                        parent.tagName === 'SCRIPT' ||
                        parent.tagName === 'STYLE' ||
                        parent.hasAttribute('data-no-highlight')) {
                        return NodeFilter.FILTER_REJECT;
                    }

                    // Skip empty text nodes
                    if (node.nodeValue.trim() === '') {
                        return NodeFilter.FILTER_SKIP;
                    }

                    return NodeFilter.FILTER_ACCEPT;
                }
            }
        );

        // Process text nodes
        let textNode;
        const nodesToProcess = [];

        // Collect nodes first to avoid DOM modification during traversal
        while (textNode = walker.nextNode()) {
            nodesToProcess.push(textNode);
        }

        // Process each text node
        nodesToProcess.forEach(textNode => {
            const text = textNode.nodeValue;
            const parent = textNode.parentNode;

            // Skip if the parent is already a highlight or has no text content
            if (parent.classList && parent.classList.contains('highlight-match') || !text) {
                return;
            }

            // Create a fresh copy of the regex for each text node to ensure lastIndex is at 0
            const freshRegex = new RegExp(regex.source, regex.flags);

            // Skip if this text doesn't match the search term
            if (!freshRegex.test(text)) {
                return;
            }

            // Reset the regex again for the actual processing
            freshRegex.lastIndex = 0;

            // Create a document fragment to hold highlighted content
            const fragment = document.createDocumentFragment();
            let lastIndex = 0;
            let match;

            // Find and process all matches
            while ((match = freshRegex.exec(text)) !== null) {
                // Add any text before this match
                if (match.index > lastIndex) {
                    fragment.appendChild(document.createTextNode(
                        text.substring(lastIndex, match.index)
                    ));
                }

                // Create a highlight span for the match
                const highlightSpan = document.createElement('span');
                highlightSpan.className = 'highlight-match';
                highlightSpan.textContent = match[0];
                // Add inline style to ensure highlighting is visible
                highlightSpan.style.backgroundColor = '#ffff00';
                highlightSpan.style.color = '#000000';
                highlightSpan.style.fontWeight = 'bold';
                fragment.appendChild(highlightSpan);

                lastIndex = freshRegex.lastIndex;

                // Prevent infinite loops for zero-length matches
                if (match.index === freshRegex.lastIndex) {
                    freshRegex.lastIndex++;
                }
            }

            // Add any text after the last match
            if (lastIndex < text.length) {
                fragment.appendChild(document.createTextNode(
                    text.substring(lastIndex)
                ));
            }

            // Replace the original text node with our fragment
            try {
                parent.replaceChild(fragment, textNode);
            } catch (e) {
                console.error('Error highlighting text:', e, 'Text:', text);
            }
        });
    }

    // Helper to escape special characters in the search term for regex
    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // Initialize search when DOM is ready
    initSearch();

    // Expose some functions to the global scope for use in other scripts
    window.userSearch = {
        clear: clearSearch,
        perform: function (term) {
            searchInput.value = term;
            updateClearButtonVisibility();
            performSearch(term);
        },
        isSearching: function () {
            return isSearching;
        }
    };
});
