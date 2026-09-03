const DEFAULT_IMAGE_REFRESH_INTERVAL_MINUTES = 15;

const translate = (text, params = []) => {
  if (typeof OC !== 'undefined' && OC.L10N && typeof OC.L10N.translate === 'function') {
    return OC.L10N.translate('digitalsignage', text, params);
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
  activatePreset: dataNode?.getAttribute('data-activate-preset-url'),
  delete: dataNode?.getAttribute('data-delete-url'),
  presetList: dataNode?.getAttribute('data-preset-list-url'),
  presetCreate: dataNode?.getAttribute('data-preset-create-url'),
  presetUpdate: dataNode?.getAttribute('data-preset-update-url'),
  presetDelete: dataNode?.getAttribute('data-preset-delete-url')
};

const CSRF_TOKEN = dataNode?.getAttribute('data-csrf-token');

let excludeTags = [];
let presets = [];

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
  return {
    name: document.getElementById('preset-name').value.trim(),
    image_folder: document.getElementById('preset-image-folder').value,
    image_fit_mode: document.getElementById('preset-image-fit-mode').value,
    image_order_mode: document.getElementById('preset-image-order-mode').value,
    imageOrderMode: document.getElementById('preset-image-order-mode').value,
    slide_interval: parseInt(document.getElementById('preset-slide-interval').value, 10) || 10,
    fullscreen_slideshow: document.getElementById('preset-fullscreen-slideshow').checked ? '1' : '0',
    header_title_source: document.getElementById('preset-header-title-source').value,
    show_slideshow: document.getElementById('preset-show-slideshow').checked ? '1' : '0',
    show_weather: document.getElementById('preset-show-weather').checked ? '1' : '0',
    show_calendar: document.getElementById('preset-show-calendar').checked ? '1' : '0',
    show_event_description: document.getElementById('preset-show-event-description').checked ? '1' : '0'
  };
}

function resetPresetForm() {
  document.getElementById('preset-id').value = '';
  document.getElementById('preset-name').value = '';
  document.getElementById('preset-image-folder').value = '';
  document.getElementById('preset-image-fit-mode').value = 'cover';
  document.getElementById('preset-image-order-mode').value = 'shuffle';
  document.getElementById('preset-slide-interval').value = '10';
  document.getElementById('preset-fullscreen-slideshow').checked = false;
  document.getElementById('preset-header-title-source').value = 'global';
  document.getElementById('preset-show-slideshow').checked = true;
  document.getElementById('preset-show-weather').checked = true;
  document.getElementById('preset-show-calendar').checked = true;
  document.getElementById('preset-show-event-description').checked = false;
  document.getElementById('save-preset-btn').textContent = translate('Save preset');
  document.getElementById('cancel-preset-edit-btn').style.display = 'none';
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
  document.getElementById('preset-slide-interval').value = String(preset.slideInterval);
  document.getElementById('preset-fullscreen-slideshow').checked = Boolean(preset.fullscreenSlideshow);
  document.getElementById('preset-header-title-source').value = preset.headerTitleSource || (preset.showDisplayName !== false ? 'global' : 'none');
  document.getElementById('preset-show-slideshow').checked = preset.showSlideshow !== false;
  document.getElementById('preset-show-weather').checked = preset.showWeather !== false;
  document.getElementById('preset-show-calendar').checked = preset.showCalendar !== false;
  document.getElementById('preset-show-event-description').checked = preset.showEventDescription === true;
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
  const fullscreen = preset.fullscreenSlideshow
    ? translate('Media-only full-screen mode')
    : translate('Standard layout');
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

  return `${escapeHtml(preset.imageFolder)} | ${escapeHtml(mode)} | ${escapeHtml(orderMode)} | ${escapeHtml(fullscreen)} | ${escapeHtml(displayName)} | ${escapeHtml(widgetSummary)} | ${preset.slideInterval}s`;
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
        <button class="button error" data-preset-delete="${preset.id}">${translate('Delete')}</button>
      </div>
    </div>
  `).join('');

  container.querySelectorAll('[data-preset-edit]').forEach((button) => {
    button.addEventListener('click', () => {
      const preset = presets.find((entry) => entry.id === parseInt(button.getAttribute('data-preset-edit'), 10));
      if (preset) {
        fillPresetForm(preset);
      }
    });
  });

  container.querySelectorAll('[data-preset-delete]').forEach((button) => {
    button.addEventListener('click', () => {
      deletePreset(parseInt(button.getAttribute('data-preset-delete'), 10));
    });
  });
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
  } catch (error) {
    console.error('Error loading presets:', error);
    const container = document.getElementById('presets-container');
    if (container) {
      container.innerHTML = `<p>${translate('Error loading presets')}: ${escapeHtml(error.message)}</p>`;
    }
  }
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
      throw new Error(result.error || translate('Error saving preset'));
    }

    if (!isUpdate) {
      resetPresetForm();
    }
    await loadPresets();
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
    button.textContent = translate('Copied!');
    setTimeout(() => {
      button.textContent = originalLabel;
    }, 2000);
  });
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
      <div class="token-item">
        <div class="token-info">
          <div class="token-name">${escapeHtml(token.name)}</div>
          <div class="token-meta">
            <div class="token-row">
              <span class="token-row-label">${translate('View URL')}</span>
              <span class="token-url">${escapeHtml(token.url)}</span>
            </div>
            <div class="token-row">
              <span class="token-row-label">${translate('Control token')}</span>
              <span class="token-url">${escapeHtml(token.controlToken || '')}</span>
            </div>
            <div class="token-row">
              <span class="token-row-label">${translate('Active preset')}</span>
              <select class="ds-input token-select" data-display-preset-select="${token.id}">
                ${renderPresetOptions(token.activePresetId)}
              </select>
              <button class="button" data-display-activate="${token.id}">${translate('Activate preset')}</button>
            </div>
          </div>
        </div>
        <div class="token-actions">
          <button class="primary" data-copy-url="${escapeHtml(token.url)}">${translate('Copy URL')}</button>
          <button class="primary" data-copy-control="${escapeHtml(token.controlToken || '')}">${translate('Copy control token')}</button>
          <button class="error" data-token-id="${token.id}">${translate('Delete')}</button>
        </div>
      </div>
    `).join('');

    container.querySelectorAll('[data-copy-url]').forEach((button) => {
      button.addEventListener('click', () => copyToClipboard(button, button.getAttribute('data-copy-url'), translate('Copy URL')));
    });

    container.querySelectorAll('[data-copy-control]').forEach((button) => {
      button.addEventListener('click', () => copyToClipboard(button, button.getAttribute('data-copy-control'), translate('Copy control token')));
    });

    container.querySelectorAll('[data-display-activate]').forEach((button) => {
      button.addEventListener('click', () => {
        const displayId = parseInt(button.getAttribute('data-display-activate'), 10);
        const select = container.querySelector(`[data-display-preset-select="${displayId}"]`);
        if (select) {
          activatePreset(displayId, parseInt(select.value, 10));
        }
      });
    });

    container.querySelectorAll('[data-token-id]').forEach((button) => {
      button.addEventListener('click', () => deleteToken(button.getAttribute('data-token-id')));
    });
  } catch (error) {
    console.error('Error loading displays:', error);
    document.getElementById('tokens-container').innerHTML = `<p>${translate('Error loading tokens')}: ${escapeHtml(error.message)}</p>`;
  }
}

async function createToken() {
  const name = document.getElementById('token-name').value.trim();
  if (!name) {
    alert(translate('Please enter a name for the token'));
    return;
  }

  try {
    const response = await fetchJson(API_URLS.create, {
      method: 'POST',
      body: JSON.stringify({ name })
    });
    const result = await parseJsonResponse(response);

    if (!response.ok || result.error) {
      throw new Error(result.error || translate('Error creating token'));
    }

    document.getElementById('token-name').value = '';
    await loadTokens();
  } catch (error) {
    console.error('Error creating display:', error);
    alert(`${translate('Error creating token')}: ${error.message}`);
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
    const select = document.getElementById('calendar_names');
    const currentValueStr = select.dataset.currentValue || '[]';
    let currentValues = [];

    try {
      currentValues = JSON.parse(currentValueStr);
    } catch (error) {
      console.error('Failed to parse calendar_names:', error);
    }

    select.innerHTML = calendars.map((calendar) =>
      `<option value="${calendar.displayName}" ${currentValues.includes(calendar.displayName) ? 'selected' : ''}>${calendar.displayName}</option>`
    ).join('');
  } catch (error) {
    console.error('Error loading calendars:', error);
    document.getElementById('calendar_name').innerHTML = `<option value="">${translate('Calendar loading error')}</option>`;
  }
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
    const eventTitlesUrl = OC.generateUrl('/apps/digitalsignage/api/event-titles');
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
    const calendarSelect = document.getElementById('calendar_names');
    const contentSplitRatioInput = document.getElementById('content_split_ratio');
    const selectedCalendars = Array.from(calendarSelect.selectedOptions).map((option) => option.value);
    const imageRefreshIntervalInput = document.getElementById('image_refresh_interval_minutes');
    const data = {
      display_name: document.getElementById('display_name').value,
      auto_fullscreen_prompt: document.getElementById('auto_fullscreen_prompt').checked ? '1' : '0',
      content_split_ratio: contentSplitRatioInput ? contentSplitRatioInput.value : '50',
      image_refresh_interval_minutes: String(parseInt(imageRefreshIntervalInput?.value || String(DEFAULT_IMAGE_REFRESH_INTERVAL_MINUTES), 10)),
      calendar_names: JSON.stringify(selectedCalendars),
      calendar_exclude: document.getElementById('calendar_exclude').value,
      color_primary: document.getElementById('color_primary').value,
      color_bg: document.getElementById('color_bg').value,
      color_text: document.getElementById('color_text').value,
      color_gradient_start: document.getElementById('color_gradient_start').value,
      color_gradient_end: document.getElementById('color_gradient_end').value,
      show_titlebar: '1',
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
  const hiddenInput = document.getElementById('calendar_exclude');
  const input = document.getElementById('calendar-exclude-input');
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

function renderExcludeTags() {
  const container = document.getElementById('calendar-exclude-tags');
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
  const hiddenInput = document.getElementById('calendar_exclude');
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
  document.getElementById('save-settings-btn')?.addEventListener('click', saveSettings);
  document.getElementById('reset-layout-btn')?.addEventListener('click', resetLayoutToDefaults);
  document.getElementById('reset-colors-btn')?.addEventListener('click', resetColorsToDefaults);
  document.getElementById('reset-text-sizes-btn')?.addEventListener('click', resetTextSizesToDefaults);
  document.getElementById('save-preset-btn')?.addEventListener('click', savePreset);
  document.getElementById('cancel-preset-edit-btn')?.addEventListener('click', resetPresetForm);

  const calendarSelect = document.getElementById('calendar_names');
  if (calendarSelect) {
    calendarSelect.addEventListener('change', loadEventTitles);
  }

  initExcludeTags();
  syncColorPickers();
  resetPresetForm();

  await Promise.all([loadCalendars(), loadFolders()]);
  await loadPresets();
  await loadTokens();
  setTimeout(loadEventTitles, 500);
});
