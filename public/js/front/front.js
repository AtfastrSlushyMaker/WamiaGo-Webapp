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

    console.log('Front Office JS loaded successfully');
});

/**
 * Initialize intersection observer to animate elements when they come into view
 */
function initializeAnimations() {
    // Only use if IntersectionObserver is supported
    if ('IntersectionObserver' in window) {
        // Elements to animate when they come into view
        const animationTargets = document.querySelectorAll('.service-card, .team-card, .testimonial-card, .step, .contact-card');

        const animationObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // Add animation classes when element is in view
                    entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                    // Stop observing once animation is applied
                    observer.unobserve(entry.target);
                }
            });
        }, {
            root: null, // viewport
            threshold: 0.1, // trigger when 10% of the element is visible
            rootMargin: '0px'
        });

        // Observe each target element
        animationTargets.forEach(target => {
            animationObserver.observe(target);
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
        // Custom carousel enhancements can be added here
    }
}
