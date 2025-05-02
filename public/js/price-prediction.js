document.addEventListener('DOMContentLoaded', function () {
    const departureCity = document.getElementById('departureCity');
    const arrivalCity = document.getElementById('arrivalCity');
    const availableSeats = document.getElementById('availableSeats');
    const pricePerPassenger = document.getElementById('pricePerPassenger');
    const predictButton = document.getElementById('predictPrice');

    if (predictButton) {
        predictButton.addEventListener('click', function (e) {
            e.preventDefault();
            predictPrice();
        });
    }

async function predictPrice() {
    try {
        if (!departureCity.value || !arrivalCity.value || !availableSeats.value) {
            alert('Please fill in all required fields.');
            return;
        }

        if (departureCity.value === arrivalCity.value) {
            alert('Departure and arrival cities must be different.');
            return;
        }

        const seats = parseInt(availableSeats.value, 10);
        if (isNaN(seats) || seats <= 0 || seats > 10) {
            alert('Please enter a valid number of seats (between 1 and 10).');
            return;
        }

        const data = {
            departureCity: departureCity.value,
            arrivalCity: arrivalCity.value,
            availableSeats: seats,
        };

        const response = await fetch('/api/predict-price', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Server error response:', errorText);
            throw new Error(`Server error: ${response.status} ${response.statusText}`);
        }

        const result = await response.json();
        if (result.error) {
            throw new Error(`API error: ${result.error}`);
        }

        pricePerPassenger.value = parseFloat(result.price).toFixed(2);
    } catch (error) {
        console.error('Error predicting price:', error);
        alert(`Failed to predict price: ${error.message}`);
    }
}
});
