// Configuration variables - read from body data attributes
const bodyEl = document.body;
const IS_PUBLIC = bodyEl.getAttribute('data-is-public') === 'true';
const PUBLIC_TOKEN = bodyEl.getAttribute('data-public-token') || null;
const BASE_URL = bodyEl.getAttribute('data-base-url') || window.location.origin + '/';
const API_BASE = IS_PUBLIC 
  ? BASE_URL + 'apps/digitalsignage/api/public/' + PUBLIC_TOKEN
  : BASE_URL + 'apps/digitalsignage/api';

let config = null;

async function loadConfig() {
  try {
    const response = await fetch(API_BASE + '/config');
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: Configuration API not available`);
    }
    
    config = await response.json();
    console.log('Configuration loaded successfully');

    // Update header and browser tab title with fallback
    const displayName = config.displayName || 'Digital Signage';
    const titleEl = document.getElementById('display-title');
    if (titleEl) {
      titleEl.textContent = displayName;
    }
    document.title = displayName;
  } catch (error) {
    console.error('Configuration loading error:', error);
    throw new Error(`Failed to load configuration: ${error.message}`);
  }
}

async function initSlideshow() {
  const el = document.getElementById('slideshow');
  
  try {
    console.log('Initializing slideshow...');
    
    const res = await fetch(API_BASE + '/images');
    const data = await res.json();
    
    if (data.error) {
      throw new Error(data.error);
    }
    
    const images = data.images;
    console.log('Images found:', images.length);
    
    if (!images || images.length === 0) {
      throw new Error('No images found');
    }
    
    function show() {
      const img = images[Math.floor(Math.random() * images.length)];
      const imageUrl = API_BASE + '/image?id=' + img.id;
      console.log('Showing image:', img.name);
      
      // Fade out
      el.style.opacity = '0';
      
      setTimeout(() => {
        // Change image while invisible
        el.className = 'slideshow';
        el.innerHTML = '';
        el.style.backgroundImage = `url('${imageUrl}')`;
        
        // Fade in
        el.style.opacity = '1';
      }, 300); // Wait for fade out to complete
      
      console.log('Image set with smooth transition');
    }
    
    // Show first image immediately
    show();
    
    // Then switch every X seconds
    setInterval(show, config.slideInterval * 1000);
    
    console.log('Slideshow started successfully');
  } catch (error) {
    console.error('Error loading slideshow:', error);
    el.className = 'slideshow';
    el.innerHTML = `<div class="error-message">
      <div>
        <h3>Slideshow Error</h3>
        <p>${error.message}</p>
        <p style="font-size: 0.9rem; margin-top: 1rem;">Check console for details</p>
      </div>
    </div>`;
  }
}

async function loadWeather() {
  try {
    const { latitude, longitude } = config.weather;
    const res = await fetch(
      `https://api.open-meteo.com/v1/forecast?latitude=${latitude}&longitude=${longitude}` +
      `&daily=temperature_2m_max,temperature_2m_min,weather_code&current_weather=true&timezone=Europe/Berlin`
    );
    const d = await res.json();
    const cw = d.current_weather;
    const daily = d.daily;
    
    // Weather code to icon mapping
    function getWeatherIcon(weatherCode) {
      if (weatherCode <= 1) return '☀️'; // Sunny
      if (weatherCode <= 3) return '⛅'; // Partly cloudy
      if (weatherCode <= 48) return '☁️'; // Cloudy
      if (weatherCode <= 67) return '🌧️'; // Rain
      if (weatherCode <= 77) return '🌨️'; // Snow
      if (weatherCode <= 82) return '🌦️'; // Showers
      if (weatherCode <= 99) return '⛈️'; // Thunderstorm
      return '🌤️'; // Default
    }
    
    // Temperature to icon mapping
    function getTemperatureIcon(temp) {
      if (temp >= 25) return '🔥';
      if (temp >= 20) return '🌡️';
      if (temp >= 15) return '😊';
      if (temp >= 10) return '🧥';
      if (temp >= 5) return '🧣';
      if (temp >= 0) return '❄️';
      return '🥶';
    }
    
    // Temperature formatting based on locale
    function formatTemperature(temp) {
      // Get locale from config, fallback to browser locale
      const locale = config.locale || navigator.language || 'en-US';
      
      // For German locales use comma, for others use period
      if (locale.startsWith('de')) {
        return temp.toString().replace('.', ',');
      } else {
        return temp.toString(); // Keep period for English and other locales
      }
    }
    
    // Get localized "Today" label
    function getTodayText() {
      const locale = config.locale || navigator.language || 'en-US';
      
      if (locale.startsWith('de')) {
        return 'Heute';
      } else if (locale.startsWith('en')) {
        return 'Today';
      } else if (locale.startsWith('fr')) {
        return "Aujourd'hui";
      } else if (locale.startsWith('es')) {
        return 'Hoy';
      } else if (locale.startsWith('it')) {
        return 'Oggi';
      } else {
        return 'Today'; // Default to English
      }
    }
    
    const currentIcon = getWeatherIcon(cw.weathercode);
    const tempIcon = getTemperatureIcon(cw.temperature);
    
    const we = document.getElementById('weather');
    we.innerHTML = `
      <div class="forecast-grid">
        <div class="forecast-day current-day">
          <div class="day-name">${getTodayText()}</div>
          <div class="day-icon">${currentIcon}</div>
          <div class="day-temp">${formatTemperature(daily.temperature_2m_min[0])}°–${formatTemperature(daily.temperature_2m_max[0])}°</div>
        </div>
        ${daily.time.slice(1,4).map((t,i) => {
          const min = daily.temperature_2m_min[i+1];
          const max = daily.temperature_2m_max[i+1];
          const weatherCode = daily.weather_code ? daily.weather_code[i+1] : 1;
          const dayIcon = getWeatherIcon(weatherCode);
          const weekday = new Intl.DateTimeFormat(config.locale || 'en-US',{weekday:'short'}).format(new Date(t));
          return `<div class="forecast-day">
            <div class="day-name">${weekday}</div>
            <div class="day-icon">${dayIcon}</div>
            <div class="day-temp">${formatTemperature(min)}°–${formatTemperature(max)}°</div>
          </div>`;
        }).join('')}
      </div>`;
  } catch (error) {
    console.error('Error loading weather:', error);
    document.getElementById('weather').innerHTML = `
      <div class="weather-error">
        <span class="error-icon">⚠️</span>
        <p>Weather error: ${error.message}</p>
      </div>`;
  }
}

async function loadICS() {
  try {
    console.log('Loading calendar from Nextcloud...');
    
    const res = await fetch(API_BASE + '/calendar');
    const data = await res.json();
    
    if (data.error) {
      throw new Error(data.error);
    }
    
    const calendarData = data.calendar;
    console.log('Calendar data received:', calendarData.length, 'events');
    
    // Parse calendar events from JSON format
    const events = [];
    for (const eventData of calendarData) {
      try {
        // eventData is already parsed JSON from the API
        // Extract event properties from the objects array
        if (eventData.objects && eventData.objects.length > 0) {
          const obj = eventData.objects[0];
          
          // Create a simple event object
          const event = {
            summary: obj.SUMMARY ? obj.SUMMARY[0] : 'Untitled Event',
            startDate: obj.DTSTART ? new Date(obj.DTSTART[0].date) : new Date(),
            endDate: obj.DTEND ? new Date(obj.DTEND[0].date) : new Date(),
            location: obj.LOCATION ? obj.LOCATION[0] : null,
            isAllDay: obj.DTSTART && obj.DTSTART[1] && obj.DTSTART[1].VALUE === 'DATE'
          };
          
          events.push(event);
        }
      } catch (e) {
        console.warn('Failed to parse event:', e, eventData);
      }
    }
    
    console.log('Total events found:', events.length);
    
    const now = new Date();
    console.log('Current date:', now);
    
    const upcoming = events.filter(e => {
      const eventDate = e.startDate;
      const eventTitle = e.summary.toLowerCase();
      
      // Check if event should be excluded (exact match)
      const shouldExclude = config.calendarExclude && config.calendarExclude.some(excludeText => 
        eventTitle === excludeText.toLowerCase()
      );
      
      const isFuture = eventDate >= now;
      console.log('Event:', e.summary, 'Date:', eventDate, 'Is future:', isFuture, 'Excluded:', shouldExclude);
      
      return isFuture && !shouldExclude;
    }).sort((a,b) => a.startDate - b.startDate)
      .slice(0,10);
    
    console.log('Upcoming events:', upcoming.length);
    
    const fmtWithTime = new Intl.DateTimeFormat(config.locale || 'de-DE', {
      weekday:'short', day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit'
    });
    const fmtDateOnly = new Intl.DateTimeFormat(config.locale || 'de-DE', {
      weekday:'short', day:'2-digit', month:'2-digit'
    });
    const cal = document.getElementById('calendar');
    
    if (upcoming.length === 0) {
      cal.innerHTML = '<p>No upcoming events</p>';
    } else {
      cal.innerHTML = '<ul>' + upcoming.map(e => {
        const eventDate = e.startDate;
        const isAllDay = e.isAllDay;
        const timeStr = isAllDay ? fmtDateOnly.format(eventDate) : fmtWithTime.format(eventDate);
        const title = e.summary;
        const location = e.location || null;
        
        return `<li>
          <div class="event-title">${title}</div>
          <div class="event-details">
            <div class="event-time">${timeStr}</div>
            ${location ? `<div class="event-location">${location}</div>` : ''}
          </div>
        </li>`;
      }).join('') + '</ul>';
    }
  } catch (error) {
    console.error('Error loading calendar:', error);
    const cal = document.getElementById('calendar');
    cal.innerHTML = `<p>Error loading calendar: ${error.message}</p>`;
  }
}

async function init() {
  try {
    console.log('Initializing Digital Signage...');
    await loadConfig();
    console.log('Config loaded:', config);
    
    console.log('Starting slideshow...');
    await initSlideshow();
    
    console.log('Loading weather...');
    await loadWeather();
    
    console.log('Loading calendar...');
    await loadICS();
    
    // Set up intervals
    setInterval(loadWeather, 3600000); // 1 hour
    setInterval(loadICS, 600000); // 10 minutes
    
    console.log('Digital Signage initialized successfully!');
  } catch(e){ 
    console.error('Initialization error:', e); 
    alert('Error: ' + e.message); 
  }
}

// Wait for DOM to be ready before initializing
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
