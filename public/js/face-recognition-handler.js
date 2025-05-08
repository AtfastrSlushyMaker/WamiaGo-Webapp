/**
 * WamiaGo Face Recognition Link Handler
 * Ensures facial recognition links go to the appropriate pages
 */

document.addEventListener('DOMContentLoaded', function() {
    // Find any setup/manage facial recognition links
    const setupLinks = document.querySelectorAll('[href*="face-setup"], [href*="face_setup"]');
    const manageLinks = document.querySelectorAll('[href*="face-manage"], [href*="face_manage"]');
    
    // Handle setup links
    setupLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = '{{ path("app_face_setup") }}';
        });
    });
    
    // Handle manage links
    manageLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = '{{ path("app_face_manage") }}';
        });
    });
}); 