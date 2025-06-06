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

            // Only update capacity after a brief delay to ensure DOM is fully processed
            setTimeout(() => {
                updateCapacityVisualization();
            }, 200);
        });

        // Hide loading overlay when modal is closed
        stationFormModal.addEventListener('hidden.bs.modal', function () {
            const loadingOverlay = document.getElementById('modalLoadingOverlay');
            if (loadingOverlay) loadingOverlay.style.display = 'none';
        });
    }
});

/**
 * Load existing locations from the database
 */
function loadExistingLocations() {
    // Show loading overlay - using both IDs for compatibility
    const loadingOverlay = document.getElementById('modalLoadingOverlay') ||
        document.getElementById('stationModalLoadingOverlay');
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
            if (document.getElementById('showOtherStations') &&
                document.getElementById('showOtherStations').checked) {
                displayExistingLocations();
            }

            // Hide loading overlay - using all possible IDs
            hideAllLoadingOverlays();
        })
        .catch(error => {
            console.error('Error loading locations:', error);
            // Hide loading overlay
            hideAllLoadingOverlays();


        });
}

/**
 * Search for a location
 */
function searchLocation() {
    const searchInput = document.getElementById('locationSearch');
    if (!searchInput || !searchInput.value.trim()) return;

    // Show loading indicator - using both IDs for compatibility
    const loadingOverlay = document.getElementById('modalLoadingOverlay') ||
        document.getElementById('stationModalLoadingOverlay');
    if (loadingOverlay) loadingOverlay.style.display = 'flex';

    // Use Nominatim for geocoding
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchInput.value.trim())}`)
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

                if (formMap) {
                    formMap.setView([lat, lng], 15);
                    placeMarker({ lat, lng });
                }

                const addressField = document.getElementById('station_address');
                const addressDisplay = document.getElementById('selectedAddress');
                if (addressField) addressField.value = result.display_name;
                if (addressDisplay) addressDisplay.textContent = result.display_name;
            } else {
                alert('No locations found for this search term.');
            }
        })
        .catch(error => {
            console.error('Error during geocoding:', error);
            alert('Error searching for location. Please try again.');
        })
        .finally(() => {
            // Hide loading overlay - using all possible IDs
            hideAllLoadingOverlays();
        });
}

/**
 * A helper function to hide all loading overlays regardless of ID
 */
function hideAllLoadingOverlays() {
    // Hide all possible loading overlays by their IDs
    ['modalLoadingOverlay', 'stationModalLoadingOverlay', 'bicycleModalLoadingOverlay'].forEach(id => {
        const overlay = document.getElementById(id);
        if (overlay) {
            overlay.style.display = 'none';
            overlay.style.visibility = 'hidden';
        }
    });

    // Also try to find any element with "LoadingOverlay" in its ID
    document.querySelectorAll('[id$="LoadingOverlay"]').forEach(overlay => {
        if (overlay) {
            overlay.style.display = 'none';
            overlay.style.visibility = 'hidden';
        }
    });
}

/**
 * Validate the station form
 */
function validateForm() {
    const form = document.getElementById('stationForm');
    if (!form) {
        console.error('Create form not found');
        alert('Form not found. Please reload the page.');
        hideAllLoadingOverlays();
        return false;
    }

    // Add validation classes
    form.classList.add('was-validated');

    // Check if form is valid
    if (!form.checkValidity()) {
        hideAllLoadingOverlays();
        return false;
    }

    // Check if location is selected
    if (!document.getElementById('station_latitude') ||
        !document.getElementById('station_longitude') ||
        !document.getElementById('station_latitude').value ||
        !document.getElementById('station_longitude').value) {
        alert('Please select a location on the map.');
        hideAllLoadingOverlays();
        return false;
    }

    // Check station name (try multiple selectors to be safe)
    let nameInput = form.querySelector('#station_name');
    if (!nameInput) {
        // Try using Symfony's naming convention
        nameInput = form.querySelector('[name$="[name]"]');
    }

    if (!nameInput || !nameInput.value.trim()) {
        console.error('Station name input not found or empty', nameInput);
        alert('Station name cannot be empty.');
        if (nameInput) nameInput.focus();
        hideAllLoadingOverlays();
        return false;
    }

    if (nameInput.value.trim().length < 2) {
        alert('Station name must be at least 2 characters long.');
        nameInput.focus();
        hideAllLoadingOverlays();
        return false;
    }

    // Get the field names based on Symfony's naming convention
    let totalDocksInput = form.querySelector('#station_totalDocks');
    if (!totalDocksInput) {
        // Try using Symfony's naming convention
        totalDocksInput = form.querySelector('[name$="[totalDocks]"]');
    }

    if (!totalDocksInput) {
        console.error('Total docks input not found');
        alert('Total docks field not found. Please reload the page.');
        hideAllLoadingOverlays();
        return false;
    }

    // Find availableBikes (this is a custom field, not part of the Symfony form)
    const availableBikesInput = document.getElementById('availableBikes');
    if (!availableBikesInput) {
        console.error('Available bikes input not found');
        alert('Available bikes field not found. Please reload the page.');
        hideAllLoadingOverlays();
        return false;
    }

    const availableBikes = parseInt(availableBikesInput.value) || 0;
    const totalDocks = parseInt(totalDocksInput.value) || 0;

    // Check if available bikes doesn't exceed total docks
    if (availableBikes > totalDocks) {
        alert('Available bikes cannot exceed total docks.');
        availableBikesInput.focus();
        hideAllLoadingOverlays();
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
    // Only run if modal and form are visible
    const modal = document.getElementById('stationFormModal');
    if (!modal || !modal.classList.contains('show')) {
        console.warn('Create modal not open, skipping validation.');
        return;
    }
    if (!validateForm()) {
        return;
    }

    // Show loading overlay
    const loadingOverlay = document.getElementById('modalLoadingOverlay');
    if (loadingOverlay) loadingOverlay.style.display = 'flex';

    // Get the form element
    const form = document.getElementById('stationForm');

    // Create a new FormData object (but we won't use form directly)
    const formData = new FormData();

    // Instead of using FormData(form), manually extract each field to avoid nesting

    // 1. Extract the name from either direct field or the Symfony form field
    const nameInput = form.querySelector('#station_name') ||
        form.querySelector('[name$="[name]"]');
    if (nameInput) {
        formData.append('name', nameInput.value);
    }

    // 2. Extract totalDocks field
    const totalDocksInput = form.querySelector('#station_totalDocks') ||
        form.querySelector('[name$="[totalDocks]"]');
    if (totalDocksInput) {
        formData.append('totalDocks', totalDocksInput.value);
    }

    // 3. Extract status field
    const statusInput = form.querySelector('#station_status') ||
        form.querySelector('[name$="[status]"]');
    if (statusInput) {
        // Ensure we're getting the value attribute for the selected option
        const selectedOption = statusInput.options[statusInput.selectedIndex];
        let statusValue = selectedOption ? selectedOption.value : statusInput.value;

        // Convert numeric status values to string values that the PHP enum expects
        if (statusValue && !isNaN(parseInt(statusValue))) {
            switch (parseInt(statusValue)) {
                case 0:
                    statusValue = 'active';
                    break;
                case 1:
                    statusValue = 'inactive';
                    break;
                case 2:
                    statusValue = 'maintenance';
                    break;
                case 3:
                    statusValue = 'disabled';
                    break;
                default:
                    statusValue = 'active';
            }
        }

        console.log("Mapped status value:", statusValue);
        formData.append('status', statusValue);
    } else {
        // Default to active if no status field found
        console.log("No status field found, defaulting to active");
        formData.append('status', 'active');
    }

    // 4. Add location fields
    formData.append('station_latitude', document.getElementById('station_latitude').value);
    formData.append('station_longitude', document.getElementById('station_longitude').value);
    formData.append('station_address', document.getElementById('station_address').value);

    const locationId = document.getElementById('station_location_id').value;
    if (locationId) {
        formData.append('station_location_id', locationId);
    }

    // 5. Add available bikes field
    const availableBikes = document.getElementById('availableBikes').value;
    formData.append('availableBikes', availableBikes);

    // Log what we're sending
    const formValues = {};
    formData.forEach((value, key) => {
        formValues[key] = value;
    });
    console.log("Form data being submitted:", formValues);

    // Get the action URL
    const actionUrl = form.getAttribute('action') || '/admin/bicycle/station/station/new';
    console.log("Submitting to:", actionUrl);

    // Submit data using AJAX
    fetch(actionUrl, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                // Try to extract more detailed error information
                const contentType = response.headers.get('content-type');

                if (contentType && contentType.includes('application/json')) {
                    // If JSON error, parse it for detailed message
                    return response.json().then(errorData => {
                        throw new Error(errorData.message || `Server error: ${response.status}`);
                    });
                } else if (contentType && contentType.includes('text/html')) {
                    // If HTML error page, try to extract useful information
                    return response.text().then(html => {
                        // Try to extract error message from HTML
                        const errorMatch = html.match(/<div class="exception-message">(.*?)<\/div>/s);
                        const messageMatch = html.match(/<h1.*?>(.*?)<\/h1>/s);
                        const extractedError = errorMatch ? errorMatch[1].trim() :
                            messageMatch ? messageMatch[1].trim() :
                                `Server error: ${response.status}`;

                        console.error("HTML Error Response:", html.substring(0, 1000) + "...");
                        throw new Error(extractedError);
                    });
                }

                throw new Error(`Network response was not ok: ${response.status}`);
            }

            // Normal JSON response
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Show success message
                showNotification('success', 'Station saved successfully');

                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('stationFormModal'));
                if (modal) modal.hide();

                // Check if we have a redirect URL and navigate to it
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    // Fallback - reload page after a short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            } else {
                showNotification('error', data.message || 'Error saving station');
                if (loadingOverlay) loadingOverlay.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error saving station:', error);
            showNotification('error', error.message || 'Failed to save station. Please try again.');
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
 * Update the capacity visualization
 */
function updateCapacityVisualization() {
    const form = document.getElementById('stationForm');
    if (!form) {
        console.warn('updateCapacityVisualization: stationForm not found');
        return;
    }
    // Get the totalDocks input using the Symfony naming pattern
    const totalDocksInput = form.querySelector('[name$="[totalDocks]"]');
    if (!totalDocksInput) {
        console.warn('updateCapacityVisualization: totalDocks input not found');
        return;
    }
    const totalDocks = parseInt(totalDocksInput.value) || 0;
    const availableBikesInput = document.getElementById('availableBikes');
    if (!availableBikesInput) {
        console.warn('updateCapacityVisualization: availableBikes input not found');
        return;
    }
    const availableBikes = parseInt(availableBikesInput.value) || 0;
    // Ensure availableBikes doesn't exceed totalDocks
    if (availableBikes > totalDocks) {
        availableBikesInput.value = totalDocks;
    }
    // Calculate values
    const updatedAvailableBikes = parseInt(availableBikesInput.value) || 0;
    const chargingBikes = 0; // This could be a separate field if needed
    const availableDocks = Math.max(0, totalDocks - updatedAvailableBikes - chargingBikes);
    // Update display
    const bikesValue = document.getElementById('availableBikesValue');
    const docksValue = document.getElementById('availableDocksValue');
    const chargingValue = document.getElementById('chargingBikesValue');
    if (bikesValue) bikesValue.textContent = updatedAvailableBikes;
    if (docksValue) docksValue.textContent = availableDocks;
    if (chargingValue) chargingValue.textContent = chargingBikes;
    // Update progress bar segments
    const bikeSegment = document.getElementById('bikeSegment');
    const dockSegment = document.getElementById('dockSegment');
    const chargingSegment = document.getElementById('chargingSegment');
    if (totalDocks > 0) {
        const bikePercent = (updatedAvailableBikes / totalDocks) * 100;
        const dockPercent = (availableDocks / totalDocks) * 100;
        const chargingPercent = (chargingBikes / totalDocks) * 100;
        if (bikeSegment) bikeSegment.style.width = bikePercent + '%';
        if (dockSegment) dockSegment.style.width = dockPercent + '%';
        if (chargingSegment) chargingSegment.style.width = chargingPercent + '%';
    } else {
        if (bikeSegment) bikeSegment.style.width = '0%';
        if (dockSegment) dockSegment.style.width = '100%';
        if (chargingSegment) chargingSegment.style.width = '0%';
    }
}

// Expose functions for global access
window.initializeStationFormModal = {
    submitStationForm,
    initializeFormMap,
    updateCapacityVisualization
};