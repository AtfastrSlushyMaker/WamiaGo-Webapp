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

        // Also set up popup edit buttons (from map popups)
        if (typeof window.setupPopupEditButtons === 'function') {
            window.setupPopupEditButtons();
        }

        // Clean up any lingering loading overlays on page load
        const editModalLoadingOverlay = document.getElementById('editModalLoadingOverlay');
        if (editModalLoadingOverlay) {
            editModalLoadingOverlay.style.display = 'none';
        }
    });

    // ======== Public Functions (Module Exports) ========

    /**
     * Initialize the station edit modal - Public function exposed to window
     * @param {number} stationId Station ID to edit
     */
    window.initEditStationModal = function (stationId) {
        console.log("Opening edit modal for station ID:", stationId);
        showLoadingOverlay(true);

        // Fix the URL path to match the route defined in StationAdminController
        fetch(`/admin/bicycle/station/${stationId}/edit-form`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (!response.ok) {
                    // *** Add URL to error message for clarity ***
                    throw new Error(`Server responded with ${response.status}: ${response.statusText} while fetching edit form`);
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

                // Initialize the modal with positioning options
                const modalElement = document.getElementById('stationEditModal');
                if (!modalElement) {
                    throw new Error("Modal element not found in response HTML");
                }

                // Set modal to appear in the center of the current viewport
                const viewportHeight = window.innerHeight;
                const scrollTop = window.scrollY || document.documentElement.scrollTop;

                // Calculate the position relative to current viewport
                const modalOptions = {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                };

                const modal = new bootstrap.Modal(modalElement, modalOptions);

                // Position the modal based on current scroll position
                modalElement.style.paddingTop = '0';

                // Add event listener for when modal is about to be shown
                modalElement.addEventListener('show.bs.modal', function () {
                    // Adjust the positioning relative to current scroll position
                    const modalDialog = modalElement.querySelector('.modal-dialog');
                    if (modalDialog) {
                        // Reset any previous manual positioning
                        modalDialog.style.marginTop = '';
                    }
                });

                // Add event listener for when the modal is hidden - to clean up loading overlay
                modalElement.addEventListener('hidden.bs.modal', function () {
                    showLoadingOverlay(false);
                });

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
            showLoadingOverlay(false);
            return;
        }

        // Get coordinates from form inputs
        const latitude = parseFloat(latitudeInput.value) || 36.8065;
        const longitude = parseFloat(longitudeInput.value) || 10.1815;

        // Get map element
        const mapElement = document.getElementById('stationEditMap');
        if (!mapElement) {
            console.error('Map element not found');
            showLoadingOverlay(false);
            return;
        }

        // Force map container to have explicit dimensions
        mapElement.style.height = '350px';
        mapElement.style.width = '100%';

        try {
            // Initialize map
            if (!editMap) {
                editMap = L.map('stationEditMap').setView([latitude, longitude], 15);

                // Use the same tile layer as the create form
                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                    maxZoom: 19,
                    subdomains: 'abcd'
                }).addTo(editMap);

                // Force map to render correctly with a delay
                setTimeout(() => {
                    editMap.invalidateSize();
                }, 200);
            } else {
                // Update existing map
                editMap.setView([latitude, longitude], 15);
                editMap.invalidateSize();
            }

            // Add marker with the same style as create form
            if (editMarker) {
                editMarker.setLatLng([latitude, longitude]);
            } else {
                editMarker = L.marker([latitude, longitude], {
                    draggable: true,
                    icon: L.divIcon({
                        className: 'custom-marker-icon',
                        html: '<div style="background-color: #6571ff; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.3); border: 2px solid white;"><i class="ti ti-map-pin" style="color: white; font-size: 20px;"></i></div>',
                        iconSize: [36, 36],
                        iconAnchor: [18, 36],
                        popupAnchor: [0, -36]
                    })
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
            showLoadingOverlay(false);
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
        if (!form) {
            console.error('Edit form not found', form);
            showToast('Form validation error: Edit form not found', 'danger');
            return false;
        }

        // Validate name
        // FIRST TRY: Try to get the name input by ID
        let nameInput = form.querySelector('#edit_name');

        // SECOND TRY: Try using name attribute (Symfony form style)
        if (!nameInput) {
            nameInput = form.querySelector('[name$="[name]"]');
        }

        console.log('validateEditForm: nameInput', nameInput, nameInput ? nameInput.value : null);

        if (!nameInput || !nameInput.value || !nameInput.value.trim()) {
            showToast('Station name cannot be empty', 'danger');
            if (nameInput) nameInput.focus();
            return false;
        }

        if (nameInput.value.trim().length < 2) {
            showToast('Station name must be at least 2 characters long', 'danger');
            nameInput.focus();
            return false;
        }

        // Validate total docks - Try multiple ways to find the field
        let totalDocksInput = form.querySelector('#edit_totalDocks');
        if (!totalDocksInput) {
            totalDocksInput = form.querySelector('[name$="[totalDocks]"]');
        }
        if (!totalDocksInput) {
            totalDocksInput = form.querySelector('input[name*="totalDocks" i]');
        }

        console.log('validateEditForm: totalDocksInput', totalDocksInput, totalDocksInput ? totalDocksInput.value : null);

        if (!totalDocksInput) {
            showToast('Form validation error: Total docks input not found', 'danger');
            return false;
        }

        const totalDocks = parseInt(totalDocksInput.value);
        if (isNaN(totalDocks) || totalDocks < 1) {
            showToast('Total docks must be at least 1', 'danger');
            totalDocksInput.focus();
            return false;
        }

        // Validate available bikes
        let availableBikesInput = form.querySelector('#edit_availableBikes');
        if (!availableBikesInput) {
            availableBikesInput = document.querySelector('#edit_availableBikes');
        }

        if (availableBikesInput) {
            const availableBikes = parseInt(availableBikesInput.value);
            if (isNaN(availableBikes) || availableBikes < 0) {
                showToast('Available bikes cannot be negative', 'danger');
                availableBikesInput.focus();
                return false;
            }
            if (availableBikes > totalDocks) {
                showToast('Available bikes cannot exceed total docks', 'danger');
                availableBikesInput.focus();
                return false;
            }
        }
        return true;
    }

    function setupEditForm() {
        const updateBtn = document.getElementById('updateStationBtn');
        const form = document.getElementById('stationEditForm');
        if (updateBtn && form) {
            updateBtn.addEventListener('click', function () {
                // Only run if modal and form are visible
                const modal = document.getElementById('stationEditModal');
                if (!modal || !modal.classList.contains('show')) {
                    console.warn('Edit modal not open, skipping validation.');
                    return;
                }
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

                // Create a new FormData object manually instead of from the form
                const formData = new FormData();

                // Extract form fields and add them with flat keys instead of nested arrays

                // Get the name field
                const nameInput = form.querySelector('#bicycle_station_name') ||
                    form.querySelector('[name$="[name]"]');
                if (nameInput) {
                    formData.append('name', nameInput.value);
                }

                // Get the status field
                const statusInput = form.querySelector('#bicycle_station_status') ||
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

                    console.log("Mapped status value in edit form:", statusValue);
                    formData.append('status', statusValue);
                }

                // Get the totalDocks field
                const totalDocksInput = form.querySelector('#bicycle_station_totalDocks') ||
                    form.querySelector('[name$="[totalDocks]"]');
                if (totalDocksInput) {
                    formData.append('totalDocks', totalDocksInput.value);
                }

                // Get available bikes field
                const availableBikesInput = document.getElementById('edit_availableBikes');
                if (availableBikesInput) {
                    formData.append('availableBikes', availableBikesInput.value);
                }

                // Add location data
                const latitudeInput = document.getElementById('edit_latitude');
                const longitudeInput = document.getElementById('edit_longitude');
                const addressInput = document.getElementById('edit_address');

                if (latitudeInput && longitudeInput) {
                    formData.append('latitude', latitudeInput.value);
                    formData.append('longitude', longitudeInput.value);
                    if (addressInput) {
                        formData.append('address', addressInput.value);
                    }
                }

                // Get the station ID
                const stationIdInput = document.getElementById('stationId');
                const stationId = stationIdInput ? stationIdInput.value :
                    form.getAttribute('data-station-id') ||
                    (form.action && form.action.match(/\/station\/(\d+)\/edit/)?.[1]);

                // Add station ID to form data
                if (stationId) {
                    formData.append('stationId', stationId);
                }

                // Include the CSRF token
                const csrfTokenInput = form.querySelector('input[name="_token"]') ||
                    form.querySelector('input[name="bicycle_station[_token]"]');
                if (csrfTokenInput) {
                    formData.append('_token', csrfTokenInput.value);
                }

                // Log what we're submitting
                const formValues = {};
                formData.forEach((value, key) => {
                    formValues[key] = value;
                });
                console.log("Edit form data being submitted:", formValues);

                // Submit form via AJAX to the correct endpoint
                fetch(`/admin/bicycle/station/${stationId}/edit`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(response => {
                        // Check if response is OK
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
                                    console.error("Server HTML Error:", html.substring(0, 1000) + "...");

                                    // Try to extract error message from HTML
                                    const errorMatch = html.match(/<div class="exception-message">(.*?)<\/div>/s);
                                    const messageMatch = html.match(/<h1.*?>(.*?)<\/h1>/s);
                                    const extractedError = errorMatch ? errorMatch[1].trim() :
                                        messageMatch ? messageMatch[1].trim() :
                                            `Server error: ${response.status}`;

                                    throw new Error(extractedError);
                                });
                            }

                            throw new Error(`Server responded with ${response.status}`);
                        }

                        // Check content type for normal handling
                        const contentType = response.headers.get("content-type");
                        if (contentType && contentType.includes("application/json")) {
                            return response.json();
                        } else {
                            return response.text().then(text => {
                                console.error("Unexpected non-JSON response:", text.substring(0, 1000) + "...");
                                throw new Error("Unexpected server response format");
                            });
                        }
                    })
                    .then(data => {
                        if (data.success) {
                            showToast('Station updated successfully', 'success');
                            // Close the modal
                            const modal = bootstrap.Modal.getInstance(document.getElementById('stationEditModal'));
                            if (modal) modal.hide();

                            // If a redirect URL is provided, navigate to it
                            if (data.redirect) {
                                window.location.href = data.redirect;
                                return;
                            }

                            // Otherwise, update the UI or reload
                            if (typeof window.updateStationUI === 'function') {
                                // Pass the updated station data received from the server
                                window.updateStationUI(data.station);
                            } else {
                                console.warn("window.updateStationUI function not found. UI will not be updated automatically. Reloading page...");
                                // Reload the page to see the changes
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
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