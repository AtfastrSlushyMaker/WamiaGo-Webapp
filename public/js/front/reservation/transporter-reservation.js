document.addEventListener('DOMContentLoaded', function() {
    // Initialize SweetAlert2
    const Swal = window.Swal;
    
    // Handle details button click
    document.addEventListener('click', async function(e) {
        if (e.target.closest('.btn-details')) {
            const reservationId = e.target.closest('.btn-details').dataset.id;
            await showDetailsModal(reservationId);
        }
        
        if (e.target.closest('.btn-accept')) {
            const reservationId = e.target.closest('.btn-accept').dataset.id;
            await handleAcceptReservation(reservationId);
        }
        
        if (e.target.closest('.btn-refuse')) {
            const reservationId = e.target.closest('.btn-refuse').dataset.id;
            await handleRefuseReservation(reservationId);
        }
    });
});

// Configuration globale de SweetAlert
Swal.mixin({
    customClass: {
        container: 'reservation-modal-container',
        popup: 'reservation-modal-popup',
        closeButton: 'swal-close-button',
        confirmButton: 'swal-confirm-button',
        cancelButton: 'swal-cancel-button'
    },
    buttonsStyling: false,
    showCloseButton: true,
    showCancelButton: true,
    showConfirmButton: true,
    allowOutsideClick: false,
    allowEscapeKey: true,
    allowEnterKey: true,
    stopKeydownPropagation: false
});

async function showDetailsModal(reservationId) {
    try {
        const response = await fetch(`/transporter/reservations/${reservationId}/details`);
        
        if (!response.ok) {
            throw new Error('Failed to fetch reservation details');
        }

        const data = await response.json();

        await Swal.fire({
            title: `<strong>${data.title}</strong>`,
            html: `
                <div class="text-start">
                    <p class="text-muted mb-3">${data.description}</p>
                    <div class="d-flex justify-content-between mb-3">
                        <span><i class="fas fa-calendar-alt me-2"></i>${data.date}</span>
                        <span class="badge ${data.status.toLowerCase()}">${data.status}</span>
                    </div>
                    <hr>
                    <div class="location-details mb-3">
                        <p><i class="fas fa-map-marker-alt text-danger me-2"></i> ${data.startLocation}</p>
                        <p><i class="fas fa-flag-checkered text-success me-2"></i> ${data.endLocation}</p>
                    </div>
                    <hr>
                    <p><i class="fas fa-user me-2"></i> Client: ${data.client}</p>
                </div>
            `,
            showCloseButton: true,
            showConfirmButton: false,
            width: '600px',
            background: '#fff',
            backdrop: 'rgba(0,0,0,0.5)',
            customClass: {
                container: 'reservation-modal-container',
                popup: 'reservation-modal-popup',
                closeButton: 'swal-close-button'
            },
            position: 'top',
            grow: 'row',
            willOpen: () => {
                const popup = Swal.getPopup();
                popup.style.top = '100px';
                popup.style.left = '20%';
                popup.style.transform = 'translateX(-50%)';
            },
            willClose: () => {
                document.activeElement.blur();
            }
        });

    } catch (error) {
        await Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message,
            timer: 3000,
            position: 'top'
        });
    }
}

async function handleAcceptReservation(reservationId) {
    try {
        const { value: formValues } = await Swal.fire({
            title: 'Accept Reservation',
            html: `
                <form id="acceptForm" class="text-start">
                    <div class="mb-3">
                        <label for="relocationDate" class="form-label">Relocation Date</label>
                        <input type="date" id="relocationDate" class="form-control" required min="${new Date().toISOString().split('T')[0]}">
                    </div>
                    <div class="mb-3">
                        <label for="relocationCost" class="form-label">Cost (€)</label>
                        <input type="number" id="relocationCost" class="form-control" step="0.01" min="0" required>
                    </div>
                </form>
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Confirm',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#4CAF50',
            cancelButtonColor: '#F44336',
            customClass: {
                container: 'reservation-modal-container',
                popup: 'reservation-modal-popup',
                closeButton: 'swal-close-button'
            },
            position: 'top',
            preConfirm: () => {
                const date = document.getElementById('relocationDate').value;
                const cost = document.getElementById('relocationCost').value;
                
                if (!date || !cost) {
                    Swal.showValidationMessage('Please fill all fields');
                    return false;
                }
                
                return { date, cost };
            }
        });

        if (formValues) {
            const csrfToken = document.getElementById('csrf-token').dataset.token;
            
            const response = await fetch(`/transporter/reservations/${reservationId}/accept`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    date: formValues.date,
                    cost: formValues.cost,
                    _token: csrfToken
                })
            });

            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || 'Failed to accept reservation');
            }

            updateReservationCard(reservationId, 'CONFIRMED');
            
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: data.message || 'Reservation accepted successfully',
                timer: 3000,
                position: 'top',
                customClass: {
                    popup: 'reservation-success-modal'
                }
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message,
            timer: 3000,
            position: 'top'
        });
    }
}

async function handleRefuseReservation(reservationId) {
    try {
        const { isConfirmed } = await Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#F44336',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, refuse it!',
            cancelButtonText: 'Cancel',
            customClass: {
                container: 'reservation-modal-container',
                popup: 'reservation-modal-popup',
                closeButton: 'swal-close-button'
            },
            position: 'top'
        });

        if (isConfirmed) {
            const csrfToken = document.getElementById('csrf-token').dataset.token;
            
            const response = await fetch(`/transporter/reservations/${reservationId}/refuse`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    _token: csrfToken
                })
            });

            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || 'Failed to refuse reservation');
            }

            updateReservationCard(reservationId, 'CANCELLED');
            
            Swal.fire({
                icon: 'success',
                title: 'Refused!',
                text: data.message || 'Reservation has been refused.',
                timer: 3000,
                position: 'top',
                customClass: {
                    popup: 'reservation-success-modal'
                }
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message,
            timer: 3000,
            position: 'top'
        });
    }
}


function updateReservationCard(reservationId, newStatus) {
    const card = document.querySelector(`.reservation-card[data-id="${reservationId}"]`);
    if (!card) return;
    
    // Update status classes
    card.className = card.className.replace(/\b(pending|confirmed|cancelled)\b/g, '');
    card.classList.add(newStatus.toLowerCase());
    
    // Update badge
    const badge = card.querySelector('.card-badge');
    if (badge) {
        badge.className = `card-badge ${newStatus.toLowerCase()}`;
        badge.textContent = newStatus;
    }
    
    // Disable action buttons
    card.querySelectorAll('.btn-accept, .btn-refuse').forEach(btn => {
        btn.style.display = 'none';
    });
}