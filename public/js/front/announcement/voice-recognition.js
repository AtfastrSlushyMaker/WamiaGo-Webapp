class VoiceRecognition {
    constructor() {
        this.recognizer = null;
        this.isRecording = false;
        this.startTime = null;
        this.timerInterval = null;
        this.accumulatedText = '';
        this.sessionId = this.generateSessionId();
    }

    init() {
        this.recordButton = document.getElementById('recordButton');
        this.timer = document.getElementById('timer');
        this.statusIndicator = document.getElementById('statusIndicator');
        this.statusText = document.getElementById('statusText');
        this.contentField = document.querySelector('textarea[name*="[content]"]');
        
        this.recordButton.addEventListener('click', () => this.toggleRecording());
    }

    async toggleRecording() {
        if (this.isRecording) {
            await this.stopRecording();
        } else {
            await this.startRecording();
        }
    }

    async startRecording() {
        try {
            // Ask for confirmation if there's existing text
            if (this.contentField.value && !confirm('Start new recording? Current text will be cleared.')) {
                return;
            }

            const config = await this.getSpeechConfig();
            const audioConfig = SpeechSDK.AudioConfig.fromDefaultMicrophoneInput();
            this.recognizer = new SpeechSDK.SpeechRecognizer(config, audioConfig);

            this.setupRecognizerCallbacks();
            await this.recognizer.startContinuousRecognitionAsync();

            // Reset fields for new recording
            this.accumulatedText = '';
            this.contentField.value = '';
            
            this.isRecording = true;
            this.startTimer();
            this.updateUI(true);
            this.sessionId = this.generateSessionId();
        } catch (error) {
            console.error('Recognition error:', error);
            this.showError('Failed to start recording: ' + error.message);
            await this.stopRecording();
        }
    }

    async stopRecording() {
        if (this.recognizer) {
            try {
                await this.recognizer.stopContinuousRecognitionAsync();
                this.recognizer.close();
            } catch (error) {
                console.error('Error stopping recognition:', error);
            } finally {
                this.recognizer = null;
            }
        }

        this.isRecording = false;
        this.stopTimer();
        this.updateUI(false);
        
        // Save the final accumulated text
        if (this.accumulatedText) {
            this.contentField.value = this.accumulatedText;
        }
    }

    setupRecognizerCallbacks() {
        this.recognizer.recognizing = (s, e) => {
            if (e.result.text) {
                // Update field with accumulated text + new partial result
                this.contentField.value = this.accumulatedText + ' ' + e.result.text;
            }
        };

        this.recognizer.recognized = (s, e) => {
            if (e.result.reason === SpeechSDK.ResultReason.RecognizedSpeech && e.result.text) {
                // Add recognized text to accumulated text
                this.accumulatedText += (this.accumulatedText ? ' ' : '') + e.result.text;
                this.contentField.value = this.accumulatedText;
            }
        };
    }

    async getSpeechConfig() {
        const response = await fetch('/api/speech/config');
        if (!response.ok) throw new Error('Failed to get speech config');
        
        const { authToken, region, language } = await response.json();
        const speechConfig = SpeechSDK.SpeechConfig.fromAuthorizationToken(authToken, region);
        speechConfig.speechRecognitionLanguage = language;
        
        return speechConfig;
    }

    startTimer() {
        this.startTime = Date.now();
        this.timerInterval = setInterval(() => {
            const elapsed = Date.now() - this.startTime;
            const minutes = Math.floor(elapsed / 60000);
            const seconds = Math.floor((elapsed % 60000) / 1000);
            this.timer.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }, 1000);
    }

    stopTimer() {
        clearInterval(this.timerInterval);
        this.timer.textContent = '00:00';
    }

    updateUI(isRecording) {
        this.recordButton.classList.toggle('recording', isRecording);
        this.statusIndicator.classList.toggle('active', isRecording);
        this.recordButton.innerHTML = `<i class="fas fa-${isRecording ? 'stop' : 'microphone'}"></i>`;
        this.statusText.textContent = isRecording ? 'Recording...' : 'Click to start recording';
    }

    showError(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger mt-3';
        alert.innerHTML = `
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <strong>Error:</strong> ${message}
        `;
        
        this.recordButton.parentElement.appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    }

    generateSessionId() {
        return Date.now().toString(36) + Math.random().toString(36).substring(2);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('recordButton')) {
        const voiceRecognition = new VoiceRecognition();
        voiceRecognition.init();
    }
});