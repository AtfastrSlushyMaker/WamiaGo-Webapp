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
    setupExportData(); // Add this line to initialize export functionality
    initStationMap();
    initBicycleTypeSelector();
    initDeleteConfirmation();
    setupTableSearch();
    setupTabPersistence();
    setupRefreshActions();
    setupCopyStationInfo();
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
            new bootstrap.Tooltip(tooltip);
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
            if (!dropdownMenu || !dropdownMenu.classList.contains('dropdown-menu')) {
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
                const rect = this.getBoundingClientRect();
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
            new bootstrap.Tooltip(tooltip);
        });
    });
});


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
 * Initialize Bootstrap tooltips
 */
function initTooltips() {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl, {
        delay: { show: 300, hide: 100 }
    }));
}

/**
 * Initialize the interactive station map
 */
/**
 * Initialize the interactive station map
 * Fixed version that properly handles map loading
 */
/**
 * Initialize the interactive station map
 * Fixed version that handles common map loading issues
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
 * Initialize the bicycle type selector
 */
function initBicycleTypeSelector() {
    const bicycleTypeOptions = document.querySelectorAll('.bicycle-type-option');
    if (bicycleTypeOptions.length === 0) return;

    bicycleTypeOptions.forEach(option => {
        const input = option.querySelector('input');

        input.addEventListener('change', function () {
            if (this.checked) {
                const bicycleType = this.value;
                updateBicycleFields(bicycleType);
            }
        });
    });
}

/**
 * Update bicycle fields based on type selection
 */
function updateBicycleFields(bicycleType) {
    const batteryLevelInput = document.getElementById('batteryLevel');
    const rangeKmInput = document.getElementById('rangeKm');

    if (bicycleType === 'premium') {
        batteryLevelInput.value = 100;
        rangeKmInput.value = 85;
    } else {
        batteryLevelInput.value = 85;
        rangeKmInput.value = 50;
    }

    // Add animation to show change
    [batteryLevelInput, rangeKmInput].forEach(input => {
        input.classList.add('is-changed');
        setTimeout(() => {
            input.classList.remove('is-changed');
        }, 1000);
    });
}

/**
 * Enable delete confirmation checkbox functionality
 */
function initDeleteConfirmation() {
    const confirmDeleteCheckbox = document.getElementById('confirmDelete');
    const deleteStationBtn = document.getElementById('deleteStationBtn');

    if (!confirmDeleteCheckbox || !deleteStationBtn) return;

    confirmDeleteCheckbox.addEventListener('change', function () {
        deleteStationBtn.disabled = !this.checked;
    });

    // Handle delete button click
    deleteStationBtn.addEventListener('click', function () {
        if (confirmDeleteCheckbox.checked) {
            const stationId = this.getAttribute('data-station-id');

            // Show loading state
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Deleting...';
            this.disabled = true;

            // Simulate network request (replace with real AJAX call)
            setTimeout(() => {
                window.location.href = `/admin/bicycle/station/${stationId}/delete/confirm`;
            }, 1500);
        }
    });
}

/**
 * Setup table search functionality
 */
function setupTableSearch() {
    const bicycleSearch = document.getElementById('bicycleSearch');
    const rentalSearch = document.getElementById('rentalSearch');

    if (bicycleSearch) {
        bicycleSearch.addEventListener('input', function () {
            filterTable('bicyclesTable', this.value);
        });
    }

    if (rentalSearch) {
        rentalSearch.addEventListener('input', function () {
            filterTable('rentalsTable', this.value);
        });
    }
}

/**
 * Filter table based on search input
 */
function filterTable(tableId, searchText) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const rows = table.querySelectorAll('tbody tr');
    const searchLower = searchText.toLowerCase();

    rows.forEach(row => {
        const textContent = row.textContent.toLowerCase();
        row.style.display = textContent.includes(searchLower) ? '' : 'none';
    });
}

/**
 * Setup tab persistence with localStorage
 */
function setupTabPersistence() {
    // Get the active tab from local storage
    const activeTab = localStorage.getItem('stationDetailActiveTab');

    // If there is an active tab in local storage, activate it
    if (activeTab) {
        const tab = document.querySelector(`[data-bs-target="${activeTab}"]`);
        if (tab) {
            const bsTab = new bootstrap.Tab(tab);
            bsTab.show();
        }
    }

    // Save active tab to local storage when tab is shown
    const tabs = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function (event) {
            localStorage.setItem('stationDetailActiveTab', event.target.getAttribute('data-bs-target'));
        });
    });
}

/**
 * Setup refresh actions and animations
 */
function setupRefreshActions() {
    const refreshStationBtn = document.getElementById('refreshStationBtn');
    const refreshBicycles = document.getElementById('refreshBicycles');

    if (refreshStationBtn) {
        refreshStationBtn.addEventListener('click', function () {
            refreshStationData(this);
        });
    }

    if (refreshBicycles) {
        refreshBicycles.addEventListener('click', function () {
            refreshBicycleData(this);
        });
    }
}

/**
 * Refresh station data with loading animation
 */
function refreshStationData(button) {
    // Show loading state
    const originalContent = button.innerHTML;
    button.innerHTML = '<i class="ti ti-loader ti-spin"></i>';
    button.disabled = true;

    // Simulate network request (replace with real AJAX call)
    setTimeout(() => {
        // Reset button
        button.innerHTML = originalContent;
        button.disabled = false;

        // Show success toast
        showToast('Station data refreshed successfully!', 'success');

        // Update last updated time
        const timeElements = document.querySelectorAll('.ti-clock');
        timeElements.forEach(element => {
            const parentElement = element.parentElement;
            if (parentElement) {
                parentElement.innerHTML = `<i class="ti ti-clock me-1"></i> Updated ${new Date().toLocaleString()}`;
            }
        });
    }, 1500);
}

/**
 * Refresh bicycle data with loading animation
 */
function refreshBicycleData(button) {
    // Show loading state
    const originalContent = button.innerHTML;
    button.innerHTML = '<i class="ti ti-loader ti-spin me-1"></i> Refreshing...';
    button.disabled = true;

    // Show loading spinner on the bicycle table
    const table = document.getElementById('bicyclesTable');
    if (table) {
        table.classList.add('loading');

        // Add overlay
        const overlay = document.createElement('div');
        overlay.className = 'table-overlay';
        overlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
        table.parentNode.appendChild(overlay);
    }

    // Simulate network request (replace with real AJAX call)
    setTimeout(() => {
        // Reset button
        button.innerHTML = originalContent;
        button.disabled = false;

        // Remove overlay
        const overlay = document.querySelector('.table-overlay');
        if (overlay) {
            overlay.remove();
        }

        // Remove loading class
        if (table) {
            table.classList.remove('loading');
        }

        // Show success toast
        showToast('Bicycle data refreshed successfully!', 'success');
    }, 1500);
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    // Create toast container if it doesn't exist
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }

    // Create toast element
    const toastId = 'toast-' + Date.now();
    const toastHTML = `
        <div id="${toastId}" class="toast align-items-center text-white bg-${type}" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="ti ti-${type === 'success' ? 'check' : type === 'warning' ? 'alert-triangle' : 'info-circle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHTML);

    // Initialize and show the toast
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        delay: 3000
    });

    toast.show();

    // Remove toast from DOM after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function () {
        this.remove();
    });
}

/**
 * Setup QR code generation
 */
/**
 * Setup QR code generation
 */
function setupQRCodeGeneration() {
    const printQRModal = document.getElementById('printQRModal');
    const qrPlaceholder = document.querySelector('.station-qr-placeholder');
    const printQRBtn = document.querySelector('#printQRModal .btn-primary');

    if (!qrPlaceholder || !printQRBtn || !printQRModal) return;

    // Generate QR code when modal is shown
    printQRModal.addEventListener('shown.bs.modal', function () {
        // Clear placeholder contents first to avoid stacking issues
        qrPlaceholder.innerHTML = '';

        // Add loading spinner in center
        const spinner = document.createElement('div');
        spinner.className = 'spinner-border text-primary';
        spinner.role = 'status';
        spinner.innerHTML = '<span class="visually-hidden">Generating QR Code...</span>';
        qrPlaceholder.appendChild(spinner);

        // Get station ID from breadcrumb
        const breadcrumbText = document.querySelector('.breadcrumb-item.active').textContent.trim() || '';
        const stationIdMatch = breadcrumbText.match(/ST-(\d+)/);
        const stationId = stationIdMatch ? stationIdMatch[1] : '';

        // Get station name
        const stationName = document.querySelector('h1.h3').textContent.trim().split('\n')[0];

        // Get station address
        let stationAddress = "No address available";
        const addressElement = document.querySelector('.info-item:nth-child(3) .info-value');
        if (addressElement && !addressElement.textContent.includes('No address set')) {
            stationAddress = addressElement.textContent.trim();
        }

        // Get station status
        let stationStatus = "Unknown";
        const statusElement = document.querySelector('.status-badge');
        if (statusElement) {
            stationStatus = statusElement.textContent.trim();
        }

        if (!stationId) {
            qrPlaceholder.innerHTML = '<div class="alert alert-danger">Could not determine station ID</div>';
            return;
        }

        // Create the station details to encode in QR
        const stationDetails = {
            id: `ST-${stationId}`,
            name: stationName,
            address: stationAddress,
            status: stationStatus,
            totalDocks: document.querySelector('.info-item:nth-child(4) .info-value').textContent.trim(),
            availableBikes: document.querySelector('.metric-value .h2').textContent.trim()
        };

        // Convert to JSON string for QR code
        const qrData = JSON.stringify(stationDetails);

        // Create QR code
        const qrApiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(qrData)}&margin=10`;

        // Create an image element with proper styling
        const qrImage = new Image();
        qrImage.alt = `QR Code for station ${stationName}`;
        qrImage.style.display = 'block';
        qrImage.style.maxWidth = '100%';
        qrImage.style.margin = '0 auto';

        // When image loads, replace the loading spinner
        qrImage.onload = function () {
            // Clear placeholder and add the image centered
            qrPlaceholder.innerHTML = '';
            qrPlaceholder.appendChild(qrImage);

            // Add image info below the QR code
            const qrInfo = document.createElement('div');
            qrInfo.className = 'text-center mt-2 small text-muted';
            qrInfo.textContent = 'Scan to access station information';

            // Add info outside the placeholder to preserve QR code dimensions
            const qrCodeContainer = document.querySelector('.qr-code-container');
            if (qrCodeContainer) {
                // Remove any previous info
                const existingInfo = qrCodeContainer.querySelector('.text-center.mt-2');
                if (existingInfo) existingInfo.remove();

                // Add new info
                qrCodeContainer.appendChild(qrInfo);
            }
        };

        // If image fails to load
        qrImage.onerror = function () {
            qrPlaceholder.innerHTML = `
                <div class="alert alert-warning">
                    <i class="ti ti-alert-triangle me-2"></i>
                    Failed to generate QR code. Please try again later.
                </div>
            `;
        };

        // Set image source to start loading
        qrImage.src = qrApiUrl;
    });

    // Handle print button click
    printQRBtn.addEventListener('click', function () {
        const stationName = document.querySelector('h1.h3').textContent.trim().split('\n')[0];
        const stationId = document.querySelector('.breadcrumb-item.active').textContent.trim();
        const qrImage = qrPlaceholder.querySelector('img');

        if (!qrImage) {
            showToast('QR code is not ready yet. Please wait a moment.', 'warning');
            return;
        }

        // Create print window with enhanced styling
        const printWindow = window.open('', '_blank', 'width=600,height=600');

        if (!printWindow) {
            showToast('Pop-up blocked. Please allow pop-ups for printing.', 'warning');
            return;
        }

        const qrImageSrc = qrImage.src;

        printWindow.document.write(`
            <html>
                <head>
                    <title>WamiaGo Station QR Code - ${stationName}</title>
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            text-align: center;
                            padding: 20px;
                            color: #333;
                        }
                        .container {
                            max-width: 500px;
                            margin: 0 auto;
                            padding: 20px;
                            border: 1px solid #ddd;
                            border-radius: 10px;
                            background-color: #f9f9f9;
                        }
                        .header {
                            margin-bottom: 20px;
                        }
                        .logo {
                            font-size: 24px;
                            font-weight: bold;
                            color: #6571ff;
                            margin-bottom: 5px;
                        }
                        .qr-container {
                            padding: 15px;
                            background-color: white;
                            border-radius: 10px;
                            display: inline-block;
                            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                        }
                        .qr-image {
                            width: 200px;
                            height: 200px;
                        }
                        .station-info {
                            margin-top: 20px;
                        }
                        .station-name {
                            font-size: 20px;
                            font-weight: bold;
                            margin-bottom: 5px;
                        }
                        .station-id {
                            color: #666;
                            margin-bottom: 15px;
                        }
                        .instructions {
                            margin-top: 20px;
                            padding: 10px;
                            border-radius: 5px;
                            background-color: #e8f4fd;
                            font-size: 14px;
                            color: #0a558c;
                        }
                        .footer {
                            margin-top: 30px;
                            font-size: 12px;
                            color: #666;
                        }
                        @media print {
                            body {
                                padding: 0;
                            }
                            .container {
                                border: none;
                                box-shadow: none;
                                padding: 0;
                            }
                            .instructions {
                                border: 1px solid #ddd;
                            }
                            .no-print {
                                display: none;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <div class="logo">WamiaGo</div>
                            <div>Bicycle Rental Station</div>
                        </div>
                        
                        <div class="qr-container">
                            <img src="${qrImageSrc}" class="qr-image" alt="Station QR Code">
                        </div>
                        
                        <div class="station-info">
                            <div class="station-name">${stationName}</div>
                            <div class="station-id">${stationId}</div>
                        </div>
                        
                        <div class="instructions">
                            <strong>Station Details in QR Code:</strong> Scan this QR code to view information about this bicycle station.
                        </div>
                        
                        <div class="footer">
                            <p>Generated on ${new Date().toLocaleDateString()}</p>
                            <p>© WamiaGo Bicycle Sharing Network</p>
                        </div>
                        
                        <button class="no-print" style="margin-top:20px;padding:10px 20px;background:#6571ff;color:white;border:0;border-radius:5px;cursor:pointer;">
                            Print QR Code
                        </button>
                    </div>
                    
                    <script>
                        // Auto print
                        document.querySelector('button').addEventListener('click', function() {
                            window.print();
                        });
                        window.addEventListener('load', function() {
                            // Automatically show print dialog after a short delay
                            setTimeout(() => {
                                window.print();
                            }, 1000);
                        });
                    </script>
                </body>
            </html>
        `);

        printWindow.document.close();
    });
}

/**
 * Setup copy station info functionality
 */
function setupCopyStationInfo() {
    const copyStationInfoBtn = document.getElementById('copyStationInfo');
    if (!copyStationInfoBtn) return;

    copyStationInfoBtn.addEventListener('click', function (e) {
        e.preventDefault();

        // Gather station information
        const stationName = document.querySelector('h1.h3').textContent.trim().split('\n')[0];
        const stationId = document.querySelector('.breadcrumb-item.active').textContent.trim();

        // Get all info items
        const infoItems = document.querySelectorAll('.info-item');
        let stationInfo = `${stationName} (${stationId})\n`;
        stationInfo += '------------------------\n';

        infoItems.forEach(item => {
            const label = item.querySelector('.info-label').textContent.trim().replace(/^\s*[\r\n]/gm, '');
            const value = item.querySelector('.info-value').textContent.trim().replace(/^\s*[\r\n]/gm, '');
            stationInfo += `${label.replace(':', '')}: ${value}\n`;
        });

        // Copy to clipboard
        navigator.clipboard.writeText(stationInfo).then(() => {
            // Change button text temporarily
            const originalText = copyStationInfoBtn.innerHTML;
            copyStationInfoBtn.innerHTML = '<i class="ti ti-check me-2"></i> Copied!';

            // Reset button text after a delay
            setTimeout(() => {
                copyStationInfoBtn.innerHTML = originalText;
            }, 2000);

            // Show success toast
            showToast('Station info copied to clipboard', 'success');
        });
    });
}

/**
 * Setup bicycle filtering
 */
function setupBicycleFiltering() {
    const filterItems = document.querySelectorAll('.dropdown-item[href="#"]');

    filterItems.forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();

            const filterText = this.textContent.trim();
            const bicycleTable = document.getElementById('bicyclesTable');

            if (!bicycleTable) return;

            const rows = bicycleTable.querySelectorAll('tbody tr');

            rows.forEach(row => {
                if (filterText.includes('All Bicycles')) {
                    row.style.display = '';
                } else {
                    const statusCell = row.querySelector('td:nth-child(5)');
                    if (statusCell) {
                        const statusText = statusCell.textContent.trim();

                        if (filterText.includes('Available') && statusText.includes('Available')) {
                            row.style.display = '';
                        } else if (filterText.includes('Maintenance') && statusText.includes('Maintenance')) {
                            row.style.display = '';
                        } else if (filterText.includes('Charging') && statusText.includes('Charging')) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    }
                }
            });

            // Update dropdown button text
            const dropdownButton = document.querySelector('.dropdown-toggle');
            if (dropdownButton) {
                const icon = this.querySelector('i').cloneNode(true);
                const text = document.createTextNode(' ' + filterText);

                dropdownButton.innerHTML = '';
                dropdownButton.appendChild(icon);
                dropdownButton.appendChild(text);
            }
        });
    });
}

function debugMap() {
    const mapElement = document.getElementById('stationMap');
    if (!mapElement) {
        console.error("Map element not found");
        return;
    }

    console.log("Map container:", mapElement);
    console.log("Map dimensions:", mapElement.offsetWidth, "×", mapElement.offsetHeight);
    console.log("Map data attributes:", {
        lat: mapElement.dataset.lat,
        lng: mapElement.dataset.lng,
        name: mapElement.dataset.name,
        availableBikes: mapElement.dataset.availableBikes,
        availableDocks: mapElement.dataset.availableDocks,
        chargingBikes: mapElement.dataset.chargingBikes,
        status: mapElement.dataset.status
    });
    console.log("Map styles:", window.getComputedStyle(mapElement));

    // Check Leaflet availability
    if (typeof L === 'undefined') {
        console.error("Leaflet is not defined. Script may not be loaded.");
    } else {
        console.log("Leaflet version:", L.version);
    }

}

/**
 * Setup export data functionality
 */
function setupExportData() {
    const exportBtn = document.querySelector('#exportDataModal .btn-primary');
    if (!exportBtn) return;

    exportBtn.addEventListener('click', function () {
        // Get export options
        const checkboxes = document.querySelectorAll('#exportDataModal input[type="checkbox"]:checked');
        const selectedData = Array.from(checkboxes).map(checkbox => checkbox.value);

        if (selectedData.length === 0) {
            showToast('Please select at least one data type to export', 'warning');
            return;
        }

        // Get export format
        const formatRadios = document.querySelectorAll('#exportDataModal input[name="exportFormat"]:checked');
        const exportFormat = formatRadios.length > 0 ? formatRadios[0].value : 'csv';

        // Get date range
        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo = document.getElementById('dateTo').value;

        if (!dateFrom || !dateTo) {
            showToast('Please select valid date range', 'warning');
            return;
        }

        // Show loading state
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="ti ti-loader ti-spin me-1"></i> Processing...';
        this.disabled = true;

        // Get station data for export
        const stationId = document.querySelector('.breadcrumb-item.active').textContent.trim();
        const stationName = document.querySelector('h1.h3').textContent.trim().split('\n')[0];

        // Create export data based on selected options
        let exportData;

        // In a real app, you would fetch this data from the server
        // For demo, we'll create sample data based on the selected options
        if (exportFormat === 'json') {
            exportData = createJsonExport(stationId, stationName, selectedData, dateFrom, dateTo);
            downloadFile(exportData, 'application/json', 'json');
        } else if (exportFormat === 'excel') {
            exportData = createCsvExport(stationId, stationName, selectedData, dateFrom, dateTo);
            downloadFile(exportData, 'application/vnd.ms-excel', 'xls');
        } else {
            // Default to CSV
            exportData = createCsvExport(stationId, stationName, selectedData, dateFrom, dateTo);
            downloadFile(exportData, 'text/csv', 'csv');
        }

        // Reset button state after a delay
        setTimeout(() => {
            this.innerHTML = originalText;
            this.disabled = false;

            // Close modal
            const exportModal = document.getElementById('exportDataModal');
            if (exportModal) {
                const bsModal = bootstrap.Modal.getInstance(exportModal);
                if (bsModal) bsModal.hide();
            }

            showToast('Data export completed successfully!', 'success');
        }, 1500);
    });
}

/**
 * Create CSV export data
 */
function createCsvExport(stationId, stationName, selectedData, dateFrom, dateTo) {
    let csv = [];

    // Add station info header
    csv.push(`Station Export: ${stationName} (${stationId})`);
    csv.push(`Export Date: ${new Date().toLocaleString()}`);
    csv.push(`Date Range: ${dateFrom} to ${dateTo}`);
    csv.push('');

    // Add station information if selected
    if (selectedData.includes('station_info')) {
        csv.push('STATION INFORMATION');
        csv.push('ID,Name,Status,Total Docks,Available Bikes,Available Docks');

        // Get info from page
        const status = document.querySelector('.status-badge').textContent.trim();
        const totalDocks = document.querySelector('.info-item:nth-child(4) .info-value').textContent.trim();
        const availableBikes = document.querySelector('.metric-value .h2').textContent.trim();
        const availableDocks = document.querySelectorAll('.metric-value .h2')[1].textContent.trim();

        csv.push(`${stationId},${stationName},${status},${totalDocks},${availableBikes},${availableDocks}`);
        csv.push('');
    }

    // Add bicycle data if selected
    if (selectedData.includes('bicycle_data')) {
        csv.push('BICYCLE DATA');
        csv.push('Bicycle ID,Type,Battery Level,Range (km),Status');

        // Get bicycles from table
        const bicycleRows = document.querySelectorAll('#bicyclesTable tbody tr');
        bicycleRows.forEach(row => {
            const bikeId = row.querySelector('td:nth-child(1)').textContent.trim();
            const bikeType = row.querySelector('td:nth-child(2)').textContent.trim();
            const batteryText = row.querySelector('td:nth-child(3) .battery-text').textContent.trim();
            const range = row.querySelector('td:nth-child(4)').textContent.trim();
            const status = row.querySelector('td:nth-child(5)').textContent.trim();

            csv.push(`${bikeId},${bikeType},${batteryText},${range},${status}`);
        });
        csv.push('');
    }

    // Add rental history if selected
    if (selectedData.includes('rental_history')) {
        csv.push('RENTAL HISTORY');
        csv.push('Rental ID,User,Bicycle,Start Time,End Time,Duration,Status');

        // Get rentals from table
        const rentalRows = document.querySelectorAll('#rentalsTable tbody tr');
        rentalRows.forEach(row => {
            const rentalId = row.querySelector('td:nth-child(1)').textContent.trim();
            const user = row.querySelector('td:nth-child(2)').textContent.trim().replace(/\s+/g, ' ');
            const bicycle = row.querySelector('td:nth-child(3)').textContent.trim();
            const startTime = row.querySelector('td:nth-child(4)').textContent.trim().replace(/\s+/g, ' ');
            const endTime = row.querySelector('td:nth-child(5)').textContent.trim().replace(/\s+/g, ' ');
            const duration = row.querySelector('td:nth-child(6)').textContent.trim();
            const status = row.querySelector('td:nth-child(7)').textContent.trim();

            csv.push(`${rentalId},${user},${bicycle},${startTime},${endTime},${duration},${status}`);
        });
        csv.push('');
    }

    // Add maintenance log if selected
    if (selectedData.includes('maintenance_log')) {
        csv.push('MAINTENANCE LOG');
        csv.push('Date,Type,Description,Technician');

        // Get maintenance items
        const maintenanceItems = document.querySelectorAll('.timeline-item');
        maintenanceItems.forEach(item => {
            const date = item.querySelector('.text-muted.small').textContent.trim();
            const type = item.querySelector('.badge').textContent.trim();
            const description = item.querySelector('p').textContent.trim();
            const technician = item.querySelector('.small span:last-child').textContent.trim();

            csv.push(`${date},${type},${description},${technician}`);
        });
    }

    return csv.join('\n');
}

/**
 * Create JSON export data
 */
function createJsonExport(stationId, stationName, selectedData, dateFrom, dateTo) {
    const exportObj = {
        exportInfo: {
            stationId: stationId,
            stationName: stationName,
            exportDate: new Date().toISOString(),
            dateRange: {
                from: dateFrom,
                to: dateTo
            }
        }
    };

    // Add station information if selected
    if (selectedData.includes('station_info')) {
        const status = document.querySelector('.status-badge').textContent.trim();
        const totalDocks = document.querySelector('.info-item:nth-child(4) .info-value').textContent.trim();
        const availableBikes = document.querySelector('.metric-value .h2').textContent.trim();
        const availableDocks = document.querySelectorAll('.metric-value .h2')[1].textContent.trim();

        exportObj.stationInfo = {
            id: stationId,
            name: stationName,
            status: status,
            totalDocks: totalDocks,
            availableBikes: availableBikes,
            availableDocks: availableDocks
        };
    }

    // Add bicycle data if selected
    if (selectedData.includes('bicycle_data')) {
        exportObj.bicycles = [];

        const bicycleRows = document.querySelectorAll('#bicyclesTable tbody tr');
        bicycleRows.forEach(row => {
            const bikeId = row.querySelector('td:nth-child(1)').textContent.trim();
            const bikeType = row.querySelector('td:nth-child(2)').textContent.trim();
            const batteryText = row.querySelector('td:nth-child(3) .battery-text').textContent.trim();
            const range = row.querySelector('td:nth-child(4)').textContent.trim();
            const status = row.querySelector('td:nth-child(5)').textContent.trim();

            exportObj.bicycles.push({
                id: bikeId,
                type: bikeType,
                batteryLevel: batteryText,
                range: range,
                status: status
            });
        });
    }

    // Add rental history if selected
    if (selectedData.includes('rental_history')) {
        exportObj.rentals = [];

        const rentalRows = document.querySelectorAll('#rentalsTable tbody tr');
        rentalRows.forEach(row => {
            const rentalId = row.querySelector('td:nth-child(1)').textContent.trim();
            const user = row.querySelector('td:nth-child(2)').textContent.trim().replace(/\s+/g, ' ');
            const bicycle = row.querySelector('td:nth-child(3)').textContent.trim();
            const startTime = row.querySelector('td:nth-child(4)').textContent.trim().replace(/\s+/g, ' ');
            const endTime = row.querySelector('td:nth-child(5)').textContent.trim().replace(/\s+/g, ' ');
            const duration = row.querySelector('td:nth-child(6)').textContent.trim();
            const status = row.querySelector('td:nth-child(7)').textContent.trim();

            exportObj.rentals.push({
                id: rentalId,
                user: user,
                bicycle: bicycle,
                startTime: startTime,
                endTime: endTime,
                duration: duration,
                status: status
            });
        });
    }

    // Add maintenance log if selected
    if (selectedData.includes('maintenance_log')) {
        exportObj.maintenanceLog = [];

        const maintenanceItems = document.querySelectorAll('.timeline-item');
        maintenanceItems.forEach(item => {
            const date = item.querySelector('.text-muted.small').textContent.trim();
            const type = item.querySelector('.badge').textContent.trim();
            const description = item.querySelector('p').textContent.trim();
            const technician = item.querySelector('.small span:last-child').textContent.trim();

            exportObj.maintenanceLog.push({
                date: date,
                type: type,
                description: description,
                technician: technician
            });
        });
    }

    return JSON.stringify(exportObj, null, 2);
}

/**
 * Handle file download
 */
function downloadFile(data, mimeType, extension) {
    // Get station ID for filename
    const stationId = document.querySelector('.breadcrumb-item.active').textContent.trim();

    // Create download link
    const blob = new Blob([data], { type: mimeType });
    const url = window.URL.createObjectURL(blob);
    const downloadLink = document.createElement('a');
    const filename = `WamiaGo_${stationId}_${new Date().toISOString().slice(0, 10)}.${extension}`;

    // Set download attributes
    downloadLink.href = url;
    downloadLink.download = filename;

    // Trigger download
    document.body.appendChild(downloadLink);
    downloadLink.click();

    // Cleanup
    setTimeout(() => {
        window.URL.revokeObjectURL(url);
        document.body.removeChild(downloadLink);
    }, 100);
}

function initializeBootstrapComponents() {
    console.log("Initializing Bootstrap components...");

    // Initialize tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        try {
            new bootstrap.Tooltip(tooltipTriggerEl);
        } catch (e) {
            console.error("Error initializing tooltip:", e);
        }
    });

    // Initialize dropdowns with a simpler approach
    const dropdownTriggerList = document.querySelectorAll('.dropdown-toggle');
    console.log(`Initializing ${dropdownTriggerList.length} dropdowns`);

    dropdownTriggerList.forEach(function (dropdownToggle) {
        try {
            // Clean up any existing dropdown first
            const existingDropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
            if (existingDropdown) {
                existingDropdown.dispose();
            }

            // Create fresh dropdown
            new bootstrap.Dropdown(dropdownToggle);
            console.log("Dropdown initialized:", dropdownToggle);

            // Add click event to debug
            dropdownToggle.addEventListener('click', function (e) {
                console.log("Dropdown toggle clicked", this);
                // Don't stop propagation for the main dropdown
                if (this.closest('td')) {
                    e.stopPropagation();
                }
            });
        } catch (e) {
            console.error("Error initializing dropdown:", e);
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

function fixDropdownMenus() {
    // Target all dropdown menus
    const allDropdowns = document.querySelectorAll('.dropdown');

    allDropdowns.forEach(dropdown => {
        const dropdownToggle = dropdown.querySelector('.dropdown-toggle');
        const dropdownMenu = dropdown.querySelector('.dropdown-menu');

        if (!dropdownToggle || !dropdownMenu) return;

        // Ensure the dropdown menu is properly positioned relative to its toggle button
        dropdownToggle.addEventListener('click', function (e) {
            // Get position of toggle button
            const rect = dropdownToggle.getBoundingClientRect();
            const viewportHeight = window.innerHeight;

            // Remove any inline styles that might be interfering
            dropdownMenu.style.removeProperty('position');
            dropdownMenu.style.removeProperty('transform');
            dropdownMenu.style.removeProperty('top');
            dropdownMenu.style.removeProperty('left');

            // Check if menu would go off bottom of viewport
            setTimeout(() => {
                const menuRect = dropdownMenu.getBoundingClientRect();
                if (menuRect.bottom > viewportHeight) {
                    dropdownMenu.style.maxHeight = (viewportHeight - rect.bottom - 10) + 'px';
                }
            }, 0);

            // Prevent event propagation if in a table cell to avoid row click events
            const isInTableCell = dropdownToggle.closest('td');
            if (isInTableCell) {
                e.stopPropagation();
            }
        });
    });

    // Handle dropdowns in table cells specially
    const tableDropdowns = document.querySelectorAll('td .dropdown');
    tableDropdowns.forEach(dropdown => {
        const dropdownMenu = dropdown.querySelector('.dropdown-menu');
        if (dropdownMenu) {
            dropdownMenu.classList.add('dropdown-menu-end');
        }
    });
}

// Add this to your existing document ready function
document.addEventListener('DOMContentLoaded', function () {
    // Call the dropdown fix function
    fixDropdownMenus();

    // Rest of your existing initialization...
});
