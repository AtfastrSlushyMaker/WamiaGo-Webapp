document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des événements
    initRelocationEvents();
});

function initRelocationEvents() {
    // Détails
    document.addEventListener('click', async (e) => {
        if (e.target.closest('.btn-details')) {
            const relocationId = e.target.closest('.btn-details').dataset.id;
            await showRelocationDetails(relocationId);
        }
        
        // Édition
        if (e.target.closest('.btn-edit')) {
            const relocationId = e.target.closest('.btn-edit').dataset.id;
            await showEditForm(relocationId);
        }
        
        // Suppression
        if (e.target.closest('.btn-delete')) {
            const button = e.target.closest('.btn-delete');
            const relocationId = button.dataset.id;
            const relocationTitle = button.dataset.title;
            const csrfToken = button.dataset.csrf;
            
            await confirmDelete(relocationId, relocationTitle, csrfToken);
        }
    });
    
    // Soumission du formulaire d'édition
    document.addEventListener('submit', async (e) => {
        if (e.target.id === 'editRelocationForm') {
            e.preventDefault();
            await submitEditForm(e.target);
        }
    });
}

async function showRelocationDetails(relocationId) {
    try {
        const response = await fetch(`/transporter/relocations/${relocationId}/details`);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        const data = await response.json();
        const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
        const modalBody = document.querySelector('#detailsModal .modal-body');
        
        modalBody.innerHTML = `
            <div class="detail-card">
                <h4>${data.reservationTitle}</h4>
                <div class="d-flex justify-content-between mb-3">
                    <span><i class="fas fa-user me-2"></i>${data.clientName}</span>
                    <span class="badge bg-${data.status === 'Active' ? 'success' : 'secondary'}">${data.status}</span>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <p><i class="fas fa-calendar-alt me-2"></i> ${data.date}</p>
                        <p><i class="fas fa-euro-sign me-2"></i> ${data.cost} €</p>
                    </div>
                    <div class="col-md-6">
                        <p><i class="fas fa-map-marker-alt me-2"></i> ${data.startLocation}</p>
                        <p><i class="fas fa-flag-checkered me-2"></i> ${data.endLocation}</p>
                    </div>
                </div>
            </div>
        `;
        
        modal.show();
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            title: 'Error',
            text: 'Failed to load relocation details',
            icon: 'error'
        });
    }
}

async function showEditForm(relocationId) {
    try {
        // Charger les données de la relocation
        const response = await fetch(`/transporter/relocations/${relocationId}/edit`);
        const data = await response.json();
        
        // Afficher le formulaire dans le modal
        const modal = new bootstrap.Modal(document.getElementById('editRelocationModal'));
        const formContainer = document.getElementById('editRelocationFormContainer');
        
        formContainer.innerHTML = `
            <form id="editRelocationForm" method="post" 
                  action="/transporter/relocations/${data.id}/update">
                <div class="form-card">
                    <div class="form-group floating-label">
                        <input type="date" name="date" id="edit_date" 
                               class="form-control" required
                               value="${data.date}">
                        <label for="edit_date">Relocation Date</label>
                    </div>
                    
                    <div class="form-group floating-label">
                        <input type="number" name="cost" id="edit_cost" 
                               class="form-control" required step="0.01" min="0"
                               value="${data.cost}">
                        <label for="edit_cost">Cost (€)</label>
                    </div>
                    
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" name="status" 
                               id="edit_status" ${data.status ? 'checked' : ''}>
                        <label class="form-check-label" for="edit_status">Active</label>
                    </div>
                    
                    <input type="hidden" name="_token" 
                           value="${generateCsrfToken('update' + data.id)}">
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
        `;
        
        modal.show();
    } catch (error) {
        showErrorAlert('Error', 'Failed to load edit form');
    }
}

async function submitEditForm(form) {
    const formData = {
        date: form.querySelector('#edit_date').value,
        cost: parseFloat(form.querySelector('#edit_cost').value),
        status: form.querySelector('#edit_status').checked
    };

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Saving...';
    
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || 'Failed to update relocation');
        }

        if (data.success) {
            await Swal.fire({
                title: 'Success',
                text: data.message,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            
            updateRelocationCard(data.relocation);
            bootstrap.Modal.getInstance(document.getElementById('editRelocationModal')).hide();
        }
    } catch (error) {
        await Swal.fire({
            title: 'Error',
            text: error.message,
            icon: 'error'
        });
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}


async function confirmDelete(relocationId, title) {
    const result = await Swal.fire({
        title: 'Confirm Deletion',
        html: `Are you sure you want to delete <strong>${title}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    });
    
    if (result.isConfirmed) {
        try {
            const response = await fetch(`/transporter/relocations/${relocationId}/delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                showSuccessAlert('Deleted!', data.message);
                removeRelocationCard(relocationId);
            } else {
                showErrorAlert('Error', data.error || 'Failed to delete relocation');
            }
        } catch (error) {
            showErrorAlert('Error', 'Network error while deleting');
        }
    }
}


function updateRelocationCard(relocationData) {
    const card = document.querySelector(`.relocation-card[data-id="${relocationData.id}"]`);
    if (!card) return;
    
    // Mettre à jour la date
    const dateElement = card.querySelector('.meta-item:nth-child(1) span');
    if (dateElement) {
        dateElement.textContent = relocationData.date;
    }
    
    // Mettre à jour le coût
    const costElement = card.querySelector('.meta-item:nth-child(2) span');
    if (costElement) {
        costElement.textContent = `${relocationData.cost} €`;
    }
    
    // Mettre à jour le statut
    const statusBadge = card.querySelector('.card-badge');
    if (statusBadge) {
        statusBadge.textContent = relocationData.status ? 'ACTIVE' : 'INACTIVE';
        statusBadge.className = `card-badge ${relocationData.status ? 'active' : 'inactive'}`;
    }
}

function removeRelocationCard(relocationId) {
    const card = document.querySelector(`.relocation-card[data-id="${relocationId}"]`);
    if (card) {
        card.style.transition = 'all 0.3s ease';
        card.style.transform = 'scale(0.9)';
        card.style.opacity = '0';
        
        setTimeout(() => card.remove(), 300);
    }
}

function showSuccessAlert(title, text) {
    Swal.fire({
        title: title,
        text: text,
        icon: 'success',
        timer: 3000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
}

function showErrorAlert(title, text) {
    Swal.fire({
        title: title,
        text: text,
        icon: 'error',
        confirmButtonText: 'OK',
        confirmButtonColor: '#d33'
    });
}

// Helper pour générer un token CSRF (à adapter selon votre système)
function generateCsrfToken(key) {
    // Implémentez cette fonction selon votre système CSRF
    return document.querySelector(`meta[name="csrf-token"]`).content;
}