/**
 * Enhanced Two-Factor Authentication Setup JavaScript
 */
document.addEventListener('DOMContentLoaded', function() {
    // Animation for step transitions
    function animateStepTransition(fromStep, toStep) {
        const currentStep = document.getElementById(`step-${fromStep}`);
        const nextStep = document.getElementById(`step-${toStep}`);
        
        if (!currentStep || !nextStep) return;
        
        // Fade out current step
        currentStep.style.opacity = '0';
        currentStep.style.transform = 'translateY(-20px)';
        
        // After a short delay, hide current and show next
        setTimeout(() => {
            currentStep.style.display = 'none';
            
            nextStep.style.display = 'block';
            nextStep.style.opacity = '0';
            
            // Trigger reflow
            void nextStep.offsetWidth;
            
            // Fade in next step
            nextStep.style.opacity = '1';
            nextStep.style.transform = 'translateY(0)';
        }, 300);
    }
    
    // Update the progress indicators
    function updateProgressIndicators(currentStep) {
        document.querySelectorAll('.step-dot').forEach((dot, index) => {
            const stepNumber = index + 1;
            
            if (stepNumber < currentStep) {
                // Previous steps are completed
                dot.classList.remove('active');
                dot.classList.add('completed');
                dot.innerHTML = '<i class="fas fa-check"></i>';
            } else if (stepNumber == currentStep) {
                // Current step is active
                dot.classList.add('active');
                dot.classList.remove('completed');
                dot.innerHTML = stepNumber;
            } else {
                // Future steps are neither active nor completed
                dot.classList.remove('active', 'completed');
                dot.innerHTML = stepNumber;
            }
        });
    }
      // Handle step navigation
    document.querySelectorAll('[data-step]').forEach(button => {
        button.addEventListener('click', function() {
            const currentStep = parseInt(this.closest('.setup-step').id.replace('step-', ''));
            const nextStep = parseInt(this.getAttribute('data-step'));
            
            animateStepTransition(currentStep, nextStep);
            updateProgressIndicators(nextStep);
        });
    });
    
    // Verification code handling for individual digits
    const verificationDigits = document.querySelectorAll('.verification-digit');
    const hiddenCodeInput = document.getElementById('verification-code');
    
    if (verificationDigits.length > 0 && hiddenCodeInput) {
        // Handle input in the verification code fields
        verificationDigits.forEach((digit, index) => {
            // Focus the first digit field when step 3 is shown
            if (index === 0) {
                document.querySelectorAll('[data-step="3"]').forEach(btn => {
                    btn.addEventListener('click', () => setTimeout(() => digit.focus(), 500));
                });
            }
            
            // Focus handling
            digit.addEventListener('keydown', (e) => {
                // If backspace is pressed and the field is empty, move to the previous field
                if (e.key === 'Backspace' && digit.value === '' && index > 0) {
                    verificationDigits[index - 1].focus();
                    return;
                }
                
                // Allow only numbers, backspace, tab, and arrow keys
                if (!/^[0-9]$/.test(e.key) && 
                    e.key !== 'Backspace' && 
                    e.key !== 'Tab' && 
                    e.key !== 'ArrowLeft' &&
                    e.key !== 'ArrowRight') {
                    e.preventDefault();
                }
            });
            
            // Handle input changes
            digit.addEventListener('input', () => {
                // Ensure only one digit
                if (digit.value.length > 1) {
                    digit.value = digit.value.slice(0, 1);
                }
                
                // Auto-advance to next field
                if (digit.value.length === 1 && index < verificationDigits.length - 1) {
                    verificationDigits[index + 1].focus();
                }
                
                // Update the hidden input with all digits
                updateVerificationCode();
            });
            
            // Handle paste events
            digit.addEventListener('paste', (e) => {
                e.preventDefault();
                const pastedData = (e.clipboardData || window.clipboardData).getData('text');
                
                // If we have a 6-digit code pasted
                if (/^\d{6}$/.test(pastedData)) {
                    // Fill in all the input fields
                    verificationDigits.forEach((input, i) => {
                        input.value = pastedData.charAt(i);
                    });
                    
                    // Update the hidden field
                    updateVerificationCode();
                    
                    // Focus the last field
                    verificationDigits[verificationDigits.length - 1].focus();
                }
            });
            
            // Handle key navigation between fields
            digit.addEventListener('keyup', (e) => {
                if (e.key === 'ArrowLeft' && index > 0) {
                    verificationDigits[index - 1].focus();
                } else if (e.key === 'ArrowRight' && index < verificationDigits.length - 1) {
                    verificationDigits[index + 1].focus();
                }
            });
        });
        
        // Function to update the hidden input with the concatenated code
        function updateVerificationCode() {
            const code = Array.from(verificationDigits).map(input => input.value).join('');
            hiddenCodeInput.value = code;
            
            // If we have all 6 digits, we can submit
            if (code.length === 6 && /^\d{6}$/.test(code)) {
                // Add a small delay to avoid accidental submissions
                setTimeout(() => {
                    // Check if all digits are filled before submitting
                    const allFilled = Array.from(verificationDigits).every(input => input.value.length === 1);
                    if (allFilled) {
                        document.querySelector('form').submit();
                    }
                }, 500);
            }
        }
    }
      // Copy to clipboard functionality
    const copyButton = document.getElementById('copy-key');
    if (copyButton) {
        copyButton.addEventListener('click', function() {
            const textToCopy = document.getElementById('setup-key').textContent;
            
            navigator.clipboard.writeText(textToCopy)
                .then(() => {
                    // Show success feedback
                    this.innerHTML = '<i class="fas fa-check"></i>';
                    this.classList.add('text-success');
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-copy"></i>';
                        this.classList.remove('text-success');
                    }, 2000);
                })
                .catch(err => {
                    console.error('Could not copy text: ', err);
                    this.innerHTML = '<i class="fas fa-times"></i>';
                    this.classList.add('text-danger');
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-copy"></i>';
                        this.classList.remove('text-danger');
                    }, 2000);
                });
        });
    }
    
    // Initialize the setup on page load
    const setupSteps = document.querySelectorAll('.setup-step');
    if (setupSteps.length > 0) {
        // Make sure the first step is visible
        setupSteps[0].style.display = 'block';
        
        // Show the QR code on page load if needed
        if (document.getElementById('qr-code-placeholder')) {
            const img = document.createElement('img');
            img.src = window.location.pathname.replace(/\/setup\/?$/, '/qr-code');
            img.alt = 'Two-factor authentication QR code';
            img.classList.add('img-fluid');
            img.style.maxWidth = '200px';
            
            img.onload = function() {
                const placeholder = document.getElementById('qr-code-placeholder');
                if (placeholder) {
                    placeholder.innerHTML = '';
                    placeholder.appendChild(img);
                }
            };
            
            img.onerror = function() {
                const placeholder = document.getElementById('qr-code-placeholder');
                if (placeholder) {
                    placeholder.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Failed to load QR code. Please refresh the page.
                        </div>
                    `;
                }
            };
        }
    }
                        <button type="button" class="btn btn-link p-0 ms-2" id="retry-qr">Try again</button>
                    </div>
                `;
                
                // Add retry functionality
                document.getElementById('retry-qr')?.addEventListener('click', loadQRCode);
            });
    }
    
    // Initialize QR code loading if we're on step 2
    if (!document.getElementById('step-2')?.classList.contains('d-none')) {
        loadQRCode();
    }
    
    // Load QR code when navigating to step 2
    document.querySelectorAll('[data-step="2"]').forEach(button => {
        button.addEventListener('click', () => setTimeout(loadQRCode, 500));
    });
});
