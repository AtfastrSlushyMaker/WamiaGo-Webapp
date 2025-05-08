class AnnouncementSearch {
    constructor() {
        this.initElements();
        this.initEvents();
    }

    initElements() {
        this.keywordInput = document.getElementById('keywordSearch');
        this.zoneFilter = document.getElementById('zoneFilter');
        this.dateFilter = document.getElementById('dateFilter');
        this.searchButton = document.getElementById('searchButton');
        this.clearButton = document.getElementById('clearFilters');
        this.announcementList = document.querySelector('.announcement-list');
    }

    initEvents() {
        // Recherche lors de la saisie (avec debounce)
        this.keywordInput.addEventListener('input', this.debounce(() => this.performSearch(), 300));
        
        // Recherche lors du changement des filtres
        this.zoneFilter.addEventListener('change', () => this.performSearch());
        this.dateFilter.addEventListener('change', () => this.performSearch());
        
        // Recherche explicite
        this.searchButton.addEventListener('click', () => this.performSearch());
        
        // Réinitialisation
        this.clearButton.addEventListener('click', () => this.clearFilters());
    }

    async performSearch() {
        const params = new URLSearchParams();
        
        if (this.keywordInput.value) params.append('keyword', this.keywordInput.value);
        if (this.zoneFilter.value) params.append('zone', this.zoneFilter.value);
        if (this.dateFilter.value) params.append('date', this.dateFilter.value);
        
        try {
            // Afficher un indicateur de chargement
            this.showLoading();
            
            const response = await fetch(`/announcements/search?${params.toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            this.announcementList.innerHTML = data.html;
            
            // Mettre à jour l'URL
            window.history.pushState({}, '', `${window.location.pathname}?${params.toString()}`);
            
        } catch (error) {
            console.error('Search error:', error);
            this.showError('Failed to load announcements. Please try again.');
        }
    }

    clearFilters() {
        this.keywordInput.value = '';
        this.zoneFilter.value = '';
        this.dateFilter.value = '';
        this.performSearch();
    }

    showLoading() {
        this.announcementList.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading announcements...</p>
            </div>
        `;
    }

    showError(message) {
        this.announcementList.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${message}
            </div>
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

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    new AnnouncementSearch();
});