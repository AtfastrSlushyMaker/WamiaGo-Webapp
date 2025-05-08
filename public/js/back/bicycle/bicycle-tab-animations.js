/**
 * Bicycle Tab Animations
 * Adds animations to the bicycle tab to represent electric bikes
 */

document.addEventListener('DOMContentLoaded', function () {
    // Only run if we're on the bicycle tab
    const bicyclesTab = document.getElementById('bicyclesTab');
    if (!bicyclesTab || !bicyclesTab.classList.contains('active')) {
        return;
    }

    console.log('Initializing bicycle tab animations');

    // Add pulsing electricity effect to the hero section
    animateHeroSection();

    // Add animations to the battery indicators
    animateBatteryIndicators();

    // Add animations to the quick action buttons
    animateQuickActions();

    // Add fade-in animations to table rows
    animateTableRows();

    // Listen for tab changes to reinitialize animations
    document.querySelectorAll('a[data-tab="bicycles"]').forEach(tab => {
        tab.addEventListener('click', function () {
            // Short timeout to ensure the tab is visible
            setTimeout(() => {
                animateHeroSection();
                animateBatteryIndicators();
                animateQuickActions();
                animateTableRows();
            }, 300);
        });
    });
});

/**
 * Add an electric pulse animation to the hero section
 */
function animateHeroSection() {
    const heroSection = document.querySelector('.bicycles-dashboard .hero-section');
    if (!heroSection) return;

    // Add an electric bolt to the hero section
    const bolt = document.createElement('div');
    bolt.className = 'electric-bolt';
    bolt.innerHTML = '<i class="ti ti-bolt"></i>';
    bolt.style.position = 'absolute';
    bolt.style.top = '20px';
    bolt.style.right = '20px';
    bolt.style.fontSize = '2.5rem';
    bolt.style.color = 'rgba(255, 255, 255, 0.7)';
    bolt.style.textShadow = '0 0 10px rgba(255, 255, 255, 0.7)';
    bolt.style.animation = 'electricPulse 2s infinite alternate';

    // Check if bolt already exists
    const existingBolt = heroSection.querySelector('.electric-bolt');
    if (existingBolt) {
        existingBolt.remove();
    }

    heroSection.appendChild(bolt);

    // Add CSS keyframes if they don't exist
    if (!document.getElementById('electricStyles')) {
        const style = document.createElement('style');
        style.id = 'electricStyles';
        style.innerHTML = `
            @keyframes electricPulse {
                0% {
                    opacity: 0.5;
                    transform: scale(0.9) rotate(-5deg);
                }
                100% {
                    opacity: 1;
                    transform: scale(1.1) rotate(5deg);
                }
            }
        `;
        document.head.appendChild(style);
    }
}

/**
 * Add animations to battery indicators
 */
function animateBatteryIndicators() {
    // Find all battery indicators
    const batteryIndicators = document.querySelectorAll('.bicycles-dashboard .battery-indicator');
    batteryIndicators.forEach(indicator => {
        // Only add charging animation to indicators that don't already have it
        if (!indicator.classList.contains('animated')) {
            indicator.classList.add('animated');

            // Get the level to determine if it's low battery
            const isCritical = indicator.classList.contains('critical');
            if (isCritical) {
                // Add a warning flash animation for critical batteries
                indicator.style.animation = 'batteryWarning 1.5s infinite';
            }
        }
    });

    // Add CSS keyframes if they don't exist
    if (!document.getElementById('batteryStyles')) {
        const style = document.createElement('style');
        style.id = 'batteryStyles';
        style.innerHTML = `
            @keyframes batteryWarning {
                0% { opacity: 1; }
                50% { opacity: 0.6; }
                100% { opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    }
}

/**
 * Add animations to quick action buttons
 */
function animateQuickActions() {
    const quickActions = document.querySelectorAll('.bicycles-dashboard .quick-action-btn');
    quickActions.forEach((btn, index) => {
        if (!btn.classList.contains('animated')) {
            btn.classList.add('animated');

            // Add a slight delay to each button for a staggered animation
            btn.style.opacity = '0';
            btn.style.transform = 'translateY(20px)';

            setTimeout(() => {
                btn.style.transition = 'all 0.5s ease';
                btn.style.opacity = '1';
                btn.style.transform = 'translateY(0)';
            }, 100 * index);
        }
    });
}

/**
 * Add fade-in animations to table rows
 */
function animateTableRows() {
    const bicycleTable = document.querySelector('.bicycles-dashboard .bicycle-table');
    if (!bicycleTable) return;

    const rows = bicycleTable.querySelectorAll('tbody tr');
    rows.forEach((row, index) => {
        if (!row.classList.contains('animated')) {
            row.classList.add('animated');

            // Stagger the animations
            row.style.opacity = '0';
            row.style.transform = 'translateX(-10px)';

            setTimeout(() => {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '1';
                row.style.transform = 'translateX(0)';
            }, 50 * index);
        }
    });
}