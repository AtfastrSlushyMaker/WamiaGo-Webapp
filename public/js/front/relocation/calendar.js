document.addEventListener('DOMContentLoaded', function() {
    let currentEventId = null;
    const calendarEl = document.getElementById('calendar');

    // Initialize FullCalendar
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'en', 
        themeSystem: 'bootstrap5',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        buttonText: {
            today: "Today", 
            month: "Month", 
            week: "Week",   
            day: "Day"     
        },
        firstDay: 1, 
        nowIndicator: true,
        editable: true,
        selectable: true,
        dayMaxEvents: true,
        weekNumbers: true,
        navLinks: true,
        events: '/transporter/relocations/api/calendar-events',
        
        eventDisplay: 'block',
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false    
        },
        eventDidMount: function(info) { 
            const timeEl = info.el.querySelector('.fc-event-time');
            if (timeEl && info.event.start.getHours() === 0 && info.event.start.getMinutes() === 0) {
                timeEl.style.display = 'none';
            }
        },
        
        eventClick: function(info) {
            const event = info.event;
            currentEventId = event.id;
            
            // Récupérer la référence au modal
            const modalElement = document.getElementById('eventModal');
            
            // Configurer le contenu du modal avec textes en anglais
            document.getElementById('eventModalTitle').textContent = event.title;
            document.getElementById('eventModalBody').innerHTML = `
                <div class="event-details">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong><i class="fas fa-user"></i> Client:</strong> ${event.extendedProps.client}</p>
                            <p><strong><i class="fas fa-coins"></i> Cost:</strong> ${event.extendedProps.cost} €</p>
                        </div>
                        <div class="col-md-6">
                            
                            <p><strong>Status:</strong> 
                                <span class="badge ${event.extendedProps.status ? 'bg-success' : 'bg-secondary'}">
                                    ${event.extendedProps.status ? 'Active' : 'Inactive'}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="location-container mt-4">
                        <div class="location-item text-danger">
                            <i class="fas fa-map-marker-alt fa-lg"></i>
                            <div>
                                <h6>Departure</h6>
                                <p>${event.extendedProps.startLocation}</p>
                            </div>
                        </div>
                        <div class="location-item text-success">
                            <i class="fas fa-flag-checkered fa-lg"></i>
                            <div>
                                <h6>Arrival</h6>
                                <p>${event.extendedProps.endLocation}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-muted small">
                        <i class="fas fa-info-circle"></i> Reservation ID: ${event.id}
                    </div>
                </div>
            `;
            
            // Créer et afficher le modal en s'assurant qu'il est centré
            const modal = new bootstrap.Modal(modalElement, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
            
            // S'assurer que le modal est bien positionné au centre
            modalElement.style.display = 'block';
            modalElement.style.paddingRight = '17px'; // Compenser la barre de défilement
            
            modal.show();
            
            // Vérifier que le modal est bien au centre après l'animation
            modalElement.addEventListener('shown.bs.modal', function () {
                const modalDialog = modalElement.querySelector('.modal-dialog');
                if (modalDialog) {
                    modalDialog.style.display = 'flex';
                    modalDialog.style.alignItems = 'center';
                }
            }, { once: true });
        }
    });
    
    // Fonction pour rafraîchir les détails d'un événement dans le modal
    async function refreshEventDetails(eventId) {
        try {
            // Récupérer les données mises à jour depuis le serveur
            const response = await fetch(`/transporter/relocations/${eventId}/details`);
            const updatedEvent = await response.json();
            
            // Si le modal est ouvert et correspond à l'événement en cours, mettre à jour son contenu
            if (currentEventId === eventId) {
                document.getElementById('eventModalTitle').textContent = updatedEvent.title;
                document.getElementById('eventModalBody').innerHTML = `
                    <div class="event-details">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong><i class="fas fa-user"></i> Client:</strong> ${updatedEvent.client}</p>
                                <p><strong><i class="fas fa-coins"></i> Cost:</strong> ${updatedEvent.cost} €</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Status:</strong> 
                                    <span class="badge ${updatedEvent.status ? 'bg-success' : 'bg-secondary'}">
                                        ${updatedEvent.status ? 'Active' : 'Inactive'}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="location-container mt-4">
                            <div class="location-item text-danger">
                                <i class="fas fa-map-marker-alt fa-lg"></i>
                                <div>
                                    <h6>Departure</h6>
                                    <p>${updatedEvent.startLocation}</p>
                                </div>
                            </div>
                            <div class="location-item text-success">
                                <i class="fas fa-flag-checkered fa-lg"></i>
                                <div>
                                    <h6>Arrival</h6>
                                    <p>${updatedEvent.endLocation}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-muted small">
                            <i class="fas fa-info-circle"></i> Reservation ID: ${eventId}
                        </div>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Failed to refresh event details:', error);
        }
    }
    
    // Delete relocation
    document.getElementById('deleteRelocationBtn').addEventListener('click', async () => {
        if (!currentEventId) return;

        const confirmation = await Swal.fire({
            title: 'Confirm Deletion',
            text: "Are you sure you want to delete this relocation?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        });

        if (confirmation.isConfirmed) {
            try {
                const response = await fetch(`/transporter/relocations/${currentEventId}/delete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                if (response.ok) {
                    window.refreshCalendar(); // Utiliser la fonction globale de rafraîchissement
                    bootstrap.Modal.getInstance(document.getElementById('eventModal')).hide();
                    Swal.fire('Deleted!', 'The relocation has been deleted.', 'success');
                }
            } catch (error) {
                Swal.fire('Error!', 'Deletion failed.', 'error');
            }
        }
    });

    // Edit relocation
    document.getElementById('editRelocationBtn').addEventListener('click', async () => {
        if (!currentEventId) return;

        try {
            const response = await fetch(`/transporter/relocations/${currentEventId}/edit`);
            const data = await response.json();

            const { value: formData } = await Swal.fire({
                title: 'Edit Relocation',
                html: `
                    <input type="datetime-local" id="editDate" class="swal2-input" 
                           value="${data.date.replace(' ', 'T')}" required>
                    <input type="number" id="editCost" class="swal2-input" 
                           value="${data.cost}" step="0.01" required>
                    <div class="form-check" style="text-align: left; margin-top:10px;">
                        <input type="checkbox" id="editStatus" 
                               ${data.status ? 'checked' : ''} class="form-check-input">
                        <label class="form-check-label" for="editStatus">Active</label>
                    </div>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Save',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    return {
                        date: document.getElementById('editDate').value,
                        cost: document.getElementById('editCost').value,
                        status: document.getElementById('editStatus').checked
                    };
                }
            });

            if (formData) {
                const updateResponse = await fetch(`/transporter/relocations/${currentEventId}/update`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });

                if (updateResponse.ok) {
                    // Rafraîchir le calendrier
                    calendar.refetchEvents();
                    
                    // Rafraîchir les détails dans le modal
                    refreshEventDetails(currentEventId);
                    
                    Swal.fire('Success!', 'The relocation has been updated.', 'success');
                }
            }
        } catch (error) {
            Swal.fire('Error!', 'Update failed.', 'error');
        }
    });

    // Ajouter une méthode d'actualisation globale pour les controllers
    window.refreshRelocationDetails = function(eventId) {
        if (eventId) {
            refreshEventDetails(eventId);
        }
        calendar.refetchEvents();
    };

    calendar.render();
});