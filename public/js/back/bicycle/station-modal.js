/**
 * WamiaGo - Station Form Modal
 * Handles the station form modal functionality including map, location selection, and form submission
 */

// Initialize map and variables
let formMap;
let formMarker;
let existingLocations = [];
let existingLocationMarkers = [];

// Initialize the form when the modal is shown
document.addEventListener('DOMContentLoaded', function () {
    // Set up the saveStationBtn click event
    const saveStationBtn = document.getElementById('saveStationBtn');
    if (saveStationBtn) {
        saveStationBtn.addEventListener('click', submitStationForm);
    }

    // Initialize modal map when shown
    const stationFormModal = document.getElementById('stationFormModal');
    if (stationFormModal) {
        stationFormModal.addEventListener('shown.bs.modal', function () {
            initializeFormMap();
            loadExistingLocations();
            setupFormListeners();
            updateCapacityVisualization();
        });
    }
});

function validateForm() {
    const form = document.getElementById('stationForm');

    // Add validation classes
    form.classList.add('was-validated');

    // Check if form is valid
    if (!form.checkValidity()) {
        return false;
    }

    // Check if location is selected
    if (!document.getElementById('station_latitude').value ||
        !document.getElementById('station_longitude').value) {
        alert('Please select a location on the map.');
        return false;
    }

    // Check if available bikes doesn't exceed total docks
    const availableBikes = parseInt(document.getElementById('availableBikes').value) || 0;
    const totalDocks = parseInt(document.getElementById('station_totalDocks').value) || 0;

    if (availableBikes > totalDocks) {
        alert('Available bikes cannot exceed total docks.');
        return false;
    }

    return true;
}


function showNotification(type, message) {
    // If you have a notification system, use it here
    if (type === 'success') {
        alert(message); // Replace with your notification system
    } else {
        alert(message); // Replace with your notification system
    }
}

function submitStationForm() {
    if (!validateForm()) {
        return;
    }

    // Show loading overlay
    const loadingOverlay = document.getElementById('modalLoadingOverlay');
    if (loadingOverlay) loadingOverlay.style.display = 'flex';

    // Get form data
    const formData = new FormData();
    formData.append('name', document.getElementById('station_name').value);
    formData.append('totalDocks', document.getElementById('station_totalDocks').value);
    formData.append('availableBikes', document.getElementById('availableBikes').value);
    formData.append('status', document.getElementById('station_status').value);
    formData.append('latitude', document.getElementById('station_latitude').value);
    formData.append('longitude', document.getElementById('station_longitude').value);
    formData.append('address', document.getElementById('station_address').value);

    // Add location ID if it exists
    const locationId = document.getElementById('station_location_id').value;
    if (locationId) {
        formData.append('locationId', locationId);
    }

    // Get the action URL from the form
    const form = document.getElementById('stationForm');
    const actionUrl = form.getAttribute('action') || '/admin/bicycle/station/new';

    // Submit data using AJAX
    fetch(actionUrl, {
        method: 'POST',
        body: formData
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Show success message
                showNotification('success', 'Station saved successfully');

                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('stationFormModal'));
                if (modal) modal.hide();

                // Reload page after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification('error', data.message || 'Error saving station');
                if (loadingOverlay) loadingOverlay.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error saving station:', error);
            showNotification('error', 'Failed to save station. Please try again.');
            if (loadingOverlay) loadingOverlay.style.display = 'none';
        });
}
/**
 * Initialize the map in the form modal
 */
function initializeFormMap() {
    // If map is already initialized, just resize it
    if (formMap) {
        formMap.invalidateSize();
        return;
    }

    // Default to center of Tunisia if no location selected
    const defaultLat = 36.8065;
    const defaultLng = 10.1815;

    // Create the map instance
    formMap = L.map('stationMap').setView([defaultLat, defaultLng], 13);

    // Add tile layer (matching the main map styling)
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        maxZoom: 19,
        subdomains: 'abcd'
    }).addTo(formMap);

    // Add click event to map for placing the station
    formMap.on('click', function (e) {
        placeMarker(e.latlng);
        reverseGeocode(e.latlng);
    });

    // Force map to render correctly
    setTimeout(() => {
        formMap.invalidateSize();
    }, 200);
}

/**
 * Place a marker at the specified location
 */
function placeMarker(latlng) {
    // Remove existing marker if any
    if (formMarker) {
        formMap.removeLayer(formMarker);
    }

    // Create a new marker
    formMarker = L.marker(latlng, {
        draggable: true,
        icon: L.divIcon({
            className: 'custom-marker-icon',
            html: '<div style="background-color: #6571ff; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.3); border: 2px solid white;"><i class="ti ti-map-pin" style="color: white; font-size: 20px;"></i></div>',
            iconSize: [36, 36],
            iconAnchor: [18, 36],
            popupAnchor: [0, -36]
        })
    }).addTo(formMap);

    // Update coordinates when marker is dragged
    formMarker.on('dragend', function () {
        const position = formMarker.getLatLng();
        updateLocationFields(position.lat, position.lng);
        reverseGeocode(position);
    });

    // Update the form fields
    updateLocationFields(latlng.lat, latlng.lng);
}

/**
 * Update the hidden form fields with location data
 */
function updateLocationFields(lat, lng, address = null, locationId = null) {
    document.getElementById('station_latitude').value = lat;
    document.getElementById('station_longitude').value = lng;

    // Update the displayed coordinates
    document.getElementById('selectedLatitude').innerHTML = `<strong>Lat:</strong> ${lat.toFixed(6)}`;
    document.getElementById('selectedLongitude').innerHTML = `<strong>Lng:</strong> ${lng.toFixed(6)}`;

    // Update address if provided
    if (address) {
        document.getElementById('station_address').value = address;
        document.getElementById('selectedAddress').textContent = address;
    }

    // Update location ID if provided (for existing locations)
    if (locationId) {
        document.getElementById('station_location_id').value = locationId;
    } else {
        document.getElementById('station_location_id').value = '';
    }
}

/**
 * Load existing locations from the database
 */
function loadExistingLocations() {
    // Show loading overlay
    const loadingOverlay = document.getElementById('modalLoadingOverlay');
    if (loadingOverlay) loadingOverlay.style.display = 'flex';

    fetch('/admin/bicycle/api/locations')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            existingLocations = data;

            // Only show other stations if the checkbox is checked
            if (document.getElementById('showOtherStations').checked) {
                displayExistingLocations();
            }

            // Hide loading overlay
            if (loadingOverlay) loadingOverlay.style.display = 'none';
        })
        .catch(error => {
            console.error('Error loading locations:', error);
            // Hide loading overlay
            if (loadingOverlay) loadingOverlay.style.display = 'none';

            // Show error notification
            alert('Failed to load existing locations. Please try again later.');
        });
}

/**
 * Display existing locations on the map
 */
function displayExistingLocations() {
    // Clear existing markers
    existingLocationMarkers.forEach(marker => formMap.removeLayer(marker));
    existingLocationMarkers = [];

    // Add markers for each location
    existingLocations.forEach(location => {
        if (location.latitude && location.longitude) {
            const locationMarker = L.marker([location.latitude, location.longitude], {
                icon: L.divIcon({
                    className: 'existing-location-marker',
                    html: '<div style="background-color: #9e9e9e; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.2); border: 2px solid white;"><i class="ti ti-map-pin" style="color: white; font-size: 14px;"></i></div>',
                    iconSize: [28, 28],
                    iconAnchor: [14, 28],
                    popupAnchor: [0, -28]
                })
            }).addTo(formMap);

            locationMarker.bindPopup(`
                <div class="location-popup">
                    <p class="mb-1"><strong>${location.address}</strong></p>
                    <button class="btn btn-sm btn-primary select-location-btn" 
                            data-id="${location.id}" 
                            data-lat="${location.latitude}" 
                            data-lng="${location.longitude}" 
                            data-address="${location.address}">
                        Select This Location
                    </button>
                </div>
            `);

            locationMarker.on('popupopen', function () {
                document.querySelectorAll('.select-location-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        const id = this.getAttribute('data-id');
                        const lat = parseFloat(this.getAttribute('data-lat'));
                        const lng = parseFloat(this.getAttribute('data-lng'));
                        const address = this.getAttribute('data-address');

                        selectExistingLocation(id, lat, lng, address);
                        locationMarker.closePopup();
                    });
                });
            });

            existingLocationMarkers.push(locationMarker);
        }
    });
}

/**
 * Select an existing location
 */
function selectExistingLocation(id, lat, lng, address) {
    placeMarker({ lat, lng });
    updateLocationFields(lat, lng, address, id);
}

/**
 * Reverse geocode to get address from coordinates
 */
function reverseGeocode(latlng) {
    // Show loading indicator
    document.getElementById('selectedAddress').textContent = 'Getting address...';

    // Using Nominatim for reverse geocoding
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latlng.lat}&lon=${latlng.lng}&zoom=18&addressdetails=1`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            const address = data.display_name || 'Unknown address';
            document.getElementById('station_address').value = address;
            document.getElementById('selectedAddress').textContent = address;
        })
        .catch(error => {
            console.error('Error during reverse geocoding:', error);
            document.getElementById('selectedAddress').textContent = 'Unable to determine address';
            document.getElementById('station_address').value = `Location at ${latlng.lat.toFixed(6)}, ${latlng.lng.toFixed(6)}`;
        });
}

/**
 * Setup form listeners
 */
function setupFormListeners() {
    // Toggle showing other stations
    const showOtherStations = document.getElementById('showOtherStations');
    if (showOtherStations) {
        showOtherStations.addEventListener('change', function () {
            if (this.checked) {
                displayExistingLocations();
            } else {
                existingLocationMarkers.forEach(marker => formMap.removeLayer(marker));
                existingLocationMarkers = [];
            }
        });
    }

    // Search location
    const searchLocationBtn = document.getElementById('searchLocationBtn');
    const locationSearch = document.getElementById('locationSearch');

    if (searchLocationBtn && locationSearch) {
        searchLocationBtn.addEventListener('click', searchLocation);
        locationSearch.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                searchLocation();
                e.preventDefault();
            }
        });
    }

    // Update capacity visualization when values change
    const availableBikes = document.getElementById('availableBikes');
    const totalDocks = document.getElementById('station_totalDocks');

    if (availableBikes && totalDocks) {
        availableBikes.addEventListener('input', updateCapacityVisualization);
        totalDocks.addEventListener('input', updateCapacityVisualization);
    }

    // Save button
    const saveStationBtn = document.getElementById('saveStationBtn');
    if (saveStationBtn) {
        saveStationBtn.addEventListener('click', submitStationForm);
    }
}

/**
 * Submit the station form
 */
function submitStationForm() {
    if (!validateForm()) {
        return;
    }

    // Show loading overlay
    const loadingOverlay = document.getElementById('modalLoadingOverlay');
    if (loadingOverlay) loadingOverlay.style.display = 'flex';

    // Get form data
    const stationData = {
        name: document.getElementById('station_name').value,
        totalDocks: parseInt(document.getElementById('station_totalDocks').value),
        availableBikes: parseInt(document.getElementById('availableBikes').value),
        status: document.getElementById('station_status').value,
        latitude: parseFloat(document.getElementById('station_latitude').value),
        longitude: parseFloat(document.getElementById('station_longitude').value),
        address: document.getElementById('station_address').value,
        locationId: document.getElementById('station_location_id').value || null
    };

    // Submit data to API
    fetch('/admin/bicycle/api/stations', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(stationData)
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Reload page to show new station
                window.location.reload();
            } else {
                alert(data.error || 'Unknown error occurred');
                if (loadingOverlay) loadingOverlay.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error creating station:', error);
            alert('Failed to create station. Please try again.');
            if (loadingOverlay) loadingOverlay.style.display = 'none';
        });
}

/**
 * Validate the form
 */
function validateForm() {
    const form = document.getElementById('stationForm');

    // Add validation classes
    form.classList.add('was-validated');

    // Check if form is valid
    if (!form.checkValidity()) {
        return false;
    }

    // Check if location is selected
    if (!document.getElementById('station_latitude').value ||
        !document.getElementById('station_longitude').value) {
        alert('Please select a location on the map.');
        return false;
    }

    // Check if available bikes doesn't exceed total docks
    const availableBikes = parseInt(document.getElementById('availableBikes').value) || 0;
    const totalDocks = parseInt(document.getElementById('station_totalDocks').value) || 0;

    if (availableBikes > totalDocks) {
        alert('Available bikes cannot exceed total docks.');
        return false;
    }

    return true;
}

/**
 * Search for a location
 */
function searchLocation() {
    const searchInput = document.getElementById('locationSearch').value.trim();
    if (!searchInput) return;

    // Show loading indicator
    const loadingOverlay = document.getElementById('modalLoadingOverlay');
    if (loadingOverlay) loadingOverlay.style.display = 'flex';

    // Use Nominatim for geocoding
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchInput)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data && data.length > 0) {
                const result = data[0];
                const lat = parseFloat(result.lat);
                const lng = parseFloat(result.lon);

                formMap.setView([lat, lng], 15);
                placeMarker({ lat, lng });

                document.getElementById('station_address').value = result.display_name;
                document.getElementById('selectedAddress').textContent = result.display_name;
            } else {
                alert('No locations found for this search term.');
            }
        })
        .catch(error => {
            console.error('Error during geocoding:', error);
            alert('Error searching for location. Please try again.');
        })
        .finally(() => {
            if (loadingOverlay) loadingOverlay.style.display = 'none';
        });
}

/**
 * Update the capacity visualization
 */
function updateCapacityVisualization() {
    const totalDocks = parseInt(document.getElementById('station_totalDocks').value) || 0;
    const availableBikes = parseInt(document.getElementById('availableBikes').value) || 0;

    // Ensure availableBikes doesn't exceed totalDocks
    if (availableBikes > totalDocks) {
        document.getElementById('availableBikes').value = totalDocks;
    }

    // Calculate values
    const updatedAvailableBikes = parseInt(document.getElementById('availableBikes').value) || 0;
    const chargingBikes = 0; // This could be a separate field if needed
    const availableDocks = Math.max(0, totalDocks - updatedAvailableBikes - chargingBikes);

    // Update display
    document.getElementById('availableBikesValue').textContent = updatedAvailableBikes;
    document.getElementById('availableDocksValue').textContent = availableDocks;
    document.getElementById('chargingBikesValue').textContent = chargingBikes;

    // Update progress bar segments
    if (totalDocks > 0) {
        const bikePercent = (updatedAvailableBikes / totalDocks) * 100;
        const dockPercent = (availableDocks / totalDocks) * 100;
        const chargingPercent = (chargingBikes / totalDocks) * 100;

        document.getElementById('bikeSegment').style.width = bikePercent + '%';
        document.getElementById('dockSegment').style.width = dockPercent + '%';
        document.getElementById('chargingSegment').style.width = chargingPercent + '%';
    } else {
        document.getElementById('bikeSegment').style.width = '0%';
        document.getElementById('dockSegment').style.width = '100%';
        document.getElementById('chargingSegment').style.width = '0%';
    }
}