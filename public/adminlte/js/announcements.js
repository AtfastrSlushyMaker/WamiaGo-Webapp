

$(function() {
    'use strict';
    
    // Handle delete confirmation modal
    $('#deleteModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var title = button.data('title');
        var modal = $(this);
        
        // Update modal content with announcement title
        if (title) {
            modal.find('#delete-confirmation-text').html(
                'Are you sure you want to delete the announcement: <strong>"' + title + '"</strong>?'
            );
        }
        
        // Update form action and token
        var action = '/admin/announcements/' + id;
        var token = $('meta[name="csrf-token"]').attr('content');
        
        modal.find('#deleteForm').attr('action', action);
        modal.find('input[name="_token"]').val(token);
    });
    
    // Search functionality
    $('#announcement-search').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('table tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
        
        // Show empty state if no results
        if ($('table tbody tr:visible').length === 0) {
            if ($('#no-results-row').length === 0) {
                $('table tbody').append(
                    '<tr id="no-results-row"><td colspan="7" class="text-center py-4">' +
                    '<img src="/adminlte/images/search-empty.svg" alt="No results" width="120" class="mb-3">' +
                    '<h5 class="text-muted">No announcements found matching your search</h5>' +
                    '</td></tr>'
                );
            }
        } else {
            $('#no-results-row').remove();
        }
    });
    
    // Add fade-in animation to table rows
    $('table tbody tr').addClass('fade-in');
    
    // Initialize tooltips
    $('[title]').tooltip();
    
    // Fix for Safari rendering issue
    setTimeout(function() {
        $('.card').addClass('rendered');
    }, 100);
});