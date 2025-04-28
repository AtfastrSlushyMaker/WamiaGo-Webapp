/**
 * WamiaGo - Station Map Handler
 * Handles the station map functionality, markers, and map-related interactions
 */

// Map and markers variables
let map;
let markers = {};

// Initialize the map when the document is loaded
document.addEventListener('DOMContentLoaded', function () {
    initializeMap();
    setupMapControls();
});

/**
 * Initialize the station map
 */
function initializeMap() {
    const mapElement = document.getElementById('stationsMap');
    if (!mapElement) {
        console.log('Map element not found, skipping map initialization');
        return;
    }

    console.log('Initializing map');

    // Make sure the map container has proper dimensions
    if (getComputedStyle(mapElement).display === 'none') {
        console.log('Map container is hidden, making it visible');
        mapElement.style.display = 'block';
    }

    // Explicit styling for map container to ensure it's visible
    mapElement.style.height = '400px';
    mapElement.style.width = '100%';
    mapElement.style.backgroundColor = '#f0f0f0';
    mapElement.style.position = 'relative';
    mapElement.style.zIndex = '1';

    // Log the map container dimensions for debugging
    console.log('Map container dimensions:',
        mapElement.offsetWidth + 'x' + mapElement.offsetHeight,
        'Style:', mapElement.style.cssText);

    // Check if map is already initialized on this element
    if (mapElement._leaflet_id) {
        console.log('Map already initialized, removing existing map first');
        if (map) {
            map.remove();
        }
    }

    // Clear any existing content
    mapElement.innerHTML = '';

    // Create a new map
    try {
        map = L.map('stationsMap', {
            zoomControl: false,
            attributionControl: true
        }).setView([36.8065, 10.1815], 13);

        console.log('Map object created successfully');

        // Add attribution control to bottom right
        L.control.attribution({
            position: 'bottomright',
            prefix: 'Leaflet'
        }).addTo(map);

        // Add zoom control to bottom right
        L.control.zoom({
            position: 'bottomright'
        }).addTo(map);

        // Add OpenStreetMap tiles
        const tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap contributors</a>'
        });

        // Make sure tiles are added to the map
        tiles.addTo(map);
        console.log('OpenStreetMap tiles added');

        // Make sure map is properly sized
        setTimeout(() => {
            map.invalidateSize(true);
            console.log('Map invalidateSize called immediately');
        }, 0);
    } catch (error) {
        console.error('Error initializing map:', error);
        mapElement.innerHTML = `
            <div class="alert alert-danger m-3">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Failed to initialize map</strong><br>
                ${error.message}<br>
                <button class="btn btn-primary mt-2" onclick="window.stationMap.initializeMap()">
                    <i class="fas fa-redo me-1"></i> Try Again
                </button>
            </div>
        `;
        return;
    }

    // Clear any existing markers
    markers = {};

    // Load stations AFTER the map is initialized
    setTimeout(() => {
        loadStationsFromAPI();
    }, 100);

    // Force map to render correctly multiple times
    setTimeout(() => {
        if (map) {
            map.invalidateSize(true);
            console.log('Map invalidateSize called after 500ms');
        }
    }, 500);
}

/**
 * Load station data from the API
 */
function loadStationsFromAPI() {
    console.log('Loading stations from API');

    // Create a loading overlay instead of replacing the map container's content
    const mapElement = document.getElementById('stationsMap');
    let loadingOverlay;

    if (mapElement) {
        // Create an overlay div instead of replacing the map element's content
        loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'map-loading-overlay';
        loadingOverlay.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading stations...</span>
                </div>
                <p class="mt-3">Loading stations...</p>
            </div>
        `;
        mapElement.appendChild(loadingOverlay);
    }

    // Load station data from API endpoint
    fetch('/admin/bicycle/api/stations', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`API response error: ${response.status}`);
            }
            return response.json();
        })
        .then(stations => {
            console.log('Stations loaded from API:', stations.length);

            // Remove loading overlay instead of clearing the container
            if (loadingOverlay) {
                loadingOverlay.remove();
            }

            if (!stations || stations.length === 0) {
                console.warn('API returned empty stations list');
                throw new Error('No stations data received from API');
            }

            // Add markers for each station
            addStationMarkersToMap(stations);

            // Make stations data available globally if needed
            window.stationData = stations;

            // Make sure map is still visible and properly sized
            if (map) {
                map.invalidateSize(true);
            }
        })
        .catch(error => {
            console.error('Error loading stations from API:', error);

            // Remove loading overlay
            if (loadingOverlay) {
                loadingOverlay.remove();
            }

            // Try to use fallback data
            if (window.stationDataFallback && window.stationDataFallback.length > 0) {
                console.log('Using fallback station data');
                window.stationData = window.stationDataFallback;
                addStationMarkersToMap(window.stationDataFallback);
            } else {
                // Display error overlay instead of replacing map container content
                const errorOverlay = document.createElement('div');
                errorOverlay.className = 'map-error-overlay';
                errorOverlay.style.cssText = 'position: absolute; top: 10px; left: 10px; right: 10px; z-index: 1000; background: white; border-radius: 4px; box-shadow: 0 1px 5px rgba(0,0,0,0.2);';
                errorOverlay.innerHTML = `
                    <div class="alert alert-danger m-3 text-center">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Failed to load stations.</strong><br>
                        <button class="btn btn-sm btn-primary mt-3" id="retryStationLoad">
                            <i class="fas fa-sync-alt me-1"></i> Try Again
                        </button>
                    </div>
                `;
                mapElement.appendChild(errorOverlay);

                // Add event listener to the retry button
                document.getElementById('retryStationLoad').addEventListener('click', function () {
                    errorOverlay.remove();
                    loadStationsFromAPI();
                });
            }
        });
}

/**
 * Add station markers to the map
 */
function addStationMarkersToMap(stations) {
    if (!map || !stations || !stations.length) {
        console.warn('Cannot add station markers: map or stations not available');
        return;
    }

    // Add markers for each station
    stations.forEach(station => {
        if (!station.lat || !station.lng) return;

        // Get the proper color based on status
        const getStatusColor = (status) => {
            switch(status) {
                case 'active': return { bg: '#4caf50', border: '#2e7d32' }; // Green
                case 'maintenance': return { bg: '#ff9800', border: '#e65100' }; // Orange
                case 'inactive': return { bg: '#9e9e9e', border: '#616161' }; // Grey
                case 'disabled': return { bg: '#f44336', border: '#c62828' }; // Red
                default: return { bg: '#9e9e9e', border: '#616161' }; // Default grey
            }
        };

        const color = getStatusColor(station.status);
        
        // Create marker with enhanced custom icon
        const marker = L.marker([station.lat, station.lng], {
            title: station.name,
            alt: `Station ${station.id}: ${station.name} (${station.status})`,
            riseOnHover: true,
            icon: L.divIcon({
                className: 'station-marker-icon',
                html: `
                    <div class="marker-container" style="position: relative;">
                        <div style="
                            background: ${color.bg};
                            width: 36px;
                            height: 36px;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            box-shadow: 0 3px 6px rgba(0,0,0,0.2);
                            border: 2.5px solid white;
                            position: relative;
                            z-index: 2;
                        ">
                            <i class="ti ti-map-pin" style="color: white; font-size: 18px;"></i>
                        </div>
                        <div style="
                            position: absolute;
                            bottom: -3px;
                            left: 50%;
                            transform: translateX(-50%);
                            width: 12px;
                            height: 12px;
                            background: white;
                            border: 3px solid ${color.border};
                            border-radius: 50%;
                            z-index: 1;
                        "></div>
                    </div>
                `,
                iconSize: [40, 45],
                iconAnchor: [20, 40],
                popupAnchor: [0, -40]
            })
        }).addTo(map);

        markers[station.id] = marker;

        // Create more attractive popup with enhanced styling
        marker.bindPopup(`
            <div class="station-info">
                <h5 class="mb-2">${station.name}</h5>
                <p class="text-muted mb-2">
                    <i class="ti ti-map-pin me-1"></i> 
                    ${station.address || 'No address available'}
                </p>
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge ${
                        station.status === 'active' ? 'bg-success' :
                        station.status === 'maintenance' ? 'bg-warning' :
                        station.status === 'inactive' ? 'bg-secondary' :
                        station.status === 'disabled' ? 'bg-danger' : 
                        'bg-secondary'
                    } px-2 py-1">
                        <i class="ti ${
                            station.status === 'active' ? 'ti-circle-check-filled' :
                            station.status === 'maintenance' ? 'ti-tools' :
                            station.status === 'inactive' ? 'ti-circle-x' :
                            station.status === 'disabled' ? 'ti-alert-triangle' : 
                            'ti-circle-x'
                        } me-1"></i>
                        ${station.status.charAt(0).toUpperCase() + station.status.slice(1)}
                    </span>
                    <span class="text-muted small">
                        ID: ST-${String(station.id).padStart(4, '0')}
                    </span>
                </div>
                
                <div class="stats-grid mb-3">
                    <div class="stat-item">
                        <div class="stat-value">${station.availableBikes}</div>
                        <div class="stat-label">Available</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">${station.totalDocks}</div>
                        <div class="stat-label">Capacity</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">${station.chargingDocks !== null && station.chargingDocks !== undefined ? station.chargingDocks : 0}</div>
                        <div class="stat-label">Charging</div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="/admin/bicycle/station/${station.id}" class="btn btn-sm btn-primary">
                        <i class="ti ti-info-circle me-1"></i> Details
                    </a>
                    <button class="btn btn-sm btn-outline-secondary popup-edit-btn" data-station-id="${station.id}">
                        <i class="ti ti-edit me-1"></i> Edit
                    </button>
                </div>
            </div>
        `, { 
            className: 'station-popup',
            maxWidth: 300,
            minWidth: 280,
            closeButton: true,
            autoClose: false
        });

        // Listen for popup open events to handle buttons inside popup
        marker.on('popupopen', function () {
            const popupElement = marker.getPopup().getElement();
            if (popupElement) {
                const editBtn = popupElement.querySelector('.popup-edit-btn');
                if (editBtn) {
                    editBtn.addEventListener('click', function () {
                        const stationId = this.getAttribute('data-station-id');
                        if (typeof window.initEditStationModal === 'function') {
                            window.initEditStationModal(stationId);
                        }
                    });
                }
            }
        });
        
        // Add hover effect for markers
        marker.on('mouseover', function() {
            this._icon.classList.add('marker-hover');
        });
        
        marker.on('mouseout', function() {
            this._icon.classList.remove('marker-hover');
        });
    });

    // Fit bounds if there are stations
    if (stations.length > 0) {
        const bounds = stations
            .filter(station => station.lat && station.lng)
            .map(station => [station.lat, station.lng]);
            
        if (bounds.length > 0) {
            map.fitBounds(bounds, {
                padding: [30, 30],
                maxZoom: 15,
                animate: true,
                duration: 1
            });
        }
    }
}

/**
 * Filter stations by status
 */
function filterStations(filter) {
    const stations = window.stationData || [];
    let filteredStations = [];

    switch (filter) {
        case 'active':
            filteredStations = stations.filter(station => station.status === 'active');
            break;
        case 'maintenance':
            filteredStations = stations.filter(station => station.status === 'maintenance');
            break;
        case 'inactive':
            filteredStations = stations.filter(station => station.status === 'inactive');
            break;
        case 'disabled':
            filteredStations = stations.filter(station => station.status === 'disabled');
            break;
        default:
            filteredStations = stations;
    }

    // Update map markers
    stations.forEach(station => {
        if ((filter === 'all') || (station.status === filter)) {
            if (markers[station.id]) markers[station.id].addTo(map);
        } else {
            if (markers[station.id]) map.removeLayer(markers[station.id]);
        }
    });

    // Update filter button styling
    document.querySelectorAll('.map-controls .btn').forEach(btn => {
        btn.classList.remove('active');
    });

    // Identify which button triggered this filter and mark it as active
    const buttonMap = {
        'all': 'showAllStations',
        'active': 'showActiveStations',
        'maintenance': 'showMaintenanceStations',
        'inactive': 'showInactiveStations',
        'disabled': 'showDisabledStations'
    };

    const buttonId = buttonMap[filter];
    if (buttonId) {
        const button = document.getElementById(buttonId);
        if (button) button.classList.add('active');
    }

    // Fit bounds to show filtered stations
    if (filteredStations.length > 0) {
        const bounds = filteredStations
            .filter(station => station.lat && station.lng)
            .map(station => [station.lat, station.lng]);

        if (bounds.length > 0) {
            map.fitBounds(bounds);
        }
    }
}

/**
 * Setup map filter buttons and other controls
 */
function setupMapControls() {
    const allButton = document.getElementById('showAllStations');
    const activeButton = document.getElementById('showActiveStations');
    const maintenanceButton = document.getElementById('showMaintenanceStations');
    const inactiveButton = document.getElementById('showInactiveStations');
    const disabledButton = document.getElementById('showDisabledStations');

    if (allButton) {
        allButton.addEventListener('click', function () { filterStations('all'); });
    }

    if (activeButton) {
        activeButton.addEventListener('click', function () { filterStations('active'); });
    }

    if (maintenanceButton) {
        maintenanceButton.addEventListener('click', function () { filterStations('maintenance'); });
    }

    if (inactiveButton) {
        inactiveButton.addEventListener('click', function () { filterStations('inactive'); });
    }

    if (disabledButton) {
        disabledButton.addEventListener('click', function () { filterStations('disabled'); });
    }

    // Setup locate station buttons
    setupLocateButtons();
}

/**
 * Setup locate station buttons
 */
function setupLocateButtons() {
    // Station locate buttons from the sidebar
    document.querySelectorAll('.station-locate-btn').forEach(button => {
        button.addEventListener('click', function () {
            const stationId = parseInt(this.getAttribute('data-station-id'), 10);
            locateStation(stationId);
        });
    });

    // View on map buttons from the table
    document.querySelectorAll('.view-on-map-btn').forEach(button => {
        button.addEventListener('click', function () {
            const stationId = parseInt(this.getAttribute('data-station-id'), 10);
            locateStation(stationId);
        });
    });

    // Highlight marker when hovering over table row
    document.querySelectorAll('.station-row').forEach(row => {
        const stationId = parseInt(row.getAttribute('data-station-id'), 10);
        row.addEventListener('mouseenter', function () {
            if (markers[stationId] && markers[stationId].getElement()) {
                markers[stationId].getElement().classList.add('highlight');
            }
        });
        row.addEventListener('mouseleave', function () {
            if (markers[stationId] && markers[stationId].getElement()) {
                markers[stationId].getElement().classList.remove('highlight');
            }
        });
    });
}

/**
 * Locate a station on the map
 */
function locateStation(stationId) {
    if (markers[stationId]) {
        map.setView(markers[stationId].getLatLng(), 16);
        markers[stationId].openPopup();
        document.getElementById('stationsMap').scrollIntoView({ behavior: 'smooth' });
    }
}

// Export functions for global use
window.stationMap = {
    initializeMap,
    filterStations,
    locateStation,
    loadStationsFromAPI
};