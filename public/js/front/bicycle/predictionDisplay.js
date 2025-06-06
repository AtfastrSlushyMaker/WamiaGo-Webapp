
class PredictionDisplay {
    /**
     * Update the UI with prediction data from the API

     */
    static updatePredictionUI(predictionData) {

        this.updateElementText('predictionDistance', predictionData.distance ? predictionData.distance.toFixed(2) : '--');
        this.updateElementText('predictionDuration', predictionData.estimatedDuration || '--');
        this.updateElementText('predictionCost', predictionData.estimatedCost ? parseFloat(predictionData.estimatedCost).toFixed(3) : '--');

        const batteryText = `${predictionData.batteryConsumption || '--'}% (${predictionData.rangeAfterTrip || '--'} km range remaining)`;
        this.updateElementText('predictionBattery', batteryText);


        this.updateElementText('predictionWeather', predictionData.weatherImpact || 'Weather data not available');

        const rechargingAlert = document.getElementById('rechargingAlert');
        if (predictionData.rechargingNeeded && predictionData.rechargingSuggestion) {
            this.updateElementText('rechargingSuggestion', predictionData.rechargingSuggestion);
            rechargingAlert.classList.remove('d-none');
        } else {
            rechargingAlert.classList.add('d-none');
        }

   
        this.updateElementText('routeSuggestion', predictionData.routeSuggestion || 'Route information not available');

       
        this.updateElementText('pointsOfInterest', predictionData.pointsOfInterest || 'No notable points of interest along this route');

        this.updateElementText('restStops', predictionData.restStops || 'No recommended rest stops for this short journey');

     
        this.updateElementText('trafficConditions', predictionData.trafficConditions || 'Standard traffic conditions expected');

 
        this.updateElementText('terrainDescription', predictionData.terrainDescription || 'Mostly flat urban terrain');

        this.updateElementText('environmentalImpact', predictionData.environmentalImpact ||
            `By choosing a bicycle instead of a car for this trip, you're saving approximately 1.5-2.5kg of CO2 emissions.`);

   
        this.updateElementText('healthBenefits', predictionData.healthBenefits || 'Provides moderate cardiovascular exercise');
        this.updateElementText('safetyTips', predictionData.safetyTips || 'Wear a helmet and follow traffic rules');


        if (predictionData.difficultyLevel) {
            const difficultyBadge = document.getElementById('difficultyBadge');
            difficultyBadge.textContent = predictionData.difficultyLevel;

            difficultyBadge.className = 'badge';
            switch (predictionData.difficultyLevel) {
                case 'Easy':
                    difficultyBadge.classList.add('bg-success');
                    break;
                case 'Moderate':
                    difficultyBadge.classList.add('bg-warning');
                    difficultyBadge.style.color = '#000';
                    break;
                case 'Challenging':
                    difficultyBadge.classList.add('bg-danger');
                    break;
                default:
                    difficultyBadge.classList.add('bg-info');
            }
        } else {
            this.updateElementText('difficultyBadge', 'Moderate');
        }
    }

    /**
     * Helper to safely update text content of an element
     */
    static updateElementText(elementId, text) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = text;
        } else {
            console.warn(`Element with ID ${elementId} not found`);
        }
    }
}

// Make this available globally
window.PredictionDisplay = PredictionDisplay;