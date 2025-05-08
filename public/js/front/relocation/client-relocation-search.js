class ClientRelocationSearch {
    constructor() {
        this.initElements();
        this.initEvents();
        this.initState();
    }

    initElements() {
        this.elements = {
            keyword: document.getElementById('relocationKeywordSearch'),
            date: document.getElementById('relocationDateFilter'),
            clearBtn: document.getElementById('relocationClearFilters'),
            listContainer: document.getElementById('relocationsList')
        };
    }

    initEvents() {
        const debouncedSearch = this.debounce(() => this.search(), 300);
        
        this.elements.keyword?.addEventListener('input', debouncedSearch);
        this.elements.date?.addEventListener('change', debouncedSearch);
        this.elements.clearBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            this.clearFilters();
        });
    }

    initState() {
        const params = new URLSearchParams(window.location.search);
        this.elements.keyword.value = params.get('keyword') || '';
        this.elements.date.value = params.get('date') || '';
    }

    async search() {
        const params = new URLSearchParams({
            keyword: this.elements.keyword.value,
            date: this.elements.date.value
        });

        try {
            this.showLoading();
            
            const response = await fetch(`/client/relocations?${params.toString()}`, {
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
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading relocations...</p>
            </div>
        `;
    }

    showError() {
        this.elements.listContainer.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Error loading relocations. Please try again.
            </div>
        `;
    }

    clearFilters() {
        this.elements.keyword.value = '';
        this.elements.date.value = '';
        this.search();
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
    new ClientRelocationSearch();
});