document.addEventListener('DOMContentLoaded', function() {
    // Handle reserve button clicks
    document.addEventListener('click', async function(e) {
        if (e.target.closest('.reserve-button')) {
            e.preventDefault();
            const announcementId = e.target.closest('.reserve-button').dataset.announcementId;
            await showReservationModal(announcementId);
        }
    });

    async function showReservationModal(announcementId) {
        try {
            // Load locations
            const locationsResponse = await fetch('/api/locations');
            if (!locationsResponse.ok) throw new Error('Failed to load locations');
            const locations = await locationsResponse.json();
            if (!Array.isArray(locations)) throw new Error('Invalid locations data received');

            const { value: formValues } = await Swal.fire({
                title: 'Create Reservation',
                html: generateReservationForm(locations),
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Confirm Reservation',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#5A6BE5',
                cancelButtonColor: '#6c757d',
                width: '500px',
                padding: '1.5rem',
                backdrop: true,
                position: 'top',
                customClass: {
                    container: 'reservation-swal-container',
                    popup: 'reservation-swal-popup',
                    title: 'reservation-swal-title',
                    htmlContainer: 'reservation-swal-html',
                    input: 'reservation-swal-input',
                    actions: 'reservation-swal-actions',
                    confirmButton: 'reservation-swal-confirm-btn',
                    cancelButton: 'reservation-swal-cancel-btn'
                },
                preConfirm: () => {
                    const description = document.getElementById('swal-description').value;
                    const date = document.getElementById('swal-date').value;
                    const startLocation = document.getElementById('swal-start-location').value;
                    const endLocation = document.getElementById('swal-end-location').value;

                    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                    document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

                    let isValid = true;

                    if (!description) {
                        showFieldError('swal-description', 'Description is required');
                        isValid = false;
                    } else if (description.length < 10 || description.length > 500) {
                        showFieldError('swal-description', 'Description must be between 10 and 500 characters');
                        isValid = false;
                    }

                    if (!date) {
                        showFieldError('swal-date', 'Date is required');
                        isValid = false;
                    } else if (new Date(date) <= new Date()) {
                        showFieldError('swal-date', 'Date must be in the future');
                        isValid = false;
                    }

                    if (!startLocation) {
                        showFieldError('swal-start-location', 'Start location is required');
                        isValid = false;
                    }

                    if (!endLocation) {
                        showFieldError('swal-end-location', 'End location is required');
                        isValid = false;
                    } else if (startLocation === endLocation) {
                        showFieldError('swal-end-location', 'Start and end locations must be different');
                        isValid = false;
                    }

                    if (!isValid) return false;

                    return { description, date, startLocation, endLocation };
                }
            });

            if (formValues) {
                Swal.fire({
                    title: 'Processing...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

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
                    if (response.status === 422 && result.errors) {
                        Swal.close();

                        // Reopen form and display server-side validation errors
                        await Swal.fire({
                            title: 'Create Reservation',
                            html: generateReservationForm(locations, formValues),
                            focusConfirm: false,
                            showCancelButton: true,
                            confirmButtonText: 'Confirm Reservation',
                            cancelButtonText: 'Cancel',
                            confirmButtonColor: '#5A6BE5',
                            cancelButtonColor: '#6c757d',
                            width: '500px',
                            padding: '1.5rem',
                            position: 'top',
                            preConfirm: () => false // disable auto-close, handled manually
                        });

                        for (const [field, message] of Object.entries(result.errors)) {
                            const fieldId = {
                                description: 'swal-description',
                                date: 'swal-date',
                                startLocation: 'swal-start-location',
                                endLocation: 'swal-end-location'
                            }[field];

                            if (fieldId) {
                                showFieldError(fieldId, message);
                            }
                        }

                        return;
                    }

                    throw new Error(result.message || 'Failed to create reservation');
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: result.message,
                    showConfirmButton: false,
                    timer: 2000
                }).then(() => updateReservationUI(announcementId, result.reservationId));
            }

        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Failed to load locations. Please try again.',
                confirmButtonColor: '#5A6BE5'
            });
        }
    }

    function generateReservationForm(locations, values = {}) {
        return `
            <form id="reservationForm" class="reservation-form">
                <div class="form-group">
                    <label for="swal-description">Description</label>
                    <textarea id="swal-description" class="form-control" placeholder="Describe your goods...">${values.description || ''}</textarea>
                </div>
                <div class="form-group">
                    <label for="swal-date">Transport Date</label>
                    <input type="datetime-local" id="swal-date" class="form-control" value="${values.date || ''}">
                </div>
                <div class="form-group">
                    <label for="swal-start-location">Start Location</label>
                    <select id="swal-start-location" class="form-control">
                        <option value="">Select start location...</option>
                        ${locations.map(loc =>
                            `<option value="${loc.id}" ${loc.id == values.startLocation ? 'selected' : ''}>${loc.address}</option>`
                        ).join('')}
                    </select>
                </div>
                <div class="form-group">
                    <label for="swal-end-location">End Location</label>
                    <select id="swal-end-location" class="form-control">
                        <option value="">Select end location...</option>
                        ${locations.map(loc =>
                            `<option value="${loc.id}" ${loc.id == values.endLocation ? 'selected' : ''}>${loc.address}</option>`
                        ).join('')}
                    </select>
                </div>
            </form>
        `;
    }

    function showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        if (!field) return;
        field.classList.add('is-invalid');

        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;

        field.parentNode.appendChild(errorDiv);
    }

    function updateReservationUI(announcementId, reservationId) {
        const reserveButtons = document.querySelectorAll(`.reserve-button[data-announcement-id="${announcementId}"]`);
        reserveButtons.forEach(button => {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-check"></i> Reserved';
            button.classList.remove('reserve-button');
            button.classList.add('reserved-button');
        });

        const announcementCards = document.querySelectorAll(`.announcement-card[data-id="${announcementId}"]`);
        announcementCards.forEach(card => {
            const statusBadge = card.querySelector('.status-badge');
            if (statusBadge) {
                statusBadge.textContent = 'Reserved';
                statusBadge.classList.remove('active', 'inactive');
                statusBadge.classList.add('reserved');
            }
        });
    }
});
