class AdminReservationSearch {
    constructor() {
        this.initElements();
        this.initEvents();
        this.initialLoad();
    }

    initElements() {
        this.keywordInput = document.getElementById('adminKeywordSearch');
        this.statusFilter = document.getElementById('adminStatusFilter');
        this.dateFilter = document.getElementById('adminDateFilter');
        this.clearButton = document.getElementById('adminClearFilters');
        this.listContainer = document.getElementById('reservations-list');
    }

    initEvents() {
        this.keywordInput.addEventListener('input', this.debounce(() => this.search(), 300));
        this.statusFilter.addEventListener('change', () => this.search());
        this.dateFilter.addEventListener('change', () => this.search());
        this.clearButton.addEventListener('click', () => this.clearFilters());
    }

    async search() {
        const params = new URLSearchParams({
            keyword: this.keywordInput.value,
            status: this.statusFilter.value,
            date: this.dateFilter.value
        });
    
        try {
            this.showLoading();
            
            const response = await fetch(`?${params.toString()}`, {
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache' // Ajouter un cache-control
                }
            });
    
            if (!response.ok) {
                const error = await response.text();
                throw new Error(`Erreur serveur: ${error}`);
            }
            
            const html = await response.text();
            this.listContainer.innerHTML = html;
            this.updateHistory(params);
    
        } catch (error) {
            console.error('Search failed:', error);
            this.showError();
        }
    }

    updateHistory(params) {
        const newUrl = `${window.location.pathname}?${params.toString()}`;
        window.history.replaceState({}, '', newUrl);
    }

    clearFilters() {
        this.keywordInput.value = '';
        this.statusFilter.value = '';
        this.dateFilter.value = '';
        this.search();
    }

    showLoading() {
        this.listContainer.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading relocations...</p>
            </div>
        `;
    }

    showError() {
        this.listContainer.innerHTML = `
            <div class="alert alert-danger mx-4 mt-4">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Error loading relocations. Please try again.
            </div>
        `;
    }

    initialLoad() {
        if (window.location.search.includes('keyword=')) {
            this.search();
        }
    }

    debounce(func, wait) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new AdminReservationSearch();
});