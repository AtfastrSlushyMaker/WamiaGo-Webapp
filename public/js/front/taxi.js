/**
 * WamiaGo Taxi Booking Javascript
 */

document.addEventListener('DOMContentLoaded', function () {
    // Initialize taxi booking form
    const quickTaxiForm = document.getElementById('quickTaxiForm');
    if (quickTaxiForm) {
        quickTaxiForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const pickup = document.getElementById('pickup').value;
            const destination = document.getElementById('destination').value;

            if (!pickup || !destination) {
                alert('Please enter both pickup and destination addresses');
                return;
            }

            // Calculate estimated price (for demo purposes)
            const priceEstimate = Math.floor(Math.random() * 30) + 10;
            alert(`Estimated price for your journey: $${priceEstimate}`);

            // Show booking confirmation options
            showBookingConfirmation(pickup, destination, priceEstimate);
        });
    }

    // Handle scheduling option
    const pickupTimeSelect = document.getElementById('pickupTime');
    if (pickupTimeSelect) {
        pickupTimeSelect.addEventListener('change', function () {
            if (this.value === 'later') {
                showSchedulingOptions();
            } else {
                hideSchedulingOptions();
            }
        });
    }
});

/**
 * Show scheduling date/time options when user selects "Schedule for Later"
 */
function showSchedulingOptions() {
    const pickupTimeContainer = document.getElementById('pickupTime').parentNode;

    // Check if scheduling options already exist
    if (!document.getElementById('schedulingOptions')) {
        const schedulingOptionsDiv = document.createElement('div');
        schedulingOptionsDiv.id = 'schedulingOptions';
        schedulingOptionsDiv.className = 'mt-3';
        schedulingOptionsDiv.innerHTML = `
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="pickupDate" class="form-label">Date</label>
                    <input type="date" class="form-control" id="pickupDate" min="${getTodayDate()}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="pickupHour" class="form-label">Time</label>
                    <input type="time" class="form-control" id="pickupHour" required>
                </div>
            </div>
        `;

        pickupTimeContainer.parentNode.insertBefore(schedulingOptionsDiv, pickupTimeContainer.nextSibling);
    }
}

/**
 * Hide scheduling options when user selects "Now"
 */
function hideSchedulingOptions() {
    const schedulingOptions = document.getElementById('schedulingOptions');
    if (schedulingOptions) {
        schedulingOptions.remove();
    }
}

/**
 * Get today's date in YYYY-MM-DD format for date input min attribute
 */
function getTodayDate() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * Show booking confirmation dialog
 */
function showBookingConfirmation(pickup, destination, price) {
    // Create a modal for booking confirmation (in a real app)
    console.log(`Booking from ${pickup} to ${destination} for $${price}`);

    // For this demo, we'll just show an alert
    if (confirm(`Your taxi from ${pickup} to ${destination} will cost approximately $${price}. Would you like to confirm this booking?`)) {
        alert('Booking confirmed! Your taxi is on the way.');
    }
}