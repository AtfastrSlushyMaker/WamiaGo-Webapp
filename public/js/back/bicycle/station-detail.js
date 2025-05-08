/**
 * WamiaGo - Station Detail Interactive Features
 * Enhanced mapping, charts and interactive components
 */

document.addEventListener('DOMContentLoaded', function () {
    console.log("DOM loaded, initializing station detail page...");

    // Debug Bootstrap availability
    if (typeof bootstrap === 'undefined') {
        console.error("CRITICAL ERROR: Bootstrap is not defined! This will break all dropdowns.");
    } else {
        console.log("Bootstrap is loaded:", bootstrap);
        console.log("Bootstrap Dropdown available:", typeof bootstrap.Dropdown);
    }

    // Check dropdown elements
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    console.log(`Found ${dropdownToggles.length} dropdown toggles:`, dropdownToggles);

    // Initialize Bootstrap components
    initializeBootstrapComponents();

    // Set up functionality
    setupQRCodeGeneration();
    setupExportData();

    // Initialize map with a slight delay to ensure DOM is fully ready
    setTimeout(() => {
        initStationMap();
    }, 100);

    initBicycleTypeSelector();
    initDeleteConfirmation();
    setupTableSearch();
    setupTabPersistence();
    setupRefreshActions();
    setupCopyStationInfo();
    addCardHoverEffects();

    // Setup popup edit buttons using the function from station-edit-modal.js
    if (typeof window.setupPopupEditButtons === 'function') {
        window.setupPopupEditButtons();
    } else {
        // Fallback: Add event listener directly if the function isn't available
        setupPopupEditButtonsDirectly();
    }
});

/**
 * Initialize Bootstrap components including dropdowns
 */
function initializeBootstrapComponents() {
    console.log("Initializing Bootstrap components with direct approach");

    // Initialize tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(tooltip => {
        try {
            // Removed unused object instantiation
        } catch (e) {
            console.error("Tooltip error:", e);
        }
    });

    // Handle dropdowns with direct DOM manipulation as fallback
    document.querySelectorAll('.dropdown-toggle').forEach(toggleBtn => {
        // Remove any existing event listeners
        const newBtn = toggleBtn.cloneNode(true);
        toggleBtn.parentNode.replaceChild(newBtn, toggleBtn);

        // Add click handler
        newBtn.addEventListener('click', function (event) {
            event.stopPropagation();

            const dropdownMenu = this.nextElementSibling;
            if (!dropdownMenu?.classList.contains('dropdown-menu')) {
                console.error("No dropdown menu found:", this);
                return;
            }

            // Close all other open dropdowns
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                if (menu !== dropdownMenu) {
                    menu.classList.remove('show');
                    menu.parentElement.querySelector('.dropdown-toggle').setAttribute('aria-expanded', 'false');
                }
            });

            // Toggle this dropdown
            const isOpen = dropdownMenu.classList.contains('show');
            dropdownMenu.classList.toggle('show');
            this.setAttribute('aria-expanded', !isOpen);

            console.log("Dropdown toggled:", dropdownMenu, "Is now open:", !isOpen);

            // Position the dropdown
            if (!isOpen) {
                const isInTable = this.closest('td') !== null;

                if (isInTable || this.closest('.action-buttons')) {
                    // Align to the right for table and action dropdowns
                    dropdownMenu.style.right = '0';
                    dropdownMenu.style.left = 'auto';
                } else {
                    // Position dropdown below toggle for all other dropdowns
                    dropdownMenu.style.left = '0';
                    dropdownMenu.style.right = 'auto';
                }
            }
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function (event) {
        if (!event.target.closest('.dropdown')) {
            const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
            openDropdowns.forEach(menu => {
                menu.classList.remove('show');
                const toggle = menu.parentElement.querySelector('.dropdown-toggle');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });

    // Fix for confirm delete checkbox
    const confirmDeleteCheckbox = document.getElementById('confirmDelete');
    const deleteStationBtn = document.getElementById('deleteStationBtn');

    if (confirmDeleteCheckbox && deleteStationBtn) {
        confirmDeleteCheckbox.addEventListener('change', function () {
            deleteStationBtn.disabled = !this.checked;
        });
    }
}

// Fix for confirm delete checkbox
const confirmDeleteCheckbox = document.getElementById('confirmDelete');
const deleteStationBtn = document.getElementById('deleteStationBtn');

if (confirmDeleteCheckbox && deleteStationBtn) {
    confirmDeleteCheckbox.addEventListener('change', function () {
        deleteStationBtn.disabled = !this.checked;
    });
}

// Re-initialize components when tab content changes
const tabElements = document.querySelectorAll('button[data-bs-toggle="tab"]');
tabElements.forEach(function (tabElement) {
    tabElement.addEventListener('shown.bs.tab', function () {
        // Reinitialize tooltips in newly visible tab
        const activeTabTooltips = document.querySelectorAll('.tab-pane.active [data-bs-toggle="tooltip"]');
        activeTabTooltips.forEach(function (tooltip) {
            // Removed unused object instantiation
        });
    });
});

/**
 * Setup export data functionality
 */
function setupExportData() {
    const exportBtn = document.querySelector('#exportDataModal .btn-primary');
    if (!exportBtn) return;

    exportBtn.addEventListener('click', function () {
        // Show loading state
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="ti ti-loader ti-spin me-1"></i> Processing...';
        this.disabled = true;

        // Simulate export process
        setTimeout(() => {
            this.innerHTML = originalText;
            this.disabled = false;

            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('exportDataModal')).hide();

            // Show success message
            alert('Station data exported successfully! The file will download shortly.');

            // Simulate file download
            const dummyLink = document.createElement('a');
            dummyLink.href = 'data:text/csv;charset=utf-8,Station data export';
            dummyLink.download = `WamiaGo_Station_${document.querySelector('.breadcrumb-item.active').textContent.trim()}_${new Date().toISOString().slice(0, 10)}.csv`;
            document.body.appendChild(dummyLink);
            dummyLink.click();
            document.body.removeChild(dummyLink);
        }, 1500);
    });
}

/**
 * Initialize the interactive station map
 */
function initStationMap() {
    console.log("Initializing station map with original implementation");

    // Wait until DOM is fully ready
    if (typeof L === 'undefined') {
        console.error("Leaflet library not loaded!");
        setTimeout(initStationMap, 300);
        return;
    }

    const mapElement = document.getElementById('stationMap');
    if (!mapElement) {
        console.error("Map element not found");
        return;
    }

    try {
        // Get station coordinates from data attributes
        const stationLat = parseFloat(mapElement.getAttribute('data-lat') || 0);
        const stationLng = parseFloat(mapElement.getAttribute('data-lng') || 0);

        // Debug coordinates
        console.log("Raw coordinates from data attributes:",
            mapElement.getAttribute('data-lat'),
            mapElement.getAttribute('data-lng')
        );
        console.log("Parsed coordinates:", stationLat, stationLng);

        // Validate coordinates
        if (isNaN(stationLat) || isNaN(stationLng) || (stationLat === 0 && stationLng === 0)) {
            console.error('Invalid station coordinates:', stationLat, stationLng);
            mapElement.innerHTML = `
                <div class="alert alert-warning m-3">
                    <i class="ti ti-map-off me-2"></i>
                    Invalid location coordinates. Please update the station location.
                </div>
            `;
            return;
        }

        // Make sure the container has dimensions
        mapElement.style.height = '400px';
        mapElement.style.width = '100%';

        // Clear any existing content
        mapElement.innerHTML = '';

        console.log("Creating map at", stationLat, stationLng);

        // Create the map with ZERO options to prevent issues
        const map = L.map('stationMap').setView([stationLat, stationLng], 15);

        // Add the most basic tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Add a simple marker - no custom icons or extras that might cause problems
        const marker = L.marker([stationLat, stationLng]).addTo(map);

        // Make sure the map renders properly MULTIPLE TIMES
        setTimeout(() => {
            map.invalidateSize();
            console.log("Map invalidateSize called (first time)");

            // Do it again for good measure
            setTimeout(() => {
                map.invalidateSize();
                console.log("Map invalidateSize called (second time)");
            }, 300);
        }, 300);

        console.log("Map initialization completed!");
    } catch (error) {
        console.error('Error initializing map:', error);
        mapElement.innerHTML = `
            <div class="alert alert-danger m-3">
                <i class="ti ti-exclamation-triangle me-2"></i>
                Error loading the map: ${error.message}
            </div>
        `;
    }
}

/**
 * Update station UI
 */
function updateStationUI(stationData) {
    if (!stationData?.id) return;

    const { name, availableBikes, totalDocks } = stationData;
    document.querySelector('#stationName').textContent = name;
    document.querySelector('#availableBikes').textContent = availableBikes;
    document.querySelector('#totalDocks').textContent = totalDocks;

    // Additional updates...
}
