document.addEventListener('DOMContentLoaded', function() {
    const Swal = window.Swal;
    const csrfToken = document.getElementById('csrf-token').dataset.token;
    
    // Handle details button click
    document.addEventListener('click', async function(e) {
        if (e.target.closest('.btn-details')) {
            const relocationId = e.target.closest('.btn-details').dataset.id;
            await showDetailsModal(relocationId);
        }
        
        if (e.target.closest('.btn-delete')) {
            const relocationId = e.target.closest('.btn-delete').dataset.id;
            const relocationTitle = e.target.closest('.btn-delete').dataset.title;
            await handleDeleteRelocation(relocationId, relocationTitle);
        }
    });

    async function showDetailsModal(relocationId) {
        try {
            const response = await fetch(`/client/relocations/${relocationId}/details`);
            
            if (!response.ok) {
                throw new Error('Failed to fetch relocation details');
            }

            const data = await response.json();

            await Swal.fire({
                title: `<strong>${data.reservationTitle}</strong>`,
                html: `
                    <div class="text-start">
                        <div class="detail-section mb-3">
                            <div class="detail-item">
                                <i class="fas fa-calendar-alt"></i>
                                <div class="detail-item-content">
                                    <div class="detail-label">Date</div>
                                    <div class="detail-value">${data.date}</div>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-euro-sign"></i>
                                <div class="detail-item-content">
                                    <div class="detail-label">Cost</div>
                                    <div class="detail-value">${data.cost} €</div>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-info-circle"></i>
                                <div class="detail-item-content">
                                    <div class="detail-label">Status</div>
                                    <div class="detail-value">
                                        <span class="badge ${data.status.toLowerCase()}">${data.status}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="detail-section mb-3">
                            <div class="detail-item">
                                <i class="fas fa-user-tie"></i>
                                <div class="detail-item-content">
                                    <div class="detail-label">Transporter</div>
                                    <div class="detail-value">${data.transporterName}</div>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-phone"></i>
                                <div class="detail-item-content">
                                    <div class="detail-label">Phone</div>
                                    <div class="detail-value">${data.transporterPhone}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <div class="detail-item">
                                <i class="fas fa-map-marker-alt text-danger"></i>
                                <div class="detail-item-content">
                                    <div class="detail-label">From</div>
                                    <div class="detail-value">${data.startLocation}</div>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-flag-checkered text-success"></i>
                                <div class="detail-item-content">
                                    <div class="detail-label">To</div>
                                    <div class="detail-value">${data.endLocation}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `,
                showCloseButton: true,
                showConfirmButton: false,
                width: '600px',
                background: '#fff',
                backdrop: 'rgba(0,0,0,0.5)',
                customClass: {
                    container: 'reservation-modal-container',
                    popup: 'reservation-modal-popup',
                    closeButton: 'swal-close-button'
                },
                position: 'top',
                grow: 'row',
                willOpen: () => {
                    const popup = Swal.getPopup();
                    //popup.style.top = '100px';
                    popup.style.left = '20%';
                    popup.style.transform = 'translateX(-50%)';
                },
                willClose: () => {
                    document.activeElement.blur();
                }
            });

        } catch (error) {
            await Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message,
                timer: 3000,
                position: 'top'
            });
        }
    }

    async function handleDeleteRelocation(relocationId, relocationTitle) {
        try {
            const result = await Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete relocation "${relocationTitle}". This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'No, cancel!',
                reverseButtons: true
            });
            
            if (result.isConfirmed) {
                const response = await fetch(`/client/relocations/${relocationId}/delete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        _token: csrfToken
                    })
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.error || 'Failed to delete relocation');
                }
                
                // Show success message
                await Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: data.message,
                    timer: 2000,
                    position: 'top'
                });
                
                // Remove the card from the UI
                removeRelocationCard(relocationId);
            }
            
        } catch (error) {
            await Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message,
                timer: 3000,
                position: 'top'
            });
        }
    }

    function removeRelocationCard(relocationId) {
        const card = document.querySelector(`.relocation-card[data-id="${relocationId}"]`);
        if (card) {
            card.style.opacity = '0';
            setTimeout(() => card.remove(), 300);
        }
    }
});