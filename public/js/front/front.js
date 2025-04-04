/**
 * WamiaGo Front Office Javascript
 */

document.addEventListener('DOMContentLoaded', function () {
    // Initialize animations for elements when they come into view
    initializeAnimations();

    // Initialize counters for statistics sections
    initializeCounters();

    // Add smooth scrolling for anchor links
    initializeSmoothScrolling();

    // Add hero carousel initialization
    initializeHeroCarousel();

    initializeDarkMode();

    initializeLazyLoading();

    initializeDropdowns();

    console.log('Front Office JS loaded successfully');
});

/**
 * Initialize intersection observer to animate elements when they come into view
 */
function initializeAnimations() {
    // Only use if IntersectionObserver is supported
    if ('IntersectionObserver' in window) {
        // Elements to animate when they come into view

        const animationObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // Get animation type from data attribute or use default
                    const animationType = entry.target.dataset.animation || 'fadeInUp';
                    const delay = parseInt(entry.target.dataset.delay || 0);

                    // Add animation classes with delay for staggered effect
                    setTimeout(() => {
                        entry.target.classList.add('animate__animated', `animate__${animationType}`);
                    }, delay);

                    // Stop observing once animation is applied
                    observer.unobserve(entry.target);
                }
            });
        }, {
            root: null,
            threshold: 0.15,
            rootMargin: '0px'
        });

        // Set staggered delays for each service card
        let delay = 0;
        document.querySelectorAll('.service-card').forEach(card => {
            card.dataset.delay = delay;
            card.dataset.animation = 'fadeInUp';
            delay += 150;
            animationObserver.observe(card);
        });

        // Set staggered delays for testimonial cards
        delay = 0;
        document.querySelectorAll('.testimonial-card').forEach(card => {
            card.dataset.delay = delay;
            card.dataset.animation = 'fadeIn';
            delay += 200;
            animationObserver.observe(card);
        });

        // Set staggered delays for steps
        delay = 0;
        document.querySelectorAll('.step').forEach(step => {
            step.dataset.delay = delay;
            step.dataset.animation = 'fadeInUp';
            delay += 200;
            animationObserver.observe(step);
        });

        // Observe CTA section with fade animation
        document.querySelectorAll('.cta').forEach(cta => {
            cta.dataset.animation = 'fadeIn';
            animationObserver.observe(cta);
        });
    }
}

/**
 * Initialize counters for statistics with animation
 */
function initializeCounters() {
    const counters = document.querySelectorAll('.counter');
    if (counters.length === 0) return;

    // Only start counter when elements are in view
    if ('IntersectionObserver' in window) {
        const counterObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counter = entry.target;
                    const target = parseInt(counter.innerText, 10);
                    let count = 0;
                    const speed = 30; // Lower is faster

                    // Animate counter
                    const updateCount = () => {
                        const increment = target / speed;
                        if (count < target) {
                            count += increment;
                            counter.innerText = Math.ceil(count);
                            setTimeout(updateCount, 30);
                        } else {
                            counter.innerText = target;
                        }
                    }

                    updateCount();
                    observer.unobserve(counter);
                }
            });
        }, {
            threshold: 0.5
        });

        counters.forEach(counter => {
            counterObserver.observe(counter);
        });
    }
}

/**
 * Add smooth scrolling behavior to anchor links
 */
function initializeSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;

            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                e.preventDefault();
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

/**
 * Enhance the hero carousel with custom behavior if needed
 */
function initializeHeroCarousel() {
    const heroCarousel = document.getElementById('heroCarousel');
    if (heroCarousel) {
        // Add pause on hover
        heroCarousel.addEventListener('mouseenter', () => {
            bootstrap.Carousel.getInstance(heroCarousel).pause();
        });

        // Resume on mouse leave
        heroCarousel.addEventListener('mouseleave', () => {
            bootstrap.Carousel.getInstance(heroCarousel).cycle();
        });

        // Add accessibility improvements
        const slides = heroCarousel.querySelectorAll('.carousel-item');
        slides.forEach((slide, index) => {
            slide.setAttribute('aria-label', `Slide ${index + 1}`);
        });
    }
}
function initializeDarkMode() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (!darkModeToggle) return;

    // Check for saved dark mode preference
    const isDarkMode = localStorage.getItem('darkMode') === 'true';

    // Set initial state
    if (isDarkMode) {
        document.body.classList.add('dark-mode');
        darkModeToggle.innerHTML = '<i class="fas fa-sun text-warning"></i>';
    } else {
        darkModeToggle.innerHTML = '<i class="fas fa-moon text-white"></i>';
    }

    // Toggle dark mode
    darkModeToggle.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        const isNowDark = document.body.classList.contains('dark-mode');

        // Save preference
        localStorage.setItem('darkMode', isNowDark);

        // Update icon with appropriate colors
        darkModeToggle.innerHTML = isNowDark ?
            '<i class="fas fa-sun text-warning"></i>' :
            '<i class="fas fa-moon text-white"></i>';
    });
}

function initializeLazyLoading() {
    if ('loading' in HTMLImageElement.prototype) {
        // Native lazy loading
        const images = document.querySelectorAll('img[data-src]');
        images.forEach(img => {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        });
    } else {
        // Fallback with Intersection Observer
        const lazyImages = document.querySelectorAll('img[data-src]');

        if (lazyImages.length === 0) return;

        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            });
        });

        lazyImages.forEach(img => {
            imageObserver.observe(img);
        });
    }
}
function initializeDropdowns() {
    const dropdowns = document.querySelectorAll('.navbar .dropdown');

    if (dropdowns.length === 0) return;

    // Add helper class to body for enhanced styling
    document.body.classList.add('js-dropdown-enhanced');

    dropdowns.forEach(dropdown => {
        let timeout;
        const dropdownToggle = dropdown.querySelector('.dropdown-toggle');
        const dropdownMenu = dropdown.querySelector('.dropdown-menu');

        if (!dropdownToggle || !dropdownMenu) return;

        // Ensure the arrow icon exists
        let toggleArrow = dropdownToggle.querySelector('.dropdown-toggle-arrow');
        if (!toggleArrow) {
            toggleArrow = document.createElement('i');
            toggleArrow.className = 'fas fa-chevron-down ms-1 dropdown-toggle-arrow';
            dropdownToggle.appendChild(toggleArrow);
        }

        // Handle focus states removal without interfering with underline
        const removeFocusStates = () => {
            if (dropdownToggle) {
                dropdownToggle.style.outline = 'none';
                dropdownToggle.style.boxShadow = 'none';
                dropdownToggle.blur();
                // Don't set border: none directly
            }
        };

        // Desktop hover behavior
        if (window.innerWidth >= 992) {
            // Handle mouseenter
            dropdown.addEventListener('mouseenter', () => {
                clearTimeout(timeout);
                removeFocusStates();

                // Force add hover class for underline
                dropdownToggle.classList.add('dropdown-hover');

                // Show dropdown
                const bsDropdown = bootstrap.Dropdown.getInstance(dropdownToggle) ||
                    new bootstrap.Dropdown(dropdownToggle);
                bsDropdown.show();
            });

            // Handle mouseleave
            dropdown.addEventListener('mouseleave', () => {
                timeout = setTimeout(() => {
                    // Remove hover class
                    dropdownToggle.classList.remove('dropdown-hover');

                    // Hide dropdown
                    const bsDropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
                    if (bsDropdown && dropdownMenu.classList.contains('show')) {
                        bsDropdown.hide();
                    }

                    removeFocusStates();
                }, 200);
            });

            // Prevent click default action
            dropdownToggle.addEventListener('click', (e) => {
                if (window.innerWidth >= 992) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        }

        // Handle Bootstrap dropdown events
        ['shown.bs.dropdown', 'show.bs.dropdown'].forEach(event => {
            dropdownToggle.addEventListener(event, () => {
                removeFocusStates();
                // Force underline to show when dropdown is active
                dropdownToggle.classList.add('dropdown-hover');
            });
        });

        ['hidden.bs.dropdown', 'hide.bs.dropdown'].forEach(event => {
            dropdownToggle.addEventListener(event, () => {
                removeFocusStates();
                // Remove underline when dropdown is closed
                dropdownToggle.classList.remove('dropdown-hover');
            });
        });
    });
}