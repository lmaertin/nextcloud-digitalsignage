// Configuration variables - read from body data attributes
const bodyEl = document.body;
const IS_PUBLIC = bodyEl.getAttribute('data-is-public') === 'true';
const PUBLIC_TOKEN = bodyEl.getAttribute('data-public-token') || null;
const BASE_URL = bodyEl.getAttribute('data-base-url') || window.location.origin + '/';
const API_BASE = IS_PUBLIC
  ? BASE_URL + 'apps/digitalsignage/api/public/' + PUBLIC_TOKEN
  : BASE_URL + 'apps/digitalsignage/api';

let config = null;
let configRevision = null;

function applyRuntimeConfig() {
  if (!config) {
    return;
  }

  const displayName = config.displayName || 'Digital Signage';
  const titleEl = document.getElementById('display-title');
  if (titleEl) {
    titleEl.textContent = displayName;
  }
  document.title = displayName;

  const splitRatio = Number.parseInt(config.contentSplitRatio, 10);
  if (Number.isFinite(splitRatio) && splitRatio >= 50 && splitRatio <= 85) {
    document.documentElement.style.setProperty('--content-split-ratio', `${splitRatio}%`);
  }

  if (config.textSizeCssVariables && typeof config.textSizeCssVariables === 'object') {
    Object.entries(config.textSizeCssVariables).forEach(([cssVariable, value]) => {
      if (typeof value === 'string' && value.trim() !== '') {
        document.documentElement.style.setProperty(cssVariable, value);
      }
    });
  }
}

function sortImagesByFilename(images) {
  return [...images].sort((left, right) => left.name.localeCompare(right.name, undefined, {
    numeric: true,
    sensitivity: 'base'
  }));
}

function shuffleImages(images) {
  const shuffled = [...images];

  for (let index = shuffled.length - 1; index > 0; index -= 1) {
    const swapIndex = Math.floor(Math.random() * (index + 1));
    [shuffled[index], shuffled[swapIndex]] = [shuffled[swapIndex], shuffled[index]];
  }

  return shuffled;
}

// Centralized date formatter functions
function normalizeLocale(locale) {
  const raw = (locale || '').trim();
  if (!raw) {
    return 'en-US';
  }

  const normalized = raw.replace('_', '-');
  const candidate = normalized;

  try {
    const [canonical] = Intl.getCanonicalLocales(candidate);
    return canonical || 'en-US';
  } catch (error) {
    return 'en-US';
  }
}

function getLocale() {
  const configuredLocale = config && typeof config.locale === 'string' ? config.locale.trim() : '';
  if (configuredLocale) {
    return normalizeLocale(configuredLocale);
  }

  const htmlLang = (document.documentElement.lang || '').trim();
  if (htmlLang) {
    return normalizeLocale(htmlLang);
  }

  return normalizeLocale(navigator.language || 'en-US');
}

function getDateFormatter(locale, options) {
  try {
    return new Intl.DateTimeFormat(normalizeLocale(locale || getLocale()), options);
  } catch (error) {
    return new Intl.DateTimeFormat('en-US', options);
  }
}

function getShortDateFormatter(locale) {
  return getDateFormatter(locale, {
    weekday: 'short',
    day: 'numeric',
    month: 'short'
  });
}

function getClockDateFormatter(locale) {
  return getDateFormatter(locale, {
    weekday: 'short',
    day: 'numeric',
    month: 'long'
  });
}

// Remove trailing dots from weekday abbreviations for consistent display
function removeDots(text) {
  return text.replace(/^([\p{L}]{1,5})\./u, '$1');
}

function getCalendarText(value) {
  if (!Array.isArray(value) || value.length === 0) {
    return '';
  }

  const rawText = String(value[0] ?? '');
  const parsedText = new DOMParser().parseFromString(rawText, 'text/html').body.textContent || '';
  return parsedText.replace(/\s+/g, ' ').trim();
}

async function loadConfig() {
  try {
    const response = await fetch(API_BASE + '/config');

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: Configuration API not available`);
    }

    config = await response.json();
    configRevision = config.revision ?? null;
    console.log('Configuration loaded successfully');
    applyRuntimeConfig();
  } catch (error) {
    console.error('Configuration loading error:', error);
    throw new Error(`Failed to load configuration: ${error.message}`);
  }
}

async function pollConfigChanges() {
  if (!IS_PUBLIC) {
    return;
  }

  try {
    const response = await fetch(API_BASE + '/config');
    if (!response.ok) {
      return;
    }

    const nextConfig = await response.json();
    const nextRevision = nextConfig.revision ?? null;

    if (configRevision !== null && nextRevision !== null && nextRevision !== configRevision) {
      window.location.reload();
      return;
    }

    config = nextConfig;
    configRevision = nextRevision;
    applyRuntimeConfig();
  } catch (error) {
    console.error('Config polling error:', error);
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

    const playbackMode = config.imageOrderMode === 'filename' ? 'filename' : 'shuffle';
    let playbackImages = playbackMode === 'filename' ? sortImagesByFilename(images) : shuffleImages(images);
    let currentIndex = 0;
    let currentVideoElement = null;
    let slideTimer = null;
    let prefetchedImage = null;
    let prefetchedImageUrl = null;

    function getNextImage() {
      if (playbackImages.length === 0) {
        return null;
      }

      if (currentIndex >= playbackImages.length) {
        playbackImages = playbackMode === 'filename' ? sortImagesByFilename(images) : shuffleImages(images);
        currentIndex = 0;
      }

      const nextImage = playbackImages[currentIndex];
      currentIndex += 1;

      return nextImage;
    }

    function prefetchNextImage() {
      if (playbackImages.length === 0) {
        return;
      }

      const nextItem = playbackImages[currentIndex];
      if (!nextItem || nextItem.type === 'video') {
        return;
      }

      const nextImageUrl = API_BASE + '/image?id=' + nextItem.id;
      if (prefetchedImageUrl === nextImageUrl) {
        return;
      }

      prefetchedImage = new Image();
      prefetchedImageUrl = nextImageUrl;
      prefetchedImage.src = nextImageUrl;

      console.log('Prefetching next image:', nextItem.name);
    }

    function show() {
      const item = getNextImage();
      if (!item) {
        throw new Error('No images found');
      }

      const itemUrl = API_BASE + '/image?id=' + item.id;
      const isVideo = item.type === 'video';
      console.log(`Showing ${isVideo ? 'video' : 'image'}:`, item.name);

      // Fade out
      el.style.opacity = '0';

      // Clear any existing slide timer
      if (slideTimer) {
        clearTimeout(slideTimer);
        slideTimer = null;
      }

      setTimeout(() => {
        // Change content while invisible
        const fitMode = config.imageFitMode || 'cover';
        el.className = `slideshow fit-${fitMode}`;
        el.innerHTML = '';
        el.style.backgroundImage = '';

        if (isVideo) {
          // Create video element
          const video = document.createElement('video');
          video.autoplay = true;
          video.muted = true;
          video.playsInline = true;
          video.style.width = '100%';
          video.style.height = '100%';
          video.style.objectFit = fitMode;
          video.src = itemUrl;

          // Auto-advance when video ends
          video.addEventListener('ended', () => {
            console.log('Video ended, advancing to next...');
            show();
          });

          // Error handling
          video.addEventListener('error', (e) => {
            console.error('Video load error:', e);
            // Skip to next item on error
            show();
          });

          el.appendChild(video);
          currentVideoElement = video;
        } else {
          // Show image as background
          el.style.backgroundImage = `url('${itemUrl}')`;
          currentVideoElement = null;

          // Prefetch the next image to avoid flickering
          prefetchNextImage();

          // Schedule next image
          slideTimer = setTimeout(show, config.slideInterval * 1000);
        }

        // Fade in
        el.style.opacity = '1';
      }, 300); // Wait for fade out to complete

      console.log(`${isVideo ? 'Video' : 'Image'} set with smooth transition`);
    }

    // Show first item immediately
    show();

    console.log('Slideshow started successfully (supports images and videos)');
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
  const weatherEl = document.getElementById('weather');
  if (!weatherEl) {
    return;
  }

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
      const locale = normalizeLocale(getLocale());
      const value = Number(temp);
      const hasFraction = Math.abs(value % 1) > Number.EPSILON;
      try {
        return new Intl.NumberFormat(locale, {
          minimumFractionDigits: hasFraction ? 1 : 0,
          maximumFractionDigits: 1
        }).format(value);
      } catch (error) {
        return String(value);
      }
    }

    // Get localized "Today" label from Intl instead of manual locale mapping
    function getTodayText() {
      const locale = normalizeLocale(getLocale());
      if (typeof Intl.RelativeTimeFormat === 'function') {
        try {
          return new Intl.RelativeTimeFormat(locale, {numeric: 'auto'}).format(0, 'day');
        } catch (error) {
          return 'Today';
        }
      }
      return 'Today';
    }

    const currentIcon = getWeatherIcon(cw.weathercode);
    const tempIcon = getTemperatureIcon(cw.temperature);

    weatherEl.innerHTML = `
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
          const weekdayFormatter = getDateFormatter(getLocale(), {weekday: 'short'});
          const weekday = removeDots(weekdayFormatter.format(new Date(t)));
          return `<div class="forecast-day">
            <div class="day-name">${weekday}</div>
            <div class="day-icon">${dayIcon}</div>
            <div class="day-temp">${formatTemperature(min)}°–${formatTemperature(max)}°</div>
          </div>`;
        }).join('')}
      </div>`;
  } catch (error) {
    console.error('Error loading weather:', error);
    if (weatherEl) {
      weatherEl.innerHTML = `
        <div class="weather-error">
          <span class="error-icon">⚠️</span>
          <p>Weather error: ${error.message}</p>
        </div>`;
    }
  }
}

async function loadICS() {
  const cal = document.getElementById('calendar');
  if (!cal) {
    return;
  }

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
            description: getCalendarText(obj.DESCRIPTION),
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

    const locale = getLocale();
    const fmtWithTime = getDateFormatter(locale, {
      weekday: 'short',
      day: 'numeric',
      month: 'long',
      hour: '2-digit',
      minute: '2-digit'
    });
    const fmtDateOnly = getDateFormatter(locale, {
      weekday: 'short',
      day: 'numeric',
      month: 'long'
    });
    if (upcoming.length === 0) {
      cal.innerHTML = '<p>No upcoming events</p>';
    } else {
      const list = document.createElement('ul');
      upcoming.forEach((event) => {
        const listItem = document.createElement('li');
        const eventDate = event.startDate;
        const isAllDay = event.isAllDay;
        const timeStr = removeDots(isAllDay ? fmtDateOnly.format(eventDate) : fmtWithTime.format(eventDate));
        const title = document.createElement('div');
        title.className = 'event-title';
        title.textContent = event.summary;
        listItem.appendChild(title);

        const details = document.createElement('div');
        details.className = 'event-details';
        const time = document.createElement('div');
        time.className = 'event-time';
        time.textContent = timeStr;
        details.appendChild(time);

        if (event.location) {
          const location = document.createElement('div');
          location.className = 'event-location';
          location.textContent = event.location;
          details.appendChild(location);
        }
        listItem.appendChild(details);

        if (config.showEventDescription && event.description) {
          const description = document.createElement('div');
          description.className = 'event-description';
          description.textContent = event.description;
          listItem.appendChild(description);
        }

        list.appendChild(listItem);
      });
      cal.replaceChildren(list);
    }
  } catch (error) {
    console.error('Error loading calendar:', error);
    if (cal) {
      cal.innerHTML = `<p>Error loading calendar: ${error.message}</p>`;
    }
  }
}

// Initialize and update date/time display
function initDateTime() {
  const timeEl = document.getElementById('time-display');
  const dateEl = document.getElementById('date-display');

  if (!timeEl || !dateEl) return;

  function updateDateTime() {
    const now = new Date();
    const locale = getLocale();

    // Format time based on locale
    const timeFormat = new Intl.DateTimeFormat(locale, {
      hour: '2-digit',
      minute: '2-digit'
    });

    // Format date - use centralized short date formatter
    const dateFormat = getClockDateFormatter(locale);

    timeEl.textContent = timeFormat.format(now);
    dateEl.textContent = removeDots(dateFormat.format(now));
  }

  // Update immediately
  updateDateTime();

  // Update every minute (no seconds displayed)
  setInterval(updateDateTime, 60000);

  console.log('Date/Time display initialized');
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
    setInterval(pollConfigChanges, 15000); // 15 seconds

    // Initialize date and time display
    initDateTime();

    // Initialize fullscreen button
    initFullscreenButton();

    // Auto-prompt for fullscreen if enabled
    if (config.autoFullscreenPrompt) {
      promptFullscreen();
    }

    console.log('Digital Signage initialized successfully!');
  } catch(e){
    console.error('Initialization error:', e);
    alert('Error: ' + e.message);
  }
}

// Auto-prompt for fullscreen
function promptFullscreen() {
  // Only prompt if not already in fullscreen
  const isFullscreen = document.fullscreenElement || document.webkitFullscreenElement;
  if (isFullscreen) return;

  // Get internationalized texts from config
  const title = config.i18n?.fullscreenPromptTitle || 'Activate fullscreen mode?';
  const yesText = config.i18n?.fullscreenPromptYes || 'Yes';
  const noText = config.i18n?.fullscreenPromptNo || 'No';

  // Create custom dialog (not blocking, preserves user gesture)
  const overlay = document.createElement('div');
  overlay.className = 'fullscreen-prompt-overlay';

  const dialog = document.createElement('div');
  dialog.className = 'fullscreen-prompt';
  dialog.innerHTML = `
    <h3>${title}</h3>
    <div class="fullscreen-prompt-buttons">
      <button class="btn-yes">${yesText}</button>
      <button class="btn-no">${noText}</button>
    </div>
  `;

  document.body.appendChild(overlay);
  document.body.appendChild(dialog);

  const closeDialog = () => {
    overlay.remove();
    dialog.remove();
  };

  // Yes button - direct user interaction
  dialog.querySelector('.btn-yes').addEventListener('click', async () => {
    closeDialog();
    const fullscreenLabels = getFullscreenButtonLabels();

    const elem = document.documentElement;
    try {
      if (elem.requestFullscreen) {
        await elem.requestFullscreen();
      } else if (elem.webkitRequestFullscreen) {
        await elem.webkitRequestFullscreen();
      }

      const btn = document.getElementById('fullscreen-btn');
      if (btn) {
        btn.textContent = '⤢';
        btn.classList.add('in-fullscreen');
        btn.title = fullscreenLabels.exit;
      }
    } catch (err) {
      console.error('Fullscreen error:', err);
    }
  });

  // No button
  dialog.querySelector('.btn-no').addEventListener('click', closeDialog);
  overlay.addEventListener('click', closeDialog);
}

function getFullscreenButtonLabels() {
  const body = document.body;

  return {
    enter: config.i18n?.fullscreenButtonEnter || body?.dataset?.fullscreenTitleEnter || 'Fullscreen',
    exit: config.i18n?.fullscreenButtonExit || body?.dataset?.fullscreenTitleExit || 'Exit fullscreen'
  };
}

// Fullscreen functionality
function initFullscreenButton() {
  const btn = document.getElementById('fullscreen-btn');
  if (!btn) return;
  const fullscreenLabels = getFullscreenButtonLabels();

  let hideTimeout;

  // Auto-hide button after 5 seconds
  const hideButton = () => {
    clearTimeout(hideTimeout);
    hideTimeout = setTimeout(() => {
      btn.classList.add('hidden');
    }, 5000);
  };

  // Show button on mouse movement
  document.addEventListener('mousemove', () => {
    btn.classList.remove('hidden');
    hideButton();
  });

  // Initial hide after 5 seconds
  hideButton();

  // Toggle fullscreen on button click (Chrome-kompatibel)
  btn.addEventListener('click', async () => {
    try {
      const isFullscreen = document.fullscreenElement || document.webkitFullscreenElement;

      if (!isFullscreen) {
        const elem = document.documentElement;
        if (elem.requestFullscreen) {
          await elem.requestFullscreen();
        } else if (elem.webkitRequestFullscreen) {
          await elem.webkitRequestFullscreen();
        }
        btn.textContent = '⤢';
        btn.classList.add('in-fullscreen');
        btn.title = fullscreenLabels.exit;
      } else {
        if (document.exitFullscreen) {
          await document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
          await document.webkitExitFullscreen();
        }
        btn.textContent = '⛶';
        btn.classList.remove('in-fullscreen');
        btn.title = fullscreenLabels.enter;
      }
    } catch (error) {
      console.error('Fullscreen toggle error:', error);
    }
  });

  // Handle fullscreen changes (e.g., ESC key) - Chrome-kompatibel
  const handleFullscreenChange = () => {
    const isFullscreen = document.fullscreenElement || document.webkitFullscreenElement;
    if (!isFullscreen) {
      btn.textContent = '⛶';
      btn.classList.remove('in-fullscreen');
      btn.title = fullscreenLabels.enter;
    } else {
      btn.textContent = '⤢';
      btn.classList.add('in-fullscreen');
      btn.title = fullscreenLabels.exit;
    }
  };

  document.addEventListener('fullscreenchange', handleFullscreenChange);
  document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
}

// Wait for DOM to be ready before initializing
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
