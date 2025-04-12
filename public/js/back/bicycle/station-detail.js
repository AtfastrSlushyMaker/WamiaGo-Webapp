/**
 * WamiaGo - Station Detail Interactive Features
 * Enhanced mapping, charts and interactive components
 */

document.addEventListener('DOMContentLoaded', function () {
    console.log("DOM loaded, debugging dropdowns...");

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

    // Set up other functionality
    setupQRCodeGeneration();
    setupExportData();
    initStationMap();
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
    console.log("Initializing map...");
    const mapElement = document.getElementById('stationMap');
    if (!mapElement) {
        console.error("Map element not found");
        return;
    }

    try {
        // Get station coordinates from data attributes
        const stationLat = parseFloat(mapElement.dataset.lat || 0);
        const stationLng = parseFloat(mapElement.dataset.lng || 0);

        console.log("Map coordinates:", stationLat, stationLng);

        // Validate coordinates - this is critical
        if (isNaN(stationLat) || isNaN(stationLng)) {
            console.error('Invalid station coordinates:', stationLat, stationLng);
            mapElement.innerHTML = `
                <div class="alert alert-warning m-3">
                    <i class="ti ti-map-off me-2"></i>
                    Invalid location coordinates. Please update the station location.
                </div>
            `;
            return;
        }

        // Fix map loading by ensuring the container has explicit dimensions
        mapElement.style.width = '100%';
        mapElement.style.height = '400px';

        // Create and initialize the map - IMPORTANT: use a slight delay
        setTimeout(() => {
            console.log("Creating map with coordinates:", stationLat, stationLng);

            // Initialize map with correct settings
            const map = L.map(mapElement, {
                center: [stationLat, stationLng],
                zoom: 15,
                zoomControl: false
            });

            // Add zoom control to bottom right
            L.control.zoom({
                position: 'bottomright'
            }).addTo(map);

            // Add light map tiles - most reliable tile provider
            const lightTiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19,
            }).addTo(map);

            // Add satellite map tiles (not added by default)
            const satelliteTiles = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 19,
                attribution: 'Imagery &copy; Esri'
            });

            // Create a custom marker element
            const markerHtml = `
                <div class="custom-marker-pin">
                    <i class="ti ti-map-pin"></i>
                </div>
            `;

            // Create a custom icon
            const customIcon = L.divIcon({
                className: 'custom-div-icon',
                html: markerHtml,
                iconSize: [40, 40],
                iconAnchor: [20, 40]
            });

            // Add marker to the map
            const marker = L.marker([stationLat, stationLng], {
                icon: customIcon,
                title: mapElement.dataset.name || 'Station'
            }).addTo(map);

            // Get additional station data
            const stationName = mapElement.dataset.name || 'Station';
            const availableBikes = mapElement.dataset.availableBikes || 0;
            const availableDocks = mapElement.dataset.availableDocks || 0;
            const chargingBikes = mapElement.dataset.chargingBikes || 0;
            const stationStatus = mapElement.dataset.status || 'Unknown';

            // Create enhanced popup content
            const popupContent = `
                <div class="station-popup">
                    <div class="popup-header">
                        <h6 class="mb-0 text-white">${stationName}</h6>
                    </div>
                    <div class="popup-body">
                        <div class="popup-info-item">
                            <div class="popup-info-label">Available Bikes</div>
                            <div class="popup-info-value">${availableBikes}</div>
                        </div>
                        <div class="popup-info-item">
                            <div class="popup-info-label">Available Docks</div>
                            <div class="popup-info-value">${availableDocks}</div>
                        </div>
                        <div class="popup-info-item">
                            <div class="popup-info-label">Charging Bikes</div>
                            <div class="popup-info-value">${chargingBikes}</div>
                        </div>
                        <div class="popup-info-item">
                            <div class="popup-info-label">Status</div>
                            <div class="popup-info-value">${stationStatus}</div>
                        </div>
                        <div class="mt-3 d-grid">
                            <a href="https://www.google.com/maps?q=${stationLat},${stationLng}" 
                              target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-external-link me-1"></i> Open in Google Maps
                            </a>
                        </div>
                    </div>
                </div>
            `;

            // Add popup to the marker
            marker.bindPopup(popupContent, {
                closeButton: true,
                className: 'station-custom-popup',
                maxWidth: 300,
                minWidth: 250
            });

            // Open popup by default
            setTimeout(() => {
                marker.openPopup();
            }, 500);

            // Setup map view toggle buttons
            if (document.getElementById('mapNormalView') && document.getElementById('mapSatelliteView')) {
                document.getElementById('mapNormalView').addEventListener('click', function () {
                    if (!map.hasLayer(lightTiles)) {
                        map.removeLayer(satelliteTiles);
                        map.addLayer(lightTiles);
                    }
                    updateActiveMapButton(this);
                });

                document.getElementById('mapSatelliteView').addEventListener('click', function () {
                    if (!map.hasLayer(satelliteTiles)) {
                        map.removeLayer(lightTiles);
                        map.addLayer(satelliteTiles);
                    }
                    updateActiveMapButton(this);
                });
            }

            // Critical: Force map refresh after it's completely loaded
            setTimeout(() => {
                console.log("Forcing map resize");
                map.invalidateSize();
            }, 500);

            // Add window resize listener to fix map resizing issues
            window.addEventListener('resize', () => {
                map.invalidateSize();
            });

            console.log("Map initialized successfully");
        }, 500); // Add delay to ensure DOM is ready
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
