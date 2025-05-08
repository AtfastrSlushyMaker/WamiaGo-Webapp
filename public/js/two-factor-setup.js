// Handle form submission for verification
document.addEventListener('DOMContentLoaded', function() {
    const verifyForm = document.getElementById('verify2faForm');
    if (verifyForm) {
        verifyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            const submitBtn = verifyForm.querySelector('button[type="submit"]');
            const spinner = submitBtn.querySelector('.spinner-border');
            const btnText = submitBtn.querySelector('span:not(.spinner-border)');
            
            spinner.classList.remove('d-none');
            submitBtn.disabled = true;
            btnText.textContent = 'Verifying...';
            
            // Reset any previous errors
            const errorEl = document.getElementById('verification-error');
            if (errorEl) {
                errorEl.textContent = '';
                errorEl.parentElement.classList.remove('was-validated');
            }
            
            // Get the verification code
            const verificationCode = document.getElementById('verification-code').value;
            const secret = document.getElementById('totp-secret').value;
            
            console.log('Submitting verification with code:', verificationCode);
            console.log('Secret key used:', secret);
            
            if (!verificationCode || verificationCode.length !== 6) {
                showVerificationError('Please enter all 6 digits of the verification code.');
                resetSubmitButton();
                return;
            }
            
            if (!secret) {
                showVerificationError('Missing secret key. Please go back to step 2 and try again.');
                resetSubmitButton();
                return;
            }
            
            // Submit the form using fetch
            fetch(verifyForm.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    code: verificationCode,
                    secret: secret
                })
            })
            .then(response => {
                if (!response.ok) {
                    console.log('Response status:', response.status);
                    return response.json().then(data => {
                        throw new Error(data.message || 'Verification failed. Please try again.');
                    }).catch(e => {
                        // If the response is not a valid JSON, throw a generic error
                        if (e instanceof SyntaxError) {
                            throw new Error('Server returned an invalid response. Please try again.');
                        }
                        throw e;
                    });
                }
                return response.json();
            })
            .then(data => {
                // Show success message
                const successEl = document.getElementById('2fa-success');
                if (successEl) {
                    successEl.classList.remove('d-none');
                    successEl.querySelector('span').textContent = data.message || 'Two-factor authentication has been enabled successfully!';
                }
                
                // Hide any error message
                const errorAlert = document.getElementById('2fa-error');
                if (errorAlert) {
                    errorAlert.classList.add('d-none');
                }
                
                // Close the modal if it exists
                const modal = document.getElementById('twoFactorSetupModal');
                if (modal && typeof bootstrap !== 'undefined') {
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) {
                        bsModal.hide();
                    }
                }
                
                // Redirect to profile page after 2 seconds
                setTimeout(() => {
                    window.location.href = '/profile';
                }, 2000);
            })
            .catch(error => {
                console.error('Verification error:', error);
                showVerificationError(error.message || 'Verification failed. Please try again.');
                resetSubmitButton();
            });
            
            function resetSubmitButton() {
                spinner.classList.add('d-none');
                submitBtn.disabled = false;
                btnText.textContent = 'Verify and Enable';
            }
            
            function showVerificationError(message) {
                const errorEl = document.getElementById('verification-error');
                if (errorEl) {
                    errorEl.textContent = message;
                    errorEl.parentElement.classList.add('was-validated');
                    
                    // Show the error in the alert div as well
                    const alertEl = document.getElementById('2fa-error');
                    if (alertEl) {
                        alertEl.classList.remove('d-none');
                        alertEl.querySelector('span').textContent = message;
                    }
                }
            }
        });
    }
});
