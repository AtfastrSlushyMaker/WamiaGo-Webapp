function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        console.warn('Toast container not found');
        return;
    }
    
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
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        toast.remove();
    }, 5000);
}


document.addEventListener('DOMContentLoaded', function() {

    
    // Vérifier que le modal existe avant de l'initialiser
    const detailsModalEl = document.getElementById('detailsModal');
    if (!detailsModalEl) return;
    
    const detailsModal = new bootstrap.Modal(detailsModalEl);
    
    document.querySelectorAll('.btn-details').forEach(button => {
        button.addEventListener('click', function() {
            const announcementId = this.getAttribute('data-id');
            fetchAnnouncementDetails(announcementId, detailsModal);
        });
    });
});

async function fetchAnnouncementDetails(announcementId, modal) {
    try {
        // Afficher le loader dans le modal
        const modalBody = document.querySelector('#detailsModal .modal-body');
        if (!modalBody) return;
        
        modalBody.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading announcement details...</p>
            </div>
        `;
        
        modal.show();

        const response = await fetch(`/transporter/announcements/${announcementId}/details`);
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        const data = await response.json();
        populateDetailsModal(data, modalBody);
        
    } catch (error) {
        console.error('Error:', error);
        if (modalBody) {
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error loading announcement details. Please try again.
                </div>
            `;
        }
    }
}

function populateDetailsModal(data, container) {
    container.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <div class="detail-item mb-3">
                    <h5 class="text-primary">Title</h5>
                    <p class="fs-5">${data.title}</p>
                </div>
                <div class="detail-item mb-3">
                    <h5 class="text-primary">Content</h5>
                    <p class="text-muted">${data.content}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detail-item mb-3">
                    <h5 class="text-primary">Zone</h5>
                    <p><span class="badge bg-primary">${data.zone}</span></p>
                </div>
                <div class="detail-item mb-3">
                    <h5 class="text-primary">Date</h5>
                    <p>${data.date}</p>
                </div>
                <div class="detail-item mb-3">
                    <h5 class="text-primary">Status</h5>
                    <span class="badge ${data.status ? 'bg-success' : 'bg-secondary'}">
                        ${data.status ? 'Active' : 'Inactive'}
                    </span>
                </div>
            </div>
        </div>
        <div class="text-center mt-3">
            <img src="/images/front/announcements/icons/d.png" 
     alt="Announcement illustration" 
     class="img-fluid"
     style="max-height: 200px;">
        </div>
    `;
}

// Enhanced form submission with better AJAX handling
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation du formulaire
    const form = document.getElementById('announcement-form');
    if (!form) return;

    // Gestion de la soumission du formulaire
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        
        // Afficher l'état de chargement
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="submit-spinner"><i class="fas fa-spinner fa-spin"></i></span> Publication en cours...';
        
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Succès
                form.classList.add('submit-success');
                showToast(data.message || 'Annonce publiée avec succès !', 'success');
                
                // Redirection après 1.5 secondes
                setTimeout(() => {
                    window.location.href = data.redirectUrl || '/transporter/announcements';
                }, 1500);
            } else {
                // Erreurs
                if (data.errors) {
                    displayFormErrors(form, data.errors);
                }
                showToast(data.message || 'Erreur lors de la soumission du formulaire', 'error');
                form.classList.add('submit-error');
                setTimeout(() => form.classList.remove('submit-error'), 1000);
            }
        } catch (error) {
            showToast('Erreur réseau. Veuillez réessayer.', 'error');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    });
    
    // Fonction pour afficher les erreurs de formulaire
    function displayFormErrors(form, errors) {
        // Réinitialiser les erreurs précédentes
        document.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        
        document.querySelectorAll('.invalid-feedback').forEach(el => {
            el.textContent = '';
        });
        
        // Afficher les nouvelles erreurs
        Object.entries(errors).forEach(([field, message]) => {
            const input = form.querySelector(`[name*="${field}"]`);
            if (input) {
                input.classList.add('is-invalid');
                const feedback = input.closest('.form-group')?.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.textContent = message;
                }
            }
        });
    }

    // Initialisation des effets 3D
    VanillaTilt.init(document.querySelectorAll("[data-3d]"), {
        max: 5,
        speed: 400,
        glare: true,
        "max-glare": 0.2,
    });
});

// Enhanced error display
function displayFormErrors(errors) {
    // Clear previous errors
    document.querySelectorAll('.is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });
    
    document.querySelectorAll('.invalid-feedback').forEach(el => {
        el.textContent = '';
    });
    
    // Add new errors
    Object.entries(errors).forEach(([field, messages]) => {
        const input = form.querySelector(`[name*="${field}"]`);
        if (input) {
            input.classList.add('is-invalid');
            const feedback = input.closest('.form-group').querySelector('.invalid-feedback');
            if (feedback) {
                feedback.textContent = Array.isArray(messages) ? messages.join(' ') : messages;
            }
        } else {
            // Show general errors
            showToast(messages, 'error');
        }
    });
}

// Gestion de la soumission du formulaire de suppression
document.getElementById('deleteAnnouncementForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Afficher un loader
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
    submitBtn.disabled = true;
    
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams(new FormData(form))
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Recharger la page ou mettre à jour l'UI
            window.location.href = data.redirectUrl || window.location.href;
        } else {
            showToast(data.error || 'Error deleting announcement', 'error');
            bootstrap.Modal.getInstance('#deleteModal').hide();
        }
    } catch (error) {
        showToast('Network error: ' + error.message, 'error');
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
});

// Initialisation des boutons d'édition
function initEditButtons() {
    document.querySelectorAll('.btn-edit').forEach(button => {
        button.addEventListener('click', function() {
            const announcementId = this.getAttribute('data-id');
            openEditModal(announcementId);
        });
    });
}

// Ouverture de la modal d'édition
async function openEditModal(announcementId) {
    try {
        const response = await fetch(`/transporter/announcements/${announcementId}/edit`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Failed to load edit form');
        }
        
        const html = await response.text();
        // ... reste du code
    } catch (error) {
        console.error('Edit error:', error);
        showToast(error.message, 'error');
    }
}

// Gestion de la soumission du formulaire d'édition
function initEditForm(announcementId, modal) {
    const form = document.getElementById('edit-announcement-form');
    if (!form) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(data.message, 'success');
                modal.hide();
                
                // Mise à jour de la carte correspondante
                updateAnnouncementCard(announcementId, data.announcement);
            } else {
                displayFormErrors(form, data.errors || {});
                if (data.message) {
                    showToast(data.message, 'error');
                }
            }
        } catch (error) {
            showToast('Network error: ' + error.message, 'error');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    });
}

// Mise à jour de la carte après édition
function updateAnnouncementCard(id, data) {
    const card = document.querySelector(`.announcement-card[data-id="${id}"]`);
    if (!card) return;
    
    // Mettez à jour les éléments de la carte
    if (card.querySelector('.card-title')) {
        card.querySelector('.card-title').textContent = data.title;
    }
    
    if (card.querySelector('.card-content')) {
        card.querySelector('.card-content').textContent = 
            data.content.length > 120 ? data.content.substring(0, 120) + '...' : data.content;
    }
    
    if (card.querySelector('.card-badge')) {
        card.querySelector('.card-badge').className = 
            `card-badge ${data.status ? 'active' : 'inactive'}`;
        card.querySelector('.card-badge').textContent = 
            data.status ? 'ACTIVE' : 'INACTIVE';
    }
    
    // Mettez à jour les autres champs si nécessaire
}

// Appelez initEditButtons au chargement
document.addEventListener('DOMContentLoaded', function() {
    initEditButtons();
});
