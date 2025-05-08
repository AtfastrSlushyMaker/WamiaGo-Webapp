// Face recognition scan line animation
function initializeScanLine() {
    const scanLine = document.getElementById('scan-line');
    if (!scanLine) return;
    
    // Check if user prefers reduced motion
    const prefersReducedMotion = document.documentElement.classList.contains('reduce-motion') || 
                               window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    
    if (!prefersReducedMotion) {
        // Apply scan line animation
        scanLine.style.opacity = '0.7';
        scanLine.style.animation = 'scan 2s infinite';
    } else {
        // For reduced motion, just show a static indicator
        scanLine.style.opacity = '0.5';
        scanLine.style.top = '50%';
        scanLine.style.height = '2px';
    }
}

// Make function globally available
window.initializeScanLine = initializeScanLine;
