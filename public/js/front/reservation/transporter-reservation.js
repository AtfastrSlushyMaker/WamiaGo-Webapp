document.addEventListener('DOMContentLoaded', function() {
    // Initialize detail buttons
    document.querySelectorAll('.btn-details').forEach(button => {
        button.addEventListener('click', function() {
            const reservationId = this.getAttribute('data-id');
            showReservationDetails(reservationId);
        });
    });

    // Initialize accept buttons
    document.querySelectorAll('.btn-accept').forEach(button => {
        button.addEventListener('click', function() {
            const reservationId = this.getAttribute('data-id');
            confirmReservationAction(reservationId, 'accept');
        });
    });

    // Initialize refuse buttons
    document.querySelectorAll('.btn-refuse').forEach(button => {
        button.addEventListener('click', function() {
            const reservationId = this.getAttribute('data-id');
            confirmReservationAction(reservationId, 'refuse');
        });
    });
});

/**
 * Display reservation details in a modal
 * @param {string} reservationId - The ID of the reservation
 */
async function showReservationDetails(reservationId) {
    try {
        const response = await fetch(`/transporter/reservations/${reservationId}/details`);
        if (!response.ok) throw new Error('Failed to fetch reservation details');
        
        const data = await response.json();

        const detailsHtml = `
            <div class="detail-card">
                <div class="reservation-header">
                    <h3>${data.announcement?.title}</h3>
                    <span class="badge bg-${getStatusBadgeColor(data.status)}">${data.status}</span>
                </div>
                
                <div class="reservation-section">
                    <h4><i class="fas fa-info-circle"></i> Description</h4>
                    <p>${data.description}</p>
                </div>
                
                <div class="reservation-section">
                    <h4><i class="fas fa-user"></i> Client Information</h4>
                    <ul class="list-unstyled">
                        <li><strong>Name:</strong> ${data.user.name}</li>
                        <li><strong>Email:</strong> ${data.user.email}</li>
                        <li><strong>Phone:</strong> ${data.user.phone}</li>
                    </ul>
                </div>
                
                <div class="reservation-section">
                    <h4><i class="fas fa-route"></i> Trip Information</h4>
                    <div class="location-info">
                        <div class="start-location">
                            <i class="fas fa-map-marker-alt text-danger"></i>
                            <p><strong>From:</strong> ${data.startLocation.address}</p>
                        </div>
                        <div class="end-location">
                            <i class="fas fa-flag-checkered text-success"></i>
                            <p><strong>To:</strong> ${data.endLocation.address}</p>
                        </div>
                    </div>
                </div>
                
                <div class="reservation-section">
                    <h4><i class="fas fa-calendar-alt"></i> Dates</h4>
                    <ul class="list-unstyled">
                        <li><strong>Reservation Date:</strong> ${formatDate(data.date)}</li>
                        
                    </ul>
                </div>

                ${data.status === 'ON_GOING' ? `
                    <div class="action-buttons mt-3">
                        <button class="btn btn-success me-2" onclick="handleReservationAction('${data.id}', 'accept')">
                            <i class="fas fa-check"></i> Accept
                        </button>
                        <button class="btn btn-danger" onclick="handleReservationAction('${data.id}', 'refuse')">
                            <i class="fas fa-times"></i> Refuse
                        </button>
                    </div>
                ` : ''}
            </div>
        `;

        await Swal.fire({
            title: 'Reservation Details',
            html: detailsHtml,
            width: '600px',
            position: 'top',
            showCloseButton: true,
            showConfirmButton: false,
            customClass: {
                container: 'reservation-details-modal',
                popup: 'reservation-details-popup'
            }
        });
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load reservation details',
            position: 'top'
        });
    }
}

/**
 * Display a confirmation dialog for accepting or refusing a reservation
 * @param {string} reservationId - The ID of the reservation
 * @param {string} action - Either 'accept' or 'refuse'
 */
async function confirmReservationAction(reservationId, action) {
    const title = action === 'accept' ? 'Accept Reservation' : 'Refuse Reservation';
    const confirmButtonText = action === 'accept' ? 'Yes, Accept it!' : 'Yes, Refuse it!';
    const confirmationText = action === 'accept' 
        ? 'Are you sure you want to accept this reservation?' 
        : 'Are you sure you want to refuse this reservation?';
    
    // Show confirmation dialog with SweetAlert2
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: confirmationText,
        icon: 'warning',
        position: 'top', // Position the modal at the top
        showCancelButton: true,
        confirmButtonText: confirmButtonText,
        cancelButtonText: 'Cancel',
        customClass: {
            container: 'reservation-modal-container',
            popup: 'reservation-modal-popup'
        }
    });
    
    // If confirmed, proceed with the action
    if (result.isConfirmed) {
        handleReservationAction(reservationId, action);
    }
}

/**
 * Process the reservation action (accept/refuse)
 * @param {string} reservationId - The ID of the reservation
 * @param {string} action - Either 'accept' or 'refuse'
 */
async function handleReservationAction(reservationId, action) {
    try {
        if (action === 'accept') {
            const { value: formValues } = await Swal.fire({
                title: 'Accept Reservation',
                html: `
                    <form id="acceptForm">
                        <div class="mb-3">
                            <label for="date" class="form-label">Relocation Date</label>
                            <input type="date" id="date" class="form-control" required 
                                   min="${new Date().toISOString().split('T')[0]}">
                        </div>
                        <div class="mb-3">
                            <label for="cost" class="form-label">Cost (€)</label>
                            <input type="number" id="cost" class="form-control" required 
                                   min="0.01" step="0.01" value="50.00">
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: 'Confirm',
                focusConfirm: false,
                position: 'top', 
                customClass: {
                    container: 'my-swal-container',
                    popup: 'my-swal-popup'
                },
                allowOutsideClick: false, // Prevent clicking outside
                scrollbarPadding: false, // Prevent layout shift
                heightAuto: false, // Prevent height issues
            });

            if (!formValues) return;

            console.log("Sending data:", formValues); // Debug log

            const response = await fetch(`/transporter/reservations/${reservationId}/accept`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formValues)
            });

            const data = await response.json();
            console.log("Response data:", data); // Debug log

            if (!response.ok) {
                throw new Error(data.error || 'Failed to accept reservation');
            }

            await Swal.fire({
                title: 'Success!',
                text: data.message,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });

            // Mise à jour de l'UI
            updateReservationCard(reservationId, 'CONFIRMED');
            
        } else if (action === 'refuse') {
            const response = await fetch(`/transporter/reservations/${reservationId}/refuse`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Failed to refuse reservation');
            }

            await Swal.fire({
                title: 'Success!',
                text: data.message,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });

            window.location.reload();
        }
    } catch (error) {
        Swal.fire({
            title: 'Error',
            text: error.message,
            icon: 'error'
        });
        console.error('Action failed:', error);
    }
}

function updateReservationCard(reservationId, newStatus) {
    const card = document.querySelector(`.reservation-card[data-id="${reservationId}"]`);
    if (card) {
        // Mise à jour du badge de statut
        const badge = card.querySelector('.card-badge');
        if (badge) {
            badge.textContent = newStatus;
            badge.className = `card-badge ${newStatus.toLowerCase()}`;
        }
        
        // Désactivation des boutons
        card.querySelectorAll('.btn-accept, .btn-refuse').forEach(btn => {
            btn.disabled = true;
        });
    }
}

/**
 * Display a toast notification
 * @param {string} message - The message to display
 * @param {string} type - The type of toast (success, error, etc.)
 */
function showToast(message, type = 'success') {
    // Create the container if it doesn't exist
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        toastContainer.style.zIndex = '1100';
        document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    toast.className = `toast show align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');

    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;

    toastContainer.appendChild(toast);

    // Auto-hide after 5 seconds
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

/**
 * Format date in a user-friendly way
 * @param {string} dateString - The date string to format
 * @returns {string} - The formatted date
 */
function formatDate(dateString) {
    return new Date(dateString).toLocaleString();
}

/**
 * Get the appropriate color class for a status
 * @param {string} status - The reservation status
 * @returns {string} - The color class for the status
 */
function getStatusBadgeColor(status) {
    const colors = {
        'ON_GOING': 'warning',
        'ACCEPTED': 'success',
        'CANCELLED': 'danger',
        'COMPLETED': 'info'
    };
    return colors[status] || 'secondary';
}

/**
 * Display form errors
 * @param {HTMLElement} form - The form element
 * @param {Object} errors - The errors object
 */
function displayFormErrors(form, errors) {
    // Reset previous errors
    document.querySelectorAll('.is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });
    
    document.querySelectorAll('.invalid-feedback').forEach(el => {
        el.textContent = '';
    });
    
    // Display new errors
    Object.entries(errors).forEach(([field, message]) => {
        // Handle dot notation for nested fields (e.g., 'parent.child')
        const fieldPath = field.split('.');
        const fieldName = fieldPath[fieldPath.length - 1];
        
        // Try different selectors to find the input
        let input = form.querySelector(`[name="${field}"]`);
        if (!input) {
            input = form.querySelector(`[name$="[${fieldName}]"]`);
        }
        
        if (input) {
            input.classList.add('is-invalid');
            const formGroup = input.closest('.form-group');
            if (formGroup) {
                const feedback = formGroup.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.textContent = Array.isArray(message) ? message.join(' ') : message;
                }
            }
        } else {
            // If field not found, show as a toast message
            showToast(Array.isArray(message) ? message.join(' ') : message, 'error');
        }
    });
}

// Global event handlers
document.addEventListener('click', function(e) {
    // Bootstrap modal close buttons
    if (e.target.closest('[data-bs-dismiss="modal"]')) {
        const modal = bootstrap.Modal.getInstance(e.target.closest('.modal'));
        if (modal) modal.hide();
    }
    
    // SweetAlert close buttons
    if (e.target.closest('.swal2-close')) {
        Swal.close();
    }
});

// Reset hidden modals
document.addEventListener('hidden.bs.modal', function() {
    document.body.classList.remove('modal-open');
    document.body.style.paddingRight = '';
    document.body.style.overflow = '';
});