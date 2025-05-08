class TransporterSearch {
    constructor() {
        this.initElements();
        this.initEvents();
        this.initializeExistingFilters();
    }

    initElements() {
        this.keywordInput = document.getElementById('transporterKeywordSearch');
        this.zoneFilter = document.getElementById('transporterZoneFilter');
        this.dateFilter = document.getElementById('transporterDateFilter');
        this.clearButton = document.getElementById('transporterClearFilters');
        this.announcementList = document.getElementById('transporterAnnouncementsList');
    }

    initEvents() {
        // Recherche lors de la saisie (avec debounce)
        this.keywordInput?.addEventListener('input', this.debounce(() => this.performSearch(), 300));
        
        // Recherche lors du changement des filtres
        this.zoneFilter?.addEventListener('change', () => this.performSearch());
        this.dateFilter?.addEventListener('change', () => this.performSearch());
        
        // Réinitialisation des filtres
        this.clearButton?.addEventListener('click', (e) => {
            e.preventDefault();
            this.clearFilters();
        });
    }

    initializeExistingFilters() {
        // Récupérer les paramètres de l'URL
        const params = new URLSearchParams(window.location.search);
        
        // Initialiser les filtres avec les valeurs de l'URL
        if (params.has('keyword')) this.keywordInput.value = params.get('keyword');
        if (params.has('zone')) this.zoneFilter.value = params.get('zone');
        if (params.has('date')) this.dateFilter.value = params.get('date');
    }

    async performSearch() {
        const params = new URLSearchParams();
        
        if (this.keywordInput?.value) params.append('keyword', this.keywordInput.value);
        if (this.zoneFilter?.value) params.append('zone', this.zoneFilter.value);
        if (this.dateFilter?.value) params.append('date', this.dateFilter.value);
        
        try {
            this.showLoading();
            
            const response = await fetch(`/transporter/announcements/search?${params.toString()}`, {
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.html) {
                this.announcementList.innerHTML = data.html;
                this.reattachEventListeners();
            }
            
            // Mettre à jour l'URL sans recharger la page
            window.history.pushState({}, '', `${window.location.pathname}?${params.toString()}`);
            
        } catch (error) {
            console.error('Search error:', error);
            this.showError('Failed to load announcements. Please try again.');
        }
    }

    reattachEventListeners() {
        // Réattacher les écouteurs d'événements pour les boutons d'action
        const detailButtons = document.querySelectorAll('.btn-details');
        const editButtons = document.querySelectorAll('.btn-edit');
        const deleteButtons = document.querySelectorAll('.btn-delete');

        detailButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                window.showAnnouncementDetails(id);
            });
        });

        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                window.editAnnouncement(id);
            });
        });

        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const title = this.getAttribute('data-title');
                const token = this.getAttribute('data-csrf');
                window.deleteAnnouncement(id, title, token);
            });
        });
    }

    clearFilters() {
        if (this.keywordInput) this.keywordInput.value = '';
        if (this.zoneFilter) this.zoneFilter.value = '';
        if (this.dateFilter) this.dateFilter.value = '';
        this.performSearch();
    }

    showLoading() {
        if (!this.announcementList) return;
        
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
        if (!this.announcementList) return;
        
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
    new TransporterSearch();
});