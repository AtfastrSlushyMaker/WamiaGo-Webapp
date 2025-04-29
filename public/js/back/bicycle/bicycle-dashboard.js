/**
 * Bicycle Management Dashboard JavaScript
 * WamiaGo Web App
 */

class BicycleDashboard {
    constructor() {
        this.init();
    }

    init() {
        this.initTabs();
        this.initFilters();
        this.initAnimations();
        this.initDataTables();
        this.initCharts();
        this.setupEventHandlers();
        console.log('Bicycle Dashboard initialized');
    }

    initTabs() {
        const tabLinks = document.querySelectorAll('.bicycle-tabs .tab-link');

        tabLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();

                // Get the tab name from data attribute
                const tabName = link.dataset.tab;

                // Update URL without reloading the page
                const url = new URL(window.location);
                url.searchParams.set('tab', tabName);
                window.history.pushState({}, '', url);

                // Activate the tab
                this.activateTab(tabName);
            });
        });

        // Handle browser back/forward navigation
        window.addEventListener('popstate', () => {
            const url = new URL(window.location);
            const tabName = url.searchParams.get('tab') || 'rentals';
            this.activateTab(tabName);
        });

        // Initialize with the current tab from URL
        const url = new URL(window.location);
        const currentTab = url.searchParams.get('tab') || 'rentals';
        this.activateTab(currentTab);
    }

    activateTab(tabName) {
        // Remove active class from all tabs and content
        const allTabs = document.querySelectorAll('.bicycle-tabs .tab-link');
        const allContents = document.querySelectorAll('.bicycle-tab-content .tab-pane');

        allTabs.forEach(tab => tab.classList.remove('active'));
        allContents.forEach(content => {
            content.classList.remove('show', 'active');
            content.classList.add('fade');
        });

        // Add active class to the selected tab and content
        const activeTab = document.querySelector(`.bicycle-tabs .tab-link[data-tab="${tabName}"]`);
        const activeContent = document.querySelector(`#${tabName}Tab`);

        if (activeTab && activeContent) {
            activeTab.classList.add('active');
            activeContent.classList.add('show', 'active');

            // Trigger animation
            this.animateTabContent(activeContent);
        }
    }

    animateTabContent(element) {
        // Remove previous animation classes
        element.classList.remove('fade-in-up');

        // Force a reflow to restart animation
        void element.offsetWidth;

        // Add animation class
        element.classList.add('fade-in-up');
    }

    initFilters() {
        const statusFilter = document.getElementById('statusFilter');
        const stationFilter = document.getElementById('stationFilter');
        const dateFromFilter = document.getElementById('dateFromFilter');
        const dateToFilter = document.getElementById('dateToFilter');
        const filterForm = document.getElementById('filterForm');

        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();

                const url = new URL(window.location);

                // Update URL with filter values
                if (statusFilter && statusFilter.value) {
                    url.searchParams.set('status', statusFilter.value);
                } else {
                    url.searchParams.delete('status');
                }

                if (stationFilter && stationFilter.value) {
                    url.searchParams.set('station', stationFilter.value);
                } else {
                    url.searchParams.delete('station');
                }

                if (dateFromFilter && dateFromFilter.value) {
                    url.searchParams.set('dateFrom', dateFromFilter.value);
                } else {
                    url.searchParams.delete('dateFrom');
                }

                if (dateToFilter && dateToFilter.value) {
                    url.searchParams.set('dateTo', dateToFilter.value);
                } else {
                    url.searchParams.delete('dateTo');
                }

                // Keep the current page and tab
                window.location.href = url.toString();
            });
        }

        // Reset filters button
        const resetFiltersBtn = document.getElementById('resetFiltersBtn');
        if (resetFiltersBtn) {
            resetFiltersBtn.addEventListener('click', () => {
                const url = new URL(window.location);
                const tab = url.searchParams.get('tab');

                // Clear all filters but keep the tab
                url.search = '';
                if (tab) {
                    url.searchParams.set('tab', tab);
                }

                window.location.href = url.toString();
            });
        }
    }

    initAnimations() {
        // Add intersection observer to animate elements when they come into view
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1
        });

        // Observe all stat cards and tables
        document.querySelectorAll('.stat-card, .bicycle-table').forEach(el => {
            observer.observe(el);
        });
    }

    initDataTables() {
        // Initialize DataTables if available
        if (typeof $.fn.DataTable !== 'undefined') {
            $('.bicycle-datatable').each(function () {
                $(this).DataTable({
                    responsive: true,
                    language: {
                        search: "",
                        searchPlaceholder: "Search...",
                        lengthMenu: "_MENU_ records per page",
                    },
                    dom: '<"d-flex justify-content-between align-items-center mb-3"<"d-flex align-items-center"l><"d-flex"f>>rt<"d-flex justify-content-between align-items-center mt-3"<"text-muted"i><"pagination-container"p>>',
                });
            });
        }
    }

    initCharts() {
        // Initialize charts if available
        if (typeof Chart !== 'undefined') {
            this.initStatusChart();
            this.initRentalChart();
        }
    }

    initStatusChart() {
        const statusChartEl = document.getElementById('bicycleStatusChart');
        if (!statusChartEl) return;

        const availableCount = parseInt(statusChartEl.getAttribute('data-available') || 0);
        const inUseCount = parseInt(statusChartEl.getAttribute('data-in-use') || 0);
        const maintenanceCount = parseInt(statusChartEl.getAttribute('data-maintenance') || 0);
        const chargingCount = parseInt(statusChartEl.getAttribute('data-charging') || 0);

        new Chart(statusChartEl, {
            type: 'doughnut',
            data: {
                labels: ['Available', 'In Use', 'Maintenance', 'Charging'],
                datasets: [{
                    data: [availableCount, inUseCount, maintenanceCount, chargingCount],
                    backgroundColor: ['#28a745', '#0d6efd', '#ffc107', '#6c757d'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const label = context.label || '';
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '70%',
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });
    }

    initRentalChart() {
        const rentalChartEl = document.getElementById('rentalActivityChart');
        if (!rentalChartEl) return;

        // Get rental data from data attributes
        const labels = JSON.parse(rentalChartEl.getAttribute('data-labels') || '[]');
        const completed = JSON.parse(rentalChartEl.getAttribute('data-completed') || '[]');
        const active = JSON.parse(rentalChartEl.getAttribute('data-active') || '[]');
        const reserved = JSON.parse(rentalChartEl.getAttribute('data-reserved') || '[]');

        new Chart(rentalChartEl, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Completed',
                        data: completed,
                        backgroundColor: '#28a745',
                        borderWidth: 0
                    },
                    {
                        label: 'Active',
                        data: active,
                        backgroundColor: '#0d6efd',
                        borderWidth: 0
                    },
                    {
                        label: 'Reserved',
                        data: reserved,
                        backgroundColor: '#6c757d',
                        borderWidth: 0
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: true,
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                animation: {
                    duration: 1500
                }
            }
        });
    }

    setupEventHandlers() {
        // Handle status change buttons
        document.querySelectorAll('.change-status-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const bicycleId = btn.getAttribute('data-bicycle-id');
                const status = btn.getAttribute('data-status');

                if (bicycleId && status) {
                    this.changeBicycleStatus(bicycleId, status);
                }
            });
        });

        // Handle edit bicycle buttons
        document.querySelectorAll('.edit-bicycle-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const bicycleId = btn.getAttribute('data-bicycle-id');
                if (bicycleId) {
                    this.loadBicycleData(bicycleId);
                }
            });
        });

        // Handle delete confirmation
        document.querySelectorAll('.delete-confirm-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = btn.getAttribute('data-target-id');
                const targetName = btn.getAttribute('data-target-name');

                if (confirm(`Are you sure you want to delete ${targetName}?`)) {
                    document.getElementById(targetId).submit();
                }
            });
        });
    }

    changeBicycleStatus(bicycleId, status) {
        // Submit the form to change bicycle status
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/admin/bicycle/bicycle/change-status';
        form.style.display = 'none';

        const bicycleIdInput = document.createElement('input');
        bicycleIdInput.type = 'hidden';
        bicycleIdInput.name = 'bicycleId';
        bicycleIdInput.value = bicycleId;

        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = status;

        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken.content;
            form.appendChild(csrfInput);
        }

        form.appendChild(bicycleIdInput);
        form.appendChild(statusInput);
        document.body.appendChild(form);
        form.submit();
    }

    loadBicycleData(bicycleId) {
        // Fetch bicycle data and populate edit form
        fetch(`/admin/bicycle/bicycle/${bicycleId}/data`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const editForm = document.getElementById('editBicycleForm');
                    if (editForm) {
                        // Set form hidden id field
                        const idField = editForm.querySelector('input[name="idBike"]');
                        if (idField) idField.value = data.idBike;

                        // Set status select
                        const statusSelect = editForm.querySelector('select[name="status"]');
                        if (statusSelect) statusSelect.value = data.status;

                        // Set battery level
                        const batteryField = editForm.querySelector('input[name="batteryLevel"]');
                        if (batteryField) batteryField.value = data.batteryLevel;

                        // Set range
                        const rangeField = editForm.querySelector('input[name="rangeKm"]');
                        if (rangeField) rangeField.value = data.rangeKm;

                        // Set station select
                        const stationSelect = editForm.querySelector('select[name="bicycleStation"]');
                        if (stationSelect && data.stationId) stationSelect.value = data.stationId;

                        // Show the edit form modal
                        const editModal = new bootstrap.Modal(document.getElementById('editBicycleModal'));
                        editModal.show();
                    }
                } else {
                    console.error('Error loading bicycle data:', data.error);
                    alert('Error loading bicycle data. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error fetching bicycle data:', error);
                alert('Error fetching bicycle data. Please try again.');
            });
    }
}

// Initialize the dashboard when DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    new BicycleDashboard();
});