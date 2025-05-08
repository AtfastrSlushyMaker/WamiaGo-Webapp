class TransporterReservationSearch {
    constructor() {
        this.initElements();
        this.initEvents();
        this.initState();
    }

    initElements() {
        this.keywordInput = document.getElementById('reservationKeywordSearch');
        this.statusFilter = document.getElementById('reservationStatusFilter');
        this.dateFilter = document.getElementById('reservationDateFilter');
        this.clearButton = document.getElementById('reservationClearFilters');
        // The container is the entire reservations-grid, not an element called reservationsList
        this.reservationsContainer = document.querySelector('.reservations-grid');
        
        // Create a fallback empty state container if needed
        if (!this.emptyStateTemplate) {
            const emptyStateDiv = document.createElement('div');
            emptyStateDiv.className = 'empty-state';
            emptyStateDiv.innerHTML = `
                <img src="/images/front/reservation/empty-state.png" alt="No reservations" class="mb-3">
                <h3>No reservations found</h3>
                <p class="text-muted">Try adjusting your filters to see more results</p>
            `;
            this.emptyStateTemplate = emptyStateDiv;
        }
    }

    initEvents() {
        // Recherche avec debounce
        const debouncedSearch = this.debounce(() => this.performSearch(), 300);
        this.keywordInput?.addEventListener('input', debouncedSearch);
        this.statusFilter?.addEventListener('change', () => this.performSearch());
        this.dateFilter?.addEventListener('change', () => this.performSearch());
        
        // Réinitialisation
        this.clearButton?.addEventListener('click', (e) => {
            e.preventDefault();
            this.clearFilters();
        });
    }

    initState() {
        // Initialisation depuis l'URL
        const params = new URLSearchParams(window.location.search);
        if (this.keywordInput) this.keywordInput.value = params.get('keyword') || '';
        if (this.statusFilter) this.statusFilter.value = params.get('status') || '';
        if (this.dateFilter) this.dateFilter.value = params.get('date') || '';
    }

    async performSearch() {
        const params = new URLSearchParams();
        
        if (this.keywordInput?.value) params.append('keyword', this.keywordInput.value);
        if (this.statusFilter?.value) params.append('status', this.statusFilter.value);
        if (this.dateFilter?.value) params.append('date', this.dateFilter.value);
        
        try {
            this.showLoading();
            
            const response = await fetch(`/transporter/reservations/?${params.toString()}`, {
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            });
            
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            
            const data = await response.json();
            
            if (data.html) {
                // If the container exists, update it
                if (this.reservationsContainer) {
                    this.reservationsContainer.innerHTML = data.html;
                } else {
                    console.error('Reservation container not found');
                    return;
                }
            } else {
                throw new Error('No HTML content received');
            }
            
            // Update browser URL without reloading
            window.history.pushState({}, '', `${window.location.pathname}?${params.toString()}`);
            
            // Reattach event listeners to the new elements
            this.reattachEventListeners();
            
        } catch (error) {
            console.error('Search error:', error);
            this.showError('Failed to load reservations. Please try again.');
        }
    }

    getFallbackContent() {
        return `
            <div class="empty-state">
                <img src="/images/front/reservation/empty-state.png" alt="No reservations" class="mb-3">
                <h3>No reservations found</h3>
                <p class="text-muted">Try adjusting your filters to see more results</p>
            </div>
        `;
    }

    reattachEventListeners() {
        // Réattacher les écouteurs d'événements pour les boutons d'action
        const detailButtons = document.querySelectorAll('.btn-details');
        const acceptButtons = document.querySelectorAll('.btn-accept');
        const refuseButtons = document.querySelectorAll('.btn-refuse');

        detailButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                // Make sure this function exists in your transporter-reservation.js
                if (typeof window.showReservationDetails === 'function') {
                    window.showReservationDetails(id);
                }
            });
        });

        acceptButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                // Make sure this function exists in your transporter-reservation.js
                if (typeof window.acceptReservation === 'function') {
                    window.acceptReservation(id);
                }
            });
        });

        refuseButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                // Make sure this function exists in your transporter-reservation.js
                if (typeof window.refuseReservation === 'function') {
                    window.refuseReservation(id);
                }  
            });
        });
    }

    clearFilters() {
        if (this.keywordInput) this.keywordInput.value = '';
        if (this.statusFilter) this.statusFilter.value = '';
        if (this.dateFilter) this.dateFilter.value = '';
        this.performSearch();
    }

    showLoading() {
        if (!this.reservationsContainer) return;
        
        this.reservationsContainer.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading reservations...</p>
            </div>
        `;
    }

    showError(message) {
        if (!this.reservationsContainer) return;
        
        this.reservationsContainer.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${message}
            </div>
            ${this.getFallbackContent()}
        `;
    }

    debounce(func, wait) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                func.apply(context, args);
            }, wait);
        };
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new TransporterReservationSearch();
});