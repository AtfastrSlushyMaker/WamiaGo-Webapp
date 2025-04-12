/**
 * Station Edit Modal
 * 
 * Handles the edit modal functionality for bicycle stations
 */
(function () {
    // ======== Global variables and module state ========
    let editMap = null;
    let editMarker = null;

    // ======== Initialize on document ready ========
    document.addEventListener('DOMContentLoaded', function () {
        console.log("Station edit modal JS loaded");
        // Set up listeners for station edit buttons
        setupEditButtonListeners();
    });

    // ======== Public Functions (Module Exports) ========

    /**
     * Initialize the station edit modal - Public function exposed to window
     * @param {number} stationId Station ID to edit
     */
    window.initEditStationModal = function (stationId) {
        console.log("Opening edit modal for station ID:", stationId);
        showLoadingOverlay(true);

        // Fetch station data from server
        fetch(`/admin/bicycle/station/${stationId}/edit`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Server responded with ${response.status}: ${response.statusText}`);
                }
                return response.text();
            })
            .then(html => {
                // Create a temporary container to hold the modal content
                const tempContainer = document.createElement('div');
                tempContainer.innerHTML = html;

                // Replace or append modal content
                const existingModal = document.getElementById('stationEditModal');
                if (existingModal) {
                    existingModal.remove();
                }

                // Append the modal HTML to the document body
                document.body.appendChild(tempContainer.firstElementChild);

                // Initialize the modal
                const modalElement = document.getElementById('stationEditModal');
                if (!modalElement) {
                    throw new Error("Modal element not found in response HTML");
                }

                const modal = new bootstrap.Modal(modalElement);
                modal.show();

                // Initialize map after modal is shown
                modalElement.addEventListener('shown.bs.modal', function () {
                    initEditMap();
                    showLoadingOverlay(false);
                });

                // Set up form submission
                setupEditForm();
            })
            .catch(error => {
                console.error('Error loading edit modal:', error);
                showLoadingOverlay(false);
                showToast('Failed to load station data. Please try again: ' + error.message, 'danger');
            });
    };

    // ======== Private Functions ========

    /**
     * Show or hide the loading overlay
     * @param {boolean} show Whether to show or hide
     */
    function showLoadingOverlay(show) {
        const loadingOverlay = document.getElementById('editModalLoadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = show ? 'flex' : 'none';
        } else {
            console.error("Loading overlay element not found");
        }
    }

    /**
     * Initialize the map in the edit modal
     */
    function initEditMap() {
        const latitudeInput = document.getElementById('edit_latitude');
        const longitudeInput = document.getElementById('edit_longitude');

        if (!latitudeInput || !longitudeInput) {
            console.error('Latitude or longitude inputs not found');
            return;
        }

        // Get coordinates from form inputs
        const latitude = parseFloat(latitudeInput.value) || 0;
        const longitude = parseFloat(longitudeInput.value) || 0;

        // Get map element
        const mapElement = document.getElementById('stationEditMap');
        if (!mapElement) {
            console.error('Map element not found');
            return;
        }

        // Force map container to have explicit dimensions
        mapElement.style.height = '350px';
        mapElement.style.width = '100%';

        try {
            // Initialize or update map
            if (!editMap) {
                editMap = L.map('stationEditMap').setView([latitude, longitude], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(editMap);

                // Force map to render correctly
                setTimeout(() => {
                    editMap.invalidateSize();
                }, 100);
            } else {
                editMap.setView([latitude, longitude], 15);
                editMap.invalidateSize();
            }

            // Add marker
            if (editMarker) {
                editMarker.setLatLng([latitude, longitude]);
            } else {
                editMarker = L.marker([latitude, longitude], {
                    draggable: true
                }).addTo(editMap);

                // Update coordinates when marker is dragged
                editMarker.on('dragend', function () {
                    const position = editMarker.getLatLng();
                    document.getElementById('edit_latitude').value = position.lat.toFixed(6);
                    document.getElementById('edit_longitude').value = position.lng.toFixed(6);

                    // Update address via reverse geocoding
                    updateAddressFromCoordinates(position.lat, position.lng);
                });
            }

            // Set up map click event
            editMap.on('click', function (e) {
                editMarker.setLatLng(e.latlng);
                document.getElementById('edit_latitude').value = e.latlng.lat.toFixed(6);
                document.getElementById('edit_longitude').value = e.latlng.lng.toFixed(6);

                // Update address via reverse geocoding
                updateAddressFromCoordinates(e.latlng.lat, e.latlng.lng);
            });

            // Update marker when coordinates are changed manually
            document.getElementById('edit_latitude').addEventListener('change', updateEditMarker);
            document.getElementById('edit_longitude').addEventListener('change', updateEditMarker);
        } catch (error) {
            console.error("Error initializing map:", error);
            mapElement.innerHTML = `<div class="alert alert-danger m-3">
                <i class="ti ti-alert-triangle me-2"></i> Error initializing map: ${error.message}
            </div>`;
        }
    }

    /**
     * Update marker position from form inputs
     */
    function updateEditMarker() {
        const lat = parseFloat(document.getElementById('edit_latitude').value);
        const lng = parseFloat(document.getElementById('edit_longitude').value);

        if (!isNaN(lat) && !isNaN(lng) && editMarker && editMap) {
            editMarker.setLatLng([lat, lng]);
            editMap.setView([lat, lng], 15);
        }
    }

    /**
     * Update address from coordinates using geocoding
     * @param {number} lat Latitude
     * @param {number} lng Longitude
     */
    function updateAddressFromCoordinates(lat, lng) {
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
            .then(response => response.json())
            .then(data => {
                const addressInput = document.getElementById('edit_address');
                if (addressInput) {
                    addressInput.value = data.display_name || '';
                }
            })
            .catch(error => console.error('Error getting address:', error));
    }
    function validateEditForm(form) {
        // Validate name
        const nameInput = form.querySelector('#edit_name');
        if (!nameInput.value.trim()) {
            showToast('Station name cannot be empty', 'danger');
            nameInput.focus();
            return false;
        }

        if (nameInput.value.trim().length < 2) {
            showToast('Station name must be at least 2 characters long', 'danger');
            nameInput.focus();
            return false;
        }

        // Validate total docks
        const totalDocksInput = form.querySelector('#edit_totalDocks');
        const totalDocks = parseInt(totalDocksInput.value);
        if (isNaN(totalDocks) || totalDocks < 1) {
            showToast('Total docks must be at least 1', 'danger');
            totalDocksInput.focus();
            return false;
        }

        // Validate available bikes
        const bikesInput = form.querySelector('#edit_availableBikes');
        const bikes = parseInt(bikesInput.value);
        if (isNaN(bikes) || bikes < 0) {
            showToast('Available bikes cannot be negative', 'danger');
            bikesInput.focus();
            return false;
        }

        // Validate bikes <= total docks
        if (bikes > totalDocks) {
            showToast('Available bikes cannot exceed total docks', 'danger');
            bikesInput.focus();
            return false;
        }

        // Validate latitude and longitude
        const latInput = document.getElementById('edit_latitude');
        const lngInput = document.getElementById('edit_longitude');

        if (!latInput.value || !lngInput.value) {
            showToast('Please set a location for the station', 'danger');
            return false;
        }

        const lat = parseFloat(latInput.value);
        const lng = parseFloat(lngInput.value);

        if (isNaN(lat) || lat < -90 || lat > 90) {
            showToast('Latitude must be between -90 and 90', 'danger');
            latInput.focus();
            return false;
        }

        if (isNaN(lng) || lng < -180 || lng > 180) {
            showToast('Longitude must be between -180 and 180', 'danger');
            lngInput.focus();
            return false;
        }

        return true;
    }
    function setupEditForm() {
        const updateBtn = document.getElementById('updateStationBtn');
        const form = document.getElementById('stationEditForm');

        if (updateBtn && form) {
            updateBtn.addEventListener('click', function () {
                // Validate the form first
                if (!validateEditForm(form)) {
                    return;
                }

                // Update form fields with map values
                if (editMarker) {
                    document.getElementById('edit_latitude').value = editMarker.getLatLng().lat.toFixed(6);
                    document.getElementById('edit_longitude').value = editMarker.getLatLng().lng.toFixed(6);
                }

                // Show loading state
                updateBtn.innerHTML = '<i class="ti ti-loader ti-spin me-1"></i> Saving...';
                updateBtn.disabled = true;

                // Get form data
                const formData = new FormData(form);

                // Get the station ID from the hidden input or form action URL
                const stationId = formData.get('stationId') || form.getAttribute('data-station-id') ||
                    (form.action && form.action.match(/\/station\/(\d+)\/edit/)?.[1]);

                if (!stationId) {
                    console.error('Station ID not found');
                    showToast('Error: Station ID not found', 'danger');
                    updateBtn.innerHTML = '<i class="ti ti-device-floppy me-1"></i> Update Station';
                    updateBtn.disabled = false;
                    return;
                }

                // Submit form via AJAX to the correct endpoint
                // Corrected URL path based on debug:router output
                fetch(`/admin/bicycle/station/station/${stationId}/edit`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json' // Explicitly accept JSON
                    }
                })
                    .then(response => {
                        // Check content type before parsing JSON
                        const contentType = response.headers.get("content-type");
                        if (contentType && contentType.indexOf("application/json") !== -1) {
                            if (!response.ok) {
                                // If JSON error response, parse it
                                return response.json().then(data => {
                                    throw new Error(data.message || `Server responded with ${response.status}`);
                                });
                            }
                            return response.json();
                        } else {
                            // If not JSON, likely an HTML error page
                            return response.text().then(text => {
                                console.error("Server response was not JSON:", text);
                                // Extract a snippet or title if possible, otherwise generic error
                                const titleMatch = text.match(/<title>(.*?)<\/title>/i);
                                const errorHint = titleMatch ? titleMatch[1] : 'Unexpected server response format.';
                                throw new Error(`Failed to update station: ${errorHint} (Status: ${response.status})`);
                            });
                        }
                    })
                    .then(data => {
                        if (data.success) {
                            showToast('Station updated successfully', 'success');
                            // Close the modal
                            const modal = bootstrap.Modal.getInstance(document.getElementById('stationEditModal'));
                            if (modal) modal.hide();

                            // Instead of reloading, call a function to update the UI
                            if (typeof window.updateStationUI === 'function') {
                                // Pass the updated station data received from the server
                                window.updateStationUI(data.station);
                            } else {
                                console.warn("window.updateStationUI function not found. UI will not be updated automatically. Please refresh manually.");
                                // Optionally, still provide a manual refresh hint
                                showToast('Station updated. Refresh page to see changes.', 'info');
                            }
                        } else {
                            // Handle application-level errors returned as JSON
                            throw new Error(data.message || 'Update failed with unspecified error.');
                        }
                    })
                    .catch(error => {
                        console.error('Error updating station:', error);
                        // Display the refined error message from the promise chain
                        showToast(error.message || 'Failed to update station', 'danger');
                    })
                    .finally(() => {
                        updateBtn.innerHTML = '<i class="ti ti-device-floppy me-1"></i> Update Station';
                        updateBtn.disabled = false;
                    });
            });
        }
    }

    /**
     * Set up event listeners for edit buttons
     */
    function setupEditButtonListeners() {
        document.querySelectorAll('.station-edit-btn').forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                const stationId = this.getAttribute('data-station-id');
                if (stationId) {
                    window.initEditStationModal(stationId);
                } else {
                    console.error("No station ID found on button", this);
                }
            });
        });
    }

    /**
     * Show toast notification
     * @param {string} message Message to display
     * @param {string} type Toast type (success, danger, info)
     */
    function showToast(message, type = 'info') {
        // Create toast container if it doesn't exist
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }

        // Create the toast element
        const toastElement = document.createElement('div');
        toastElement.className = `toast align-items-center text-white bg-${type} border-0`;
        toastElement.setAttribute('role', 'alert');
        toastElement.setAttribute('aria-live', 'assertive');
        toastElement.setAttribute('aria-atomic', 'true');

        toastElement.innerHTML = `
                        <div class="d-flex">
                            <div class="toast-body">
                                ${message}
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    `;

        toastContainer.appendChild(toastElement);

        const toast = new bootstrap.Toast(toastElement, {
            delay: 5000
        });

        toast.show();

        // Remove toast from DOM after it's hidden
        toastElement.addEventListener('hidden.bs.toast', function () {
            this.remove();
        });
    }

    // Make the setupPopupEditButtons function global
    window.setupPopupEditButtons = function () {
        // Global event listener for popup edit buttons
        document.addEventListener('click', function (e) {
            if (e.target && e.target.closest('.popup-edit-btn')) {
                e.preventDefault();
                const button = e.target.closest('.popup-edit-btn');
                const stationId = button.getAttribute('data-station-id');

                // Open the edit modal
                if (typeof window.initEditStationModal === 'function') {
                    window.initEditStationModal(stationId);
                } else {
                    console.error('Edit modal function not found');
                }
            }
        });
    };
})();