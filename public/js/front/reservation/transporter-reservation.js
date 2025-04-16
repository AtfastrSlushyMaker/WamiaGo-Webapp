document.addEventListener('DOMContentLoaded', function() {
    // Gestion des clics sur les boutons
    document.addEventListener('click', async function(e) {
        if (e.target.closest('.btn-details')) {
            const reservationId = e.target.closest('.btn-details').dataset.id;
            await showDetailsModal(reservationId);
        }
        
        if (e.target.closest('.btn-accept')) {
            const reservationId = e.target.closest('.btn-accept').dataset.id;
            await handleReservationAction(reservationId, 'accept');
        }
        
        if (e.target.closest('.btn-refuse')) {
            const reservationId = e.target.closest('.btn-refuse').dataset.id;
            await handleReservationAction(reservationId, 'refuse');
        }
    });
});

async function showDetailsModal(reservationId) {
    try {
        const response = await fetch(`/transporter/reservations/${reservationId}/details`);
        const data = await response.json();
        
        const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
        const modalBody = document.querySelector('#detailsModal .modal-body');
        
        modalBody.innerHTML = `
            <div class="detail-card">
                <h4>${data.title}</h4>
                <p class="text-muted">${data.description}</p>
                <div class="d-flex justify-content-between">
                    <span><i class="fas fa-calendar-alt me-2"></i>${data.date}</span>
                    <span class="badge ${data.status.toLowerCase()}">${data.status}</span>
                </div>
                <hr>
                <div class="location-details">
                    <p><i class="fas fa-map-marker-alt text-danger me-2"></i> ${data.startLocation}</p>
                    <p><i class="fas fa-flag-checkered text-success me-2"></i> ${data.endLocation}</p>
                </div>
                <hr>
                <p><i class="fas fa-user me-2"></i> Client: ${data.client}</p>
            </div>
        `;
        
        modal.show();
    } catch (error) {
        showToast('Failed to load reservation details', 'error');
    }
}

async function handleReservationAction(reservationId, action) {
    if (!confirm(`Are you sure you want to ${action} this reservation?`)) return;
    
    try {
        const response = await fetch(`/transporter/reservations/${reservationId}/${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `_token=${document.querySelector('meta[name="csrf-token"]').content}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            updateReservationCard(reservationId, data.newStatus);
            showToast(data.message, 'success');
        } else {
            showToast(data.error || 'Action failed', 'error');
        }
    } catch (error) {
        showToast('Network error - please try again', 'error');
    }
}

function updateReservationCard(reservationId, newStatus) {
    const card = document.querySelector(`.reservation-card[data-id="${reservationId}"]`);
    if (!card) return;
    
    // Mise à jour du statut
    card.className = card.className.replace(/\b(pending|confirmed|cancelled)\b/g, '');
    card.classList.add(newStatus.toLowerCase());
    
    // Mise à jour du badge
    const badge = card.querySelector('.card-badge');
    if (badge) {
        badge.className = `card-badge ${newStatus.toLowerCase()}`;
        badge.textContent = newStatus;
    }
    
    // Désactivation des boutons
    card.querySelectorAll('.btn-accept, .btn-refuse').forEach(btn => {
        btn.disabled = true;
    });
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast show align-items-center text-white bg-${type}`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    document.getElementById('toast-container').appendChild(toast);
    
    // Suppression automatique après 5 secondes
    setTimeout(() => toast.remove(), 5000);
}