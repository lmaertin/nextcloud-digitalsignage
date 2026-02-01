const translate = (text, params = []) => {
  if (typeof OC !== 'undefined' && OC.L10N && typeof OC.L10N.translate === 'function') {
    return OC.L10N.translate('digitalsignage', text, params);
  }
  return Array.isArray(params) && params.length > 0 ? text.replace('%s', params[0]) : text;
};

// Helper function to escape HTML
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Get URLs from data attributes
const API_URLS = {
  list: document.querySelector('[data-list-url]')?.getAttribute('data-list-url'),
  create: document.querySelector('[data-create-url]')?.getAttribute('data-create-url'),
  delete: document.querySelector('[data-delete-url]')?.getAttribute('data-delete-url')
};

const CSRF_TOKEN = document.querySelector('[data-csrf-token]')?.getAttribute('data-csrf-token');

async function loadTokens() {
  try {
    console.log('Loading tokens...');
    console.log('API URL:', API_URLS.list);

    const response = await fetch(API_URLS.list, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'requesttoken': CSRF_TOKEN
      },
      credentials: 'same-origin'
    });

    console.log('Response status:', response.status);

    if (!response.ok) {
      const text = await response.text();
      console.error('Response error:', text);
      throw new Error('HTTP ' + response.status + ': ' + text);
    }

    const tokens = await response.json();
    console.log('Tokens received:', tokens);

    const container = document.getElementById('tokens-container');

    if (!tokens || tokens.length === 0) {
      container.innerHTML = `<p>${translate('No tokens yet')}</p>`;
      return;
    }

    container.innerHTML = tokens.map(token => `
      <div class="token-item">
        <div class="token-info">
          <div class="token-name">${escapeHtml(token.name)}</div>
          <div class="token-url">${escapeHtml(token.url)}</div>
        </div>
        <div class="token-actions">
          <button class="primary" data-copy-url="${escapeHtml(token.url)}" title="${translate('Copy URL')}">${translate('Copy')}</button>
          <button class="error" data-token-id="${token.id}" data-token-name="${escapeHtml(token.name)}" title="${translate('Delete token')}">${translate('Delete')}</button>
        </div>
      </div>
    `).join('');

    // Add event listeners to copy buttons
    container.querySelectorAll('.primary[data-copy-url]').forEach(btn => {
      btn.addEventListener('click', function() {
        const url = this.getAttribute('data-copy-url');
        navigator.clipboard.writeText(url).then(() => {
          this.textContent = translate('Copied!');
          setTimeout(() => { this.textContent = translate('Copy'); }, 2000);
        });
      });
    });

    // Add event listeners to delete buttons
    container.querySelectorAll('.error').forEach(btn => {
      btn.addEventListener('click', function() {
        deleteToken(this.getAttribute('data-token-id'), this.getAttribute('data-token-name'));
      });
    });
  } catch (error) {
    console.error('Error loading tokens:', error);
    document.getElementById('tokens-container').innerHTML = `<p>${translate('Error loading tokens')}: ${escapeHtml(error.message)}</p>`;
  }
}

async function createToken() {
  const name = document.getElementById('token-name').value;
  if (!name) {
    alert(translate('Please enter a name for the token'));
    return;
  }

  try {
    console.log('Creating token:', name);

    const response = await fetch(API_URLS.create, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'requesttoken': CSRF_TOKEN
      },
      credentials: 'same-origin',
      body: JSON.stringify({ name: name })
    });

    console.log('Create response status:', response.status);
    const result = await response.json();
    console.log('Create result:', result);

    if (result.error) {
      alert(`${translate('Error creating token')}: ${result.error}`);
    } else {
      alert(translate('Token created successfully'));
      document.getElementById('token-name').value = '';
      loadTokens();
    }
  } catch (error) {
    console.error('Error creating token:', error);
    alert(`${translate('Error creating token')}: ${error.message}`);
  }
}

async function deleteToken(id) {
  const msg = translate('Are you sure you want to delete this token?');
  if (!confirm(msg)) {
    return;
  }

  try {
    console.log('Deleting token:', id);
    const url = API_URLS.delete.replace('TOKEN_ID', id);

    await fetch(url, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'requesttoken': CSRF_TOKEN
      },
      credentials: 'same-origin'
    });

    loadTokens();
  } catch (error) {
    console.error('Error deleting token:', error);
    alert(`${translate('Error deleting token')}: ${error.message}`);
  }
}

// Setup event listeners when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
  // Create token button
  const createBtn = document.getElementById('create-token-btn');
  if (createBtn) {
    createBtn.addEventListener('click', createToken);
  }

  // Save settings button
  const saveSettingsBtn = document.getElementById('save-settings-btn');
  if (saveSettingsBtn) {
    saveSettingsBtn.addEventListener('click', saveSettings);
  }

  // Reset colors button
  const resetColorsBtn = document.getElementById('reset-colors-btn');
  if (resetColorsBtn) {
    resetColorsBtn.addEventListener('click', resetColorsToDefaults);
  }

  // Load tokens
  loadTokens();

  // Load calendars and folders for dropdowns
  loadCalendars();
  loadFolders();

  // Load event titles for autocomplete when calendars change
  const calendarSelect = document.getElementById('calendar_names');
  if (calendarSelect) {
    calendarSelect.addEventListener('change', loadEventTitles);
    // Load initially after calendars are loaded
    setTimeout(loadEventTitles, 500);
  }

  // Weather link preview
  updateWeatherLink();
  const latInput = document.getElementById('weather_latitude');
  const lonInput = document.getElementById('weather_longitude');
  [latInput, lonInput].forEach(input => {
    if (input) {
      input.addEventListener('change', updateWeatherLink);
      input.addEventListener('input', updateWeatherLink);
    }
  });

  // Initialize exclude tags
  initExcludeTags();

  // Synchronize color pickers and hex fields (aus index.php ausgelagert)
  syncColorPickers();
});

async function loadCalendars() {
  try {
    const calendarsUrl = OC.generateUrl('/apps/digitalsignage/api/calendars');
    const response = await fetch(calendarsUrl, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'requesttoken': CSRF_TOKEN
      },
      credentials: 'same-origin'
    });

    const calendars = await response.json();
    const select = document.getElementById('calendar_names');

    // Get current value from PHP (JSON array)
    const currentValueStr = select.dataset.currentValue || '[]';
    let currentValues = [];
    try {
      currentValues = JSON.parse(currentValueStr);
    } catch (e) {
      console.error('Failed to parse calendar_names:', e);
    }

    select.innerHTML = calendars.map(cal =>
        `<option value="${cal.displayName}" ${currentValues.includes(cal.displayName) ? 'selected' : ''}>${cal.displayName}</option>`
      ).join('');
  } catch (error) {
    console.error('Error loading calendars:', error);
    document.getElementById('calendar_name').innerHTML = `<option value="">${translate('Calendar loading error')}</option>`;
  }
}

async function loadFolders() {
  try {
    const foldersUrl = OC.generateUrl('/apps/digitalsignage/api/folders');
    const response = await fetch(foldersUrl, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'requesttoken': CSRF_TOKEN
      },
      credentials: 'same-origin'
    });

    const folders = await response.json();
    const select = document.getElementById('image_folder');

    // Get current value from PHP
    const currentValue = select.dataset.currentValue || '';

    select.innerHTML = `<option value="">${translate('Select folder')}</option>` +
      folders.sort().map(folder =>
        `<option value="${folder}" ${folder === currentValue ? 'selected' : ''}>${folder}</option>`
      ).join('');
  } catch (error) {
    console.error('Error loading folders:', error);
    document.getElementById('image_folder').innerHTML = `<option value="">${translate('Error loading folders')}</option>`;
  }
}

async function loadEventTitles() {
  try {
    const eventTitlesUrl = OC.generateUrl('/apps/digitalsignage/api/event-titles');
    const response = await fetch(eventTitlesUrl, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'requesttoken': CSRF_TOKEN
      },
      credentials: 'same-origin'
    });

    if (!response.ok) {
      return;
    }

    const titles = await response.json();
    const datalist = document.getElementById('event-titles-list');

    if (!datalist) {
      return;
    }

    datalist.innerHTML = '';

    titles.forEach(title => {
      if (!excludeTags.includes(title)) {
        const option = document.createElement('option');
        option.value = title;
        datalist.appendChild(option);
      }
    });
  } catch (error) {
  }
}

async function saveSettings() {
  const msgSpan = document.getElementById('settings-msg');

  try {
    // Get selected calendars from multi-select
    const calendarSelect = document.getElementById('calendar_names');
    const selectedCalendars = Array.from(calendarSelect.selectedOptions).map(opt => opt.value);

    const data = {
      display_name: document.getElementById('display_name').value,
      show_display_name: document.getElementById('show_display_name').checked ? '1' : '0',
      calendar_names: JSON.stringify(selectedCalendars),
      image_folder: document.getElementById('image_folder').value,
      slide_interval: document.getElementById('slide_interval').value,
      weather_latitude: document.getElementById('weather_latitude').value,
      weather_longitude: document.getElementById('weather_longitude').value,
      calendar_exclude: document.getElementById('calendar_exclude').value,
      color_primary: document.getElementById('color_primary').value,
      color_bg: document.getElementById('color_bg').value,
      color_text: document.getElementById('color_text').value,
      color_gradient_start: document.getElementById('color_gradient_start').value,
      color_gradient_end: document.getElementById('color_gradient_end').value,
      show_titlebar: '1'
    };

    const saveUrl = OC.generateUrl('/apps/digitalsignage/settings/user');

    const response = await fetch(saveUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'requesttoken': CSRF_TOKEN
      },
      credentials: 'same-origin',
      body: JSON.stringify(data)
    });

    const result = await response.json();

    if (result.status === 'success') {
      msgSpan.textContent = translate('Settings saved successfully');
      msgSpan.style.color = 'green';
      setTimeout(() => { msgSpan.textContent = ''; }, 3000);
    } else {
      throw new Error(translate('Error saving settings'));
    }
  } catch (error) {
    console.error('Error saving settings:', error);
    msgSpan.textContent = translate('Error saving settings');
    msgSpan.style.color = 'red';
  }
}

// Exclude Tags Management
let excludeTags = [];

function updateWeatherLink() {
  const link = document.getElementById('weather-map-link');
  const latInput = document.getElementById('weather_latitude');
  const lonInput = document.getElementById('weather_longitude');
  if (!link || !latInput || !lonInput) {
    return;
  }
  const lat = parseFloat(latInput.value) || 0;
  const lon = parseFloat(lonInput.value) || 0;
  const zoom = 14;
  link.href = `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lon}&zoom=${zoom}`;
}

function initExcludeTags() {
  const hiddenInput = document.getElementById('calendar_exclude');
  const input = document.getElementById('calendar-exclude-input');
  const addBtn = document.getElementById('add-exclude-btn');

  if (!hiddenInput || !input || !addBtn) {
    return;
  }

  // Load existing tags from hidden input
  try {
    const existingValue = hiddenInput.value || '[]';
    excludeTags = JSON.parse(existingValue);
  } catch (e) {
    console.error('Error parsing exclude tags:', e);
    excludeTags = [];
  }

  renderExcludeTags();

  // Add tag on button click
  addBtn.addEventListener('click', function() {
    const value = input.value.trim();
    if (value && !excludeTags.includes(value)) {
      excludeTags.push(value);
      input.value = '';
      renderExcludeTags();
      updateHiddenInput();
      loadEventTitles(); // Reload to update available suggestions
    }
  });

  // Add tag on Enter key
  input.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      const value = input.value.trim();
      if (value && !excludeTags.includes(value)) {
        excludeTags.push(value);
        input.value = '';
        renderExcludeTags();
        updateHiddenInput();
        loadEventTitles(); // Reload to update available suggestions
      }
    }
  });
}

function renderExcludeTags() {
  const container = document.getElementById('calendar-exclude-tags');
  if (!container) return;

  if (excludeTags.length === 0) {
    container.innerHTML = `<span style="color: #999; font-style: italic;">${translate('No exclude terms yet')}</span>`;
    return;
  }

  container.innerHTML = excludeTags.map((tag, index) => `
    <span class="exclude-tag">
      <span>${escapeHtml(tag)}</span>
      <span class="exclude-tag-remove" data-index="${index}">×</span>
    </span>
  `).join('');

  // Add event listeners to remove buttons
  container.querySelectorAll('.exclude-tag-remove').forEach(btn => {
    btn.addEventListener('click', function() {
      const index = parseInt(this.getAttribute('data-index'));
      excludeTags.splice(index, 1);
      renderExcludeTags();
      updateHiddenInput();
      loadEventTitles(); // Reload to update available suggestions
    });
  });
}

function updateHiddenInput() {
  const hiddenInput = document.getElementById('calendar_exclude');
  if (hiddenInput) {
    hiddenInput.value = JSON.stringify(excludeTags);
  }
}

// Synchronize color pickers and hex fields (aus index.php ausgelagert)
function syncColorPickers() {
  ['primary','bg','text','gradient_start','gradient_end'].forEach(function(type) {
    const colorInput = document.getElementById('color_' + type);
    const hexInput = document.getElementById('color_' + type + '_hex');
    if (colorInput && hexInput) {
      colorInput.addEventListener('input', function() {
        hexInput.value = colorInput.value;
      });
      hexInput.addEventListener('input', function() {
        if (/^#[0-9a-fA-F]{6}$/.test(hexInput.value)) {
          colorInput.value = hexInput.value;
        }
      });
    }
  });
}

// Reset colors to default values
function resetColorsToDefaults() {
  const defaults = {
    'color_primary': '#0066cc',
    'color_bg': '#f8f9fa',
    'color_text': '#2c3e50',
    'color_gradient_start': '#0066cc',
    'color_gradient_end': '#3399ff'
  };

  Object.keys(defaults).forEach(key => {
    const colorInput = document.getElementById(key);
    const hexInput = document.getElementById(key + '_hex');
    if (colorInput && hexInput) {
      colorInput.value = defaults[key];
      hexInput.value = defaults[key];
    }
  });
}
