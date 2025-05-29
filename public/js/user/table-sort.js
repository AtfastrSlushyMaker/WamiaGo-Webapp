/**
 * Table Sorting Functionality for WamiaGo User Management
 * 
 * This script adds the ability to sort table columns by clicking on headers
 * An arrow indicator will show the current sorting direction
 */
document.addEventListener('DOMContentLoaded', function () {
    // Add sort icons and classes to table headers
    initializeSortableTable();

    // Handle the click events for sortable headers
    document.querySelectorAll('#users-table th.sortable').forEach(header => {
        header.addEventListener('click', function () {
            sortTable(this);
        });
    });
});

/**
 * Initialize the sortable table - add classes and icons if needed
 */
function initializeSortableTable() {
    // Add sortable class and icons to appropriate table headers if not already present
    const tableHeaders = document.querySelectorAll('#users-table th:not(:first-child):not(:last-child)');

    tableHeaders.forEach(header => {
        // Skip the checkbox column and actions column
        if (!header.querySelector('input[type="checkbox"]') && !header.classList.contains('actions')) {
            if (!header.classList.contains('sortable')) {
                header.classList.add('sortable');
                header.setAttribute('data-sort', header.textContent.trim().toLowerCase().replace(/\s+/g, '-'));
            }

            if (!header.querySelector('.sort-icon')) {
                const sortIcon = document.createElement('i');
                sortIcon.className = 'fas fa-sort sort-icon';
                header.appendChild(document.createTextNode(' '));
                header.appendChild(sortIcon);
            }
        }
    });
}

/**
 * Sort the table based on the clicked header
 * @param {HTMLElement} header - The table header element that was clicked
 */
function sortTable(header) {
    const table = document.getElementById('users-table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const columnIndex = Array.from(header.parentNode.children).indexOf(header);
    const sortDirection = header.classList.contains('asc') ? 'desc' : 'asc';

    // Reset all headers
    table.querySelectorAll('th').forEach(th => {
        th.classList.remove('asc', 'desc');
    });

    // Set the current sort direction
    header.classList.add(sortDirection);

    // Sort the rows
    rows.sort((rowA, rowB) => {
        let cellA = rowA.querySelectorAll('td')[columnIndex];
        let cellB = rowB.querySelectorAll('td')[columnIndex];

        // Skip the sort if we don't have both cells
        if (!cellA || !cellB) return 0;

        // Extract text content, handling special cases
        let valueA = getCellSortValue(cellA);
        let valueB = getCellSortValue(cellB);

        // Compare based on the type of data
        if (!isNaN(valueA) && !isNaN(valueB)) {
            // Numbers
            return sortDirection === 'asc'
                ? Number(valueA) - Number(valueB)
                : Number(valueB) - Number(valueA);
        } else if (isDate(valueA) && isDate(valueB)) {
            // Dates
            const dateA = new Date(valueA);
            const dateB = new Date(valueB);
            return sortDirection === 'asc'
                ? dateA - dateB
                : dateB - dateA;
        } else {
            // Strings, default
            return sortDirection === 'asc'
                ? String(valueA).localeCompare(String(valueB))
                : String(valueB).localeCompare(String(valueA));
        }
    });

    // Remove all rows and re-append them in the new order
    rows.forEach(row => tbody.appendChild(row));

    // Show visual feedback
    flashTableHighlight();

    // Update pagination if needed
    if (typeof updatePagination === 'function') {
        updatePagination();
    }
}

/**
 * Extract the appropriate value from a cell for sorting
 * @param {HTMLElement} cell - The table cell element
 * @returns {string|number} - The value to use for sorting
 */
function getCellSortValue(cell) {
    // Check for badges with status info
    const badge = cell.querySelector('.status-badge, .role-badge');
    if (badge) {
        return badge.textContent.trim();
    }

    // Check for verification status
    const verification = cell.querySelector('.verification-indicator');
    if (verification) {
        return verification.classList.contains('verified') ? 'Verified' : 'Not Verified';
    }

    // For user cells, try to get the name
    const userName = cell.querySelector('.user-name');
    if (userName) {
        return userName.textContent.trim();
    }

    // Default to cell text content
    return cell.textContent.trim();
}

/**
 * Check if a string appears to be a date
 * @param {string} str - The string to check
 * @returns {boolean} - True if the string appears to be a date
 */
function isDate(str) {
    // Basic date check - this could be enhanced based on your date formats
    return /^\d{2}[./-]\d{2}[./-]\d{4}$/.test(str) ||
        /^\d{4}[./-]\d{2}[./-]\d{2}$/.test(str) ||
        !isNaN(Date.parse(str));
}

/**
 * Add a brief highlight effect to show the table has been sorted
 */
function flashTableHighlight() {
    const tbody = document.querySelector('#users-table tbody');
    tbody.style.transition = 'background-color 0.5s ease';
    tbody.style.backgroundColor = 'rgba(63, 106, 216, 0.05)';

    setTimeout(() => {
        tbody.style.backgroundColor = 'transparent';
        setTimeout(() => {
            tbody.style.transition = '';
        }, 500);
    }, 500);
}
