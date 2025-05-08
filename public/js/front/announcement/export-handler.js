document.addEventListener('DOMContentLoaded', function() {
    const exportForm = document.getElementById('exportForm');
    const exportSuccessToast = document.getElementById('exportSuccessToast');

    if (exportForm) {
        exportForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const queryString = new URLSearchParams(formData).toString();
            const url = this.action + '?' + queryString;

            try {
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating PDF...';
                submitBtn.disabled = true;

                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error('Export failed');
                }

                // Get the filename from the Content-Disposition header if available
                const contentDisposition = response.headers.get('Content-Disposition');
                const filenameMatch = contentDisposition && contentDisposition.match(/filename="(.+)"/);
                const filename = filenameMatch ? filenameMatch[1] : 'announcements.pdf';

                // Convert response to blob and download
                const blob = await response.blob();
                const downloadUrl = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = downloadUrl;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(downloadUrl);

                // Show success toast
                const toast = new bootstrap.Toast(exportSuccessToast);
                toast.show();

                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
                modal.hide();

            } catch (error) {
                console.error('Export error:', error);
                // Show error message
                Swal.fire({
                    icon: 'error',
                    title: 'Export Failed',
                    text: 'Failed to generate PDF. Please try again.',
                    confirmButtonClass: 'btn btn-primary'
                });
            } finally {
                // Reset button state
                submitBtn.innerHTML = originalBtnText;
                submitBtn.disabled = false;
            }
        });
    }

    // Initialize date range pickers
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        input.addEventListener('change', function() {
            const dateFrom = document.querySelector('input[name="dateFrom"]');
            const dateTo = document.querySelector('input[name="dateTo"]');
            
            if (dateFrom && dateTo && dateFrom.value && dateTo.value) {
                if (new Date(dateFrom.value) > new Date(dateTo.value)) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Date Range',
                        text: 'The start date must be before the end date.',
                        confirmButtonClass: 'btn btn-primary'
                    });
                    this.value = '';
                }
            }
        });
    });
});