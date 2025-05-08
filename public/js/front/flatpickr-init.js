/**
 * Flatpickr Date Picker Initialization
 * 
 * This script initializes the flatpickr date picker for the registration form's
 * date of birth field with appropriate configuration.
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Flatpickr init script loaded');
    
    // Initialize flatpickr for the date of birth field
    if (typeof flatpickr === 'function') {
        console.log('Flatpickr is available');
        
        // Try multiple possible selectors for the date field
        const possibleSelectors = [
            '#reg_date_of_birth',                          // Custom ID in the template
            '#registration_form_dateOfBirth',              // Default Symfony form ID
            'input[name="registration_form[dateOfBirth]"]' // Input by name
        ];
        
        let datePickerElement = null;
        
        // Find the first matching selector
        for (const selector of possibleSelectors) {
            const element = document.querySelector(selector);
            if (element) {
                console.log('Found date input with selector:', selector);
                datePickerElement = element;
                break;
            }
        }
        
        if (datePickerElement) {
            console.log('Initializing flatpickr on element:', datePickerElement);
            
            const datePicker = flatpickr(datePickerElement, {
                dateFormat: "Y-m-d",
                maxDate: "today",
                minDate: new Date().getFullYear() - 100 + "-01-01", // Allow dates up to 100 years ago
                allowInput: true,
                altInput: true,
                altFormat: "F j, Y", // Display format: Month day, Year (e.g., April 28, 2025)
                disableMobile: false, // Enable native date picker on mobile
                locale: {
                    firstDayOfWeek: 1 // Start with Monday
                },
                // Highlight the current date
                onReady: function(selectedDates, dateStr, instance) {
                    console.log('Flatpickr is ready');
                    // Add a subtle highlight to the date picker container
                    instance.calendarContainer.classList.add('date-picker-ready');
                }
            });

            // Add input focus styling
            datePickerElement.addEventListener('focus', function() {
                this.parentNode.classList.add('focused');
            });

            datePickerElement.addEventListener('blur', function() {
                this.parentNode.classList.remove('focused');
            });
        } else {
            console.warn('Date of birth input element not found. Tried these selectors:', possibleSelectors);
        }
    } else {
        console.warn('Flatpickr library not loaded. Date picker will not be initialized.');
    }
});

console.log('Flatpickr init script loaded');
console.log('Flatpickr available:', typeof flatpickr === 'function');
console.log('Date element found:', document.getElementById('reg_date_of_birth'));
