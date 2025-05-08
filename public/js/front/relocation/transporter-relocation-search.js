class RelocationSearch {
    constructor() {
        this.initElements();
        this.initEvents();
        this.initState();
    }

    initElements() {
        this.elements = {
            keyword: document.getElementById('relocationKeywordSearch'),
            status: document.getElementById('relocationStatusFilter'),
            date: document.getElementById('relocationDateFilter'),
            clearBtn: document.getElementById('relocationClearFilters'),
            listContainer: document.getElementById('relocationsList')
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
        const params = new URLSearchParams({
            keyword: this.elements.keyword?.value || '',
            status: this.elements.status?.value || '',
            date: this.elements.date?.value || ''
        });

        try {
            this.showLoading();
            
            const response = await fetch(`/transporter/relocations?${params.toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) throw new Error(response.statusText);
            
            const html = await response.text();
            this.elements.listContainer.innerHTML = html;
            window.history.pushState({}, '', `?${params.toString()}`);

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
                <p class="mt-3 text-muted">Loading relocations...</p>
            </div>
        `;
    }

    showError() {
        this.elements.listContainer.innerHTML = `
            <div class="error-state text-center py-5">
                <i class="fas fa-exclamation-triangle fa-2x text-danger mb-3"></i>
                <h5 class="fw-medium">Error loading relocations</h5>
                <p class="text-muted">Please try again later</p>
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

document.addEventListener('DOMContentLoaded', () => {
    new RelocationSearch();
});