/**
 * Direct Date Picker Implementation
 * This is a simpler direct implementation of flatpickr
 */

document.addEventListener('DOMContentLoaded', function() {
    // Find all date inputs
    const dateInputs = document.querySelectorAll('.date-input');
    
    dateInputs.forEach(input => {
        if (typeof flatpickr === 'function') {
            flatpickr(input, {
                dateFormat: "Y-m-d",
                maxDate: "today",
                minDate: new Date().getFullYear() - 100 + "-01-01",
                allowInput: true,
                altInput: true,
                altFormat: "F j, Y",
                disableMobile: false,
                locale: {
                    firstDayOfWeek: 1
                },
                onChange: function(selectedDates, dateStr, instance) {
                    console.log('Date selected:', dateStr);
                }
            });
        }
    });
});
