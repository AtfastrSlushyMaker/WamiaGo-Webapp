// Variables globales
let draggableModal = null;
let currentAnnouncementId = null;
let currentButton = null;

/**
 * Initialise la fonctionnalité de déplacement pour la modale
 */
function initializeDraggableModal() {
    const modal = document.getElementById('detailsModal');
    const modalDialog = modal.querySelector('.modal-dialog');
    const modalHeader = modal.querySelector('.modal-header');
    const modalContent = modal.querySelector('.modal-content');
    
    // Variables pour le déplacement
    let isDragging = false;
    let startX, startY, startLeft, startTop;

    // Initialiser le gestionnaire d'événements pour le déplacement
    modalHeader.addEventListener('mousedown', function(e) {
        // Ne pas déclencher le déplacement sur le bouton de fermeture
        if (e.target.closest('.btn-close')) return;
        
        // Marquer le début du déplacement
        isDragging = true;
        
        // Préparer le modal pour le déplacement
        modalDialog.classList.add('modal-dialog-draggable');
        document.body.classList.add('no-select');
        modalHeader.classList.add('dragging');
        
        // Enregistrer la position initiale du modal et du curseur
        startLeft = modalDialog.offsetLeft;
startTop = modalDialog.offsetTop;
        startX = e.clientX;
        startY = e.clientY;
        
        // Empêcher la sélection de texte et autres comportements par défaut
        e.preventDefault();
    });

    // Gérer le déplacement pendant que la souris bouge
    document.addEventListener('mousemove', function(e) {
        if (!isDragging) return;
        
        // Calculer le déplacement
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;
        
        // Vérifier les limites de l'écran
        let newLeft = startLeft + dx;
        let newTop = startTop + dy;
        
        // Empêcher le modal de sortir des limites de l'écran
        if (newLeft < 0) newLeft = 0;
        if (newTop < 0) newTop = 0;
        
        const maxX = window.innerWidth - modalDialog.offsetWidth;
        const maxY = window.innerHeight - 100; // Permettre un peu de dépassement en bas
        
        if (newLeft > maxX) newLeft = maxX;
        if (newTop > maxY) newTop = maxY;
        
        // Appliquer la nouvelle position
        modalDialog.style.left = `${newLeft}px`;
        modalDialog.style.top = `${newTop}px`;
    });

    // Arrêter le déplacement quand la souris est relâchée
    document.addEventListener('mouseup', function() {
        if (!isDragging) return;
        
        // Réinitialiser l'état de déplacement
        isDragging = false;
        document.body.classList.remove('no-select');
        modalHeader.classList.remove('dragging');
        
        // Conserver la classe pour maintenir le positionnement
    });

    // Arrêter le déplacement si la souris sort de la fenêtre
    document.addEventListener('mouseleave', function() {
        if (isDragging) {
            isDragging = false;
            document.body.classList.remove('no-select');
            modalHeader.classList.remove('dragging');
        }
    });

    // Réinitialiser la position du modal quand il est fermé
    modal.addEventListener('hidden.bs.modal', function() {
        modalDialog.classList.remove('modal-dialog-draggable');
        modalDialog.style.left = '';
        modalDialog.style.top = '';
        draggableModal = false;
    });

    

    
}


/**
 * Initialise les fonctionnalités des modals d'annonce
 */
function initializeAnnouncementModals() {
    const detailsModalEl = document.getElementById('detailsModal');
    if (!detailsModalEl) return;

    const detailsModal = new bootstrap.Modal(detailsModalEl, {
        backdrop: true,
        keyboard: true
    });

    // Réinitialise les styles après fermeture
    detailsModalEl.addEventListener('hidden.bs.modal', function () {
        document.body.style.overflow = 'auto';
        document.body.style.paddingRight = '0';
    });

    // Clic sur bouton "Détails"
    document.addEventListener('click', function (e) {
        if (e.target.closest('.btn-details')) {
            currentButton = e.target.closest('.btn-details');
            const card = currentButton.closest('.announcement-card');
            currentAnnouncementId = card?.dataset?.id;

            if (!currentAnnouncementId) return;

            // Charger et afficher les détails
            showAnnouncementDetails(currentAnnouncementId, detailsModal);

            // Positionner la modale près du bouton sur desktop
            if (window.innerWidth > 992) {
                setTimeout(() => positionModalNearButton(currentButton), 100);
            }
        }
    });

    // Clic sur bouton "Modifier"
    document.getElementById('editAnnouncementBtn')?.addEventListener('click', function () {
        if (currentAnnouncementId) {
            openEditModal(currentAnnouncementId);
        }
    });

    // Activer le drag & drop une seule fois
    detailsModalEl.addEventListener('shown.bs.modal', function () {
        if (!draggableModal) {
            initializeDraggableModal();
            draggableModal = true;
        }
    });
}


/**
 * Positionne le modal près du bouton cliqué
 * @param {HTMLElement} button - Le bouton qui a déclenché l'ouverture du modal
 */
function positionModalNearButton(button) {
    const modal = document.querySelector('#detailsModal .modal-dialog');
    if (!modal) return;
    
    const buttonRect = button.getBoundingClientRect();
    const modalWidth = modal.offsetWidth;
    
    // Calculer la position idéale
    let top = buttonRect.top + window.scrollY - 20;
    let left = buttonRect.right - modalWidth + 20;
    
    // Ajuster si la modale sort de l'écran
    if (left < 20) left = 20;
    if (top < 20) top = 20;
    
    const maxX = window.innerWidth - modalWidth - 20;
    if (left > maxX) left = maxX;
    
    // Appliquer le positionnement initial
    modal.classList.add('modal-dialog-draggable');
    modal.style.left = `${left}px`;
    modal.style.top = `${top}px`;
}

/**
 * Affiche les détails d'une annonce dans le modal
 * @param {string|number} id - L'ID de l'annonce à afficher
 * @param {bootstrap.Modal} modal - L'instance du modal Bootstrap
 */
async function showAnnouncementDetails(id, modal) {
    const modalBody = document.getElementById('detailsModalBody');
    if (!modalBody) return;

    // Afficher le loader
    modalBody.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading announcement details...</p>
        </div>
    `;

    // Afficher le modal
    modal.show();
    // Positionner juste après l'ouverture
setTimeout(() => {
    if (window.innerWidth > 992) {
        positionModalNearButton(currentButton);
    }
}, 10);

    try {
        // Récupérer les données de l'annonce
        const response = await fetch(`/transporter/announcements/${id}/details`);
        if (!response.ok) throw new Error('Network response was not ok');
        
        const data = await response.json();
        
        // Afficher les détails dans le modal
        renderDetails(data, modalBody);
        
    } catch (error) {
        console.error('Error:', error);
        modalBody.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Failed to load announcement details. Please try again.
            </div>
        `;
    }
}

/**
 * Affiche les détails de l'annonce dans le corps du modal
 * @param {Object} data - Les données de l'annonce
 * @param {HTMLElement} container - L'élément conteneur où afficher les détails
 */
function renderDetails(data, container) {
    // Même contenu que votre fonction renderDetails originale
    container.innerHTML = `
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="detail-card">
                    <div class="detail-icon">
                        <i class="fas fa-heading"></i>
                    </div>
                    <div>
                        <h6 class="detail-label">TITLE</h6>
                        <p class="detail-value">${data.title}</p>
                    </div>
                </div>
                
                <div class="detail-card">
                    <div class="detail-icon">
                        <i class="fas fa-align-left"></i>
                    </div>
                    <div>
                        <h6 class="detail-label">DESCRIPTION</h6>
                        <p class="detail-value text-muted">${data.content}</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="detail-card">
                    <div class="detail-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>
                        <h6 class="detail-label">ZONE</h6>
                        <p class="detail-value">
                            <span class="badge bg-primary">${data.zone}</span>
                        </p>
                    </div>
                </div>
                
                <div class="detail-card">
                    <div class="detail-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div>
                        <h6 class="detail-label">DATE</h6>
                        <p class="detail-value">${data.date}</p>
                    </div>
                </div>
                
                <div class="detail-card">
                    <div class="detail-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div>
                        <h6 class="detail-label">STATUS</h6>
                        <p class="detail-value">
                            <span class="badge ${data.status ? 'bg-success' : 'bg-secondary'}">
                                ${data.status ? 'Active' : 'Inactive'}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <div class="truck-animation-container">
                <div class="road"></div>
                <img src="/images/front/announcements/icons/d.png" class="truck-image" alt="Truck">
                <img src="/images/front/announcements/icons/wheel.png" class="truck-wheel wheel-front" alt="Wheel">
                <img src="/images/front/announcements/icons/wheel.png" class="truck-wheel wheel-rear" alt="Wheel">
            </div>
        </div>
    `;
}

function closeModalAndRefreshList() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('detailsModal'));
    modal.hide();

    // Recharger la liste des annonces via AJAX
    fetch('/transporter/announcements/')
        .then(response => response.text())
        .then(html => {
            // Extraire uniquement la partie de la liste des annonces
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newList = doc.querySelector('#announcement-list');

            const currentList = document.querySelector('#announcement-list');
            if (newList && currentList) {
                currentList.innerHTML = newList.innerHTML;
            }
        })
        .catch(error => {
            console.error('Erreur lors du rechargement de la liste :', error);
        });
}

// Initialisation de la modale de suppression
function initializeDeleteModal() {
    const deleteModalEl = document.getElementById('deleteModal');
    if (!deleteModalEl) return;

    // Positionnement forcé
    const dialog = deleteModalEl.querySelector('.modal-dialog');
    dialog.style.top = '100px';
    dialog.style.left = '50%';
    dialog.style.transform = 'translateX(-50%)';

    const deleteModal = new bootstrap.Modal(deleteModalEl, {
        backdrop: true,
        keyboard: true
    });

    let currentAnnouncementId = null;

    document.addEventListener('click', function (e) {
        if (e.target.closest('.btn-delete')) {
            const button = e.target.closest('.btn-delete');
            currentAnnouncementId = button.dataset.id;
            const announcementTitle = button.dataset.title;

            // Mettre à jour le titre dans la modale
            const titleEl = document.getElementById('announcementTitleToDelete');
            if (titleEl) {
                titleEl.textContent = announcementTitle;
            }

            // Mettre à jour l'action du formulaire
            const form = document.getElementById('deleteAnnouncementForm');
            if (form) {
                form.action = `/transporter/announcements/${currentAnnouncementId}/delete`;
            }

            // Mettre à jour le token CSRF
            const csrfInput = document.getElementById('deleteCsrfToken');
            if (csrfInput) {
                csrfInput.value = button.dataset.csrf || '';
            }

            // Afficher la modale
            deleteModal.show();
        }
    });
}


// Initialisation des boutons d'édition
function initializeEditButtons() {
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-edit')) {
            const button = e.target.closest('.btn-edit');
            const announcementId = button.dataset.id;
            openEditModal(announcementId);
        }
    });
}

async function openEditModal(announcementId) {
    try {
        const editModalEl = document.getElementById('editModal');
        if (!editModalEl) {
            console.error('Edit modal element not found');
            return;
        }

        // Forcer le positionnement avant ouverture
        const dialog = editModalEl.querySelector('.modal-dialog');
        dialog.style.top = '50px';
        dialog.style.left = '50%';
        dialog.style.transform = 'translateX(-50%)';

        const editModal = new bootstrap.Modal(editModalEl, {
            backdrop: true,
            keyboard: true
        });

        // Afficher le loader dans le corps de la modale
        const modalBody = editModalEl.querySelector('#editModalBody');
        modalBody.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Chargement du formulaire...</p>
            </div>
        `;

        editModal.show();

        // Charger dynamiquement le contenu
        const response = await fetch(`/transporter/announcements/${announcementId}/edit`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!response.ok) throw new Error('Impossible de charger le formulaire');

        const html = await response.text();
        modalBody.innerHTML = html;

        // Initialiser le drag & drop
        initEditModalDrag();

    } catch (error) {
        console.error('Edit modal error:', error);

        const modalBody = document.querySelector('#editModalBody');
        if (modalBody) {
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Erreur lors du chargement du formulaire : ${error.message}
                </div>
            `;
        }
    }
}



function initEditModalDrag() {
    const modal = document.querySelector('#editModal .modal-dialog');
    const header = document.querySelector('#editModal .modal-header');
    
    if (!modal || !header) return;

    let isDragging = false;
    let startX, startY, startLeft, startTop;

    header.addEventListener('mousedown', function(e) {
        if (e.target.closest('.btn-close')) return;
        
        isDragging = true;
        modal.classList.add('modal-dialog-draggable');
        document.body.classList.add('no-select');
        
        startLeft = modal.offsetLeft;
        startTop = modal.offsetTop;
        startX = e.clientX;
        startY = e.clientY;
        
        e.preventDefault();
    });

    document.addEventListener('mousemove', function(e) {
        if (!isDragging) return;
        
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;
        
        let newLeft = startLeft + dx;
        let newTop = startTop + dy;
        
        // Limites de l'écran
        newLeft = Math.max(0, Math.min(newLeft, window.innerWidth - modal.offsetWidth));
        newTop = Math.max(0, Math.min(newTop, window.innerHeight - 100));
        
        modal.style.left = `${newLeft}px`;
        modal.style.top = `${newTop}px`;
    });

    document.addEventListener('mouseup', () => {
        isDragging = false;
        document.body.classList.remove('no-select');
    });
}

// Gestion de la soumission du formulaire
function handleEditFormSubmission() {
    document.addEventListener('submit', async function(e) {
        if (e.target.id === 'edit-announcement-form') {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Updating...';
            
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
                    
                    // Fermer le modal après un délai
                    setTimeout(() => {
                        bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
                        
                        // Mettre à jour la carte
                        updateAnnouncementCard(data.announcement);
                    }, 1500);
                } else {
                    displayFormErrors(form, data.errors || {});
                    showToast(data.message || 'Error updating announcement', 'error');
                }
            } catch (error) {
                showToast('Network error: ' + error.message, 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
    });
}


function updateAnnouncementCard(announcementData) {
    const card = document.querySelector(`.announcement-card[data-id="${announcementData.id}"]`);
    if (!card) return;
    
    // Mettre à jour les éléments de la carte
    if (card.querySelector('.card-title')) {
        card.querySelector('.card-title').textContent = announcementData.title;
    }
    
    if (card.querySelector('.card-content')) {
        card.querySelector('.card-content').textContent = 
            announcementData.content.length > 120 
                ? announcementData.content.substring(0, 120) + '...' 
                : announcementData.content;
    }
    
    if (card.querySelector('.card-badge')) {
        const badge = card.querySelector('.card-badge');
        badge.className = `card-badge ${announcementData.status ? 'active' : 'inactive'}`;
        badge.textContent = announcementData.status ? 'ACTIVE' : 'INACTIVE';
    }
    
    // Mettre à jour la zone et date si nécessaire
    const metaItems = card.querySelectorAll('.meta-item span');
    if (metaItems.length > 0) {
        metaItems[0].textContent = announcementData.zone;
        metaItems[1].textContent = announcementData.date;
    }
}

async function handleEditFormSubmit(form) {
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Updating...';
    
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            
            // Fermer le modal après un délai
            setTimeout(() => {
                const editModal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
                if (editModal) editModal.hide();
                
                // Mettre à jour la carte
                if (data.announcement) {
                    updateAnnouncementCard(data.announcement);
                }
            }, 1500);
        } else {
            displayFormErrors(form, data.errors || {});
            showToast(data.message || 'Error updating announcement', 'error');
        }
    } catch (error) {
        console.error('Update error:', error);
        showToast('Network error: ' + error.message, 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

function displayFormErrors(form, errors) {
    // Réinitialiser les erreurs précédentes
    form.querySelectorAll('.is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });
    
    form.querySelectorAll('.invalid-feedback').forEach(el => {
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







document.addEventListener('DOMContentLoaded', function() {
    initializeAnnouncementModals();
    initializeDeleteModal();
    initializeEditButtons();
    handleEditFormSubmission();
    document.addEventListener('submit', function(e) {
        if (e.target.id === 'edit-announcement-form') {
            e.preventDefault();
            handleEditFormSubmit(e.target);
        }
    });
});