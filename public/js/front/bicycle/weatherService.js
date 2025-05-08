/**
 * Weather Service for Bicycle Rental
 * Handles weather data fetching and interpretation for bicycle rentals
 */

class WeatherService {
    constructor() {
        this.baseUrl = 'https://api.openweathermap.org/data/2.5/weather';
        this.apiKey = null;
        this.weatherIcons = {
            // Clear weather
            '01d': 'fa-sun',
            '01n': 'fa-moon',
            // Few clouds
            '02d': 'fa-cloud-sun',
            '02n': 'fa-cloud-moon',
            // Scattered clouds
            '03d': 'fa-cloud',
            '03n': 'fa-cloud',
            // Broken clouds
            '04d': 'fa-cloud',
            '04n': 'fa-cloud',
            // Shower rain
            '09d': 'fa-cloud-showers-heavy',
            '09n': 'fa-cloud-showers-heavy',
            // Rain
            '10d': 'fa-cloud-rain',
            '10n': 'fa-cloud-rain',
            // Thunderstorm
            '11d': 'fa-bolt',
            '11n': 'fa-bolt',
            // Snow
            '13d': 'fa-snowflake',
            '13n': 'fa-snowflake',
            // Mist
            '50d': 'fa-smog',
            '50n': 'fa-smog'
        };

        this.weatherFactors = {
            // Clear
            800: 1.0,
            // Few clouds
            801: 1.0,
            // Scattered clouds
            802: 1.0,
            // Broken clouds
            803: 1.0,
            804: 1.0,
            // Rain and drizzle (various codes)
            300: 1.1, 301: 1.1, 302: 1.15, 310: 1.1, 311: 1.15, 312: 1.2,
            313: 1.2, 314: 1.2, 321: 1.1, 500: 1.1, 501: 1.15, 502: 1.2,
            503: 1.25, 504: 1.3, 511: 1.25, 520: 1.15, 521: 1.2, 522: 1.25,
            531: 1.2,
            // Thunderstorm
            200: 1.3, 201: 1.3, 202: 1.4, 210: 1.2, 211: 1.3, 212: 1.4,
            221: 1.3, 230: 1.2, 231: 1.2, 232: 1.3,
            // Snow
            600: 1.25, 601: 1.3, 602: 1.4, 611: 1.25, 612: 1.25, 613: 1.3,
            615: 1.25, 616: 1.3, 620: 1.25, 621: 1.3, 622: 1.4,
            // Atmosphere (mist, fog, etc)
            701: 1.1, 711: 1.2, 721: 1.1, 731: 1.2, 741: 1.1, 751: 1.2,
            761: 1.2, 762: 1.3, 771: 1.3, 781: 1.5
        };
    }

    /**
     * Initialize the weather service by fetching the API key from the backend
     * @returns {Promise} - Promise that resolves when API key is loaded
     */
    async init() {
        try {
            const response = await fetch('/api/weather/key');
            if (!response.ok) {
                throw new Error('Failed to fetch API key');
            }
            const data = await response.json();
            this.apiKey = data.apiKey;
            return true;
        } catch (error) {
            console.error('Failed to initialize weather service:', error);
            return false;
        }
    }

    /**
     * Get weather data for a specific location
     * @param {number} lat - Latitude
     * @param {number} lon - Longitude
     * @returns {Promise} - Weather data promise
     */
    async getWeatherData(lat, lon) {
        // Ensure API key is loaded
        if (!this.apiKey) {
            await this.init();
        }

        try {
            const url = `${this.baseUrl}?lat=${lat}&lon=${lon}&units=metric&appid=${this.apiKey}`;
            const response = await fetch(url);

            if (!response.ok) {
                throw new Error('Failed to fetch weather data');
            }

            const data = await response.json();
            return this.processWeatherData(data);
        } catch (error) {
            console.error('Weather service error:', error);
            // Return default weather data in case of error
            return {
                temperature: 25,
                description: 'Weather data unavailable',
                icon: 'fa-sun',
                weatherFactor: 1.0
            };
        }
    }

    /**
     * Process the raw weather data into useful format
     * @param {object} data - Raw weather data from API
     * @returns {object} - Processed weather data
     */
    processWeatherData(data) {
        const weatherId = data.weather[0].id;
        const iconCode = data.weather[0].icon;

        return {
            temperature: Math.round(data.main.temp),
            description: data.weather[0].description,
            icon: this.getWeatherIcon(iconCode),
            windSpeed: data.wind.speed,
            weatherFactor: this.getWeatherFactor(weatherId, data)
        };
    }

    /**
     * Get the appropriate Font Awesome icon class for the weather
     * @param {string} iconCode - OpenWeather icon code
     * @returns {string} - Font Awesome icon class
     */
    getWeatherIcon(iconCode) {
        return this.weatherIcons[iconCode] || 'fa-cloud';
    }

    /**
     * Calculate weather factor for pricing
     * @param {number} weatherId - OpenWeather ID
     * @param {object} weatherData - Full weather data
     * @returns {number} - Pricing factor
     */
    getWeatherFactor(weatherId, weatherData) {
        // Get base factor from weather condition
        let factor = this.weatherFactors[weatherId] || 1.0;

        // Adjust for extreme temperatures
        const temp = weatherData.main.temp;
        if (temp > 35) factor += 0.1;
        if (temp < 5) factor += 0.15;
        if (temp < 0) factor += 0.1;

        // Adjust for wind speed
        if (weatherData.wind.speed > 10) factor += 0.1;
        if (weatherData.wind.speed > 20) factor += 0.15;

        return Math.round(factor * 100) / 100; // Round to 2 decimal places
    }

    /**
     * Get weather description based on factor
     * @param {number} factor - Weather factor
     * @returns {string} - Description of conditions
     */
    getWeatherDescription(factor) {
        if (factor <= 1.0) return 'Good (no surcharge)';
        if (factor <= 1.1) return 'Mild (10% surcharge)';
        if (factor <= 1.2) return 'Moderate (20% surcharge)';
        if (factor <= 1.3) return 'Challenging (30% surcharge)';
        return 'Difficult (40%+ surcharge)';
    }
}

// Export the weather service for use in other files
window.WeatherService = WeatherService;