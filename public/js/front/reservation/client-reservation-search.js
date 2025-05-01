class ClientReservationSearch {
    constructor() {
        this.initElements();
        this.initEvents();
        this.initState(); // Assurez-vous que le nom correspond exactement
    }

    initElements() {
        this.elements = {
            keyword: document.getElementById('reservationKeywordSearch'),
            status: document.getElementById('reservationStatusFilter'),
            date: document.getElementById('reservationDateFilter'),
            clearBtn: document.getElementById('reservationClearFilters'),
            listContainer: document.getElementById('reservationsList')
        };
    }

    initEvents() {
        const debouncedSearch = this.debounce(() => this.performSearch(), 300);
        this.elements.keyword?.addEventListener('input', debouncedSearch);
        this.elements.status?.addEventListener('change', debouncedSearch);
        this.elements.date?.addEventListener('change', debouncedSearch);
        this.elements.clearBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            this.clearFilters();
        });
    }

    initState() {
        const params = new URLSearchParams(window.location.search);
        this.elements.keyword.value = params.get('keyword') || '';
        this.elements.status.value = params.get('status') || '';
        this.elements.date.value = params.get('date') || '';
    }

    async performSearch() {
        const params = new URLSearchParams();
        
        if (this.elements.keyword?.value) params.append('keyword', this.elements.keyword.value);
        if (this.elements.status?.value) params.append('status', this.elements.status.value);
        if (this.elements.date?.value) params.append('date', this.elements.date.value);
        
        try {
            this.showLoading();
            
            const response = await fetch(`/client/reservations?${params.toString()}`, {
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            });
            
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            
            const html = await response.text();
            this.elements.listContainer.innerHTML = html;
            
            // Mise à jour URL sans rechargement
            window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
            
        } catch (error) {
            console.error('Search error:', error);
            this.showError();
        }
    }

    showLoading() {
        this.elements.listContainer.innerHTML = `
            <div class="loading-state text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading reservations...</p>
            </div>
        `;
    }

    showError() {
        this.elements.listContainer.innerHTML = `
            <div class="alert alert-danger m-3">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Failed to load reservations. Please try again.
            </div>
        `;
    }

    clearFilters() {
        this.elements.keyword.value = '';
        this.elements.status.value = '';
        this.elements.date.value = '';
        this.performSearch();
    }

    debounce(func, wait) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }
}

// Initialisation conditionnelle
if (document.getElementById('reservationsList')) {
    document.addEventListener('DOMContentLoaded', () => {
        new ClientReservationSearch();
    });
}