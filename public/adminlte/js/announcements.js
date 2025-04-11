document.addEventListener('DOMContentLoaded', function() {
    // Gestion de la modal de suppression
    $('#deleteModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const announcementId = button.data('id');
        const modal = $(this);
        
        // Mise à jour du formulaire
        const deleteForm = modal.find('#deleteForm');
        deleteForm.attr('action', `/admin/announcements/${announcementId}`);
        deleteForm.find('input[name="_token"]').val(button.data('token'));
    });

    // Initialisation des tooltips
    $('[data-toggle="tooltip"]').tooltip();
});