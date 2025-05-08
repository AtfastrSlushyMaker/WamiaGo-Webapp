/**
 * Face Recognition Quality Module
 * Provides face image quality assessment for better recognition results
 */
window.FaceQualityCheck = (function() {
    'use strict';

    /**
     * Assess the quality of a face image before sending it for verification
     * @param {HTMLCanvasElement} canvas - The canvas with the face image
     * @returns {Object} Quality assessment results
     */
    function assessFaceImageQuality(canvas) {
        // Create a dedicated canvas for analysis
        const analysisCanvas = document.createElement('canvas');
        const ctx = analysisCanvas.getContext('2d', { willReadFrequently: true });
        
        // Use a smaller size for performance
        analysisCanvas.width = 100;
        analysisCanvas.height = 100;
        
        // Draw the original image to the analysis canvas
        ctx.drawImage(canvas, 0, 0, analysisCanvas.width, analysisCanvas.height);
        
        // Get image data for analysis
        const imageData = ctx.getImageData(0, 0, analysisCanvas.width, analysisCanvas.height);
        const data = imageData.data;
        
        // Calculate quality metrics
        const brightnessResult = analyzeBrightness(data);
        const blurResult = estimateBlurriness(data, analysisCanvas.width, analysisCanvas.height);
        const facePositionResult = checkFacePosition(canvas);
        const occlusionResult = detectFaceOcclusions(canvas);
        
        // Combine results
        const quality = {
            brightness: brightnessResult.value,
            blurriness: blurResult.value,
            facePosition: facePositionResult.value,
            hasGlasses: occlusionResult.hasGlasses,
            isPoorQuality: brightnessResult.isPoor || blurResult.isPoor || facePositionResult.isPoor || occlusionResult.hasGlasses,
            qualityScore: calculateOverallScore(brightnessResult, blurResult, facePositionResult),
            issues: []
        };
        
        // Collect any quality issues
        if (brightnessResult.isPoor) {
            quality.issues.push(brightnessResult.message);
        }
        if (blurResult.isPoor) {
            quality.issues.push(blurResult.message);
        }
        if (facePositionResult.isPoor) {
            quality.issues.push(facePositionResult.message);
        }
        if (occlusionResult.hasGlasses) {
            quality.issues.push("Glasses detected, may affect recognition.");
        }
        
        return quality;
    }
    
    /**
     * Analyze image brightness
     * @param {Uint8ClampedArray} data - Image data array
     * @returns {Object} Brightness assessment
     */
    function analyzeBrightness(data) {
        let totalBrightness = 0;
        const pixelCount = data.length / 4;
        
        for (let i = 0; i < data.length; i += 4) {
            // Convert RGB to brightness using standard luminance formula
            const brightness = 0.299 * data[i] + 0.587 * data[i+1] + 0.114 * data[i+2];
            totalBrightness += brightness;
        }
        
        const averageBrightness = totalBrightness / pixelCount;
        
        // Determine brightness quality
        let isPoor = false;
        let message = "";
        
        if (averageBrightness < 40) {
            isPoor = true;
            message = "Image is too dark. Try improving lighting.";
        } else if (averageBrightness > 220) {
            isPoor = true;
            message = "Image is too bright. Reduce direct lighting.";
        }
        
        return {
            value: averageBrightness,
            isPoor: isPoor,
            message: message
        };
    }
    
    /**
     * Estimate image blurriness
     * @param {Uint8ClampedArray} data - Image data array
     * @param {number} width - Image width
     * @param {number} height - Image height
     * @returns {Object} Blurriness assessment
     */
    function estimateBlurriness(data, width, height) {
        // Create grayscale version for edge detection
        const grayscale = new Uint8Array(width * height);
        for (let i = 0, j = 0; i < data.length; i += 4, j++) {
            grayscale[j] = Math.round(0.299 * data[i] + 0.587 * data[i+1] + 0.114 * data[i+2]);
        }
        
        // Calculate a simple sharpness measure using edge strength
        let edgeStrength = 0;
        for (let y = 1; y < height - 1; y++) {
            for (let x = 1; x < width - 1; x++) {
                const idx = y * width + x;
                // Horizontal and vertical gradients (simplified Sobel operator)
                const gx = Math.abs(grayscale[idx + 1] - grayscale[idx - 1]);
                const gy = Math.abs(grayscale[idx + width] - grayscale[idx - width]);
                // Combined gradient magnitude
                edgeStrength += Math.sqrt(gx * gx + gy * gy);
            }
        }
        
        // Normalize by image size
        const normalizedEdgeStrength = edgeStrength / ((width - 2) * (height - 2));
        
        // Lower values indicate blurrier images
        const blurThreshold = 15;
        const isPoor = normalizedEdgeStrength < blurThreshold;
        
        return {
            value: normalizedEdgeStrength,
            isPoor: isPoor,
            message: isPoor ? "Image appears to be blurry. Hold the camera steady." : ""
        };
    }
    
    /**
     * Check face position in the image (basic estimation)
     * @param {HTMLCanvasElement} canvas - The canvas with the face image
     * @returns {Object} Face position assessment
     */
    function checkFacePosition(canvas) {
        // For now, we'll just assume face position is good without actual face detection
        // In a production system, this would use a face detection algorithm to check
        // if the face is centered and properly sized in the frame
        return {
            value: 1.0, // 1.0 = good position
            isPoor: false,
            message: ""
        };
    }
    
    /**
     * Check for potential face occlusions (glasses, masks, etc.)
     * This is a very simplified version for demonstration
     * @param {HTMLCanvasElement} canvas - Canvas with face image
     * @returns {Object} Occlusion assessment
     */
    function detectFaceOcclusions(canvas) {
        // In a production system, this would use a trained ML model 
        // Here we use a very simplified approximation
        
        const ctx = canvas.getContext('2d');
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imageData.data;
        
        // For glasses detection, we look for horizontal edges in the eye region
        // This is a very approximate approach
        
        // Assume eye region is in the middle third of the image height
        // and centered horizontally
        const eyeRegionTop = Math.floor(canvas.height * 0.3);
        const eyeRegionBottom = Math.floor(canvas.height * 0.5);
        const eyeRegionLeft = Math.floor(canvas.width * 0.2);
        const eyeRegionRight = Math.floor(canvas.width * 0.8);
        
        // Count edge pixels in the eye region
        let edgePixelCount = 0;
        
        for (let y = eyeRegionTop; y < eyeRegionBottom; y++) {
            for (let x = eyeRegionLeft; x < eyeRegionRight; x++) {
                const idx = (y * canvas.width + x) * 4;
                
                // Skip if we're at the edge of the canvas
                if (x === 0 || x === canvas.width - 1) continue;
                
                // Calculate horizontal gradient (simple edge detection)
                const leftIdx = (y * canvas.width + (x-1)) * 4;
                const rightIdx = (y * canvas.width + (x+1)) * 4;
                
                const gx = Math.abs(data[rightIdx] - data[leftIdx]) + 
                           Math.abs(data[rightIdx+1] - data[leftIdx+1]) + 
                           Math.abs(data[rightIdx+2] - data[leftIdx+2]);
                
                // If there's a strong edge, count it
                if (gx > 100) {
                    edgePixelCount++;
                }
            }
        }
        
        // Normalize by area
        const eyeRegionArea = (eyeRegionBottom - eyeRegionTop) * (eyeRegionRight - eyeRegionLeft);
        const edgeDensity = edgePixelCount / eyeRegionArea;
        
        // Higher edge density might indicate glasses
        const glassesThreshold = 0.05;
        const hasGlasses = edgeDensity > glassesThreshold;
        
        return {
            hasGlasses: hasGlasses,
            confidence: Math.min(1.0, edgeDensity / (glassesThreshold * 2))
        };
    }
    
    /**
     * Calculate overall quality score based on all metrics
     * @param {Object} brightness - Brightness assessment
     * @param {Object} blurriness - Blurriness assessment
     * @param {Object} facePosition - Face position assessment
     * @returns {number} Overall quality score from 0 to 100
     */
    function calculateOverallScore(brightness, blurriness, facePosition) {
        // Normalize brightness (0-255) to 0-1 range, with peak at ~120
        let brightnessScore = 0;
        if (brightness.value < 120) {
            brightnessScore = brightness.value / 120;
        } else {
            brightnessScore = 1 - ((brightness.value - 120) / 135);
        }
        brightnessScore = Math.max(0, Math.min(1, brightnessScore));
        
        // Edge strength (blurriness inverse) - higher is better
        const sharpnessScore = Math.min(1, blurriness.value / 30);
        
        // Face position score
        const positionScore = facePosition.value;
        
        // Weighted average for final score (0-100)
        const score = Math.round(
            (brightnessScore * 0.4 + sharpnessScore * 0.4 + positionScore * 0.2) * 100
        );
        
        return score;
    }
    
    /**
     * Enhance a face image before sending it for verification
     * @param {HTMLCanvasElement} canvas - The canvas with the face image
     * @returns {string} Enhanced image data URL
     */
    function enhanceFaceImage(canvas) {
        const ctx = canvas.getContext('2d');
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imageData.data;
        
        // Simple contrast enhancement
        const brightness = analyzeBrightness(data).value;
        
        // Apply different enhancements based on image characteristics
        if (brightness < 80) {
            // Brighten dark images
            for (let i = 0; i < data.length; i += 4) {
                // Increase brightness while preserving details
                data[i] = Math.min(255, data[i] * 1.2);
                data[i+1] = Math.min(255, data[i+1] * 1.2);
                data[i+2] = Math.min(255, data[i+2] * 1.2);
            }
        } else if (brightness > 200) {
            // Tone down very bright images
            for (let i = 0; i < data.length; i += 4) {
                data[i] = data[i] * 0.9;
                data[i+1] = data[i+1] * 0.9;
                data[i+2] = data[i+2] * 0.9;
            }
        }
        
        // Apply the modified pixels back to canvas
        ctx.putImageData(imageData, 0, 0);
        
        // Return enhanced image as data URL
        return canvas.toDataURL('image/jpeg', 0.92);
    }
      // Return public API
    return {
        assessFaceImageQuality,
        enhanceFaceImage,
        detectFaceOcclusions
    };
})();
