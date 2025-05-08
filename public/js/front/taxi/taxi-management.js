// filepath: taxi-management-system/public/js/front/Taxi/taxi-management.js

document.addEventListener('DOMContentLoaded', function() {
    // Initialize the map for taxi stations
    const map = L.map('taxiMap').setView([40.7128, -74.006], 13);

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);

    // Define a reusable taxi icon
    const taxiIcon = L.divIcon({
        className: 'taxi-marker-icon',
        html: '<div><i class="fas fa-taxi"></i></div>',
        iconSize: [36, 36],
        iconAnchor: [18, 18]
    });

    // Bounds for auto-zoom
    const bounds = L.latLngBounds();

    // Add taxi station markers
    const stations = []; // This should be populated with actual station data

    stations.forEach(station => {
        if (station.location) {
            // Create marker
            const marker = L.marker(
                [station.location.latitude, station.location.longitude],
                {
                    icon: taxiIcon,
                    title: station.name
                }
            ).addTo(map);

            // Extend bounds for auto-zoom
            bounds.extend([station.location.latitude, station.location.longitude]);

            // Create popup
            marker.bindPopup(`
                <div class="station-info p-2">
                    <h5>${station.name}</h5>
                    <p>${station.location.address || 'No address available'}</p>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Available taxis:</span>
                        <span class="fw-bold">${station.availableTaxis}</span>
                    </div>
                    <a href="/taxi-station/${station.id}" class="btn btn-sm btn-taxi mt-2 w-100">
                        View Station Details
                    </a>
                </div>
            `);

            // Add animation to the marker
            marker.on('add', function() {
                const markerElement = marker.getElement();
                if (markerElement) {
                    markerElement.style.transition = 'transform 0.5s ease-in-out';
                    markerElement.style.transform = 'scale(0)';
                    setTimeout(() => {
                        markerElement.style.transform = 'scale(1)';
                    }, 100);
                }
            });
        }
    });

    // Fit map to bounds if we have stations
    if(bounds.isValid()) {
        map.fitBounds(bounds);
    }

    // Add a fade-in animation for the map container
    const mapContainer = document.getElementById('taxiMap');
    if (mapContainer) {
        mapContainer.style.opacity = '0';
        mapContainer.style.transition = 'opacity 1s ease-in-out';
        setTimeout(() => {
            mapContainer.style.opacity = '1';
        }, 100);
    }
});



