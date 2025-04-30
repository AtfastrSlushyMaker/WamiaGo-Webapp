class AdminAnnouncementSearch {
    constructor() {
        console.log('AdminAnnouncementSearch initialized');
        this.initElements();
        this.initEvents();
    }

    initElements() {
        this.keywordInput = document.getElementById('adminKeywordSearch');
        this.zoneFilter = document.getElementById('adminZoneFilter');
        this.dateFilter = document.getElementById('adminDateFilter');
        this.clearButton = document.getElementById('adminClearFilters');
        this.tableBody = document.querySelector('table tbody');
        this.paginationContainer = document.querySelector('.pagination-container');

        // Debug log elements
        console.log('Elements found:', {
            keywordInput: !!this.keywordInput,
            zoneFilter: !!this.zoneFilter,
            dateFilter: !!this.dateFilter,
            clearButton: !!this.clearButton,
            tableBody: !!this.tableBody,
            paginationContainer: !!this.paginationContainer
        });
    }

    initEvents() {
        if (this.keywordInput) {
            this.keywordInput.addEventListener('input', this.debounce(() => this.performSearch(), 300));
        }
        if (this.zoneFilter) {
            this.zoneFilter.addEventListener('change', () => this.performSearch());
        }
        if (this.dateFilter) {
            this.dateFilter.addEventListener('change', () => this.performSearch());
        }
        if (this.clearButton) {
            this.clearButton.addEventListener('click', () => this.clearFilters());
        }
    }

    async performSearch() {
        const params = new URLSearchParams();
        
        if (this.keywordInput?.value) params.append('keyword', this.keywordInput.value);
        if (this.zoneFilter?.value) params.append('zone', this.zoneFilter.value);
        if (this.dateFilter?.value) params.append('date', this.dateFilter.value);

        try {
            this.showLoading();
            
            const response = await fetch(`${window.location.pathname}?${params.toString()}`, {
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            
            this.tableBody.innerHTML = data.html;
            if (this.paginationContainer && data.pagination) {
                this.paginationContainer.innerHTML = data.pagination;
            }
            
            window.history.pushState({}, '', `${window.location.pathname}?${params.toString()}`);
            
        } catch (error) {
            console.error('Search error:', error);
            this.showError('Failed to load announcements. Please try again.');
        }
    }
    
    bindPaginationLinks() {
        const links = this.paginationContainer.querySelectorAll('a.page-link');
        links.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                window.history.pushState({}, '', e.target.href);
                this.performSearch();
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
        if (this.tableBody) {
            this.tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading announcements...</p>
                    </td>
                </tr>
            `;
        }
    }

    showError(message) {
        if (this.tableBody) {
            this.tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${message}
                        </div>
                    </td>
                </tr>
            `;
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

// Initialize on document load
document.addEventListener('DOMContentLoaded', () => {
    new AdminAnnouncementSearch();
});