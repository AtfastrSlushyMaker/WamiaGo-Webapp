/**
 * WamiaGo Bicycle Management
 * Unified script for bicycle management functionality
 */
document.addEventListener('DOMContentLoaded', function () {
    console.log('WamiaGo Bicycle Management initialized');

    // -----------------------------------------------------
    // Modal Positioning & Visibility
    // -----------------------------------------------------    // Fix Bootstrap modal styling and add form enhancements
    const style = document.createElement('style');
    style.innerHTML = `
        /* Modal Positioning Fixes */
        .modal {
            padding-right: 0 !important;
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
        }
        
        /* Show modal properly when .show class is applied */
        .modal.show {
            display: block !important;
        }
        
        /* Fix backdrop issues */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1040;
            width: 100vw;
            height: 100vh;
            background-color: #000;
        }
        
        /* Center dialogs properly */
        .modal-dialog {
            position: relative;
            margin: 0.75rem auto;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            min-height: calc(100% - 1.5rem);
            max-width: 500px;
            transform: none !important;
        }
        
        /* Position dialog in the center of viewport */
        .modal-dialog-centered {
            margin-top: 0;
            margin-bottom: 0;
            min-height: 100vh;
            align-items: center;
        }
        
        .modal-dialog.modal-lg {
            max-width: 800px;
        }
        
        /* Ensure content has proper width */
        .modal-content {
            width: 100%;
            position: relative;
            background-color: #fff;
            border-radius: 0.3rem;
        }
        
        /* When modal is open, disable body scrolling */
        .modal-open {
            overflow: hidden;
            padding-right: 0 !important;
        }
        
        /* Form field enhancements */
        .form-control.is-valid,
        .form-select.is-valid {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.1);
        }
        
        .form-control.is-invalid,
        .form-select.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.1);
        }
        
        /* Better input groups with icons */
        .input-group-text {
            transition: all 0.2s ease;
        }
        
        .input-group-text i {
            width: 16px;
            text-align: center;
        }
        
        /* Input group when valid */
        .form-control.is-valid + .input-group-text,
        .input-group-text + .form-control.is-valid {
            border-color: #28a745;
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        /* Input group when invalid */
        .form-control.is-invalid + .input-group-text,
        .input-group-text + .form-control.is-invalid {
            border-color: #dc3545;
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        /* Range and battery info styling */
        .range-info, .form-text {
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        
        /* Modal body with max height for long forms */
        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
    `;
    document.head.appendChild(style);
    // Initialize bicycle modals
    function initializeBicycleModals() {
        const bicycleModalIds = ['#addBicycleModal', '#editBicycleModal', '#deleteBicycleModal'];

        bicycleModalIds.forEach(modalId => {
            const modalElement = document.querySelector(modalId);
            if (modalElement) {
                console.log(`Initializing modal: ${modalId}`);

                // Clean up any existing modal instances first
                try {
                    const existingInstance = bootstrap.Modal.getInstance(modalElement);
                    if (existingInstance) {
                        existingInstance.dispose();
                        console.log(`Disposed existing modal instance for ${modalId}`);
                    }
                } catch (e) {
                    console.log(`No existing modal instance to dispose for ${modalId}`);
                }

                // Ensure proper modal attributes
                modalElement.setAttribute('data-bs-backdrop', 'static');
                modalElement.setAttribute('data-bs-keyboard', 'true');
                modalElement.style.zIndex = '1050';

                // Fix position
                modalElement.style.top = '0';
                modalElement.style.left = '0';
                modalElement.style.right = '0';
                modalElement.style.bottom = '0';
                modalElement.style.display = 'none';
                modalElement.style.position = 'fixed';

                // Center modal dialog
                const dialog = modalElement.querySelector('.modal-dialog');
                if (dialog) {
                    if (!dialog.classList.contains('modal-dialog-centered')) {
                        dialog.classList.add('modal-dialog-centered');
                    }

                    // Force proper styling
                    dialog.style.display = 'flex';
                    dialog.style.alignItems = 'center';
                    dialog.style.justifyContent = 'center';
                    dialog.style.margin = '0 auto';
                    dialog.style.minHeight = '100vh';
                    dialog.style.maxWidth = modalId === '#addBicycleModal' || modalId === '#editBicycleModal' ? '800px' : '500px';
                    dialog.style.width = '95%';

                    // Remove any transform that might affect positioning
                    dialog.style.transform = 'none';
                }

                // Create new modal instance with correct options
                const modalInstance = new bootstrap.Modal(modalElement, {
                    backdrop: 'static',
                    keyboard: true
                });

                // Handle modal events
                modalElement.addEventListener('show.bs.modal', function () {
                    console.log(`Modal showing: ${modalId}`);
                    // Make sure we start with proper visibility
                    modalElement.style.display = 'block';
                    document.body.classList.add('modal-open');

                    // Force page to scroll to top to ensure modal is visible
                    window.scrollTo(0, 0);
                });

                modalElement.addEventListener('shown.bs.modal', function () {
                    console.log(`Modal shown: ${modalId}`);

                    // Force body to have modal-open class
                    document.body.classList.add('modal-open');

                    // Fix any scroll issues
                    document.body.style.overflow = 'hidden';
                    document.body.style.paddingRight = '0 !important';

                    // Remove any existing backdrop (in case of duplicates)
                    const backdropCount = document.querySelectorAll('.modal-backdrop').length;
                    if (backdropCount > 1) {
                        console.log(`Found ${backdropCount} backdrops, removing extras`);
                        const backdrops = document.querySelectorAll('.modal-backdrop');
                        for (let i = 1; i < backdrops.length; i++) {
                            backdrops[i].remove();
                        }
                    }

                    // Ensure the modal is visible in the viewport by scrolling if needed
                    const modalContent = modalElement.querySelector('.modal-content');
                    if (modalContent) {
                        const modalRect = modalContent.getBoundingClientRect();
                        if (modalRect.top < 0) {
                            modalContent.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }
                });

                modalElement.addEventListener('hidden.bs.modal', function () {
                    console.log(`Modal hidden: ${modalId}`);
                    resetFormValidation(modalElement.querySelector('form'));
                    // Reset body styles
                    document.body.style.overflow = '';
                });

                console.log(`Modal ${modalId} successfully initialized`);
            }
        });
    }

    // -----------------------------------------------------
    // Form Validation & Submission
    // -----------------------------------------------------    // Validate bicycle form
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
                // Add visual feedback for valid input
                batteryInput.classList.add('is-valid');

                // Update any visual indicators if present
                const batteryGroup = batteryInput.closest('.form-group');
                if (batteryGroup) {
                    let colorClass = 'text-danger';
                    if (batteryValue >= 75) {
                        colorClass = 'text-success';
                    } else if (batteryValue >= 50) {
                        colorClass = 'text-primary';
                    } else if (batteryValue >= 25) {
                        colorClass = 'text-warning';
                    }

                    // Update icon if it exists
                    const batteryIcon = batteryGroup.querySelector('.input-group-text i');
                    if (batteryIcon) {
                        batteryIcon.className = '';
                        batteryIcon.classList.add('fas');

                        if (batteryValue >= 75) {
                            batteryIcon.classList.add('fa-battery-full', colorClass);
                        } else if (batteryValue >= 50) {
                            batteryIcon.classList.add('fa-battery-three-quarters', colorClass);
                        } else if (batteryValue >= 25) {
                            batteryIcon.classList.add('fa-battery-half', colorClass);
                        } else {
                            batteryIcon.classList.add('fa-battery-quarter', colorClass);
                        }
                    }
                }
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
                // Add visual feedback for valid input
                rangeInput.classList.add('is-valid');

                // Update range icon if it exists with color based on range
                const rangeGroup = rangeInput.closest('.form-group');
                if (rangeGroup) {
                    const rangeIcon = rangeGroup.querySelector('.input-group-text i');
                    if (rangeIcon) {
                        if (rangeValue > 75) {
                            rangeIcon.className = 'fas fa-route text-success';
                        } else if (rangeValue > 40) {
                            rangeIcon.className = 'fas fa-route text-primary';
                        } else if (rangeValue > 20) {
                            rangeIcon.className = 'fas fa-route text-warning';
                        } else {
                            rangeIcon.className = 'fas fa-route text-danger';
                        }
                    }
                }
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

        // Last updated validation (if present)
        const lastUpdatedInput = form.querySelector('[name$="[lastUpdated]"]');
        if (lastUpdatedInput && lastUpdatedInput.value === '') {
            isValid = false;
            addError(lastUpdatedInput, 'Last updated date is required');
            errorMessages.push('Last updated date is required');
        } else if (lastUpdatedInput) {
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

    // Add error message to form field
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

    // Remove error message from form field
    function removeError(input) {
        input.classList.remove('is-invalid');
        const errorDiv = input.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.textContent = '';
        }
    }

    // Reset form validation but keep values (for edit)
    function resetFormValidation(form) {
        if (!form) return;

        // Clear all validation errors
        const invalidFields = form.querySelectorAll('.is-invalid');
        invalidFields.forEach(field => removeError(field));

        // Remove any alert messages
        const alerts = form.querySelectorAll('.alert');
        alerts.forEach(alert => alert.remove());

        // Reset validation classes but keep the values
        const formControls = form.querySelectorAll('.form-control, .form-select');
        formControls.forEach(control => {
            control.classList.remove('is-valid', 'is-invalid');
        });
    }    // Reset form completely (for add new)
    function resetForm(form) {
        if (!form) return;

        // Reset the form to default values
        form.reset();

        // Also reset validation
        resetFormValidation(form);

        // Reset select elements to default value
        const selects = form.querySelectorAll('select');
        selects.forEach(select => {
            // If there's a default option (usually the first one)
            if (select.options.length > 0) {
                // Either select the option with empty value or the first option
                const defaultOption = Array.from(select.options).find(opt => opt.value === '') || select.options[0];
                if (defaultOption) {
                    defaultOption.selected = true;
                }
            }
        });

        // Set battery level to 0 by default
        const batteryInput = form.querySelector('[name$="[batteryLevel]"]');
        if (batteryInput) {
            batteryInput.value = '0';
        }
    }
    // Set up real-time form validation
    function setupRealTimeValidation(form) {
        if (!form) return;

        // Set up battery level input with real-time feedback
        const batteryInput = form.querySelector('[name$="[batteryLevel]"]');
        if (batteryInput) {
            // Function to update battery UI based on value
            const updateBatteryUI = (value) => {
                const isValid = !isNaN(value) && value >= 0 && value <= 100;

                // Update visual feedback
                if (isValid) {
                    batteryInput.classList.remove('is-invalid');
                    batteryInput.classList.add('is-valid');

                    // Update battery icon
                    const batteryIcon = batteryInput.closest('.form-group')?.querySelector('.input-group-text i');
                    if (batteryIcon) {
                        batteryIcon.className = 'fas';

                        if (value >= 75) {
                            batteryIcon.classList.add('fa-battery-full', 'text-success');
                        } else if (value >= 50) {
                            batteryIcon.classList.add('fa-battery-three-quarters', 'text-primary');
                        } else if (value >= 25) {
                            batteryIcon.classList.add('fa-battery-half', 'text-warning');
                        } else {
                            batteryIcon.classList.add('fa-battery-quarter', 'text-danger');
                        }
                    }

                    // Update helper text if it exists
                    const formText = batteryInput.closest('.form-group')?.querySelector('.form-text');
                    if (formText) {
                        let statusText = 'Critical - Needs immediate charging';
                        if (value >= 75) {
                            statusText = 'Excellent - Ready for extended use';
                        } else if (value >= 50) {
                            statusText = 'Good - Suitable for medium trips';
                        } else if (value >= 25) {
                            statusText = 'Low - Consider charging soon';
                        }
                        formText.innerHTML = `Battery level: <strong>${value}%</strong>. ${statusText}`;
                    }
                } else {
                    batteryInput.classList.add('is-invalid');
                    batteryInput.classList.remove('is-valid');
                }
            };

            // Add input event listener
            batteryInput.addEventListener('input', function () {
                const value = parseFloat(this.value);
                updateBatteryUI(value);
            });

            // Initialize with current value
            updateBatteryUI(parseFloat(batteryInput.value) || 0);
        }

        // Set up range input with real-time feedback
        const rangeInput = form.querySelector('[name$="[rangeKm]"]');
        if (rangeInput) {
            // Function to update range UI based on value
            const updateRangeUI = (value) => {
                const isValid = !isNaN(value) && value >= 0;

                // Update visual feedback
                if (isValid) {
                    rangeInput.classList.remove('is-invalid');
                    rangeInput.classList.add('is-valid');

                    // Update range icon
                    const rangeIcon = rangeInput.closest('.form-group')?.querySelector('.input-group-text i');
                    if (rangeIcon) {
                        rangeIcon.className = 'fas fa-route';

                        if (value > 75) {
                            rangeIcon.classList.add('text-success');
                        } else if (value > 40) {
                            rangeIcon.classList.add('text-primary');
                        } else if (value > 20) {
                            rangeIcon.classList.add('text-warning');
                        } else {
                            rangeIcon.classList.add('text-danger');
                        }
                    }

                    // Add or update a range indicator if it doesn't exist
                    let rangeInfo = rangeInput.closest('.form-group')?.querySelector('.range-info');
                    if (!rangeInfo) {
                        rangeInfo = document.createElement('div');
                        rangeInfo.className = 'range-info form-text mt-1';
                        rangeInput.closest('.form-group')?.appendChild(rangeInfo);
                    }

                    let rangeStatus = 'Very limited range';
                    if (value > 75) {
                        rangeStatus = 'Extended range';
                    } else if (value > 40) {
                        rangeStatus = 'Good range';
                    } else if (value > 20) {
                        rangeStatus = 'Moderate range';
                    }

                    rangeInfo.innerHTML = `Range: <strong>${value} km</strong>. ${rangeStatus}`;

                } else {
                    rangeInput.classList.add('is-invalid');
                    rangeInput.classList.remove('is-valid');
                }
            };

            // Add input event listener
            rangeInput.addEventListener('input', function () {
                const value = parseFloat(this.value);
                updateRangeUI(value);
            });

            // Initialize with current value
            updateRangeUI(parseFloat(rangeInput.value) || 0);
        }

        // Link battery level to range if both exist
        if (batteryInput && rangeInput) {
            // Set up a relationship between battery and range
            batteryInput.addEventListener('change', function () {
                const batteryValue = parseFloat(this.value) || 0;
                const currentRange = parseFloat(rangeInput.value) || 0;

                // Only suggest range update if battery is very low or very high
                if (batteryValue < 20 && currentRange > 30) {
                    const suggestedRange = Math.max(15, currentRange * 0.6);
                    if (confirm(`Battery level is low (${batteryValue}%). Would you like to update the range to ${Math.round(suggestedRange)} km to reflect this?`)) {
                        rangeInput.value = Math.round(suggestedRange);
                        // Trigger the range input event
                        const event = new Event('input');
                        rangeInput.dispatchEvent(event);
                    }
                } else if (batteryValue > 90 && currentRange < 40) {
                    const suggestedRange = Math.min(75, currentRange * 1.5);
                    if (confirm(`Battery level is high (${batteryValue}%). Would you like to update the range to ${Math.round(suggestedRange)} km to reflect this?`)) {
                        rangeInput.value = Math.round(suggestedRange);
                        // Trigger the range input event
                        const event = new Event('input');
                        rangeInput.dispatchEvent(event);
                    }
                }
            });
        }
    }

    // -----------------------------------------------------
    // Button Event Handlers
    // -----------------------------------------------------    // Handle add bicycle button
    function setupAddBicycleButton() {
        const addButtons = document.querySelectorAll('[data-bs-toggle="modal"][data-bs-target="#addBicycleModal"]');

        addButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                console.log('Add bicycle button clicked');

                // Get modal elements
                const modal = document.getElementById('addBicycleModal');
                const modalTitle = document.getElementById('addBicycleModalLabel');
                const modalBody = modal.querySelector('.modal-body');

                // Force proper modal positioning
                const dialog = modal.querySelector('.modal-dialog');
                if (dialog) {
                    dialog.classList.add('modal-dialog-centered');
                    dialog.style.display = 'flex';
                    dialog.style.alignItems = 'center';
                    dialog.style.justifyContent = 'center';
                    dialog.style.margin = '0 auto';
                    dialog.style.minHeight = '100vh';
                }

                // Set modal title
                modalTitle.innerHTML = `<i class="fas fa-plus me-2"></i> Add New Bicycle`;

                // Add loading indicator
                modalBody.innerHTML = `
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Loading form...</span>
                        </div>
                        <p>Loading form...</p>
                    </div>
                `;

                // Reset scroll position to ensure modal is visible
                window.scrollTo(0, 0);

                // Show modal - try to dispose first to avoid conflicts
                try {
                    const existingInstance = bootstrap.Modal.getInstance(modal);
                    if (existingInstance) {
                        existingInstance.dispose();
                    }
                } catch (e) {
                    console.log('No existing modal instance to dispose');
                }

                // Create new instance with proper options
                const modalInstance = new bootstrap.Modal(modal, {
                    backdrop: 'static',
                    keyboard: true
                });

                // Show the modal
                modalInstance.show();

                // Make sure the modal is visible and body has the right class
                setTimeout(() => {
                    document.body.classList.add('modal-open');
                    modal.style.display = 'block';
                }, 50);// Load bicycle form via AJAX
                fetch('/admin/bicycle/add', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok (Status: ' + response.status + ')');
                        }
                        return response.text();
                    }).then(html => {
                        modalBody.innerHTML = html;

                        // Set the form ID if it's not already set
                        const form = modal.querySelector('form');
                        if (form && !form.id) {
                            form.id = 'addBicycleForm';
                        }

                        // Make sure form action is set correctly
                        if (form && (!form.action || form.action === '')) {
                            form.action = '/admin/bicycle/add';
                        }

                        // Make sure battery level is set to 0 by default
                        const batteryInput = form.querySelector('[name$="[batteryLevel]"]');
                        if (batteryInput) {
                            batteryInput.value = '0';

                            // Trigger the input event to update the icon
                            const inputEvent = new Event('input');
                            batteryInput.dispatchEvent(inputEvent);
                        }

                        // Setup real-time validation
                        setupRealTimeValidation(form);

                        // Setup form submission after content is loaded
                        setupBicycleFormSubmission(form);
                    })
                    .catch(error => {
                        console.error('Error loading bicycle form:', error);
                        modalBody.innerHTML = `
                            <div class="alert alert-danger mb-0">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <strong>Error loading form:</strong> ${error.message}
                                <div class="mt-3">
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="location.reload()">
                                        <i class="fas fa-sync-alt me-2"></i> Refresh Page
                                    </button>
                                </div>
                            </div>
                        `;
                    });
            });
        });
    }

    // Handle edit bicycle buttons
    function setupEditBicycleButtons() {
        document.querySelectorAll('.edit-bicycle').forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();

                const bicycleId = this.getAttribute('data-bicycle-id');
                if (!bicycleId) return;

                // Get modal elements
                const modal = document.getElementById('editBicycleModal');
                const modalTitle = document.getElementById('editBicycleModalLabel');
                const modalBody = modal.querySelector('.modal-body');

                // Update modal title
                modalTitle.innerHTML = `<i class="fas fa-bicycle me-2"></i> Edit Bicycle #${bicycleId}`;

                // Add loading indicator
                modalBody.innerHTML = `
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p>Loading bicycle data...</p>
                    </div>
                `;

                // Show modal
                const modalInstance = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal);
                modalInstance.show();
                // Load bicycle data via AJAX
                fetch('/admin/bicycle/' + bicycleId + '/edit-form')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.text();
                    }).then(html => {
                        modalBody.innerHTML = html;                        // Set the form ID if it's not already set
                        const form = modal.querySelector('form');
                        if (form && !form.id) {
                            form.id = 'editBicycleForm';
                        }

                        // Setup real-time validation for edit form
                        setupRealTimeValidation(form);

                        // Trigger input events to update UI with current values
                        const batteryInput = form.querySelector('[name$="[batteryLevel]"]');
                        if (batteryInput) {
                            const inputEvent = new Event('input');
                            batteryInput.dispatchEvent(inputEvent);
                        }

                        const rangeInput = form.querySelector('[name$="[rangeKm]"]');
                        if (rangeInput) {
                            const inputEvent = new Event('input');
                            rangeInput.dispatchEvent(inputEvent);
                        }

                        // Setup form submission after content is loaded
                        setupBicycleFormSubmission(form);
                    })
                    .catch(error => {
                        modalBody.innerHTML = `
                            <div class="alert alert-danger mb-0">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                Error loading bicycle data: ${error.message}
                            </div>
                        `;
                    });
            });
        });
    }
    // Handle delete bicycle buttons
    function setupDeleteBicycleButtons() {
        document.querySelectorAll('.delete-bicycle').forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();

                const bicycleId = this.getAttribute('data-bicycle-id');
                if (!bicycleId) return;

                // Get modal elements
                const modal = document.getElementById('deleteBicycleModal');
                const bicycleIdElement = document.getElementById('deleteBicycleId');
                const bicycleIdInput = document.getElementById('deleteBicycleIdInput');

                // Set bicycle ID
                if (bicycleIdElement) {
                    bicycleIdElement.textContent = bicycleId;
                }

                // Set hidden form input
                if (bicycleIdInput) {
                    bicycleIdInput.value = bicycleId;
                }

                // Show confirmation message
                const confirmationElement = modal.querySelector('.bicycle-delete-confirmation');
                if (confirmationElement) {
                    confirmationElement.textContent = `Are you sure you want to delete Bicycle #${bicycleId}?`;
                }

                // Set up checkbox for confirmation
                const confirmCheckbox = document.getElementById('confirmBicycleDelete');
                const deleteButton = document.getElementById('deleteBicycleBtn');

                if (confirmCheckbox && deleteButton) {
                    // Reset checkbox
                    confirmCheckbox.checked = false;
                    deleteButton.disabled = true;

                    // Add event listener
                    confirmCheckbox.addEventListener('change', function () {
                        deleteButton.disabled = !this.checked;
                    });
                }

                // Show modal
                const modalInstance = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal);
                modalInstance.show();
            });
        });
    }

    // -----------------------------------------------------
    // Form Submission
    // -----------------------------------------------------

    // Setup form submission for all bicycle forms
    function setupBicycleFormSubmission(form) {
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            // Validate form
            const validation = validateBicycleForm(form);
            if (!validation.isValid) {
                // Show validation errors
                const errorContainer = form.querySelector('.form-errors') || document.createElement('div');
                errorContainer.className = 'alert alert-danger form-errors';
                errorContainer.innerHTML = `
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Please correct the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        ${validation.errorMessages.map(msg => `<li>${msg}</li>`).join('')}
                    </ul>
                `;

                // Add error container if it doesn't exist
                if (!form.querySelector('.form-errors')) {
                    form.insertBefore(errorContainer, form.firstChild);
                }

                return;
            }

            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2"></i> Submitting...';
            }            // Submit form using Fetch API
            const formData = new FormData(form);

            fetch(form.action, {
                method: form.method || 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => {
                    // Check if the response is JSON
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json().then(data => {
                            if (!response.ok) {
                                return Promise.reject(data);
                            }
                            return data;
                        });
                    }

                    // If it's not JSON (like HTML), and the response is not OK, reject
                    if (!response.ok) {
                        throw new Error('Server returned status ' + response.status);
                    }

                    // For successful non-JSON responses, return a success object
                    return { success: true };
                })
                .then(data => {
                    if (data.success) {
                        // Success - reload page or show success message
                        window.location.reload();
                    } else {
                        // Server validation errors
                        const errorContainer = form.querySelector('.form-errors') || document.createElement('div');
                        errorContainer.className = 'alert alert-danger form-errors';
                        errorContainer.innerHTML = `
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Form submission failed:</strong>
                        <p class="mb-0 mt-2">${data.message || 'Unknown error occurred'}</p>
                    `;

                        // Add error container if it doesn't exist
                        if (!form.querySelector('.form-errors')) {
                            form.insertBefore(errorContainer, form.firstChild);
                        }

                        // Reset submit button
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalBtnHtml;
                        }
                    }
                }).catch(error => {
                    console.error('Form submission error:', error);

                    // Check if this is a structured error response from our API
                    let errorMessage = 'An unexpected error occurred while processing your request.';

                    if (typeof error === 'object') {
                        // Use the error message if available
                        if (error.message) {
                            errorMessage = error.message;
                        }

                        // If we have field-specific errors, highlight them on the form
                        if (error.errors && typeof error.errors === 'object') {
                            for (const fieldName in error.errors) {
                                const fieldError = error.errors[fieldName];
                                // Try to find the field in the form using different selector patterns
                                const field = form.querySelector(`[name$="[${fieldName}]"]`) ||
                                    form.querySelector(`#bicycle_${fieldName}`) ||
                                    form.querySelector(`[name*="${fieldName}"]`);

                                if (field) {
                                    // Add error highlight and message to the field
                                    field.classList.add('is-invalid');
                                    let errorDiv = field.parentNode.querySelector('.invalid-feedback');
                                    if (!errorDiv) {
                                        errorDiv = document.createElement('div');
                                        errorDiv.className = 'invalid-feedback';
                                        field.parentNode.appendChild(errorDiv);
                                    }
                                    errorDiv.textContent = Array.isArray(fieldError) ? fieldError.join(', ') : fieldError;
                                    errorDiv.style.display = 'block';
                                }
                            }
                        }
                    }

                    // Network or other errors
                    const errorContainer = form.querySelector('.form-errors') || document.createElement('div');
                    errorContainer.className = 'alert alert-danger form-errors';
                    errorContainer.innerHTML = `
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Form submission failed:</strong>
                    <p class="mb-0 mt-2">${errorMessage}</p>
                `;

                    // Add error container if it doesn't exist
                    if (!form.querySelector('.form-errors')) {
                        form.insertBefore(errorContainer, form.firstChild);
                    }

                    // Reset submit button
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnHtml;
                    }
                });
        });
    }

    // -----------------------------------------------------
    // Initialize Everything
    // -----------------------------------------------------    // Initialize all components
    function initialize() {
        // Initialize modals
        initializeBicycleModals();

        // Setup button handlers
        setupAddBicycleButton();
        setupEditBicycleButtons();
        setupDeleteBicycleButtons();

        // Setup form submissions for existing forms
        const bicycleForms = document.querySelectorAll('#addBicycleForm, #editBicycleForm');
        bicycleForms.forEach(form => {
            setupBicycleFormSubmission(form);
            setupRealTimeValidation(form);
        });

        // Ensure forms have correct action paths
        const addBicycleForm = document.getElementById('addBicycleForm');
        if (addBicycleForm && !addBicycleForm.getAttribute('action')) {
            addBicycleForm.setAttribute('action', '/admin/bicycle/add');
        }

        // Make sure edit form has X-Requested-With header set
        const editBicycleForm = document.getElementById('editBicycleForm');
        if (editBicycleForm) {
            const formAction = editBicycleForm.getAttribute('action');
            if (formAction) {
                console.log('Edit form action path: ' + formAction);
            }
        }
    }

    // Call initialize function after a short delay to ensure DOM is fully loaded
    setTimeout(initialize, 100);
});
