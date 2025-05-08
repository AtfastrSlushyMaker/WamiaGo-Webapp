/**
 * Face Recognition Enhancement Module
 * Provides advanced features for the face recognition system
 */
window.FaceRecognitionEnhancement = (function() {
    'use strict';

    /**
     * Check browser compatibility for face recognition
     * Tests for camera, face detection API, and other required features
     */
    function checkBrowserCompatibility() {
        const compatibility = {
            camera: !!navigator.mediaDevices && !!navigator.mediaDevices.getUserMedia,
            faceDetection: 'FaceDetector' in window,
            webgl: checkWebGLSupport(),
            compatible: false,
            supportMessage: ''
        };

        // Determine overall compatibility
        if (!compatibility.camera) {
            compatibility.supportMessage = 'Camera access is not supported in this browser';
        } else if (!compatibility.webgl) {
            compatibility.supportMessage = 'Your browser does not support WebGL, which may limit face recognition accuracy';
            compatibility.compatible = true; // Still usable, but with limitations
        } else {
            compatibility.compatible = true;
            compatibility.supportMessage = compatibility.faceDetection ? 
                'Full face recognition support detected' : 
                'Basic face recognition support detected';
        }

        return compatibility;
    }

    /**
     * Check WebGL support for canvas operations
     */
    function checkWebGLSupport() {
        const canvas = document.createElement('canvas');
        const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
        return !!gl;
    }

    /**
     * Analyze lighting conditions using video input
     * @param {HTMLVideoElement} videoElement - The video element to analyze
     */
    function analyzeLightingConditions(videoElement) {
        if (!videoElement || !videoElement.videoWidth) {
            return {
                status: 'unknown',
                message: 'Camera not initialized'
            };
        }

        // Create a small canvas for analysis
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        
        // Use a smaller size for performance
        canvas.width = 50;
        canvas.height = 50;
        
        // Draw current video frame to canvas at reduced size
        ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
        
        // Get image data for analysis
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imageData.data;
        
        // Calculate average brightness
        let totalBrightness = 0;
        const pixelCount = data.length / 4;
        
        for (let i = 0; i < data.length; i += 4) {
            // Convert RGB to brightness (standard luminance formula)
            const brightness = 0.299 * data[i] + 0.587 * data[i+1] + 0.114 * data[i+2];
            totalBrightness += brightness;
        }
        
        const averageBrightness = totalBrightness / pixelCount;
        
        // Analyze contrast by checking deviation from average
        let contrastSum = 0;
        for (let i = 0; i < data.length; i += 4) {
            const pixelBrightness = 0.299 * data[i] + 0.587 * data[i+1] + 0.114 * data[i+2];
            contrastSum += Math.abs(pixelBrightness - averageBrightness);
        }
        
        const averageContrast = contrastSum / pixelCount;
        
        // Determine lighting status
        let status, message;
        
        if (averageBrightness < 40) {
            status = 'poor';
            message = 'Lighting too dark. Please move to a brighter area.';
        } else if (averageBrightness > 220) {
            status = 'poor';
            message = 'Lighting too bright. Please reduce direct light on your face.';
        } else if (averageContrast < 10) {
            status = 'poor';
            message = 'Low contrast detected. Please improve lighting conditions.';
        } else {
            status = 'good';
            message = 'Lighting conditions are good.';
        }
        
        return {
            status: status,
            message: message,
            data: {
                brightness: averageBrightness,
                contrast: averageContrast
            }
        };
    }

    /**
     * Start face detection using browser's Face Detection API
     * @param {HTMLVideoElement} videoElement - Video element to detect faces in
     */
    function startFaceDetection(videoElement) {
        // Check if the Face Detection API is available
        if (!('FaceDetector' in window)) {
            console.warn('Face Detection API not available');
            return {
                status: 'unavailable',
                message: 'Advanced face detection not supported in this browser'
            };
        }

        const faceDetector = new FaceDetector({
            fastMode: true,
            maxDetectedFaces: 1
        });

        let detectionActive = true;
        let lastDetectionResult = null;
        
        // Function to update face position indicators
        function updateFacePositionIndicators(faces) {
            const faceOverlay = document.getElementById('face-overlay');
            const statusElement = document.getElementById('face-detection-status');
            
            if (!faceOverlay || !statusElement) return;
            
            const statusDot = statusElement.querySelector('.detection-dot');
            const statusText = statusElement.querySelector('.detection-status-text');
            
            if (faces.length === 0) {
                // No face detected
                faceOverlay.style.borderColor = 'rgba(255, 255, 255, 0.8)';
                faceOverlay.classList.add('pulse');
                
                if (statusDot) statusDot.className = 'detection-dot not-detected';
                if (statusText) statusText.textContent = 'No face detected';
                
                return {
                    detected: false,
                    message: 'No face detected'
                };
            }
            
            // Face detected, get the first face
            const face = faces[0];
            const faceBox = face.boundingBox;
            
            // Check if face is centered and sized appropriately
            const videoWidth = videoElement.videoWidth;
            const videoHeight = videoElement.videoHeight;
            
            // Calculate relative face position and size
            const faceCenterX = faceBox.left + faceBox.width / 2;
            const faceCenterY = faceBox.top + faceBox.height / 2;
            const faceRelativeWidth = faceBox.width / videoWidth;
            
            // Determine position quality
            const videoCenterX = videoWidth / 2;
            const videoCenterY = videoHeight / 2;
            
            // Calculate percentage from center (0 = perfect center)
            const xOffCenter = Math.abs((faceCenterX - videoCenterX) / videoCenterX);
            const yOffCenter = Math.abs((faceCenterY - videoCenterY) / videoCenterY);
            
            const isCentered = xOffCenter < 0.15 && yOffCenter < 0.15;
            const isGoodSize = faceRelativeWidth > 0.2 && faceRelativeWidth < 0.6;
            
            // Update UI based on face position
            if (isCentered && isGoodSize) {
                // Perfect position
                faceOverlay.style.borderColor = 'rgba(76, 175, 80, 0.8)'; // Green
                faceOverlay.classList.remove('pulse');
                
                if (statusDot) statusDot.className = 'detection-dot detected';
                if (statusText) statusText.textContent = 'Face positioned well';
                
                return {
                    detected: true,
                    positioned: true,
                    message: 'Face positioned well'
                };
            } else {
                // Not ideally positioned
                faceOverlay.style.borderColor = 'rgba(255, 193, 7, 0.8)'; // Yellow/warning
                faceOverlay.classList.add('pulse');
                
                if (statusDot) statusDot.className = 'detection-dot searching';
                
                let guidanceMessage = 'Adjust your face position: ';
                
                if (!isGoodSize) {
                    if (faceRelativeWidth < 0.2) {
                        guidanceMessage += 'Move closer to the camera';
                    } else {
                        guidanceMessage += 'Move further from the camera';
                    }
                } else if (!isCentered) {
                    if (faceCenterX < videoCenterX) {
                        guidanceMessage += 'Move right';
                    } else {
                        guidanceMessage += 'Move left';
                    }
                    
                    if (faceCenterY < videoCenterY) {
                        guidanceMessage += ' and down';
                    } else if (faceCenterY > videoCenterY) {
                        guidanceMessage += ' and up';
                    }
                }
                
                if (statusText) statusText.textContent = guidanceMessage;
                
                return {
                    detected: true,
                    positioned: false,
                    message: guidanceMessage
                };
            }
        }

        // Start detection loop
        function detectFace() {
            if (!detectionActive) return;
            
            faceDetector.detect(videoElement)
                .then(faces => {
                    lastDetectionResult = updateFacePositionIndicators(faces);
                    requestAnimationFrame(detectFace);
                })
                .catch(error => {
                    console.error('Face detection error:', error);
                    requestAnimationFrame(detectFace);
                });
        }

        // Start initial detection
        detectFace();
        
        // Return control object
        return {
            stop: function() {
                detectionActive = false;
            },
            getLastResult: function() {
                return lastDetectionResult;
            },
            isActive: function() {
                return detectionActive;
            }
        };
    }

    /**
     * Enable automatic capture when face is properly positioned
     * @param {HTMLElement} captureButton - The button to click for capture
     * @param {HTMLVideoElement} videoElement - The video element with the face
     */
    function enableAutomaticCapture(captureButton, videoElement) {
        if (!captureButton || !videoElement) {
            console.warn('Cannot enable auto-capture: missing elements');
            return null;
        }

        let captureController = null;
        let lastGoodPositionTime = 0;
        const POSITION_STABILITY_TIME = 1500; // Time face must be well-positioned (ms)
        
        // Don't use FaceDetector if it's not available
        if (!('FaceDetector' in window)) {
            return null;
        }
        
        const faceDetector = new FaceDetector({
            fastMode: true,
            maxDetectedFaces: 1
        });

        function checkFacePosition() {
            if (!videoElement.videoWidth) return;
            
            faceDetector.detect(videoElement)
                .then(faces => {
                    if (faces.length === 0) {
                        // No face detected, reset timer
                        lastGoodPositionTime = 0;
                        return;
                    }
                    
                    const face = faces[0];
                    const faceBox = face.boundingBox;
                    
                    // Calculate if face is well positioned
                    const videoWidth = videoElement.videoWidth;
                    const videoHeight = videoElement.videoHeight;
                    
                    const faceCenterX = faceBox.left + faceBox.width / 2;
                    const faceCenterY = faceBox.top + faceBox.height / 2;
                    const faceRelativeWidth = faceBox.width / videoWidth;
                    
                    const videoCenterX = videoWidth / 2;
                    const videoCenterY = videoHeight / 2;
                    
                    const xOffCenter = Math.abs((faceCenterX - videoCenterX) / videoCenterX);
                    const yOffCenter = Math.abs((faceCenterY - videoCenterY) / videoCenterY);
                    
                    const isCentered = xOffCenter < 0.15 && yOffCenter < 0.15;
                    const isGoodSize = faceRelativeWidth > 0.25 && faceRelativeWidth < 0.5;
                    
                    if (isCentered && isGoodSize) {
                        // Face is well positioned
                        const now = Date.now();
                        
                        if (lastGoodPositionTime === 0) {
                            // Just started being in good position
                            lastGoodPositionTime = now;
                        } else if (now - lastGoodPositionTime > POSITION_STABILITY_TIME) {
                            // Face has been in good position for long enough
                            // Trigger capture automatically
                            captureButton.click();
                            
                            // Stop monitoring
                            if (captureController) {
                                clearInterval(captureController);
                                captureController = null;
                            }
                            
                            console.log('Auto-captured face after stable positioning');
                            return;
                        }
                    } else {
                        // Not well positioned, reset timer
                        lastGoodPositionTime = 0;
                    }
                })
                .catch(error => {
                    console.error('Auto-capture face detection error:', error);
                });
        }
        
        // Start monitoring face position
        captureController = setInterval(checkFacePosition, 500);
        
        // Return controller to stop auto-capture if needed
        return {
            stop: function() {
                if (captureController) {
                    clearInterval(captureController);
                    captureController = null;
                }
            },
            isActive: function() {
                return captureController !== null;
            }
        };
    }

    /**
     * Detect when a user moves to indicate they are a real person (liveness detection)
     * @param {HTMLVideoElement} videoElement - The video element to analyze
     */
    function detectLiveness(videoElement) {
        if (!videoElement || !videoElement.videoWidth) {
            return null;
        }

        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        
        // Use a small canvas for performance
        canvas.width = 50;
        canvas.height = 50;
        
        let previousImageData = null;
        let movementDetected = false;
        let lastMovementTime = 0;
        let livenessController = null;
        
        function analyzeMovement() {
            // Draw current video frame to canvas
            ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
            
            // Get image data for analysis
            const currentImageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            
            if (!previousImageData) {
                previousImageData = currentImageData;
                return {
                    movement: false,
                    message: 'Movement analysis starting'
                };
            }
            
            // Compare frames to detect movement
            const currentData = currentImageData.data;
            const previousData = previousImageData.data;
            let changedPixels = 0;
            
            // Calculate pixel differences
            for (let i = 0; i < currentData.length; i += 16) { // Sample every 4th pixel for performance
                const rDiff = Math.abs(currentData[i] - previousData[i]);
                const gDiff = Math.abs(currentData[i+1] - previousData[i+1]);
                const bDiff = Math.abs(currentData[i+2] - previousData[i+2]);
                
                // If any channel changed significantly
                if (rDiff > 25 || gDiff > 25 || bDiff > 25) {
                    changedPixels++;
                }
            }
            
            // Determine if movement is significant
            const movementThreshold = 5; // Minimum number of changed pixels
            
            const currentMovement = changedPixels > movementThreshold;
            
            if (currentMovement) {
                lastMovementTime = Date.now();
                movementDetected = true;
            }
            
            // Save current frame for next comparison
            previousImageData = currentImageData;
            
            return {
                movement: currentMovement,
                anyMovement: movementDetected,
                lastMovementTime: lastMovementTime,
                message: currentMovement ? 'Movement detected' : 'No current movement'
            };
        }
        
        // Start monitoring for movement
        livenessController = setInterval(analyzeMovement, 300);
        
        return {
            check: analyzeMovement,
            hasDetectedLiveness: function() {
                return movementDetected;
            },
            stop: function() {
                if (livenessController) {
                    clearInterval(livenessController);
                    livenessController = null;
                }
            }
        };
    }

    // Return public API
    return {
        checkBrowserCompatibility,
        analyzeLightingConditions,
        startFaceDetection,
        enableAutomaticCapture,
        detectLiveness
    };
})();