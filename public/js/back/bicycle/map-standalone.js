/**
 * Standalone map initialization
 * This file is dedicated ONLY to map functionality for standalone maps (not modal maps)
 */

// Initialize map immediately when script loads
(function () {
    console.log('[MAP] Standalone map initializer loading...');

    // Allow a moment for the DOM to be fully ready
    setTimeout(initializeStandaloneMap, 100);

    // Also try again a bit later if the first attempt fails
    setTimeout(initializeStandaloneMap, 500);
})();

/**
 * Initialize the map in isolation from other scripts
 */
function initializeStandaloneMap() {
    console.log('[MAP] Attempting standalone map initialization');

    try {
        // Check for Leaflet
        if (typeof L === 'undefined') {
            console.error('[MAP] Leaflet library not available');
            return;
        }

        // Get the map element
        const mapEl = document.getElementById('stationMap');

        // Skip if we're in a modal context - let the modal scripts handle their own maps
        if (mapEl && mapEl.closest('.modal')) {
            console.log('[MAP] Map is in a modal, skipping standalone initialization');
            return;
        }

        if (!mapEl) {
            console.error('[MAP] Map element not found');
            return;
        }

        // Check if map is already initialized
        if (mapEl._leaflet_id) {
            console.log('[MAP] Map already initialized, skipping');
            return;
        }

        console.log('[MAP] Map element found:', mapEl);

        // Get coordinates from data attributes
        const lat = parseFloat(mapEl.dataset.lat || 0);
        const lng = parseFloat(mapEl.dataset.lng || 0);
        const stationName = mapEl.dataset.name || 'Station';
        const availableBikes = mapEl.dataset.availableBikes || 0;
        const availableDocks = mapEl.dataset.availableDocks || 0;
        const chargingBikes = mapEl.dataset.chargingBikes || 0;
        const status = mapEl.dataset.status || 'Unknown';

        console.log('[MAP] Coordinates:', lat, lng);

        // Basic validation
        if (isNaN(lat) || isNaN(lng) || (lat === 0 && lng === 0)) {
            console.error('[MAP] Invalid coordinates');
            mapEl.innerHTML = `
                <div class="alert alert-warning m-3">
                    <i class="ti ti-map-off me-2"></i>
                    Invalid location coordinates
                </div>
            `;
            return;
        }

        // Set explicit dimensions - CRUCIAL for map to render
        mapEl.style.height = '400px';
        mapEl.style.width = '100%';
        mapEl.innerHTML = '';

        // Create map with custom styling
        console.log('[MAP] Creating map at', lat, lng);
        const map = L.map('stationMap', {
            zoomControl: true,
            attributionControl: true,
            scrollWheelZoom: true,
            dragging: true
        }).setView([lat, lng], 15);

        // Add a beautiful styled tile layer
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 19
        }).addTo(map);

        // Create a fancy styled popup with station info
        const popupContent = `
            <div class="station-popup">
                <div class="station-popup-header">
                    <h6 class="mb-0"><i class="ti ti-map-pin me-2"></i>${stationName}</h6>
                </div>
                <div class="station-popup-body">
                    <div class="station-data-item">
                        <div class="station-data-icon bg-primary-subtle text-primary">
                            <i class="ti ti-bike"></i>
                        </div>
                        <div>
                            <div class="fw-medium">${availableBikes} Bikes Available</div>
                            <small class="text-muted">Ready to rent</small>
                        </div>
                    </div>
                    <div class="station-data-item">
                        <div class="station-data-icon bg-success-subtle text-success">
                            <i class="ti ti-parking"></i>
                        </div>
                        <div>
                            <div class="fw-medium">${availableDocks} Docks Available</div>
                            <small class="text-muted">For returns</small>
                        </div>
                    </div>
                    <div class="station-data-item">
                        <div class="station-data-icon bg-warning-subtle text-warning">
                            <i class="ti ti-bolt"></i>
                        </div>
                        <div>
                            <div class="fw-medium">${chargingBikes} Bikes Charging</div>
                            <small class="text-muted">Currently at station</small>
                        </div>
                    </div>
                </div>
                <div class="station-popup-footer">
                    <div>
                        <span class="status-indicator ${status.toLowerCase() === 'active' ? 'active' :
                status.toLowerCase() === 'maintenance' ? 'maintenance' : 'inactive'}"></span>
                        ${status}
                    </div>
                    <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank" class="text-primary">
                        <i class="ti ti-external-link"></i> View on Google Maps
                    </a>
                </div>
            </div>
        `;

        // Create a custom icon for the marker
        const customIcon = L.divIcon({
            className: 'custom-div-icon',
            html: `<div class="station-marker-icon"><i class="ti ti-map-pin"></i></div>`,
            iconSize: [36, 36],
            iconAnchor: [18, 18]
        });

        // Add the marker with custom icon and popup
        const marker = L.marker([lat, lng], { icon: customIcon }).addTo(map);
        marker.bindPopup(popupContent);

        // Add custom map controls
        const mapLayerControl = L.control({ position: 'topright' });
        mapLayerControl.onAdd = function (map) {
            const div = L.DomUtil.create('div', 'map-layer-control');
            div.innerHTML = `
                <button type="button" class="map-layer-btn active" data-layer="default">
                    <i class="ti ti-map"></i> Standard
                </button>
                <button type="button" class="map-layer-btn" data-layer="satellite">
                    <i class="ti ti-photo"></i> Satellite
                </button>
                <button type="button" class="map-layer-btn" data-layer="dark">
                    <i class="ti ti-moon"></i> Dark
                </button>
            `;

            // Add button event listeners
            setTimeout(() => {
                const buttons = div.querySelectorAll('.map-layer-btn');
                buttons.forEach(btn => {
                    btn.addEventListener('click', function () {
                        // Remove active class from all buttons
                        buttons.forEach(b => b.classList.remove('active'));
                        // Add active class to clicked button
                        this.classList.add('active');

                        // Change the map layer based on button data
                        const layerType = this.getAttribute('data-layer');
                        changeMapLayer(map, layerType);
                    });
                });
            }, 100);

            return div;
        };
        mapLayerControl.addTo(map);

        // Open popup automatically
        setTimeout(() => {
            marker.openPopup();
        }, 500);

        // Force map refresh
        setTimeout(() => {
            map.invalidateSize();
            console.log('[MAP] Map refreshed');
        }, 200);

        // Add event listeners for the map control buttons
        const mapNormalBtn = document.getElementById('mapNormalView');
        const mapSatelliteBtn = document.getElementById('mapSatelliteView');

        if (mapNormalBtn && mapSatelliteBtn) {
            mapNormalBtn.addEventListener('click', function () {
                changeMapLayer(map, 'default');
                mapNormalBtn.classList.add('active');
                mapSatelliteBtn.classList.remove('active');
            });

            mapSatelliteBtn.addEventListener('click', function () {
                changeMapLayer(map, 'satellite');
                mapSatelliteBtn.classList.add('active');
                mapNormalBtn.classList.remove('active');
            });
        }

        console.log('[MAP] Map initialization successful!');
    } catch (err) {
        console.error('[MAP] Error initializing map:', err);

        const mapEl = document.getElementById('stationMap');
        if (mapEl) {
            mapEl.innerHTML = `
                <div class="alert alert-danger m-3">
                    <i class="ti ti-alert-circle me-2"></i>
                    Map initialization error: ${err.message}
                </div>
            `;
        }
    }
}

/**
 * Change the map tile layer based on selected style
 */
function changeMapLayer(map, layerType) {
    // Remove any existing tile layers
    map.eachLayer(layer => {
        if (layer instanceof L.TileLayer) {
            map.removeLayer(layer);
        }
    });

    // Add the selected tile layer
    if (layerType === 'satellite') {
        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
            maxZoom: 19
        }).addTo(map);
    } else if (layerType === 'dark') {
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 19
        }).addTo(map);
    } else {
        // Default light style
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 19
        }).addTo(map);
    }
}