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