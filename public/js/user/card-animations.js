/**
 * Card Animations and Effects
 * Modern animations for the user cards grid view
 */

/* Entry animations for cards - uses IntersectionObserver */
document.addEventListener('DOMContentLoaded', function() {
    // Apply animations to cards when they come into view
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1
    });

    // Apply initial styles
    function setupCardAnimations() {
        const userCards = document.querySelectorAll('#users-card-container .col-xl-3');
        
        userCards.forEach((card, index) => {
            // Set initial opacity and transform
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = `all 0.5s ease ${index * 0.1}s`;
            
            // Observe the card
            observer.observe(card);
        });
        
        // CSS class to animate cards in
        const style = document.createElement('style');
        style.textContent = `
            #users-card-container .col-xl-3.animate-in {
                opacity: 1 !important;
                transform: translateY(0) !important;
            }
        `;
        document.head.appendChild(style);
    }

    // Wait for cards to be rendered
    const cardViewBtn = document.getElementById('card-view-btn');
    if (cardViewBtn) {
        cardViewBtn.addEventListener('click', function() {
            // Wait for DOM update
            setTimeout(setupCardAnimations, 100);
        });
    }

    // Initialize on page load if card view is active
    const cardView = document.getElementById('card-view');
    if (cardView && cardView.style.display !== 'none') {
        setTimeout(setupCardAnimations, 100);
    }
    
    // Handle pagination - reapply animations when page changes
    document.addEventListener('click', function(e) {
        if (e.target.closest('.pagination-container') && document.getElementById('card-view').style.display !== 'none') {
            // Wait for new cards to be rendered
            setTimeout(setupCardAnimations, 100);
        }
    });
});

// Enhanced hover effects for cards
document.addEventListener('mouseover', function(e) {
    const userCard = e.target.closest('.user-card');
    if (userCard) {
        const allCards = document.querySelectorAll('.user-card');
        allCards.forEach(card => {
            if (card !== userCard) {
                card.style.filter = 'blur(1px) grayscale(20%)';
                card.style.transform = 'scale(0.98)';
            }
        });
    }
}, true);

document.addEventListener('mouseout', function(e) {
    if (e.target.closest('.user-card')) {
        const allCards = document.querySelectorAll('.user-card');
        allCards.forEach(card => {
            card.style.filter = '';
            card.style.transform = '';
        });
    }
}, true);
