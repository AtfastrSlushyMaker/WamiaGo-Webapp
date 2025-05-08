// Face recognition login script
// Global debug logging function to avoid reference errors
function logDebug(message, data = null) {
    const debugContent = document.getElementById('debug-content');
    if (!debugContent) {
        console.log(`[Face Recognition] ${message}`, data);
        return;
    }
    
    const now = new Date();
    const timeStr = now.toLocaleTimeString() + '.' + now.getMilliseconds().toString().padStart(3, '0');
    let logEntry = `[${timeStr}] ${message}`;
    
    if (data) {
        try {
            if (typeof data === 'object') {
                logEntry += ': ' + JSON.stringify(data, null, 2);
            } else {
                logEntry += ': ' + data;
            }
        } catch (e) {
            logEntry += ': [Cannot stringify data]';
        }
    }
    
    const div = document.createElement('div');
    div.textContent = logEntry;
    debugContent.appendChild(div);
    debugContent.scrollTop = debugContent.scrollHeight;
}

document.addEventListener('DOMContentLoaded', function() {
    const video = document.getElementById('webcam');
    const canvas = document.getElementById('canvas');
    const captureButton = document.getElementById('capture-face');
    const switchCameraButton = document.getElementById('switch-camera');
    const statusMessage = document.getElementById('status-message');
    const scanLine = document.getElementById('scan-line');
    const debugSection = document.getElementById('debug-section');
    const debugContent = document.getElementById('debug-content');
    const testApiButton = document.getElementById('test-api');
    const verificationStatus = document.getElementById('verification-status');
    const verificationStatusText = document.getElementById('verification-status-text');
    
    // Initialize enhancements if available
    let autoCapture = null;
    let livenessDetection = null;
    const enhancementAPI = window.FaceRecognitionEnhancement;
    
    // Show debug section in development environment
    if (debugSection) {
        debugSection.classList.remove('d-none');
    }    
      // Initialize camera when page loads
    if (video) {
        // Add a slight delay before initializing webcam to ensure DOM is fully loaded
        setTimeout(() => {
            startWebcam();
            logDebug('Initializing webcam on page load');
            
            // Setup accessibility features
            setupAccessibilityFeatures();
            
            // Start enhanced face detection if available
            if (enhancementAPI) {
                logDebug('Starting enhanced face detection');
                enhancementAPI.startFaceDetection(video);
                
                // Enable automatic capture when face is properly positioned
                autoCapture = enhancementAPI.enableAutomaticCapture(captureButton, video);
                
                // Start liveness detection
                livenessDetection = enhancementAPI.detectLiveness(video);
                if (livenessDetection) {
                    logDebug('Liveness detection activated');
                    
                    // Show liveness indicator
                    const livenessIndicator = document.getElementById('liveness-indicator');
                    if (livenessIndicator) {
                        livenessIndicator.classList.remove('d-none');
                        
                        // Check liveness status periodically
                        setInterval(() => {
                            if (livenessDetection.hasDetectedLiveness()) {
                                livenessIndicator.querySelector('span').textContent = 'Liveness confirmed';
                                livenessIndicator.querySelector('.spinner-border').style.display = 'none';
                                // Remove indicator after 3 seconds
                                setTimeout(() => {
                                    livenessIndicator.classList.add('d-none');
                                }, 3000);
                            }
                        }, 1000);
                    }
                }
                
                // Analyze lighting conditions periodically
                setInterval(() => {
                    if (video.videoWidth && enhancementAPI) {
                        const lightingInfo = enhancementAPI.analyzeLightingConditions(video);
                        if (lightingInfo && lightingInfo.status === 'poor') {
                            showStatus(lightingInfo.message, 'warning');
                        }
                    }
                }, 5000);
            }
        }, 500);
        
        // Add a button to manually initialize camera if automatic initialization fails
        const statusMessage = document.getElementById('status-message');
        if (statusMessage) {
            const retryButton = document.createElement('button');
            retryButton.textContent = 'Retry Camera Access';
            retryButton.className = 'btn btn-warning mt-2';
            retryButton.addEventListener('click', () => {
                logDebug('Manual camera initialization triggered');
                startWebcam();
            });
            
            // Add the button after the status message
            statusMessage.parentNode.insertBefore(retryButton, statusMessage.nextSibling);
            // Initially hide the button
            retryButton.style.display = 'none';
            
            // Show the button if camera initialization fails
            setTimeout(() => {
                if (!stream) {
                    retryButton.style.display = 'block';
                    logDebug('Camera not initialized after timeout, showing retry button');
                }
            }, 3000);
        }
    } else {
        console.error('Webcam element not found');
        logDebug('ERROR: Webcam element not found in DOM');
    }
    
    let stream = null;
    let facingMode = 'user'; // Front camera
    
    // Update steps
    function updateStep(stepId) {
        document.querySelectorAll('.step').forEach(step => step.classList.remove('active'));
        document.getElementById(stepId).classList.add('active');
    }
    
    // Start webcam
    async function startWebcam() {
        try {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            
            // Check if browser supports getUserMedia
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('Your browser does not support camera access. Please use a modern browser.');
            }
            
            const constraints = {
                video: {
                    facingMode: facingMode,
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            };
            
            logDebug('Requesting camera with constraints', constraints);
            
            // Add a timeout for camera access
            const timeoutPromise = new Promise((_, reject) => {
                setTimeout(() => reject(new Error('Camera access timeout - this may be a permission issue')), 5000);
            });
            
            // Race between camera access and timeout
            stream = await Promise.race([
                navigator.mediaDevices.getUserMedia(constraints),
                timeoutPromise
            ]);
            
            if (!stream) {
                throw new Error('Could not access camera stream');
            }
            
            video.srcObject = stream;
            
            // Add event listeners for video element
            video.addEventListener('loadedmetadata', () => {
                logDebug('Video metadata loaded');
                  // Initialize face detection status indicator
                const faceDetectionStatus = document.getElementById('face-detection-status');
                if (faceDetectionStatus) {
                    faceDetectionStatus.classList.remove('d-none');
                    startFaceDetection(video);
                }
                
                // Initialize quality meter if the quality check module is available
                const qualityMeter = document.getElementById('quality-meter');
                if (qualityMeter && window.FaceQualityCheck) {
                    qualityMeter.style.display = 'block';
                    
                    // Update quality meter periodically
                    setInterval(() => {
                        if (!video.videoWidth) return;
                        
                        // Capture current frame to a temporary canvas
                        const tempCanvas = document.createElement('canvas');
                        const tempCtx = tempCanvas.getContext('2d');
                        tempCanvas.width = video.videoWidth;
                        tempCanvas.height = video.videoHeight;
                        tempCtx.drawImage(video, 0, 0, tempCanvas.width, tempCanvas.height);
                        
                        // Assess quality
                        const qualityResult = window.FaceQualityCheck.assessFaceImageQuality(tempCanvas);
                        
                        // Update progress bar
                        const progressBar = qualityMeter.querySelector('.progress-bar');
                        const qualityScore = qualityResult.qualityScore;
                        
                        progressBar.style.width = qualityScore + '%';
                        progressBar.setAttribute('aria-valuenow', qualityScore);
                        
                        // Set color based on quality score
                        if (qualityScore < 40) {
                            progressBar.classList.remove('bg-success', 'bg-warning');
                            progressBar.classList.add('bg-danger');
                        } else if (qualityScore < 70) {
                            progressBar.classList.remove('bg-success', 'bg-danger');
                            progressBar.classList.add('bg-warning');
                        } else {
                            progressBar.classList.remove('bg-warning', 'bg-danger');
                            progressBar.classList.add('bg-success');
                        }
                    }, 1000);
                }
            });
            
            video.addEventListener('error', (e) => {
                logDebug('Video element error', e);
                showStatus('Video element error: ' + (e.message || 'Unknown error'), 'danger');
            });            await video.play();
            
            // Add animation to the face overlay to draw attention
            document.getElementById('face-overlay').classList.add('pulse');
            
            // Initialize scan line animation if available
            if (window.initializeScanLine) {
                window.initializeScanLine();
            }
            
            logDebug('Camera started successfully');
            if (captureButton) captureButton.disabled = false;
            updateStep('step-face');
            
        } catch (error) {
            console.error('Error accessing webcam:', error);
            logDebug('Camera access error', error.message);
            showStatus('Could not access webcam: ' + error.message + '. Please ensure camera permissions are allowed.', 'danger');
            if (captureButton) captureButton.disabled = true;
            
            // Suggest solutions based on common errors
            if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                showStatus('Camera access denied. Please allow camera access in your browser settings and reload the page.', 'danger');
            } else if (error.name === 'NotFoundError') {
                showStatus('No camera found. Please connect a camera and reload the page.', 'danger');
            } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
                showStatus('Camera is already in use by another application. Please close other apps that might be using your camera.', 'danger');
            }
        }
    }
    
    // Switch camera
    if (switchCameraButton) {
        switchCameraButton.addEventListener('click', function() {
            facingMode = facingMode === 'user' ? 'environment' : 'user';
            logDebug(`Switching camera to ${facingMode}`);
            startWebcam();
        });
    }
    
    // Capture and verify face
    if (captureButton) {
        captureButton.addEventListener('click', async function() {
            if (!stream) {
                showStatus('Camera not available. Please refresh and try again.', 'danger');
                return;
            }
            
            // Check if liveness detection has confirmed movement (anti-spoofing)
            if (livenessDetection && !livenessDetection.hasDetectedLiveness()) {
                showStatus('Please move slightly to confirm you are a real person.', 'warning');
                  // Show the liveness indicator if it's hidden
                const livenessIndicator = document.getElementById('liveness-indicator');
                if (livenessIndicator) {
                    livenessIndicator.classList.remove('d-none');
                    livenessIndicator.querySelector('span').textContent = 'Please move slightly...';
                }
                
                // Wait for liveness to be confirmed
                let livenessTimeout = setTimeout(() => {
                    // If liveness still not confirmed after timeout, proceed anyway
                    proceedWithCapture();
                }, 3000);
                
                // Check for liveness every 300ms
                const livenessInterval = setInterval(() => {
                    if (livenessDetection.hasDetectedLiveness()) {
                        clearTimeout(livenessTimeout);
                        clearInterval(livenessInterval);
                        
                        // Update liveness indicator
                        if (livenessIndicator) {
                            livenessIndicator.querySelector('span').textContent = 'Liveness confirmed!';
                            // Hide after 1 second
                            setTimeout(() => {
                                livenessIndicator.classList.add('d-none');
                            }, 1000);
                        }
                        
                        proceedWithCapture();
                    }
                }, 300);
                
                return;
            }
            
            // If we don't have liveness detection or it's already confirmed
            proceedWithCapture();
            
            // Function to proceed with the actual face capture and verification
            async function proceedWithCapture() {
                // Start scanning animation
                scanLine.style.opacity = '1';
                scanLine.style.animation = 'scan 2s infinite';
                
                // Show processing message
                showStatus('Verifying your face...', 'info');
                showVerificationStatus(true, 'Analyzing face...');
                captureButton.disabled = true;
                
                try {
                    // Start countdown and capture process
                    await startCaptureCountdown(3); // 3 second countdown
                              // Capture frame
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
              // Assess face image quality if the quality check module is available
            let imageData;
            if (window.FaceQualityCheck) {
                // Check image quality first
                const qualityResult = window.FaceQualityCheck.assessFaceImageQuality(canvas);
                logDebug('Face image quality assessment:', qualityResult);
                  // Check for face occlusions
                const occlusionResult = window.FaceQualityCheck.detectFaceOcclusions(canvas);
                logDebug('Face occlusion detection:', occlusionResult);
                
                if (occlusionResult.hasGlasses && occlusionResult.confidence > 0.7) {
                    showStatus('Please remove glasses for better recognition accuracy', 'warning');
                    // Give user time to read the warning
                    await new Promise(resolve => setTimeout(resolve, 2000));
                }
                
                if (qualityResult.isPoorQuality) {
                    // Show quality issues but still continue
                    showStatus('Face image may have quality issues: ' + qualityResult.issues.join(', '), 'warning');
                    // Give user time to read the warning
                    await new Promise(resolve => setTimeout(resolve, 1500));
                }
                
                // Apply image enhancement
                imageData = window.FaceQualityCheck.enhanceFaceImage(canvas);
                logDebug('Enhanced image, size: ' + Math.round(imageData.length / 1024) + 'KB');
            } else {
                // Fallback to standard image capture
                imageData = canvas.toDataURL('image/jpeg');
                logDebug('Image captured, size: ' + Math.round(imageData.length / 1024) + 'KB');
            }
                
            // Prepare data for request
            const data = JSON.stringify({
                faceImage: imageData
            });
                    
                    logDebug('Sending verification request to server');
                    
                    // Send to server as JSON
                    let response;
                    let usedFallback = false;
                    try {
                        // Get the verify URL from the data attribute
                        const verifyUrl = captureButton.dataset.verifyUrl;
                        logDebug('Attempting primary face verification endpoint', verifyUrl);
                        
                        showVerificationStatus(true, 'Connecting to server...');
                        
                        response = await fetch(verifyUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            body: data,
                            // Prevent redirection follow to capture actual response
                            redirect: 'manual'
                        });
                        
                        // If response status is 0, that typically means a CORS or network error
                        if (response.status === 0) {
                            throw new Error(`Server responded with status: ${response.status}`);
                        }
                    } catch (fetchError) {
                        logDebug('Primary endpoint fetch error', fetchError.message);
                        // Try the diagnostic endpoint as a fallback
                        logDebug('Retrying with diagnostic endpoint...');
                        usedFallback = true;
                        
                        try {
                            // Get the check URL from the data attribute
                            const checkUrl = captureButton.dataset.checkUrl;
                            // Check server connectivity with diagnostic endpoint
                            logDebug('Checking server connectivity', checkUrl);
                            
                            response = await fetch(checkUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ 
                                    test: true,
                                    imageSize: Math.round(imageData.length / 1024)
                                })
                            });
                            
                            logDebug('Diagnostic endpoint response status:', response.status);
                        } catch (diagnosticError) {
                            logDebug('Diagnostic endpoint error', diagnosticError.message);
                            throw new Error('Both verification endpoints failed. Please check server connectivity.');
                        }
                    }
                    
                    logDebug(`Server response status: ${response.status} ${response.statusText}`);
                    
                    // First check the content type to make sure we're getting JSON
                    const contentType = response.headers.get('content-type');
                    logDebug('Response content type:', contentType);
                    
                    let result;
                    
                    if (!contentType || !contentType.includes('application/json')) {
                        try {
                            // Try to get response text for debugging
                            const responseText = await response.text();
                            logDebug('Non-JSON response received:', responseText.substring(0, 200));
                            
                            // Try to parse it as JSON anyway (sometimes servers send wrong content type)
                            try {
                                result = JSON.parse(responseText);
                                logDebug('Successfully parsed response as JSON despite wrong content type');
                            } catch (jsonParseError) {
                                // It's not valid JSON
                                throw new Error('Invalid response format from server: ' + contentType);
                            }
                        } catch (textError) {
                            throw new Error('Could not read server response');
                        }
                    } else {
                        // We have a proper JSON content type, parse it
                        try {
                            result = await response.json();
                            logDebug('Response data', result);
                        } catch (jsonError) {
                            logDebug('JSON parse error', jsonError);
                            throw new Error('Failed to parse JSON response');
                        }
                    }
                    
                    if (result.success || result.verified) {
                        logDebug('Face verification successful', result);
                        showStatus('Face verified successfully! Redirecting...', 'success');
                        showVerificationStatus(true, 'Verification successful!');
                        updateStep('step-verify');
                        
                        // Redirect to specified URL or home
                        setTimeout(() => {
                            window.location.href = result.redirect || '/';
                        }, 1500);
                    } else {
                        logDebug('Face verification failed', result);
                        // If we're using the fallback endpoint, don't proceed with login
                        if (usedFallback) {
                            showStatus('Face verification service unavailable. Please try password login.', 'danger');
                            setTimeout(() => {
                                window.location.href = '/login?error=face_api_unavailable';
                            }, 3000);
                            return;
                        }
                        
                        // Check if the response contains API status information
                        if (result.api_status && result.api_status === 'offline') {
                            showStatus('Face recognition server is offline. Please try password login.', 'danger');
                        } else if (result.api_status && result.api_status === 'degraded') {
                            showStatus('Face recognition service is experiencing issues. Please try again or use password login.', 'warning');
                        } else {
                            // Format an informative error message
                            let errorMessage = '';
                            
                            // If the message contains "Face matched", it's actually a successful match but the server returned an error
                            if (result.message && result.message.includes('Face matched')) {
                                errorMessage = 'Your face was recognized, but there was an authentication error. Please try again.';
                            } else {
                                errorMessage = result.message || 'Face verification failed';
                            }
                            
                            // Add any error details if available
                            if (result.error_details) {
                                errorMessage += ' (' + result.error_details + ')';
                            }
                            
                            showStatus(errorMessage, 'danger');
                        }
                        
                        captureButton.disabled = false;
                        scanLine.style.opacity = '0';
                        scanLine.style.animation = 'none';
                        showVerificationStatus(false);
                    }
                } catch (error) {
                    console.error('Error verifying face:', error);
                    logDebug('Exception during verification', error);
                    showStatus('An error occurred during face verification: ' + error.message, 'danger');
                    captureButton.disabled = false;
                    scanLine.style.opacity = '0';
                    scanLine.style.animation = 'none';
                    showVerificationStatus(false);
                }
            }
        });
    }
      function showStatus(message, type) {
        if (!statusMessage) return;
        
        logDebug(`Status message (${type}): ${message}`);
        statusMessage.textContent = message;
        statusMessage.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-warning', 'alert-info');
        statusMessage.classList.add(`alert-${type}`);
    }
    
    // Countdown and capture process
    async function startCaptureCountdown(seconds) {
        return new Promise((resolve) => {
            const countdownElement = document.getElementById('countdown');
            const countdownSpan = countdownElement.querySelector('span');
            const faceOverlay = document.getElementById('face-overlay');
            
            // Make sure the face overlay has the pulse animation
            faceOverlay.classList.add('pulse');
            
            // Show the countdown element
            countdownElement.classList.remove('d-none');
            
            let remainingSeconds = seconds;
            
            // Update countdown display
            function updateCountdown() {
                countdownSpan.textContent = remainingSeconds;
                countdownSpan.style.animation = 'none'; // Reset animation
                
                // Force reflow to restart animation
                void countdownSpan.offsetWidth;
                countdownSpan.style.animation = 'countdown 1s ease-in-out forwards';
            }
            
            updateCountdown();
            
            // Start countdown interval
            const intervalId = setInterval(() => {
                remainingSeconds--;
                
                if (remainingSeconds > 0) {
                    updateCountdown();
                } else {
                    // Countdown finished
                    clearInterval(intervalId);
                    
                    // Flash the overlay to indicate capture
                    faceOverlay.style.borderColor = 'rgba(255, 255, 255, 1)';
                    faceOverlay.style.boxShadow = '0 0 20px rgba(255, 255, 255, 0.8)';
                    
                    setTimeout(() => {
                        // Reset overlay styles
                        faceOverlay.style.borderColor = '';
                        faceOverlay.style.boxShadow = '';
                        
                        // Hide countdown
                        countdownElement.classList.add('d-none');
                        
                        // Continue with capture process
                        resolve();
                    }, 500); // Flash duration
                }
            }, 1000);
        });
    }
    
    function showVerificationStatus(show, message = null) {
        if (!verificationStatus) return;
        
        if (show) {
            verificationStatus.classList.remove('d-none');
            if (message) {
                verificationStatusText.textContent = message;
            }
        } else {
            verificationStatus.classList.add('d-none');
        }
    }    // Real-time face detection
    async function startFaceDetection(videoElement) {
        const faceDetectionStatus = document.getElementById('face-detection-status');
        
        try {
            // Check if the Face Detection API is available
            if (!('FaceDetector' in window)) {
                logDebug('Face Detection API not available');
                return;
            }
            
            const faceDetector = new FaceDetector({
                fastMode: true,
                maxDetectedFaces: 1
            });
            
            logDebug('Starting face detection');
            
            let detectionActive = true;
            let noFaceCounter = 0;
            const maxNoFaceFrames = 5; // Number of frames without a face before we consider it "not detected"
            
            async function detectFace() {
                if (!detectionActive) return;
                
                try {
                    const faces = await faceDetector.detect(videoElement);
                    
                    if (faces.length > 0) {
                        // Face detected
                        faceDetectionStatus.querySelector('span').textContent = 'Face detected';
                        document.getElementById('face-overlay').style.borderColor = 'rgba(76, 175, 80, 0.8)'; // Green border
                        noFaceCounter = 0;
                    } else {
                        // No face detected
                        noFaceCounter++;
                        
                        if (noFaceCounter >= maxNoFaceFrames) {
                            faceDetectionStatus.querySelector('span').textContent = 'Position your face';
                            document.getElementById('face-overlay').style.borderColor = 'rgba(255, 255, 255, 0.8)'; // Reset border
                        }
                    }
                } catch (error) {
                    logDebug('Face detection error', error);
                    faceDetectionStatus.querySelector('span').textContent = 'Detection error';
                }
                
                // Run detection again in the next animation frame
                requestAnimationFrame(detectFace);
            }
            
            // Start the detection loop
            detectFace();
            
            // Cleanup function
            return function stopDetection() {
                detectionActive = false;
            };
        } catch (error) {
            logDebug('Error initializing face detection', error);
            if (faceDetectionStatus) {
                faceDetectionStatus.querySelector('span').textContent = 'Detection error';
            }
        }
    }


    
    // Test API connection in development mode
    if (testApiButton) {
        testApiButton.addEventListener('click', async function() {
            showStatus('Testing API connection...', 'info');
            try {
                const checkUrl = testApiButton.dataset.checkUrl;
                const response = await fetch(checkUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        test: true,
                        timestamp: new Date().toISOString()
                    })
                });
                
                logDebug(`Test API response status: ${response.status} ${response.statusText}`);
                
                const contentType = response.headers.get('content-type');
                logDebug('Test response content type:', contentType);
                
                if (!contentType || !contentType.includes('application/json')) {
                    const responseText = await response.text();
                    logDebug('Test API non-JSON response:', responseText.substring(0, 200));
                    throw new Error('API returned invalid content type: ' + contentType);
                }
                
                const result = await response.json();
                logDebug('Test API response:', result);
                
                if (result.success) {
                    // Check API status
                    if (result.api_status === 'online') {
                        showStatus(`API connection successful! Facial recognition system is online and ready.`, 'success');
                    } else if (result.api_status === 'degraded') {
                        showStatus(`API connection successful, but face recognition service is degraded: ${result.api_message}`, 'warning');
                    } else if (result.api_status === 'offline') {
                        showStatus(`API check successful, but face recognition service is offline: ${result.api_message}`, 'warning');
                    } else {
                        showStatus(`API connection successful. Status: ${result.api_status || 'unknown'}`, 'info');
                    }
                    
                    // Add API info to debug log
                    if (result.server_info) {
                        logDebug('Server information:', result.server_info);
                    }
                } else {
                    showStatus(result.message || 'API test failed', 'warning');
                }
            } catch (error) {
                console.error('Test API error:', error);
                showStatus('API test failed: ' + error.message, 'danger');
            }
        });
    }
    
    // Handle accessibility preferences
function setupAccessibilityFeatures() {
        const container = document.querySelector('.webcam-container');
        if (!container) return;
        
        // Check if user prefers reduced motion
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        
        if (prefersReducedMotion) {
            // Disable animations for users who prefer reduced motion
            document.documentElement.classList.add('reduce-motion');
            logDebug('Reduced motion preference detected, animations disabled');
        }
        
        // Add accessibility toggle for animations
        const accessibilityToggle = document.createElement('button');
        accessibilityToggle.className = 'btn btn-sm btn-outline-light position-absolute';
        accessibilityToggle.style.top = '10px';
        accessibilityToggle.style.left = '10px';
        accessibilityToggle.style.zIndex = '30';
        accessibilityToggle.innerHTML = '<i class="fas fa-universal-access"></i>';
        accessibilityToggle.title = 'Toggle animations (accessibility)';
          accessibilityToggle.addEventListener('click', function() {
            document.documentElement.classList.toggle('reduce-motion');
            const animationsDisabled = document.documentElement.classList.contains('reduce-motion');
            logDebug(`Animations ${animationsDisabled ? 'disabled' : 'enabled'} by user preference`);
            
            // Update scan line animation if available
            if (window.initializeScanLine) {
                window.initializeScanLine();
            }
            
            // Show feedback to user
            showStatus(`Animations ${animationsDisabled ? 'disabled' : 'enabled'} for accessibility`, 'info');
            setTimeout(() => {
                if (statusMessage.textContent.includes('Animations')) {
                    statusMessage.classList.add('d-none');
                }
            }, 3000);
        });
        
        container.appendChild(accessibilityToggle);
    }
    
    // Initialize accessibility features
    setupAccessibilityFeatures();
});