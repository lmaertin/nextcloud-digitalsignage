const DEFAULT_IMAGE_REFRESH_INTERVAL_MINUTES = 15;

const translate = (text, params = []) => {
  if (typeof OC !== 'undefined' && OC.L10N && typeof OC.L10N.translate === 'function') {
    const translated = OC.L10N.translate('digitalsignage', text, params);
    if (translated !== text) {
      return translated;
    }
  }

  const translationAttributes = {
    'Display timezone': 'data-translation-display-timezone',
    'Nextcloud timezone': 'data-translation-nextcloud-timezone',
    'Weather latitude': 'data-translation-weather-latitude',
    'Weather longitude': 'data-translation-weather-longitude',
    'Nextcloud Weather location': 'data-translation-nextcloud-weather-location',
    'Save display settings': 'data-translation-save-display-settings',
    'Update display': 'data-translation-update-display',
    'Saved': 'data-translation-saved',
    'Error saving display settings': 'data-translation-error-saving-display-settings',
    'Search location': 'data-translation-search-location',
    'City or address': 'data-translation-city-or-address',
    'Search': 'data-translation-search',
    'Location results': 'data-translation-location-results',
    'Select location': 'data-translation-select-location',
    'No locations found': 'data-translation-no-locations-found',
    'Location search failed': 'data-translation-location-search-failed',
    'Location data provided by': 'data-translation-location-data-provided-by'
  };
  const translatedFromTemplate = dataNode?.getAttribute(translationAttributes[text]);
  if (translatedFromTemplate) {
    return translatedFromTemplate;
  }

  return Array.isArray(params) && params.length > 0 ? text.replace('%s', params[0]) : text;
};

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

const dataNode = document.querySelector('[data-csrf-token]');

const API_URLS = {
  list: dataNode?.getAttribute('data-list-url'),
  create: dataNode?.getAttribute('data-create-url'),
  update: dataNode?.getAttribute('data-update-url'),
  clone: dataNode?.getAttribute('data-clone-url'),
  activatePreset: dataNode?.getAttribute('data-activate-preset-url'),
  delete: dataNode?.getAttribute('data-delete-url'),
  presetList: dataNode?.getAttribute('data-preset-list-url'),
  presetCreate: dataNode?.getAttribute('data-preset-create-url'),
  presetUpdate: dataNode?.getAttribute('data-preset-update-url'),
  presetDelete: dataNode?.getAttribute('data-preset-delete-url'),
  presetClone: dataNode?.getAttribute('data-preset-clone-url')
};

const CSRF_TOKEN = dataNode?.getAttribute('data-csrf-token');

let excludeTags = [];
let presets = [];
let availableCalendars = [];
const locationSearchResults = new Map();

function fetchJson(url, options = {}) {
  return fetch(url, {
    headers: {
      'Content-Type': 'application/json',
      requesttoken: CSRF_TOKEN,
      ...(options.headers || {})
    },
    credentials: 'same-origin',
    ...options
  });
}

async function parseJsonResponse(response) {
  const body = await response.text();

  try {
    return JSON.parse(body);
  } catch (error) {
    const compactBody = body.trim();
    if (compactBody.startsWith('<!DOCTYPE') || compactBody.startsWith('<html')) {
      throw new Error('Server returned HTML instead of JSON');
    }

    throw new Error(compactBody || 'Invalid JSON response');
  }
}

function getPresetFormData() {
  const calendarSelect = document.getElementById('preset-calendar-names');
  return {
    name: document.getElementById('preset-name').value.trim(),
    image_folder: document.getElementById('preset-image-folder').value,
    image_fit_mode: document.getElementById('preset-image-fit-mode').value,
    image_order_mode: document.getElementById('preset-image-order-mode').value,
    imageOrderMode: document.getElementById('preset-image-order-mode').value,
    recursive_media: document.getElementById('preset-recursive-media').checked ? '1' : '0',
    slide_interval: parseInt(document.getElementById('preset-slide-interval').value, 10) || 10,
    header_title_source: document.getElementById('preset-header-title-source').value,
    show_slideshow: document.getElementById('preset-show-slideshow').checked ? '1' : '0',
    show_weather: document.getElementById('preset-show-weather').checked ? '1' : '0',
    show_calendar: document.getElementById('preset-show-calendar').checked ? '1' : '0',
    show_event_description: document.getElementById('preset-show-event-description').checked ? '1' : '0',
    calendar_names: JSON.stringify(Array.from(calendarSelect.selectedOptions).map((option) => option.value)),
    calendar_exclude: document.getElementById('preset-calendar-exclude').value,
  };
}

function resetPresetForm() {
  document.getElementById('preset-id').value = '';
  document.getElementById('preset-name').value = '';
  document.getElementById('preset-image-folder').value = '';
  document.getElementById('preset-image-fit-mode').value = 'cover';
  document.getElementById('preset-image-order-mode').value = 'shuffle';
  document.getElementById('preset-recursive-media').checked = false;
  document.getElementById('preset-slide-interval').value = '10';
  document.getElementById('preset-header-title-source').value = 'global';
  document.getElementById('preset-show-slideshow').checked = true;
  document.getElementById('preset-show-weather').checked = true;
  document.getElementById('preset-show-calendar').checked = true;
  document.getElementById('preset-show-event-description').checked = false;
  populatePresetCalendars([]);
  setPresetExcludeTags([]);
  document.getElementById('save-preset-btn').textContent = translate('Save preset');
  document.getElementById('cancel-preset-edit-btn').style.display = 'none';
}

function showPresetEditor(preset = null) {
  const editor = document.getElementById('preset-editor');
  const trigger = document.getElementById('new-preset-btn');
  if (!editor) {
    return;
  }
  editor.hidden = false;
  if (trigger) {
    trigger.hidden = true;
  }
  if (preset) {
    fillPresetForm(preset);
  } else {
    resetPresetForm();
  }
  editor.scrollIntoView({behavior: 'smooth', block: 'nearest'});
}

function hidePresetEditor() {
  const editor = document.getElementById('preset-editor');
  const trigger = document.getElementById('new-preset-btn');
  if (editor) {
    editor.hidden = true;
  }
  if (trigger) {
    trigger.hidden = false;
  }
  resetPresetForm();
}

function getTextSizeSettings() {
  return Array.from(document.querySelectorAll('[data-text-size-field="1"]')).reduce((values, input) => {
    values[input.name] = input.value.trim();
    return values;
  }, {});
}

function fillPresetForm(preset) {
  document.getElementById('preset-id').value = String(preset.id);
  document.getElementById('preset-name').value = preset.name;
  document.getElementById('preset-image-folder').value = preset.imageFolder;
  document.getElementById('preset-image-fit-mode').value = preset.imageFitMode;
  document.getElementById('preset-image-order-mode').value = preset.imageOrderMode || 'shuffle';
  document.getElementById('preset-recursive-media').checked = preset.recursiveMedia === true;
  document.getElementById('preset-slide-interval').value = String(preset.slideInterval);
  document.getElementById('preset-header-title-source').value = preset.headerTitleSource || (preset.showDisplayName !== false ? 'global' : 'none');
  document.getElementById('preset-show-slideshow').checked = preset.showSlideshow !== false;
  document.getElementById('preset-show-weather').checked = preset.showWeather !== false;
  document.getElementById('preset-show-calendar').checked = preset.showCalendar !== false;
  document.getElementById('preset-show-event-description').checked = preset.showEventDescription === true;
  populatePresetCalendars(preset.calendarNames || []);
  setPresetExcludeTags(preset.calendarExclude || []);
  document.getElementById('save-preset-btn').textContent = translate('Update preset');
  document.getElementById('cancel-preset-edit-btn').style.display = 'inline-flex';
}

function renderPresetSummary(preset) {
  const mode = preset.imageFitMode === 'contain'
    ? translate('Fit complete (with background)')
    : translate('Fill (crop if needed)');
  const orderMode = preset.imageOrderMode === 'filename'
    ? translate('By filename')
    : translate('Shuffle');
  const displayName = preset.showDisplayName
    ? translate('Display name on')
    : translate('Display name off');
  const widgets = [];
  if (preset.showSlideshow !== false) {
    widgets.push(translate('Images / Slideshow'));
  }
  if (preset.showWeather !== false) {
    widgets.push(translate('Weather'));
  }
  if (preset.showCalendar !== false) {
    widgets.push(translate('Calendar'));
  }
  const widgetSummary = `${translate('Widgets')}: ${widgets.join(', ')}`;

  return `${escapeHtml(preset.imageFolder)} | ${escapeHtml(mode)} | ${escapeHtml(orderMode)} | ${escapeHtml(displayName)} | ${escapeHtml(widgetSummary)} | ${preset.slideInterval}s`;
}

function renderPresetList() {
  const container = document.getElementById('presets-container');
  if (!container) {
    return;
  }

  if (presets.length === 0) {
    container.innerHTML = `<p>${translate('No presets yet')}</p>`;
    return;
  }

  container.innerHTML = presets.map((preset) => `
    <div class="preset-item">
      <div class="preset-details">
        <div class="preset-name">${escapeHtml(preset.name)}</div>
        <div class="preset-summary">${renderPresetSummary(preset)}</div>
      </div>
      <div class="token-actions">
        <button class="button" data-preset-edit="${preset.id}">${translate('Edit')}</button>
        <button class="button" data-preset-clone="${preset.id}">${translate('Clone')}</button>
        <button class="button error" data-preset-delete="${preset.id}">${translate('Delete')}</button>
      </div>
    </div>
  `).join('');

  container.querySelectorAll('[data-preset-edit]').forEach((button) => {
    button.addEventListener('click', () => {
      const preset = presets.find((entry) => entry.id === parseInt(button.getAttribute('data-preset-edit'), 10));
      if (preset) {
        showPresetEditor(preset);
      }
    });
  });

  container.querySelectorAll('[data-preset-delete]').forEach((button) => {
    button.addEventListener('click', () => {
      deletePreset(parseInt(button.getAttribute('data-preset-delete'), 10));
    });
  });

  container.querySelectorAll('[data-preset-clone]').forEach((button) => {
    button.addEventListener('click', () => clonePreset(parseInt(button.getAttribute('data-preset-clone'), 10)));
  });
}

async function clonePreset(id) {
  try {
    const response = await fetchJson(API_URLS.presetClone.replace('PRESET_ID', String(id)), {method: 'POST'});
    const result = await parseJsonResponse(response);
    if (!response.ok || result.error) {
      throw new Error(result.error || translate('Error cloning preset'));
    }
    await loadPresets();
  } catch (error) {
    console.error('Error cloning preset:', error);
    alert(`${translate('Error cloning preset')}: ${error.message}`);
  }
}

function renderPresetOptions(activePresetId) {
  return presets.map((preset) => {
    const selected = preset.id === activePresetId ? 'selected' : '';
    return `<option value="${preset.id}" ${selected}>${escapeHtml(preset.name)}</option>`;
  }).join('');
}

async function loadPresets() {
  try {
    const response = await fetchJson(API_URLS.presetList, { method: 'GET' });
    const result = await parseJsonResponse(response);

    if (!response.ok) {
      throw new Error(result.error || translate('Error loading presets'));
    }

    presets = Array.isArray(result) ? result : [];
    renderPresetList();
    populateCreatePresetOptions();
  } catch (error) {
    console.error('Error loading presets:', error);
    const container = document.getElementById('presets-container');
    if (container) {
      container.innerHTML = `<p>${translate('Error loading presets')}: ${escapeHtml(error.message)}</p>`;
    }
  }
}

function scrollToSavedPresets() {
  document.querySelector('.ds-preset-list-heading')?.scrollIntoView({behavior: 'smooth', block: 'start'});
}

async function savePreset() {
  const presetId = document.getElementById('preset-id').value;
  const data = getPresetFormData();

  if (!data.name) {
    alert(translate('Please enter a preset name'));
    return;
  }

  if (data.show_slideshow === '0' && data.show_weather === '0' && data.show_calendar === '0') {
    alert(translate('Enable at least one widget'));
    return;
  }

  try {
    const isUpdate = presetId !== '';
    const url = isUpdate
      ? API_URLS.presetUpdate.replace('PRESET_ID', presetId)
      : API_URLS.presetCreate;
    const method = isUpdate ? 'PUT' : 'POST';

    const response = await fetchJson(url, {
      method,
      body: JSON.stringify(data)
    });
    const result = await parseJsonResponse(response);

    if (!response.ok || result.error) {
      throw new Error(translate(result.error || 'Error saving preset'));
    }

    hidePresetEditor();
    await loadPresets();
    scrollToSavedPresets();
    await loadTokens();
  } catch (error) {
    console.error('Error saving preset:', error);
    alert(`${translate('Error saving preset')}: ${error.message}`);
  }
}

async function deletePreset(id) {
  if (!confirm(translate('Are you sure you want to delete this preset?'))) {
    return;
  }

  try {
    const response = await fetchJson(API_URLS.presetDelete.replace('PRESET_ID', String(id)), {
      method: 'DELETE'
    });
    const result = await parseJsonResponse(response);

    if (!response.ok || result.error) {
      throw new Error(result.error || translate('Error deleting preset'));
    }

    resetPresetForm();
    await loadPresets();
    await loadTokens();
  } catch (error) {
    console.error('Error deleting preset:', error);
    alert(`${translate('Error deleting preset')}: ${error.message}`);
  }
}

async function activatePreset(displayId, presetId) {
  try {
    const response = await fetchJson(API_URLS.activatePreset.replace('DISPLAY_ID', String(displayId)), {
      method: 'POST',
      body: JSON.stringify({ presetId })
    });
    const result = await parseJsonResponse(response);

    if (!response.ok || result.error) {
      throw new Error(result.error || translate('Error activating preset'));
    }

    await loadTokens();
  } catch (error) {
    console.error('Error activating preset:', error);
    alert(`${translate('Error activating preset')}: ${error.message}`);
  }
}

function copyToClipboard(button, value, originalLabel) {
  navigator.clipboard.writeText(value || '').then(() => {
    const originalText = button.textContent;
    button.textContent = '✓';
    button.setAttribute('aria-label', translate('Copied!'));
    setTimeout(() => {
      button.textContent = originalText;
      button.setAttribute('aria-label', originalLabel);
    }, 2000);
  });
}

async function updateDisplaySettings(displayId, button) {
  const container = button.closest('[data-display-id]');
  const data = {
    name: container.querySelector('[data-display-name]').value.trim(),
    time_zone: container.querySelector('[data-display-timezone]').value.trim(),
    weather_latitude: container.querySelector('[data-display-latitude]').value.trim() || null,
    weather_longitude: container.querySelector('[data-display-longitude]').value.trim() || null,
    active_preset_id: Number(container.querySelector('[data-display-preset-select]').value)
  };

  try {
    const response = await fetchJson(API_URLS.update.replace('DISPLAY_ID', String(displayId)), {
      method: 'PUT',
      body: JSON.stringify(data)
    });
    const result = await parseJsonResponse(response);
    if (!response.ok || result.error) {
      throw new Error(result.error || translate('Error saving display settings'));
    }
    button.textContent = translate('Saved');
    container.classList.remove('is-editing');
    container.querySelector('[data-display-editor]').hidden = true;
    container.querySelector('[data-display-list-actions]').hidden = false;
    document.getElementById('new-display-btn').hidden = false;
    container.querySelector('[data-display-name-label]')?.replaceChildren(document.createTextNode(data.name));
    setTimeout(() => { button.textContent = translate('Update display'); }, 2000);
  } catch (error) {
    console.error('Error saving display settings:', error);
    alert(`${translate('Error saving display settings')}: ${error.message}`);
  }
}

function toggleDisplayEditor(button, visible) {
  const container = button.closest('[data-display-id]');
  const editor = container.querySelector('[data-display-editor]');
  const listActions = container.querySelector('[data-display-list-actions]');
  container.classList.toggle('is-editing', visible);
  const newDisplayButton = document.getElementById('new-display-btn');
  if (newDisplayButton) {
    newDisplayButton.hidden = visible;
  }
  if (editor) {
    editor.hidden = !visible;
  }
  if (listActions) {
    listActions.hidden = visible;
  }
}

async function searchDisplayLocation(displayId, button) {
  const container = button.closest('[data-display-id]');
  const queryInput = container.querySelector('[data-location-query]');
  const resultsSelect = container.querySelector('[data-location-results]');
  const query = queryInput.value.trim();
  if (!query) {
    return;
  }

  button.disabled = true;
  try {
    const response = await fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&limit=5&q=${encodeURIComponent(query)}`, {
      headers: {Accept: 'application/json'}
    });
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const results = await response.json();
    locationSearchResults.set(displayId, results);
    resultsSelect.innerHTML = `<option value="">${translate('Select location')}</option>` + results.map((result, index) =>
      `<option value="${index}">${escapeHtml(result.display_name)}</option>`
    ).join('');
    resultsSelect.hidden = results.length === 0;
    if (results.length === 0) {
      queryInput.setCustomValidity(translate('No locations found'));
      queryInput.reportValidity();
      queryInput.setCustomValidity('');
    }
  } catch (error) {
    console.error('Location search failed:', error);
    alert(translate('Location search failed'));
  } finally {
    button.disabled = false;
  }
}

async function applyDisplayLocation(displayId, select) {
  const result = locationSearchResults.get(displayId)?.[Number(select.value)];
  if (!result) {
    return;
  }

  const container = select.closest('[data-display-id]');
  container.querySelector('[data-display-latitude]').value = result.lat;
  container.querySelector('[data-display-longitude]').value = result.lon;

  try {
    const response = await fetch(`https://api.open-meteo.com/v1/forecast?latitude=${encodeURIComponent(result.lat)}&longitude=${encodeURIComponent(result.lon)}&timezone=auto&current=temperature_2m`);
    if (!response.ok) {
      return;
    }
    const weatherLocation = await response.json();
    if (typeof weatherLocation.timezone === 'string') {
      container.querySelector('[data-display-timezone]').value = weatherLocation.timezone;
    }
  } catch (error) {
    console.warn('Timezone lookup failed:', error);
  }
}

async function searchCreateLocation(input) {
  const query = input.value.trim();
  if (!query) {
    return;
  }

  try {
    const response = await fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=${encodeURIComponent(query)}`, {
      headers: {Accept: 'application/json'}
    });
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const [result] = await response.json();
    if (!result) {
      alert(translate('No locations found'));
      return;
    }

    document.getElementById('display-create-latitude').value = result.lat;
    document.getElementById('display-create-longitude').value = result.lon;
    const timezoneResponse = await fetch(`https://api.open-meteo.com/v1/forecast?latitude=${encodeURIComponent(result.lat)}&longitude=${encodeURIComponent(result.lon)}&timezone=auto&current=temperature_2m`);
    if (timezoneResponse.ok) {
      const location = await timezoneResponse.json();
      document.getElementById('display-create-timezone').value = location.timezone || '';
    }
  } catch (error) {
    console.error('Create display location search failed:', error);
    alert(translate('Location search failed'));
  }
}

async function loadTokens() {
  try {
    const response = await fetchJson(API_URLS.list, { method: 'GET' });
    const tokens = await parseJsonResponse(response);
    const container = document.getElementById('tokens-container');

    if (!response.ok) {
      throw new Error(tokens.error || translate('Error loading tokens'));
    }

    if (!tokens || tokens.length === 0) {
      container.innerHTML = `<p>${translate('No displays yet')}</p>`;
      return;
    }

    container.innerHTML = tokens.map((token) => `
      <div class="token-item" data-display-id="${token.id}">
        <div class="token-info">
          <div class="token-name" data-display-name-label>${escapeHtml(token.name)}</div>
          <div class="token-meta">
            <div class="token-row">
              <span class="token-row-label">${translate('View URL')}</span>
              <div class="token-value-copy">
                <a class="token-url" href="${escapeHtml(token.url)}" target="_blank" rel="noopener noreferrer">${escapeHtml(token.url)}</a>
                <button class="icon-button" type="button" data-copy-url="${escapeHtml(token.url)}" aria-label="${translate('Copy URL')}" title="${translate('Copy URL')}">⧉</button>
              </div>
            </div>
            <div class="token-row">
              <span class="token-row-label">${translate('Control token')}</span>
              <div class="token-value-copy">
                <span class="token-url">${escapeHtml(token.controlToken || '')}</span>
                <button class="icon-button" type="button" data-copy-control="${escapeHtml(token.controlToken || '')}" aria-label="${translate('Copy control token')}" title="${translate('Copy control token')}">⧉</button>
              </div>
            </div>
            <div class="display-editor-fields ds-object-editor" data-display-editor hidden>
            <div class="token-name-edit">
              <label class="token-row-label" for="display-name-${token.id}">${translate('Display name')}</label>
              <input class="ds-input token-display-name" id="display-name-${token.id}" data-display-name value="${escapeHtml(token.name)}" />
            </div>
            <div class="token-row token-location-search-row">
              <label class="token-row-label" for="display-location-${token.id}">${translate('Search location')}</label>
              <div class="token-location-search-controls">
                <div class="token-location-search-inputs">
                  <input class="ds-input" id="display-location-${token.id}" data-location-query placeholder="${translate('City or address')}" />
                </div>
                <select class="ds-input" data-location-results hidden aria-label="${translate('Location results')}">
                  <option value="">${translate('Select location')}</option>
                </select>
                <small>${translate('Location data provided by')} <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a>.</small>
              </div>
            </div>
            <div class="token-row">
              <label class="token-row-label" for="display-timezone-${token.id}">${translate('Display timezone')}</label>
              <input class="ds-input" id="display-timezone-${token.id}" data-display-timezone value="${escapeHtml(token.timeZone || '')}" list="timezone-options" placeholder="${translate('Nextcloud timezone')}" />
            </div>
            <div class="token-row token-coordinate-row">
              <label class="token-row-label" for="display-latitude-${token.id}">${translate('Weather latitude')}</label>
              <input class="ds-input" id="display-latitude-${token.id}" data-display-latitude type="number" min="-90" max="90" step="any" value="${token.weatherLatitude ?? ''}" placeholder="${translate('Nextcloud Weather location')}" />
              <label class="token-row-label" for="display-longitude-${token.id}">${translate('Weather longitude')}</label>
              <input class="ds-input" id="display-longitude-${token.id}" data-display-longitude type="number" min="-180" max="180" step="any" value="${token.weatherLongitude ?? ''}" placeholder="${translate('Nextcloud Weather location')}" />
            </div>
            <div class="token-row">
              <span class="token-row-label">${translate('Active preset')}</span>
              <select class="ds-input token-select" data-display-preset-select="${token.id}">
                ${renderPresetOptions(token.activePresetId)}
              </select>
            </div>
            <div class="display-editor-actions ds-editor-actions">
              <button class="button primary" data-display-save="${token.id}">${translate('Update display')}</button>
              <button class="button" data-display-cancel="${token.id}">${translate('Cancel edit')}</button>
            </div>
            </div>
          </div>
        </div>
        <div class="token-actions" data-display-list-actions>
          <button class="button" data-display-edit="${token.id}">${translate('Edit')}</button>
          <button class="button" data-display-clone="${token.id}">${translate('Clone')}</button>
          <button class="button error" data-token-id="${token.id}">${translate('Delete')}</button>
        </div>
      </div>
    `).join('');

    container.querySelectorAll('[data-copy-url]').forEach((button) => {
      button.addEventListener('click', () => copyToClipboard(button, button.getAttribute('data-copy-url'), translate('Copy URL')));
    });

    container.querySelectorAll('[data-copy-control]').forEach((button) => {
      button.addEventListener('click', () => copyToClipboard(button, button.getAttribute('data-copy-control'), translate('Copy control token')));
    });

    container.querySelectorAll('[data-display-save]').forEach((button) => {
      button.addEventListener('click', () => updateDisplaySettings(parseInt(button.getAttribute('data-display-save'), 10), button));
    });

    container.querySelectorAll('[data-display-edit]').forEach((button) => {
      button.addEventListener('click', () => toggleDisplayEditor(button, true));
    });

    container.querySelectorAll('[data-display-clone]').forEach((button) => {
      button.addEventListener('click', () => cloneDisplay(parseInt(button.getAttribute('data-display-clone'), 10)));
    });

    container.querySelectorAll('[data-display-cancel]').forEach((button) => {
      button.addEventListener('click', () => {
        document.getElementById('new-display-btn').hidden = false;
        loadTokens();
      });
    });

    container.querySelectorAll('[data-location-query]').forEach((input) => {
      input.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') {
          return;
        }

        event.preventDefault();
        searchDisplayLocation(
          parseInt(input.closest('[data-display-id]').getAttribute('data-display-id'), 10),
          input
        );
      });
    });

    container.querySelectorAll('[data-location-results]').forEach((select) => {
      select.addEventListener('change', () => applyDisplayLocation(parseInt(select.closest('[data-display-id]').getAttribute('data-display-id'), 10), select));
    });

    container.querySelectorAll('[data-token-id]').forEach((button) => {
      button.addEventListener('click', () => deleteToken(button.getAttribute('data-token-id')));
    });
  } catch (error) {
    console.error('Error loading displays:', error);
    document.getElementById('tokens-container').innerHTML = `<p>${translate('Error loading tokens')}: ${escapeHtml(error.message)}</p>`;
  }
}

async function cloneDisplay(id) {
  try {
    const response = await fetchJson(API_URLS.clone.replace('DISPLAY_ID', String(id)), {method: 'POST'});
    const result = await parseJsonResponse(response);
    if (!response.ok || result.error) {
      throw new Error(result.error || translate('Error cloning display'));
    }
    await loadTokens();
  } catch (error) {
    console.error('Error cloning display:', error);
    alert(`${translate('Error cloning display')}: ${error.message}`);
  }
}

async function createToken() {
  const name = document.getElementById('token-name').value.trim();
  if (!name) {
    alert(translate('Please enter a name for the token'));
    return;
  }

  try {
    const presetSelect = document.getElementById('display-create-preset');
    const response = await fetchJson(API_URLS.create, {
      method: 'POST',
      body: JSON.stringify({
        name,
        time_zone: document.getElementById('display-create-timezone').value.trim(),
        weather_latitude: document.getElementById('display-create-latitude').value.trim() || null,
        weather_longitude: document.getElementById('display-create-longitude').value.trim() || null,
        active_preset_id: presetSelect.value ? Number(presetSelect.value) : null
      })
    });
    const result = await parseJsonResponse(response);

    if (!response.ok || result.error) {
      throw new Error(result.error || translate('Error creating token'));
    }

    document.getElementById('token-name').value = '';
    hideDisplayCreateEditor();
    await loadTokens();
  } catch (error) {
    console.error('Error creating display:', error);
    alert(`${translate('Error creating token')}: ${error.message}`);
  }
}

function showDisplayCreateEditor() {
  const editor = document.getElementById('display-create-editor');
  const trigger = document.getElementById('new-display-btn');
  if (editor) {
    editor.hidden = false;
    if (trigger) {
      trigger.hidden = true;
    }
    editor.querySelector('#token-name')?.focus();
  }
}

function hideDisplayCreateEditor() {
  const editor = document.getElementById('display-create-editor');
  const trigger = document.getElementById('new-display-btn');
  if (editor) {
    editor.hidden = true;
  }
  if (trigger) {
    trigger.hidden = false;
  }
  const input = document.getElementById('token-name');
  if (input) {
    input.value = '';
  }
  ['display-create-location', 'display-create-timezone', 'display-create-latitude', 'display-create-longitude'].forEach((id) => {
    const field = document.getElementById(id);
    if (field) {
      field.value = '';
    }
  });
}

function populateCreatePresetOptions() {
  const select = document.getElementById('display-create-preset');
  if (select) {
    select.innerHTML = renderPresetOptions(null);
  }
}

async function deleteToken(id) {
  if (!confirm(translate('Are you sure you want to delete this token?'))) {
    return;
  }

  try {
    const url = API_URLS.delete.replace('TOKEN_ID', id);
    const response = await fetchJson(url, { method: 'DELETE' });
    const result = await parseJsonResponse(response);

    if (!response.ok || result.error) {
      throw new Error(result.error || translate('Error deleting token'));
    }

    await loadTokens();
  } catch (error) {
    console.error('Error deleting token:', error);
    alert(`${translate('Error deleting token')}: ${error.message}`);
  }
}

async function loadCalendars() {
  try {
    const calendarsUrl = OC.generateUrl('/apps/digitalsignage/api/calendars');
    const response = await fetchJson(calendarsUrl, { method: 'GET' });
    const calendars = await response.json();
    availableCalendars = calendars;
    populatePresetCalendars([]);
  } catch (error) {
    console.error('Error loading calendars:', error);
    document.getElementById('preset-calendar-names').innerHTML = `<option value="">${translate('Calendar loading error')}</option>`;
  }
}

function populatePresetCalendars(selectedValues) {
  const select = document.getElementById('preset-calendar-names');
  if (!select) {
    return;
  }
  const selected = new Set(selectedValues);
  select.innerHTML = availableCalendars.map((calendar) =>
    `<option value="${escapeHtml(calendar.displayName)}" ${selected.has(calendar.displayName) ? 'selected' : ''}>${escapeHtml(calendar.displayName)}</option>`
  ).join('');
}

async function loadFolders() {
  try {
    const foldersUrl = OC.generateUrl('/apps/digitalsignage/api/folders');
    const response = await fetchJson(foldersUrl, { method: 'GET' });
    const folders = await response.json();
    const presetFolderSelect = document.getElementById('preset-image-folder');
    const optionsMarkup = `<option value="">${translate('Select folder')}</option>` +
      folders.sort().map((folder) => `<option value="${folder}">${folder}</option>`).join('');

    if (presetFolderSelect) {
      presetFolderSelect.innerHTML = optionsMarkup;
    }
  } catch (error) {
    console.error('Error loading folders:', error);
    const presetFolderSelect = document.getElementById('preset-image-folder');
    if (presetFolderSelect) {
      presetFolderSelect.innerHTML = `<option value="">${translate('Error loading folders')}</option>`;
    }
  }
}

async function loadEventTitles() {
  try {
    const calendarSelect = document.getElementById('preset-calendar-names');
    const selectedCalendars = calendarSelect
      ? Array.from(calendarSelect.selectedOptions).map((option) => option.value)
      : [];
    const eventTitlesUrl = `${OC.generateUrl('/apps/digitalsignage/api/event-titles')}?calendar_names=${encodeURIComponent(JSON.stringify(selectedCalendars))}`;
    const response = await fetchJson(eventTitlesUrl, { method: 'GET' });
    if (!response.ok) {
      return;
    }

    const titles = await response.json();
    const datalist = document.getElementById('event-titles-list');
    if (!datalist) {
      return;
    }

    datalist.innerHTML = '';
    titles.forEach((title) => {
      if (!excludeTags.includes(title)) {
        const option = document.createElement('option');
        option.value = title;
        datalist.appendChild(option);
      }
    });
  } catch (error) {
    console.error('Error loading event titles:', error);
  }
}

async function saveSettings() {
  const msgSpan = document.getElementById('settings-msg');

  try {
    const contentSplitRatioInput = document.getElementById('content_split_ratio');
    const imageRefreshIntervalInput = document.getElementById('image_refresh_interval_minutes');
    const data = {
      auto_fullscreen_prompt: document.getElementById('auto_fullscreen_prompt').checked ? '1' : '0',
      content_split_ratio: contentSplitRatioInput ? contentSplitRatioInput.value : '50',
      image_refresh_interval_minutes: String(parseInt(imageRefreshIntervalInput?.value || String(DEFAULT_IMAGE_REFRESH_INTERVAL_MINUTES), 10)),
      color_primary: document.getElementById('color_primary').value,
      color_bg: document.getElementById('color_bg').value,
      color_text: document.getElementById('color_text').value,
      color_gradient_start: document.getElementById('color_gradient_start').value,
      color_gradient_end: document.getElementById('color_gradient_end').value,
      show_titlebar: '1',
      message_bg_color: document.getElementById('message_bg_color').value,
      message_bg_opacity: document.getElementById('message_bg_opacity').value,
      message_text_color: document.getElementById('message_text_color').value,
      message_font_size: document.getElementById('message_font_size').value,
      message_width_percent: document.getElementById('message_width_percent').value,
      message_position: document.getElementById('message_position').value,
      ...getTextSizeSettings()
    };

    const saveUrl = OC.generateUrl('/apps/digitalsignage/settings/user');
    const response = await fetchJson(saveUrl, {
      method: 'POST',
      body: JSON.stringify(data)
    });
    const result = await response.json();

    if (result.status === 'success') {
      msgSpan.textContent = translate('Settings saved successfully');
      msgSpan.style.color = 'green';
      setTimeout(() => {
        msgSpan.textContent = '';
      }, 3000);
    } else {
      throw new Error(translate('Error saving settings'));
    }
  } catch (error) {
    console.error('Error saving settings:', error);
    msgSpan.textContent = translate('Error saving settings');
    msgSpan.style.color = 'red';
  }
}

function initExcludeTags() {
  const hiddenInput = document.getElementById('preset-calendar-exclude');
  const input = document.getElementById('preset-calendar-exclude-input');
  const addButton = document.getElementById('add-exclude-btn');

  if (!hiddenInput || !input || !addButton) {
    return;
  }

  try {
    excludeTags = JSON.parse(hiddenInput.value || '[]');
  } catch (error) {
    console.error('Error parsing exclude tags:', error);
    excludeTags = [];
  }

  renderExcludeTags();

  const addTag = () => {
    const value = input.value.trim();
    if (value && !excludeTags.includes(value)) {
      excludeTags.push(value);
      input.value = '';
      renderExcludeTags();
      updateHiddenInput();
      loadEventTitles();
    }
  };

  addButton.addEventListener('click', addTag);
  input.addEventListener('keypress', (event) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      addTag();
    }
  });
}

function setPresetExcludeTags(tags) {
  excludeTags = Array.isArray(tags) ? tags : [];
  const hiddenInput = document.getElementById('preset-calendar-exclude');
  if (hiddenInput) {
    hiddenInput.value = JSON.stringify(excludeTags);
  }
  renderExcludeTags();
}

function renderExcludeTags() {
  const container = document.getElementById('preset-calendar-exclude-tags');
  if (!container) {
    return;
  }

  if (excludeTags.length === 0) {
    container.innerHTML = `<span style="color: #999; font-style: italic;">${translate('No exclude terms yet')}</span>`;
    return;
  }

  container.innerHTML = excludeTags.map((tag, index) => `
    <span class="exclude-tag">
      <span>${escapeHtml(tag)}</span>
      <span class="exclude-tag-remove" data-index="${index}">x</span>
    </span>
  `).join('');

  container.querySelectorAll('.exclude-tag-remove').forEach((button) => {
    button.addEventListener('click', () => {
      const index = parseInt(button.getAttribute('data-index'), 10);
      excludeTags.splice(index, 1);
      renderExcludeTags();
      updateHiddenInput();
      loadEventTitles();
    });
  });
}

function updateHiddenInput() {
  const hiddenInput = document.getElementById('preset-calendar-exclude');
  if (hiddenInput) {
    hiddenInput.value = JSON.stringify(excludeTags);
  }
}

function syncColorPickers() {
  ['primary', 'bg', 'text', 'gradient_start', 'gradient_end'].forEach((type) => {
    const colorInput = document.getElementById(`color_${type}`);
    const hexInput = document.getElementById(`color_${type}_hex`);
    if (!colorInput || !hexInput) {
      return;
    }

    colorInput.addEventListener('input', () => {
      hexInput.value = colorInput.value;
    });

    hexInput.addEventListener('input', () => {
      if (/^#[0-9a-fA-F]{6}$/.test(hexInput.value)) {
        colorInput.value = hexInput.value;
      }
    });
  });

  ['message_bg_color', 'message_text_color'].forEach((id) => {
    const colorInput = document.getElementById(id);
    const hexInput = document.getElementById(`${id}_hex`);
    if (!colorInput || !hexInput) {
      return;
    }

    colorInput.addEventListener('input', () => {
      hexInput.value = colorInput.value;
    });

    hexInput.addEventListener('input', () => {
      if (/^#[0-9a-fA-F]{6}$/.test(hexInput.value)) {
        colorInput.value = hexInput.value;
      }
    });
  });
}

function resetColorsToDefaults() {
  const defaults = {
    color_primary: '#0066cc',
    color_bg: '#f8f9fa',
    color_text: '#2c3e50',
    color_gradient_start: '#0066cc',
    color_gradient_end: '#3399ff'
  };

  Object.entries(defaults).forEach(([key, value]) => {
    const colorInput = document.getElementById(key);
    const hexInput = document.getElementById(`${key}_hex`);
    if (colorInput && hexInput) {
      colorInput.value = value;
      hexInput.value = value;
    }
  });
}

function resetLayoutToDefaults() {
  const defaults = {
    content_split_ratio: '50'
  };

  Object.entries(defaults).forEach(([key, value]) => {
    const input = document.getElementById(key);
    if (input) {
      input.value = value;
    }
  });
}

function resetMessageStyleToDefaults() {
  const defaults = {
    message_bg_color: '#0066cc',
    message_text_color: '#ffffff'
  };

  Object.entries(defaults).forEach(([key, value]) => {
    const colorInput = document.getElementById(key);
    const hexInput = document.getElementById(`${key}_hex`);
    if (colorInput && hexInput) {
      colorInput.value = value;
      hexInput.value = value;
    }
  });

  const opacityInput = document.getElementById('message_bg_opacity');
  if (opacityInput) {
    opacityInput.value = '50';
  }

  const fontSizeInput = document.getElementById('message_font_size');
  if (fontSizeInput) {
    fontSizeInput.value = '1.0';
  }

  const widthInput = document.getElementById('message_width_percent');
  if (widthInput) {
    widthInput.value = '88';
  }

  const positionInput = document.getElementById('message_position');
  if (positionInput) {
    positionInput.value = 'top';
  }

  updateMessageStylePreview();
}

function hexToRgba(hex, opacityPercent) {
  const match = /^#([0-9a-fA-F]{6})$/.exec(hex);
  if (!match) {
    return 'rgba(0, 102, 204, 1)';
  }

  const red = parseInt(match[1].slice(0, 2), 16);
  const green = parseInt(match[1].slice(2, 4), 16);
  const blue = parseInt(match[1].slice(4, 6), 16);
  const alpha = Math.max(0, Math.min(100, opacityPercent)) / 100;

  return `rgba(${red}, ${green}, ${blue}, ${alpha})`;
}

function updateMessageStylePreview() {
  const bubble = document.getElementById('message-style-preview-bubble');
  if (!bubble) {
    return;
  }

  const bgColor = document.getElementById('message_bg_color')?.value || '#0066cc';
  const opacity = Number.parseInt(document.getElementById('message_bg_opacity')?.value, 10) || 0;
  const textColor = document.getElementById('message_text_color')?.value || '#ffffff';
  const fontSize = document.getElementById('message_font_size')?.value || '1.0';
  const widthPercent = document.getElementById('message_width_percent')?.value || '88';

  const opacityLabel = document.getElementById('message_bg_opacity_value');
  if (opacityLabel) {
    opacityLabel.textContent = `${opacity}%`;
  }

  bubble.style.background = hexToRgba(bgColor, opacity);
  bubble.style.color = textColor;
  bubble.style.fontSize = `${fontSize}rem`;
  bubble.style.width = `min(${widthPercent}%, 100% - 24px)`;
}

function initMessageStylePreview() {
  const inputIds = [
    'message_bg_color',
    'message_bg_color_hex',
    'message_bg_opacity',
    'message_text_color',
    'message_text_color_hex',
    'message_font_size',
    'message_width_percent'
  ];

  inputIds.forEach((id) => {
    document.getElementById(id)?.addEventListener('input', updateMessageStylePreview);
  });

  updateMessageStylePreview();
}

function resetTextSizesToDefaults() {
  document.querySelectorAll('[data-text-size-field="1"]').forEach((input) => {
    const defaultValue = input.getAttribute('data-default-value');
    if (defaultValue !== null) {
      input.value = defaultValue;
    }
  });
}

document.addEventListener('DOMContentLoaded', async () => {
  document.getElementById('create-token-btn')?.addEventListener('click', createToken);
  document.getElementById('new-display-btn')?.addEventListener('click', showDisplayCreateEditor);
  document.getElementById('cancel-display-create-btn')?.addEventListener('click', hideDisplayCreateEditor);
  document.getElementById('display-create-location')?.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      searchCreateLocation(event.currentTarget);
    }
  });
  document.getElementById('new-preset-btn')?.addEventListener('click', () => showPresetEditor());
  document.getElementById('save-settings-btn')?.addEventListener('click', saveSettings);
  document.getElementById('reset-layout-btn')?.addEventListener('click', resetLayoutToDefaults);
  document.getElementById('reset-colors-btn')?.addEventListener('click', resetColorsToDefaults);
  document.getElementById('reset-message-style-btn')?.addEventListener('click', resetMessageStyleToDefaults);
  document.getElementById('reset-text-sizes-btn')?.addEventListener('click', resetTextSizesToDefaults);
  document.getElementById('save-preset-btn')?.addEventListener('click', savePreset);
  document.getElementById('cancel-preset-edit-btn')?.addEventListener('click', hidePresetEditor);

  const calendarSelect = document.getElementById('preset-calendar-names');
  if (calendarSelect) {
    calendarSelect.addEventListener('change', loadEventTitles);
  }

  initExcludeTags();
  syncColorPickers();
  initMessageStylePreview();
  resetPresetForm();

  await Promise.all([loadCalendars(), loadFolders()]);
  await loadPresets();
  await loadTokens();
  setTimeout(loadEventTitles, 500);
});
