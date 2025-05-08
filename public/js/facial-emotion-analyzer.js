/**
 * Facial Expression Analysis Module
 * Provides basic emotion detection for face recognition system
 */
window.FacialEmotionAnalyzer = (function() {
    'use strict';

    // Emotion detection states
    const emotionStates = {
        NEUTRAL: 'neutral',
        HAPPY: 'happy',
        SAD: 'sad', 
        SURPRISED: 'surprised',
        ANGRY: 'angry',
        UNKNOWN: 'unknown'
    };

    /**
     * Detect basic facial expression based on face geometry
     * This is a simple approximation - production systems would use ML models
     * @param {HTMLCanvasElement|HTMLVideoElement} source - Video or canvas with face
     * @returns {Object} Detected emotion and confidence
     */
    function detectEmotion(source) {
        // Create a temporary canvas for analysis
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        
        // Set canvas size
        const width = 100;  // Small size for performance
        const height = source.videoHeight ? 
            Math.floor(source.videoHeight * (width / source.videoWidth)) : 
            Math.floor(source.height * (width / source.width));
        
        canvas.width = width;
        canvas.height = height;
        
        // Draw video/canvas to analysis canvas
        ctx.drawImage(source, 0, 0, canvas.width, canvas.height);
        
        // Try to detect face using a simple face detection approximation
        // Production systems should use Face Detection API or ML models
        const faceData = detectFaceFeatures(canvas);
        
        if (!faceData.faceDetected) {
            return {
                emotion: emotionStates.UNKNOWN,
                confidence: 0,
                message: "No face detected for emotion analysis"
            };
        }
        
        // Perform simple emotion analysis based on face geometry
        // This is a very basic approximation
        return analyzeEmotion(faceData);
    }
    
    /**
     * Detect basic face features using simple image processing
     * @param {HTMLCanvasElement} canvas - Canvas with face image
     * @returns {Object} Face features data
     */
    function detectFaceFeatures(canvas) {
        const ctx = canvas.getContext('2d');
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imageData.data;
        
        // Very basic skin tone detection to estimate face region
        // This is a simplified approach and not robust for all skin tones and lighting
        let skinPixels = [];
        for (let y = 0; y < canvas.height; y++) {
            for (let x = 0; x < canvas.width; x++) {
                const idx = (y * canvas.width + x) * 4;
                const r = data[idx];
                const g = data[idx + 1];
                const b = data[idx + 2];
                
                // Simple skin tone estimation: higher red component relative to others
                // This is very approximate and won't work for all skin tones or lighting conditions
                if (r > 95 && g > 40 && b > 20 && 
                    r > g && r > b && 
                    Math.abs(r - g) > 15) {
                    skinPixels.push({x, y});
                }
            }
        }
        
        // If not enough skin tone pixels found, probably no face
        if (skinPixels.length < canvas.width * canvas.height * 0.1) {
            return { faceDetected: false };
        }
        
        // Estimate face position from skin pixels
        const xValues = skinPixels.map(p => p.x);
        const yValues = skinPixels.map(p => p.y);
        
        const faceLeft = Math.min(...xValues);
        const faceRight = Math.max(...xValues);
        const faceTop = Math.min(...yValues);
        const faceBottom = Math.max(...yValues);
        
        const faceWidth = faceRight - faceLeft;
        const faceHeight = faceBottom - faceTop;
        const faceCenterX = faceLeft + faceWidth / 2;
        const faceCenterY = faceTop + faceHeight / 2;
        
        // Estimate eye region (very approximate)
        const eyeRegionTop = faceTop + faceHeight * 0.2;
        const eyeRegionBottom = faceTop + faceHeight * 0.4;
        const eyeRegionLeft = faceLeft;
        const eyeRegionRight = faceRight;
        
        // Estimate mouth region (very approximate)
        const mouthRegionTop = faceTop + faceHeight * 0.65;
        const mouthRegionBottom = faceTop + faceHeight * 0.85;
        const mouthRegionLeft = faceLeft + faceWidth * 0.25;
        const mouthRegionRight = faceLeft + faceWidth * 0.75;
        
        // Analyze mouth shape by looking for horizontal edges
        let mouthEdgePixels = [];
        for (let y = mouthRegionTop; y < mouthRegionBottom; y++) {
            for (let x = mouthRegionLeft; x < mouthRegionRight; x++) {
                const idx = (y * canvas.width + x) * 4;
                
                // Skip if at canvas edge
                if (x <= 0 || x >= canvas.width - 1) continue;
                
                // Simple horizontal edge detection
                const leftIdx = (y * canvas.width + (x-1)) * 4;
                const rightIdx = (y * canvas.width + (x+1)) * 4;
                
                const gx = Math.abs(data[rightIdx] - data[leftIdx]) + 
                           Math.abs(data[rightIdx+1] - data[leftIdx+1]) + 
                           Math.abs(data[rightIdx+2] - data[leftIdx+2]);
                
                // If strong edge, add to mouth pixels
                if (gx > 80) {
                    mouthEdgePixels.push({x, y});
                }
            }
        }
        
        // Analyze mouth shape from edge pixels
        const mouthAnalysis = analyzeMouthShape(mouthEdgePixels, mouthRegionTop, mouthRegionBottom);
        
        return {
            faceDetected: true,
            faceBounds: {
                left: faceLeft,
                top: faceTop,
                right: faceRight, 
                bottom: faceBottom,
                width: faceWidth,
                height: faceHeight,
                centerX: faceCenterX,
                centerY: faceCenterY
            },
            mouthCurvature: mouthAnalysis.curvature,
            mouthOpenness: mouthAnalysis.openness
        };
    }
    
    /**
     * Analyze mouth shape from detected edge pixels
     * @param {Array} mouthPixels - Array of detected mouth edge coordinates
     * @param {Number} regionTop - Top boundary of mouth region
     * @param {Number} regionBottom - Bottom boundary of mouth region
     * @returns {Object} Mouth shape analysis
     */
    function analyzeMouthShape(mouthPixels, regionTop, regionBottom) {
        if (mouthPixels.length < 10) {
            return { curvature: 0, openness: 0 };
        }
        
        // Sort pixels by y-coordinate 
        mouthPixels.sort((a, b) => a.y - b.y);
        
        // Group pixels by y-coordinate for mouth shape analysis
        const pixelsByY = {};
        mouthPixels.forEach(p => {
            if (!pixelsByY[p.y]) pixelsByY[p.y] = [];
            pixelsByY[p.y].push(p.x);
        });
        
        // For each row, find the leftmost and rightmost pixel
        // to estimate mouth width at that height
        const mouthWidths = [];
        Object.entries(pixelsByY).forEach(([y, xValues]) => {
            if (xValues.length >= 2) {
                const left = Math.min(...xValues);
                const right = Math.max(...xValues);
                mouthWidths.push({
                    y: parseInt(y),
                    width: right - left
                });
            }
        });
        
        if (mouthWidths.length < 3) {
            return { curvature: 0, openness: 0 };
        }
        
        // Determine if mouth is smiling (wider at top) or frowning (wider at bottom)
        const topThird = mouthWidths.filter(w => w.y < regionTop + (regionBottom - regionTop) / 3);
        const bottomThird = mouthWidths.filter(w => w.y > regionTop + 2 * (regionBottom - regionTop) / 3);
        
        const topAvgWidth = topThird.length > 0 
            ? topThird.reduce((sum, w) => sum + w.width, 0) / topThird.length 
            : 0;
            
        const bottomAvgWidth = bottomThird.length > 0 
            ? bottomThird.reduce((sum, w) => sum + w.width, 0) / bottomThird.length 
            : 0;
            
        // Curvature: positive = smile, negative = frown
        const curvature = topAvgWidth - bottomAvgWidth;
        
        // Check for mouth openness (vertical height of mouth area)
        const mouthHeight = mouthPixels.length > 0 
            ? Math.max(...mouthPixels.map(p => p.y)) - Math.min(...mouthPixels.map(p => p.y))
            : 0;
            
        // Normalize by region size
        const openness = mouthHeight / (regionBottom - regionTop);
        
        return { 
            curvature: curvature,
            openness: openness
        };
    }
    
    /**
     * Analyze emotion based on detected face features
     * @param {Object} faceData - Detected face features
     * @returns {Object} Emotion analysis
     */
    function analyzeEmotion(faceData) {
        // Very basic emotion recognition based on mouth curvature
        // Positive curvature indicates smile, negative indicates frown
        
        const { mouthCurvature, mouthOpenness } = faceData;
        
        let emotion = emotionStates.NEUTRAL;
        let confidence = 0.5; // Default medium confidence
        let message = "Neutral expression detected";
        
        if (mouthCurvature > 3) {
            // Smiling
            emotion = emotionStates.HAPPY;
            confidence = Math.min(0.9, 0.5 + mouthCurvature / 10);
            message = "Smile detected";
        } 
        else if (mouthCurvature < -3) {
            // Frowning 
            emotion = emotionStates.SAD;
            confidence = Math.min(0.9, 0.5 + Math.abs(mouthCurvature) / 10);
            message = "Sad expression detected";
        }
        else if (mouthOpenness > 0.5) {
            // Mouth open wide - surprised
            emotion = emotionStates.SURPRISED;
            confidence = Math.min(0.9, mouthOpenness);
            message = "Surprised expression detected";
        }
        
        return {
            emotion: emotion,
            confidence: confidence,
            message: message,
            details: {
                mouthCurvature: mouthCurvature,
                mouthOpenness: mouthOpenness
            }
        };
    }
    
    /**
     * Request user to make a specific expression for liveness verification
     * @param {HTMLVideoElement} videoElement - Video element with face
     * @param {String} requestedEmotion - Emotion to request (from emotionStates)
     * @returns {Promise} Resolves with success/failure of verification
     */
    function verifyLivenessByExpression(videoElement, requestedEmotion = emotionStates.HAPPY) {
        return new Promise((resolve) => {
            // Create UI for prompting the user
            const container = videoElement.parentElement;
            if (!container) {
                resolve({ success: false, message: "Cannot create UI prompt" });
                return;
            }
            
            // Create prompt overlay
            const promptOverlay = document.createElement('div');
            promptOverlay.className = 'position-absolute top-0 start-0 end-0 bottom-0 d-flex flex-column justify-content-center align-items-center';
            promptOverlay.style.backgroundColor = 'rgba(0,0,0,0.7)';
            promptOverlay.style.zIndex = '25';
            
            // Create prompt message
            const promptMessage = document.createElement('div');
            promptMessage.className = 'text-white text-center p-3 mb-3';
            promptMessage.style.fontSize = '1.5rem';
            
            // Set message based on requested emotion
            switch(requestedEmotion) {
                case emotionStates.HAPPY:
                    promptMessage.innerHTML = 'Please <strong>smile</strong> for the camera';
                    break;
                case emotionStates.SURPRISED:
                    promptMessage.innerHTML = 'Please look <strong>surprised</strong> (open your mouth)';
                    break;
                default:
                    promptMessage.innerHTML = 'Please look at the camera';
            }
            
            // Create progress indicator
            const progressContainer = document.createElement('div');
            progressContainer.className = 'bg-dark w-75 rounded overflow-hidden';
            progressContainer.style.maxWidth = '300px';
            
            const progressBar = document.createElement('div');
            progressBar.className = 'bg-primary';
            progressBar.style.height = '10px';
            progressBar.style.width = '0%';
            progressBar.style.transition = 'width 0.3s ease-in-out';
            
            progressContainer.appendChild(progressBar);
            promptOverlay.appendChild(promptMessage);
            promptOverlay.appendChild(progressContainer);
            container.appendChild(promptOverlay);
            
            // Timeout for verification
            const verificationTimeout = 10000; // 10 seconds
            const checkInterval = 300; // Check every 300ms
            
            let startTime = Date.now();
            let bestMatch = 0;
            let lastEmotionResult = null;
            
            // Start checking for the requested emotion
            const checkEmotionInterval = setInterval(() => {
                const elapsedTime = Date.now() - startTime;
                
                // Update progress bar for time
                const timeProgress = Math.min(100, (elapsedTime / verificationTimeout) * 100);
                progressBar.style.width = timeProgress + '%';
                
                // Check current emotion
                const emotionResult = detectEmotion(videoElement);
                lastEmotionResult = emotionResult;
                
                // Check if the detected emotion matches the requested one
                if (emotionResult.emotion === requestedEmotion) {
                    bestMatch = Math.max(bestMatch, emotionResult.confidence);
                    
                    // If good confidence, finish early
                    if (emotionResult.confidence > 0.7) {
                        clearInterval(checkEmotionInterval);
                        finishVerification(true, "Expression verified successfully!");
                    }
                }
                
                // Update message based on current detection
                if (emotionResult.emotion !== emotionStates.UNKNOWN) {
                    promptMessage.innerHTML = `Please <strong>smile</strong> for the camera<br>
                        <small class="text-${emotionResult.emotion === requestedEmotion ? 'success' : 'light'}">
                            ${emotionResult.message}
                        </small>`;
                }
                
                // Timeout check
                if (elapsedTime >= verificationTimeout) {
                    clearInterval(checkEmotionInterval);
                    
                    if (bestMatch > 0.5) {
                        finishVerification(true, "Expression verified!");
                    } else {
                        finishVerification(false, "Could not verify expression. Please try again.");
                    }
                }
            }, checkInterval);
            
            function finishVerification(success, message) {
                // Update UI to show result
                promptMessage.innerHTML = message;
                progressBar.style.width = '100%';
                progressBar.className = success ? 'bg-success' : 'bg-danger';
                
                // Remove overlay after a delay
                setTimeout(() => {
                    container.removeChild(promptOverlay);
                    resolve({ 
                        success: success, 
                        message: message,
                        bestMatch: bestMatch,
                        lastDetection: lastEmotionResult
                    });
                }, 1500);
            }
        });
    }
    
    // Return public API
    return {
        detectEmotion,
        verifyLivenessByExpression,
        emotionStates
    };
})();
