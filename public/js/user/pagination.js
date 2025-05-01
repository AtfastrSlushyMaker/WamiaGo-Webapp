/**
 * Pagination Module
 * Handles pagination functionality for the user table
 */
(function() {
    function createPagination(currentPage, totalPages) {
        console.log('Creating pagination:', { currentPage, totalPages });
        const paginationContainer = document.querySelector('.pagination');
        if (!paginationContainer) {
            console.error('Pagination container not found');
            return;
        }

        let paginationHtml = `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        `;

        for (let i = 1; i <= totalPages; i++) {
            paginationHtml += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
        }

        paginationHtml += `
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        `;

        paginationContainer.innerHTML = paginationHtml;
        setupPaginationHandlers();
    }

    function setupPaginationHandlers() {
        const paginationLinks = document.querySelectorAll('.pagination .page-link');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.dataset.page);
                if (!isNaN(page) && typeof window.loadUsers === 'function') {
                    window.loadUsers(page);
                }
            });
        });
    }

    // Initialize when the DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Pagination module initialized');
        setupPaginationHandlers();
        
        // Make createPagination function available globally
        window.createPagination = createPagination;
    });
})(); 