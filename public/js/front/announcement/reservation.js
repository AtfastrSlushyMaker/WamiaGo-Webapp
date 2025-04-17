document.addEventListener('DOMContentLoaded', function() {
    // Gestion du clic sur le bouton Réserver
    document.addEventListener('click', async function(e) {
        if (e.target.closest('.reserve-button')) {
            e.preventDefault();
            const announcementId = e.target.closest('.reserve-button').dataset.announcementId;
            await showReservationModal(announcementId);
        }
    });

    async function showReservationModal(announcementId) {
        try {
            // Récupérer les localisations disponibles
            const locationsResponse = await fetch('/api/locations');
            const locations = await locationsResponse.json();
            
            const { value: formValues } = await Swal.fire({
                title: 'Créer une réservation',
                html: `
                    <div class="reservation-form">
                        <div class="mb-3">
                            <label for="swal-description" class="form-label">Description</label>
                            <textarea id="swal-description" class="form-control" placeholder="Décrivez votre marchandise..." required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="swal-date" class="form-label">Date de transport</label>
                            <input type="datetime-local" id="swal-date" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="swal-start-location" class="form-label">Lieu de départ</label>
                            <select id="swal-start-location" class="form-select" required>
                                <option value="">Sélectionnez...</option>
                                ${locations.map(loc => `<option value="${loc.id}">${loc.name}</option>`).join('')}
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="swal-end-location" class="form-label">Lieu d'arrivée</label>
                            <select id="swal-end-location" class="form-select" required>
                                <option value="">Sélectionnez...</option>
                                ${locations.map(loc => `<option value="${loc.id}">${loc.name}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Confirmer',
                cancelButtonText: 'Annuler',
                confirmButtonColor: '#5A6BE5',
                preConfirm: () => {
                    return {
                        description: document.getElementById('swal-description').value,
                        date: document.getElementById('swal-date').value,
                        startLocation: document.getElementById('swal-start-location').value,
                        endLocation: document.getElementById('swal-end-location').value
                    };
                },
                inputValidator: (values) => {
                    if (!values.description || !values.date || !values.startLocation || !values.endLocation) {
                        return 'Tous les champs sont obligatoires!';
                    }
                    if (values.startLocation === values.endLocation) {
                        return 'Les lieux de départ et d\'arrivée doivent être différents!';
                    }
                    if (new Date(values.date) < new Date()) {
                        return 'La date doit être dans le futur!';
                    }
                },
                customClass: {
                    container: 'reservation-swal-container',
                    popup: 'reservation-swal-popup',
                    title: 'reservation-swal-title',
                    htmlContainer: 'reservation-swal-html',
                    input: 'reservation-swal-input',
                    actions: 'reservation-swal-actions',
                    confirmButton: 'reservation-swal-confirm-btn'
                }
            });

            if (formValues) {
                // Envoyer la réservation
                const response = await fetch(`/announcements/${announcementId}/create-reservation`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(formValues)
                });

                const result = await response.json();
                
                if (!response.ok) {
                    throw new Error(result.error || 'Erreur lors de la création de la réservation');
                }

                // Afficher le succès
                Swal.fire({
                    icon: 'success',
                    title: 'Succès!',
                    text: result.message,
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                    willClose: () => {
                        // Rafraîchir la page ou faire une action supplémentaire
                        window.location.reload();
                    }
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: error.message,
                confirmButtonColor: '#5A6BE5'
            });
        }
    }
});