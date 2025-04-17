document.addEventListener('DOMContentLoaded', function() {
    const Swal = window.Swal;
    
    // Handle details button click
    document.addEventListener('click', async function(e) {
        if (e.target.closest('.btn-details')) {
            const reservationId = e.target.closest('.btn-details').dataset.id;
            await showDetailsModal(reservationId);
        }
        
        if (e.target.closest('.btn-update')) {
            const reservationId = e.target.closest('.btn-update').dataset.id;
            await handleUpdateReservation(reservationId);
        }
        
        if (e.target.closest('.btn-delete')) {
            const reservationId = e.target.closest('.btn-delete').dataset.id;
            await handleDeleteReservation(reservationId);
        }
    });

    async function showDetailsModal(reservationId) {
        try {
            const response = await fetch(`/client/reservations/${reservationId}/details`);
            
            if (!response.ok) {
                throw new Error('Failed to fetch reservation details');
            }

            const data = await response.json();

            await Swal.fire({
                title: `<strong>${data.title}</strong>`,
                html: `
                    <div class="text-start">
                        <p class="text-muted mb-3">${data.description}</p>
                        <div class="d-flex justify-content-between mb-3">
                            <span><i class="fas fa-calendar-alt me-2"></i>${data.date}</span>
                            <span class="badge ${data.status.toLowerCase()}">${data.status}</span>
                        </div>
                        <hr>
                        <div class="location-details mb-3">
                            <p><i class="fas fa-map-marker-alt text-danger me-2"></i> ${data.startLocation}</p>
                            <p><i class="fas fa-flag-checkered text-success me-2"></i> ${data.endLocation}</p>
                        </div>
                    </div>
                `,
                showCloseButton: true,
                showConfirmButton: false
            });

        } catch (error) {
            await Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message
            });
        }
    }

    async function handleUpdateReservation(reservationId) {
        try {
            // Load locations first
            const locationsResponse = await fetch('/api/locations');
            if (!locationsResponse.ok) {
                throw new Error('Failed to load locations');
            }
            const locations = await locationsResponse.json();
    
            // Load form data
            const response = await fetch(`/client/reservations/${reservationId}/update-form`);
            const data = await response.json();
    
            if (!data.success) {
                throw new Error(data.error || 'Failed to load form');
            }
    
            // Create temporary container to parse HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = data.html;
    
            // Extract form values for pre-fill
            const currentDescription = tempDiv.querySelector('#update-description').value;
            const currentDate = tempDiv.querySelector('#update-date').value;
            const currentStartLocation = tempDiv.querySelector('#update-start-location').value;
            const currentEndLocation = tempDiv.querySelector('#update-end-location').value;
    
            const { value: formValues } = await Swal.fire({
                title: 'Update Reservation',
                html: generateUpdateForm(
                    currentDescription,
                    currentDate,
                    currentStartLocation,
                    currentEndLocation,
                    locations
                ),
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Update Reservation',
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
                    title: 'Updating...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
    
                const updateResponse = await fetch(`/client/reservations/${reservationId}/update`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(formValues)
                });
    
                const result = await updateResponse.json();
    
                if (!updateResponse.ok) {
                    if (updateResponse.status === 422 && result.errors) {
                        Swal.close();
    
                        // Reopen form with errors
                        await Swal.fire({
                            title: 'Update Reservation',
                            html: generateUpdateForm(
                                formValues.description,
                                formValues.date,
                                formValues.startLocation,
                                formValues.endLocation,
                                locations
                            ),
                            focusConfirm: false,
                            showCancelButton: true,
                            confirmButtonText: 'Update Reservation',
                            cancelButtonText: 'Cancel',
                            confirmButtonColor: '#5A6BE5',
                            cancelButtonColor: '#6c757d',
                            width: '500px',
                            padding: '1.5rem',
                            position: 'top',
                            preConfirm: () => false
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
    
                    throw new Error(result.message || 'Failed to update reservation');
                }
    
                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    text: result.message,
                    showConfirmButton: false,
                    timer: 2000
                }).then(() => {
                    location.reload(); 
                });
            }
        } catch (error) {
            console.error('Update error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Failed to update reservation. Please try again.',
                confirmButtonColor: '#5A6BE5'
            });
        }
    }
    
    function generateUpdateForm(description, date, startLocation, endLocation, locations) {
        return `
            <form id="update-reservation-form">
                <div class="mb-3">
                    <label for="swal-description" class="form-label">Description</label>
                    <textarea id="swal-description" class="form-control" rows="4" required>${description || ''}</textarea>
                    <div class="invalid-feedback"></div>
                </div>
                
                <div class="mb-3">
                    <label for="swal-date" class="form-label">Date</label>
                    <input type="datetime-local" id="swal-date" class="form-control" 
                           value="${date || ''}" required>
                    <div class="invalid-feedback"></div>
                </div>
                
                <div class="mb-3">
                    <label for="swal-start-location" class="form-label">Start Location</label>
                    <select id="swal-start-location" class="form-select" required>
                        <option value="">Select start location</option>
                        ${generateLocationOptions(locations, startLocation)}
                    </select>
                    <div class="invalid-feedback"></div>
                </div>
                
                <div class="mb-3">
                    <label for="swal-end-location" class="form-label">End Location</label>
                    <select id="swal-end-location" class="form-select" required>
                        <option value="">Select end location</option>
                        ${generateLocationOptions(locations, endLocation)}
                    </select>
                    <div class="invalid-feedback"></div>
                </div>
            </form>
        `;
    }
    
    
    function generateLocationOptions(locations, selectedId) {
        if (!Array.isArray(locations)) {
            console.error('Locations must be an array');
            return '';
        }
    
        return locations.map(location => `
            <option value="${location.id}" ${location.id == selectedId ? 'selected' : ''}>
                ${location.address}
            </option>
        `).join('');
    }
    
    
    function showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        if (!field) return;
        
        field.classList.add('is-invalid');
        
        let feedback = field.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            field.parentNode.insertBefore(feedback, field.nextSibling);
        }
        
        feedback.textContent = message;
    }

    async function handleDeleteReservation(reservationId) {
        try {
            const result = await Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'No, cancel!',
                reverseButtons: true
            });
            
            if (result.isConfirmed) {
                const csrfToken = document.getElementById('csrf-token').dataset.token;
                const deleteResponse = await fetch(`/client/reservations/${reservationId}/delete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        _token: csrfToken
                    })
                });
                
                const data = await deleteResponse.json();
                
                if (!deleteResponse.ok) {
                    throw new Error(data.error || 'Failed to delete reservation');
                }
                
                // Show success message
                await Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: data.message
                });
                
                // Remove the card from the UI
                removeReservationCard(reservationId);
            }
            
        } catch (error) {
            await Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message
            });
        }
    }

    function updateReservationCard(reservationId, reservationData) {
        const card = document.querySelector(`.reservation-card[data-id="${reservationId}"]`);
        if (!card) return;
        
        // Update card content
        if (card.querySelector('.card-title')) {
            card.querySelector('.card-title').textContent = reservationData.title;
        }
        
        if (card.querySelector('.card-content')) {
            card.querySelector('.card-content').textContent = 
                reservationData.description.length > 120 ? 
                reservationData.description.substring(0, 120) + '...' : 
                reservationData.description;
        }
    }

    function removeReservationCard(reservationId) {
        const card = document.querySelector(`.reservation-card[data-id="${reservationId}"]`);
        if (card) {
            card.style.opacity = '0';
            setTimeout(() => card.remove(), 300);
        }
    }
});