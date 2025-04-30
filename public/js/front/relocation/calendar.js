document.addEventListener('DOMContentLoaded', function() {
    let currentEventId = null;
    const calendarEl = document.getElementById('calendar');

    // Initialize FullCalendar
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'fr',
        themeSystem: 'bootstrap5',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        buttonText: {
            today: "Aujourd'hui",
            month: "Mois",
            week: "Semaine",
            day: "Jour"
        },
        firstDay: 1, // Start week on Monday
        nowIndicator: true,
        editable: true,
        selectable: true,
        dayMaxEvents: true,
        weekNumbers: true,
        navLinks: true,
        events: '/transporter/relocations/api/calendar-events',
        
        eventDisplay: 'block', // Affiche l'événement en bloc
        eventTimeFormat: {     // Format de l'heure (24h)
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        },
        eventDidMount: function(info) { // Cacher l'heure si 00:00
            const timeEl = info.el.querySelector('.fc-event-time');
            if (timeEl && info.event.start.getHours() === 0 && info.event.start.getMinutes() === 0) {
                timeEl.style.display = 'none';
            }
        },
        
        eventClick: function(info) {
            const event = info.event;
            currentEventId = event.id;
            const modal = new bootstrap.Modal(document.getElementById('eventModal'));
            
            document.getElementById('eventModalTitle').textContent = event.title;
            document.getElementById('eventModalBody').innerHTML = `
                <div class="event-details">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong><i class="fas fa-user"></i> Client:</strong> ${event.extendedProps.client}</p>
                            <p><strong><i class="fas fa-coins"></i> Coût:</strong> ${event.extendedProps.cost} €</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong><i class="fas fa-calendar-day"></i> Date réservation:</strong> 
                            ${event.extendedProps.reservationDate}</p>
                            <p><strong>Statut:</strong> 
                                <span class="badge ${event.extendedProps.status ? 'bg-success' : 'bg-secondary'}">
                                    ${event.extendedProps.status ? 'Actif' : 'Inactif'}
                                </span>
                            </p>
                        </div>
                    </div>
        
                    <div class="location-container mt-4">
                        <div class="location-item text-danger">
                            <i class="fas fa-map-marker-alt fa-lg"></i>
                            <div>
                                <h6>Départ</h6>
                                <p>${event.extendedProps.startLocation}</p>
                            </div>
                        </div>
                        <div class="location-item text-success">
                            <i class="fas fa-flag-checkered fa-lg"></i>
                            <div>
                                <h6>Arrivée</h6>
                                <p>${event.extendedProps.endLocation}</p>
                            </div>
                        </div>
                    </div>
        
                    <div class="mt-4 text-muted small">
                        <i class="fas fa-info-circle"></i> ID Réservation: ${event.id}
                    </div>
                </div>
            `;
            
            modal.show();
        }
    });
    
    // Delete relocation
    document.getElementById('deleteRelocationBtn').addEventListener('click', async () => {
        if (!currentEventId) return;

        const confirmation = await Swal.fire({
            title: 'Confirmer la suppression',
            text: "Êtes-vous sûr de vouloir supprimer ce déplacement ?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Oui, supprimer !'
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
                    calendar.refetchEvents();
                    bootstrap.Modal.getInstance(document.getElementById('eventModal')).hide();
                    Swal.fire('Supprimé!', 'Le déplacement a été supprimé.', 'success');
                }
            } catch (error) {
                Swal.fire('Erreur!', 'La suppression a échoué.', 'error');
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
                title: 'Modifier le déplacement',
                html: `
                    <input type="datetime-local" id="editDate" class="swal2-input" 
                           value="${data.date.replace(' ', 'T')}" required>
                    <input type="number" id="editCost" class="swal2-input" 
                           value="${data.cost}" step="0.01" required>
                    <div class="form-check" style="text-align: left; margin-top:10px;">
                        <input type="checkbox" id="editStatus" 
                               ${data.status ? 'checked' : ''} class="form-check-input">
                        <label class="form-check-label" for="editStatus">Actif</label>
                    </div>
                `,
                focusConfirm: false,
                showCancelButton: true,
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
                    calendar.refetchEvents();
                    Swal.fire('Succès!', 'Le déplacement a été mis à jour.', 'success');
                }
            }
        } catch (error) {
            Swal.fire('Erreur!', 'La mise à jour a échoué.', 'error');
        }
    });

    calendar.render();
});
