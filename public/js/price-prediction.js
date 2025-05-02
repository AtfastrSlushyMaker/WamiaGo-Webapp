document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('pricePredictionForm');
    const departureCity = document.getElementById('departureCity');
    const arrivalCity = document.getElementById('arrivalCity');
    const availableSeats = document.getElementById('availableSeats');
    const pricePerPassenger = document.getElementById('pricePerPassenger');
    const predictButton = document.getElementById('predictPrice');
    const errorContainer = document.getElementById('errorContainer');

    if (form && predictButton) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            predictPrice();
        });
    }

    async function predictPrice() {
        try {
            // Clear previous errors
            if (errorContainer) {
                errorContainer.textContent = '';
                errorContainer.style.display = 'none';
            }

            // Validate inputs
            if (!departureCity.value || !arrivalCity.value || !availableSeats.value) {
                showError('Please fill in all required fields.');
                return;
            }

            if (departureCity.value === arrivalCity.value) {
                showError('Departure and arrival cities must be different.');
                return;
            }

            const seats = parseInt(availableSeats.value, 10);
            if (isNaN(seats) || seats <= 0 || seats > 10) {
                showError('Please enter a valid number of seats (between 1 and 10).');
                return;
            }

            // Show loading state
            predictButton.disabled = true;
            predictButton.textContent = 'Predicting...';

            const data = {
                departureCity: departureCity.value.trim(),
                arrivalCity: arrivalCity.value.trim(),
                availableSeats: seats,
            };

            const response = await fetch('/api/predict-price', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest' // Add this header
                },
                body: JSON.stringify({
                    departureCity: departureCity.value.trim(),
                    arrivalCity: arrivalCity.value.trim(),
                    availableSeats: parseInt(availableSeats.value, 10)
                }),
                credentials: 'same-origin' // Add this for session/cookies
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'Failed to predict price');
            }
            pricePerPassenger.value = parseFloat(result.price).toFixed(2);

            if (!result.price) {
                throw new Error('No price returned from the server');
            }

            const price = parseFloat(result.price);
            if (isNaN(price) || price <= 0) {
                throw new Error('Invalid price received');
            }

            pricePerPassenger.value = price.toFixed(2);
        } catch (error) {
            console.error('Error predicting price:', error);
            showError(error.message || 'Failed to predict price. Please try again.');
        } finally {
            if (predictButton) {
                predictButton.disabled = false;
                predictButton.textContent = 'Predict Price';
            }
        }
    }

    function showError(message) {
        console.error('Error:', message);
        if (errorContainer) {
            errorContainer.textContent = message;
            errorContainer.style.display = 'block';
        } else {
            alert(message);
        }
    }
});