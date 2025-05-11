/**
 * Edit Bicycle Form Handler
 * Standalone script to handle edit bicycle modal functionality
 */
document.addEventListener('DOMContentLoaded', function () {
    // Form validation function
    function validateBicycleForm(form) {
        let isValid = true;
        const errorMessages = [];

        // Battery Level validation
        const batteryInput = form.querySelector('[name$="[batteryLevel]"]');
        if (batteryInput) {
            const batteryValue = parseFloat(batteryInput.value);
            if (isNaN(batteryValue) || batteryValue < 0 || batteryValue > 100) {
                isValid = false;
                addError(batteryInput, 'Battery level must be between 0 and 100%');
                errorMessages.push('Battery level must be between 0 and 100%');
            } else {
                removeError(batteryInput);
            }
        }

        // Range validation
        const rangeInput = form.querySelector('[name$="[rangeKm]"]');
        if (rangeInput) {
            const rangeValue = parseFloat(rangeInput.value);
            if (isNaN(rangeValue) || rangeValue < 0) {
                isValid = false;
                addError(rangeInput, 'Range must be a positive number');
                errorMessages.push('Range must be a positive number');
            } else {
                removeError(rangeInput);
            }
        }

        // Status validation
        const statusInput = form.querySelector('[name$="[status]"]');
        if (statusInput && statusInput.value === '') {
            isValid = false;
            addError(statusInput, 'Status is required');
            errorMessages.push('Status is required');
        } else {
            removeError(statusInput);
        }

        // Last updated validation
        const lastUpdatedInput = form.querySelector('[name$="[lastUpdated]"]');
        if (lastUpdatedInput && lastUpdatedInput.value === '') {
            isValid = false;
            addError(lastUpdatedInput, 'Last updated date is required');
            errorMessages.push('Last updated date is required');
        } else {
            removeError(lastUpdatedInput);
        }

        // Station validation - Only if bicycle status is "Available"
        const stationInput = form.querySelector('[name$="[bicycleStation]"]');
        if (statusInput && statusInput.value === 'available' && stationInput && stationInput.value === '') {
            isValid = false;
            addError(stationInput, 'Station is required for available bicycles');
            errorMessages.push('Station is required for available bicycles');
        } else if (stationInput) {
            removeError(stationInput);
        }

        return { isValid, errorMessages };
    }

    // Add error indicator and message
    function addError(input, message) {
        input.classList.add('is-invalid');

        // Create or update error message
        let errorDiv = input.parentNode.querySelector('.invalid-feedback');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            input.parentNode.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
    }

    // Remove error indicator and message
    function removeError(input) {
        input.classList.remove('is-invalid');
        const errorDiv = input.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.textContent = '';
        }
    }

    // Handle loading bicycle data for editing
    function setupEditBicycleButtons() {
        document.querySelectorAll('.edit-bicycle').forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                const bicycleId = this.getAttribute('data-bicycle-id');

                if (bicycleId) {
                    // Show loading state
                    const modal = new bootstrap.Modal(document.getElementById('editBicycleModal'));
                    const modalBody = document.getElementById('editBicycleModal').querySelector('.modal-body');

                    // Add loading indicator
                    modalBody.innerHTML = `
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary mb-3" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p>Loading bicycle data...</p>
                        </div>
                    `;

                    modal.show();

                    // Fetch bicycle data with improved error handling
                    fetch(`/admin/bicycle/${bicycleId}/data`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`Server returned ${response.status}: ${response.statusText}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('Bicycle data received:', data);

                            // Get the form template and clone it properly
                            const templateContent = document.getElementById('editBicycleFormTemplate');
                            if (!templateContent) {
                                throw new Error('Edit bicycle form template not found in the DOM');
                            }

                            const formClone = document.importNode(templateContent.content, true);

                            // Clear the modal body and append the cloned form
                            modalBody.innerHTML = '';
                            modalBody.appendChild(formClone);

                            // Get the form that was just added to the DOM
                            const editForm = modalBody.querySelector('#editBicycleForm');

                            // Set form hidden id field
                            const idField = editForm.querySelector('input[name="bicycleId"]');
                            if (idField) idField.value = bicycleId;

                            // Set form action URL - make sure it points to the correct controller action
                            const formAction = `/admin/bicycle/${bicycleId}/edit`;
                            editForm.setAttribute('action', formAction);
                            editForm.setAttribute('data-action-url', formAction);

                            // Set form fields values
                            const batteryField = editForm.querySelector('[name$="[batteryLevel]"]');
                            if (batteryField) batteryField.value = data.batteryLevel;

                            const rangeField = editForm.querySelector('[name$="[rangeKm]"]');
                            if (rangeField) rangeField.value = data.rangeKm;

                            const statusSelect = editForm.querySelector('[name$="[status]"]');
                            if (statusSelect && data.status) {
                                Array.from(statusSelect.options).forEach(option => {
                                    if (option.value === data.status) {
                                        option.selected = true;
                                    }
                                });
                            }

                            const stationSelect = editForm.querySelector('[name$="[bicycleStation]"]');
                            if (stationSelect && data.bicycleStation) {
                                Array.from(stationSelect.options).forEach(option => {
                                    if (option.value == data.bicycleStation) {
                                        option.selected = true;
                                    }
                                });
                            }

                            const lastUpdatedField = editForm.querySelector('[name$="[lastUpdated]"]');
                            if (lastUpdatedField) lastUpdatedField.value = data.lastUpdated;

                            // Add a hidden field to preserve the tab parameter
                            const urlParams = new URLSearchParams(window.location.search);
                            if (urlParams.has('tab')) {
                                let tabInput = document.createElement('input');
                                tabInput.type = 'hidden';
                                tabInput.name = 'tab';
                                tabInput.value = urlParams.get('tab');
                                editForm.appendChild(tabInput);
                            }

                            // Setup form submission handler
                            editForm.addEventListener('submit', handleEditFormSubmit);

                            // Setup real-time field validation
                            editForm.querySelectorAll('input, select').forEach(input => {
                                input.addEventListener('change', function () {
                                    if (this.name.includes('batteryLevel')) {
                                        const value = parseFloat(this.value);
                                        if (isNaN(value) || value < 0 || value > 100) {
                                            addError(this, 'Battery level must be between 0 and 100%');
                                        } else {
                                            removeError(this);
                                        }
                                    } else if (this.name.includes('rangeKm')) {
                                        const value = parseFloat(this.value);
                                        if (isNaN(value) || value < 0) {
                                            addError(this, 'Range must be a positive number');
                                        } else {
                                            removeError(this);
                                        }
                                    } else if (this.name.includes('status')) {
                                        if (this.value === '') {
                                            addError(this, 'Status is required');
                                        } else {
                                            removeError(this);

                                            // Check if status is "Available", then station is required
                                            const stationInput = editForm.querySelector('[name$="[bicycleStation]"]');
                                            if (this.value === 'available' && stationInput && stationInput.value === '') {
                                                addError(stationInput, 'Station is required for available bicycles');
                                            } else if (stationInput) {
                                                removeError(stationInput);
                                            }
                                        }
                                    } else if (this.name.includes('bicycleStation')) {
                                        const statusInput = editForm.querySelector('[name$="[status]"]');
                                        if (statusInput && statusInput.value === 'available' && this.value === '') {
                                            addError(this, 'Station is required for available bicycles');
                                        } else {
                                            removeError(this);
                                        }
                                    } else if (this.name.includes('lastUpdated')) {
                                        if (this.value === '') {
                                            addError(this, 'Last updated date is required');
                                        } else {
                                            removeError(this);
                                        }
                                    }
                                });
                            });
                        })
                        .catch(error => {
                            console.error('Error loading bicycle data:', error);
                            modalBody.innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Error loading bicycle data:</strong> ${error.message}
                                </div>
                                <div class="text-center mt-3">
                                    <button type="button" class="btn btn-primary reload-form-btn">
                                        <i class="fas fa-sync-alt me-2"></i> Try Again
                                    </button>
                                </div>
                            `;

                            // Add event listener for the reload button
                            const reloadBtn = modalBody.querySelector('.reload-form-btn');
                            if (reloadBtn) {
                                reloadBtn.addEventListener('click', function () {
                                    button.click();
                                });
                            }
                        });
                }
            });
        });
    }

    // Handle edit form submission
    function handleEditFormSubmit(e) {
        e.preventDefault();

        const editForm = e.target;
        const { isValid, errorMessages } = validateBicycleForm(editForm);

        if (!isValid) {
            // Show validation errors
            const errorAlert = document.createElement('div');
            errorAlert.className = 'alert alert-danger mt-3';
            errorAlert.innerHTML = '<strong>Error:</strong> Please correct the following issues:<ul>' +
                errorMessages.map(msg => `<li>${msg}</li>`).join('') + '</ul>';

            // Remove existing alerts
            const existingAlerts = editForm.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());

            // Add new alert
            editForm.insertAdjacentElement('afterbegin', errorAlert);

            // Focus first error
            const firstError = editForm.querySelector('.is-invalid');
            if (firstError) firstError.focus();
        } else {
            // Add tab parameter
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('tab')) {
                let tabInput = editForm.querySelector('input[name="tab"]');
                if (!tabInput) {
                    tabInput = document.createElement('input');
                    tabInput.type = 'hidden';
                    tabInput.name = 'tab';
                    editForm.appendChild(tabInput);
                }
                tabInput.value = urlParams.get('tab');
            }

            // Get bicycle ID and set form action
            const bicycleId = editForm.querySelector('.bicycle-id-field').value;
            if (!bicycleId) {
                console.error('No bicycle ID found in the form');
                return;
            }

            // Set form action and submit
            const formAction = `/admin/bicycle/${bicycleId}/edit`;
            editForm.action = formAction;
            console.log(`Submitting form to ${formAction} with bicycle ID ${bicycleId}`);

            // Actually submit the form
            editForm.submit();
        }
    }

    // Initialize edit bicycle functionality
    setupEditBicycleButtons();
});
