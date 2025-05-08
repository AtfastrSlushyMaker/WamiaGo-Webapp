document.addEventListener('DOMContentLoaded', function() {
    // Handle relocation form submission
    document.addEventListener('submit', async function(e) {
        if (e.target.id === 'relocationForm') {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
            
            try {
                // Add CSRF token to form data
                const csrfToken = document.querySelector('#relocationForm input[name="_token"]').value;
                formData.append('_token', csrfToken);
                
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.error || 'Error creating relocation');
                }

                if (data.success) {
                    showToast(data.message, 'success');
                    
                    // Close modal and refresh
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('relocationModal'));
                        if (modal) modal.hide();
                        
                        // Update the reservation card
                        updateReservationStatus(data.reservationId, 'CONFIRMED');
                    }, 1500);
                } else {
                    throw new Error(data.error || 'Error creating relocation');
                }
            } catch (error) {
                showToast(error.message, 'error');
                console.error('Relocation error:', error);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
    });
    
    // Open relocation modal when accepting a reservation
    document.addEventListener('click', async function(e) {
        if (e.target.closest('.btn-accept')) {
            e.preventDefault();
            const reservationId = e.target.closest('.btn-accept').dataset.id;
            await openRelocationModal(reservationId);
        }
    });
});

async function openRelocationModal(reservationId) {
    try {
        const csrfToken = document.getElementById('csrf-token').dataset.token;
        const response = await fetch(`/transporter/reservations/${reservationId}/create-relocation`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            }
        });

        if (!response.ok) {
            throw new Error('Failed to load form');
        }

        const html = await response.text();
        const modalBody = document.querySelector('#relocationModal .modal-body');
        modalBody.innerHTML = html;

        const modal = new bootstrap.Modal(document.getElementById('relocationModal'));
        modal.show();

    } catch (error) {
        console.error('Relocation modal error:', error);
        showToast('Failed to load relocation form', 'error');
    }
}

function updateReservationStatus(reservationId, newStatus) {
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
        btn.disabled = true;
    });
}

function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    const toast = document.createElement('div');
    
    toast.className = `toast show align-items-center text-white bg-${type}`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto-hide after 5 seconds
    setTimeout(() => toast.remove(), 5000);
}