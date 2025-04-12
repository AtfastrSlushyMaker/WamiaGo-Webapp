
document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // Handle delete confirmation modal
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const modal = this;
            
            // Mise à jour du texte de confirmation
            const title = button.getAttribute('data-title');
            if (title) {
                const confirmationText = modal.querySelector('#delete-confirmation-text');
                if (confirmationText) {
                    confirmationText.innerHTML = 
                        'Are you sure you want to delete the announcement: <strong>"' + title + '"</strong>?';
                }
            }
            
            // Mise à jour du formulaire
            const form = modal.querySelector('#deleteForm');
            if (form) {
                // Utilisez data-delete-url si disponible, sinon construisez l'URL
                form.action = button.getAttribute('data-delete-url') || 
                             '/admin/announcements/' + button.getAttribute('data-id') + '/delete';
                
                const tokenInput = form.querySelector('input[name="_token"]');
                if (tokenInput) {
                    tokenInput.value = button.getAttribute('data-token');
                }
            }
        });
    }
    
    // Search functionality
    const searchInput = document.getElementById('announcement-search');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const value = this.value.toLowerCase();
            const rows = document.querySelectorAll('table tbody tr');
            let visibleRows = 0;
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const isVisible = text.includes(value);
                row.style.display = isVisible ? '' : 'none';
                if (isVisible) visibleRows++;
            });
            
            // Show empty state if no results
            const tbody = document.querySelector('table tbody');
            let noResultsRow = document.getElementById('no-results-row');
            
            if (visibleRows === 0 && !noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.id = 'no-results-row';
                noResultsRow.innerHTML = `
                    <td colspan="7" class="text-center py-4">
                        <img src="/adminlte/images/search-empty.svg" alt="No results" width="120" class="mb-3">
                        <h5 class="text-muted">No announcements found matching your search</h5>
                    </td>
                `;
                tbody.appendChild(noResultsRow);
            } else if (visibleRows > 0 && noResultsRow) {
                noResultsRow.remove();
            }
        });
    }
    
    // Add fade-in animation to table rows
    document.querySelectorAll('table tbody tr').forEach(row => {
        row.classList.add('fade-in');
    });
    
    // Initialize tooltips (Bootstrap 5+)
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Fix for rendering issue
    setTimeout(function() {
        document.querySelectorAll('.card').forEach(card => {
            card.classList.add('rendered');
        });
    }, 100);
});