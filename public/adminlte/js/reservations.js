document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // Initialisation des toasts Bootstrap
    const toastElList = [].slice.call(document.querySelectorAll('.toast'));
    const toastList = toastElList.map(function(toastEl) {
        return new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: 5000
        });
    });
    toastList.forEach(toast => toast.show());
    
    // Handle delete confirmation modal
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const modal = this;
            
            // Mise à jour du texte de confirmation
            const title = button.getAttribute('data-title');
            if (title) {
                const confirmationText = modal.querySelector('#delete-confirmation-text');
                if (confirmationText) {
                    confirmationText.innerHTML = 
                        'Are you sure you want to delete the announcement: <strong>"' + title + '"</strong>?';
                }
            }
            
            const form = modal.querySelector('#deleteForm');
            if (form) {
                form.action = button.getAttribute('data-delete-url') || 
                            '/admin/reservations/' + button.getAttribute('data-id') + '/delete';
                
                const tokenInput = form.querySelector('input[name="_token"]');
                if (tokenInput) {
                    tokenInput.value = button.getAttribute('data-token');
                }
            }
        });
    }
    
    // Add fade-in animation to table rows
    document.querySelectorAll('table tbody tr').forEach(row => {
        row.classList.add('fade-in');
    });
    
    // Initialize tooltips (Bootstrap 5+)
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Add 3D hover effect to cards
    document.querySelectorAll('.card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'perspective(1000px) rotateX(5deg) translateY(-5px)';
            card.style.boxShadow = '0 20px 30px rgba(0, 0, 0, 0.15)';
            card.style.transition = 'all 0.3s ease';
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = '';
            card.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.08)';
        });
    });
    
    // Add 3D effect to images
    document.querySelectorAll('.img-3d').forEach(img => {
        img.addEventListener('mouseenter', () => {
            img.style.transform = 'perspective(1000px) rotateX(10deg) translateY(-5px)';
            img.style.boxShadow = '0 20px 30px rgba(0, 0, 0, 0.15)';
            img.style.transition = 'all 0.5s ease';
        });
        
        img.addEventListener('mouseleave', () => {
            img.style.transform = '';
            img.style.boxShadow = '';
        });
    });

    // Animation supplémentaire pour les icônes
    document.querySelectorAll('.animated-icon').forEach(icon => {
        icon.addEventListener('mouseenter', function() {
            this.style.animation = 'none';
            setTimeout(() => {
                this.style.animation = '';
            }, 10);
        });
        
        // Effet de rebond au clic
        icon.addEventListener('click', function() {
            this.style.transform = 'scale(0.8)';
            setTimeout(() => {
                this.style.transform = '';
            }, 300);
        });
    });
});

// Fonction pour afficher un toast (utilisable avec AJAX)
function showToast(type, message) {
    const toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) return;
    
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div class="toast show align-items-center text-white bg-${type} border-0" id="${toastId}" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    // Auto-hide après 5 secondes
    setTimeout(() => {
        const toastElement = document.getElementById(toastId);
        if (toastElement) {
            bootstrap.Toast.getInstance(toastElement)?.hide();
            setTimeout(() => toastElement.remove(), 500);
        }
    }, 5000);
    
    // Initialisation du toast Bootstrap
    const toastElement = document.getElementById(toastId);
    if (toastElement) {
        new bootstrap.Toast(toastElement).show();
    }
}

// Add animations to CSS
document.head.insertAdjacentHTML('beforeend', `
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
        .fade-out {
            animation: fadeOut 0.3s ease-out forwards;
        }
        .card {
            transition: all 0.3s ease;
        }
        .img-3d {
            transition: all 0.5s ease;
        }
        
        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
        }
        
        .toast {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            overflow: hidden;
            margin-bottom: 1rem;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.4s cubic-bezier(0.21, 1.02, 0.73, 1);
        }
        
        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }
        
        .toast.hide {
            opacity: 0;
            transform: translateY(-20px);
        }
        
        .bg-success {
            background-color: #28a745 !important;
        }
        
        .bg-error {
            background-color: #dc3545 !important;
        }
        
        /* Animation */
        @keyframes slideInRight {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .toast.show {
            animation: slideInRight 0.5s forwards, fadeIn 0.5s forwards;
        }
    </style>
`);