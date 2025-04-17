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

async function showDetailsModal(reservationId) {
    try {
        const response = await fetch(`/transporter/reservations/${reservationId}/details`);
        if (!response.ok) {
            throw new Error('Failed to fetch reservation details');
        }
        
        const data = await response.json();
        
        // Référence à la carte de réservation
        const btn = document.querySelector(`.btn-details[data-id="${reservationId}"]`);
        const card = btn.closest('.reservation-card');
        const cardRect = card.getBoundingClientRect();
        
        Swal.fire({
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
                popup: 'reservation-modal-popup'
            },
            willOpen: () => {
                const popup = Swal.getPopup();
                
                // Calcul d'une position qui soit visible et proche de la carte
                let topPosition = cardRect.top + window.scrollY - 20;
                
                // S'assurer que la modale ne sort pas du haut de l'écran
                if (topPosition < 20) topPosition = 20;
                
                // Positionnement horizontal aligné avec la carte
                let leftPosition = cardRect.left + window.scrollX;
                
                // Ajustement pour que la modale ne dépasse pas à gauche
                if (leftPosition < 20) leftPosition = 20;
                
                popup.style.top = `${topPosition}px`;
                popup.style.left = `${leftPosition}px`;
                popup.style.position = 'absolute';
            }
        });
        
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message,
            timer: 3000
        });
    }
}

async function handleAcceptReservation(reservationId) {
    try {
        // Référence à la carte de réservation
        const btn = document.querySelector(`.btn-accept[data-id="${reservationId}"]`);
        const card = btn.closest('.reservation-card');
        const cardRect = card.getBoundingClientRect();
        
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
            confirmButtonText: '<i class="fas fa-check me-1"></i> Confirm',
            cancelButtonText: '<i class="fas fa-times me-1"></i> Cancel',
            confirmButtonColor: '#4caf50',
            cancelButtonColor: '#f44336',
            customClass: {
                container: 'reservation-modal-container',
                popup: 'reservation-modal-popup',
                confirmButton: 'swal-confirm-button',
                cancelButton: 'swal-cancel-button'
            },
            preConfirm: () => {
                return {
                    date: document.getElementById('relocationDate').value,
                    cost: document.getElementById('relocationCost').value
                };
            },
            willOpen: () => {
                const popup = Swal.getPopup();
                
                // Calcul d'une position qui soit visible et proche de la carte
                let topPosition = cardRect.top + window.scrollY - 20;
                if (topPosition < 20) topPosition = 20;
                
                let leftPosition = cardRect.left + window.scrollX;
                if (leftPosition < 20) leftPosition = 20;
                
                popup.style.top = `${topPosition}px`;
                popup.style.left = `${leftPosition}px`;
                popup.style.position = 'absolute';
            }
        });

        if (formValues) {
            // Validate form
            if (!formValues.date || !formValues.cost) {
                throw new Error('Please fill all fields');
            }

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

            // Update UI
            updateReservationCard(reservationId, 'CONFIRMED');
            
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: data.message || 'Reservation accepted successfully',
                timer: 3000,
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
            timer: 3000
        });
    }
}

async function handleRefuseReservation(reservationId) {
    try {
        // Référence à la carte de réservation
        const btn = document.querySelector(`.btn-refuse[data-id="${reservationId}"]`);
        const card = btn.closest('.reservation-card');
        const cardRect = card.getBoundingClientRect();
        
        const { isConfirmed } = await Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f44336',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '<i class="fas fa-ban me-1"></i> Yes, refuse it!',
            cancelButtonText: '<i class="fas fa-arrow-left me-1"></i> Cancel',
            customClass: {
                container: 'reservation-modal-container',
                popup: 'reservation-modal-popup',
                confirmButton: 'swal-confirm-button',
                cancelButton: 'swal-cancel-button'
            },
            willOpen: () => {
                const popup = Swal.getPopup();
                
                // Calcul d'une position qui soit visible et proche de la carte
                let topPosition = cardRect.top + window.scrollY - 20;
                if (topPosition < 20) topPosition = 20;
                
                let leftPosition = cardRect.left + window.scrollX;
                if (leftPosition < 20) leftPosition = 20;
                
                popup.style.top = `${topPosition}px`;
                popup.style.left = `${leftPosition}px`;
                popup.style.position = 'absolute';
            }
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

            // Update UI
            updateReservationCard(reservationId, 'CANCELLED');
            
            Swal.fire({
                icon: 'success',
                title: 'Refused!',
                text: data.message || 'Reservation has been refused.',
                timer: 3000,
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
            timer: 3000
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