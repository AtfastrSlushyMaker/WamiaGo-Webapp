document.addEventListener('DOMContentLoaded', function () {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const htmlElement = document.documentElement;
    const darkModeIcon = darkModeToggle.querySelector('i');

    // Check for saved theme preference or use prefer-color-scheme
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

    // Apply theme based on saved preference or system preference
    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
        enableDarkMode();
    } else {
        disableDarkMode();
    }

    // Toggle theme when button is clicked
    darkModeToggle.addEventListener('click', function () {
        if (htmlElement.getAttribute('data-bs-theme') === 'dark') {
            disableDarkMode();
        } else {
            enableDarkMode();
        }
    });

    // Functions to enable/disable dark mode
    function enableDarkMode() {
        htmlElement.setAttribute('data-bs-theme', 'dark');
        darkModeIcon.classList.remove('fa-moon');
        darkModeIcon.classList.add('fa-sun');
        localStorage.setItem('theme', 'dark');
    }

    function disableDarkMode() {
        htmlElement.setAttribute('data-bs-theme', 'light');
        darkModeIcon.classList.remove('fa-sun');
        darkModeIcon.classList.add('fa-moon');
        localStorage.setItem('theme', 'light');
    }
});