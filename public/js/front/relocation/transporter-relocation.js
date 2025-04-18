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

        // Utiliser SweetAlert2 au lieu de Bootstrap Modal
        await Swal.fire({
            title: 'Relocation Details',
            html: `
                <div class="detail-card">
                    <div class="detail-section">
                        <div class="detail-item">
                            <i class="fas fa-clipboard-list"></i>
                            <div>
                                <h6 class="detail-label">Reservation</h6>
                                <p class="detail-value">${data.reservationTitle}</p>
                            </div>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-user"></i>
                            <div>
                                <h6 class="detail-label">Client</h6>
                                <p class="detail-value">${data.clientName}</p>
                            </div>
                        </div>
                    </div>

                    <div class="detail-section">
                        <div class="detail-item">
                            <i class="fas fa-calendar-alt"></i>
                            <div>
                                <h6 class="detail-label">Date</h6>
                                <p class="detail-value">${data.date}</p>
                            </div>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-euro-sign"></i>
                            <div>
                                <h6 class="detail-label">Cost</h6>
                                <p class="detail-value">${data.cost} €</p>
                            </div>
                        </div>
                    </div>

                    <div class="detail-section">
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <h6 class="detail-label">Start Location</h6>
                                <p class="detail-value">${data.startLocation}</p>
                            </div>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-flag-checkered"></i>
                            <div>
                                <h6 class="detail-label">End Location</h6>
                                <p class="detail-value">${data.endLocation}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `,
            width: '600px',
            showCloseButton: true,
            showConfirmButton: false,
            customClass: {
                container: 'relocation-modal-container',
                popup: 'relocation-modal-popup',
                title: 'relocation-modal-title',
                closeButton: 'relocation-modal-close',
                content: 'relocation-modal-content'
            }
        });
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            title: 'Error',
            text: 'Failed to load relocation details',
            icon: 'error',
            confirmButtonColor: '#5A6BE5'
        });
    }
}

async function showEditForm(relocationId) {
    try {
        const response = await fetch(`/transporter/relocations/${relocationId}/edit`);
        const data = await response.json();
        
        const { value: formValues } = await Swal.fire({
            title: 'Edit Relocation',
            html: `
                <form id="editRelocationForm" class="edit-form">
                    <div class="form-group">
                        <label for="edit_date">Relocation Date</label>
                        <input type="date" id="edit_date" class="form-control" 
                               value="${data.date}" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_cost">Cost (€)</label>
                        <input type="number" id="edit_cost" class="form-control" 
                               value="${data.cost}" required step="0.01" min="0">
                    </div>
                    
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="edit_status" 
                               ${data.status ? 'checked' : ''}>
                        <label class="form-check-label" for="edit_status">Active</label>
                    </div>
                </form>
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Save Changes',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#5A6BE5',
            width: '500px',
            customClass: {
                container: 'edit-modal-container',
                popup: 'edit-modal-popup',
                title: 'edit-modal-title',
                confirmButton: 'edit-modal-confirm',
                cancelButton: 'edit-modal-cancel'
            },
            preConfirm: () => ({
                date: document.getElementById('edit_date').value,
                cost: parseFloat(document.getElementById('edit_cost').value),
                status: document.getElementById('edit_status').checked
            })
        });

        if (formValues) {
            await updateRelocation(relocationId, formValues);
        }
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

    // Animation de mise à jour
    card.style.transition = 'all 0.3s ease';
    card.style.transform = 'scale(0.95)';
    card.style.opacity = '0.7';

    setTimeout(() => {
        // Mettre à jour le contenu
        card.querySelector('.meta-item:nth-child(1) span').textContent = relocationData.date;
        card.querySelector('.meta-item:nth-child(2) span').textContent = `${relocationData.cost} €`;
        
        const statusBadge = card.querySelector('.card-badge');
        statusBadge.textContent = relocationData.status ? 'ACTIVE' : 'INACTIVE';
        statusBadge.className = `card-badge ${relocationData.status ? 'active' : 'inactive'}`;

        // Animation de retour
        card.style.transform = 'scale(1)';
        card.style.opacity = '1';
    }, 300);
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