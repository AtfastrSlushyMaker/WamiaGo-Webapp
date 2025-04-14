// Initialisation de la modale
let draggableModal = null;

function initializeDraggableModal() {
    const modal = document.getElementById('detailsModal');
    const modalDialog = modal.querySelector('.modal-dialog');
    const modalHeader = modal.querySelector('.modal-header');
    
    let isDragging = false;
    let startX, startY, startLeft, startTop;

    modalHeader.style.cursor = 'grab';

    modalHeader.addEventListener('mousedown', (e) => {
        if (e.target.closest('.btn-close')) return;
        
        isDragging = true;
        modalDialog.style.position = 'fixed';
        modalDialog.style.margin = '0';
        
        const rect = modalDialog.getBoundingClientRect();
        startLeft = rect.left;
        startTop = rect.top;
        startX = e.clientX;
        startY = e.clientY;
        
        document.body.style.userSelect = 'none';
        modalHeader.style.cursor = 'grabbing';
        
        e.preventDefault();
    });

    document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;
        
        modalDialog.style.left = `${startLeft + dx}px`;
        modalDialog.style.top = `${startTop + dy}px`;
    });

    document.addEventListener('mouseup', () => {
        isDragging = false;
        document.body.style.userSelect = '';
        modalHeader.style.cursor = 'grab';
    });
}

function initializeAnnouncementModals() {
    const detailsModal = new bootstrap.Modal('#detailsModal');
    let currentAnnouncementId = null;
    let currentButton = null;

    // Gestion du clic sur Details
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-details')) {
            currentButton = e.target.closest('.btn-details');
            const card = e.target.closest('.announcement-card');
            currentAnnouncementId = card.dataset.id;
            showAnnouncementDetails(currentAnnouncementId);
            
            // Positionner la modale près du bouton cliqué (sur desktop)
            if (window.innerWidth > 992) {
                positionModalNearButton(currentButton);
            }
        }
    });

    // Gestion du bouton Edit
    document.getElementById('editAnnouncementBtn')?.addEventListener('click', function() {
        if (currentAnnouncementId) {
            window.location.href = `/transporter/announcements/${currentAnnouncementId}/edit`;
        }
    });

    // Positionner la modale près du bouton cliqué
    function positionModalNearButton(button) {
        const modal = document.querySelector('#detailsModal .modal-dialog');
        if (!modal) return;
        
        const buttonRect = button.getBoundingClientRect();
        const modalWidth = modal.offsetWidth;
        
        // Calculer la position
        let top = buttonRect.top + window.scrollY - 20;
        let left = buttonRect.left - modalWidth - 20;
        
        // Ajuster si la modale sort de l'écran
        if (left < 20) left = 20;
        if (top < 20) top = 20;
        
        // Appliquer le positionnement
        modal.style.position = 'absolute';
        modal.style.left = `${left}px`;
        modal.style.top = `${top}px`;
        modal.style.margin = '0';
    }

    // Réinitialiser le positionnement lors du redimensionnement
    window.addEventListener('resize', function() {
        const modal = document.querySelector('#detailsModal .modal-dialog');
        if (modal) {
            modal.style.position = '';
            modal.style.left = '';
            modal.style.top = '';
            modal.style.margin = '';
        }
    });

    async function showAnnouncementDetails(id) {
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

        detailsModal.show();

        try {
            const response = await fetch(`/transporter/announcements/${id}/details`);
            if (!response.ok) throw new Error('Network response was not ok');
            
            const data = await response.json();
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

    function renderDetails(data, container) {
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
                <img src="/images/front/announcements/icons/d.png" 
                     alt="Announcement illustration" 
                     class="img-fluid announcement-illustration">
            </div>
        `;
    }
    detailsModal.show();
    initializeDraggableModal();
}

// Initialisation quand le DOM est prêt
document.addEventListener('DOMContentLoaded', initializeAnnouncementModals);