const translationsElement = document.getElementById('ds-i18n');
const translate = (text, params = []) => {
  if (translationsElement) {
    const key = text.toLowerCase().replace(/\s+/g, '-');
    const mapped = translationsElement.dataset[key] || translationsElement.dataset[text.replace(/[^a-zA-Z0-9]/g, '').toLowerCase()] || translationsElement.dataset[text];
    if (mapped) {
      if (Array.isArray(params) && params.length > 0) {
        return mapped.replace('%s', params[0]).replace('{url}', params[0]);
      }
      return mapped;
    }
  }
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
        <div>
          <strong>${escapeHtml(token.name)}</strong><br>
          <span class="token-url">${escapeHtml(token.url)}</span>
        </div>
        <button class="button button-delete" data-token-id="${token.id}">${translate('Delete')}</button>
      </div>
    `).join('');

    // Add event listeners to delete buttons
    container.querySelectorAll('.button-delete').forEach(btn => {
      btn.addEventListener('click', function() {
        deleteToken(this.getAttribute('data-token-id'));
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
      alert(translate('Token created successfully! URL: %s', [result.url]));
      document.getElementById('token-name').value = '';
      loadTokens();
    }
  } catch (error) {
    console.error('Error creating token:', error);
    alert(`${translate('Error creating token')}: ${error.message}`);
  }
}

async function deleteToken(id) {
  if (!confirm(translate('Are you sure you want to delete this token?'))) {
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

  // Load tokens
  loadTokens();
});

async function saveSettings() {
  const msgSpan = document.getElementById('settings-msg');

  try {
    const data = {
      calendar_name: document.getElementById('calendar_name').value,
      image_folder: document.getElementById('image_folder').value,
      slide_interval: document.getElementById('slide_interval').value,
      weather_latitude: document.getElementById('weather_latitude').value,
      weather_longitude: document.getElementById('weather_longitude').value,
      calendar_exclude: document.getElementById('calendar_exclude').value
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
      msgSpan.textContent = '✓ Einstellungen gespeichert';
      msgSpan.style.color = 'green';
      setTimeout(() => { msgSpan.textContent = ''; }, 3000);
    } else {
      throw new Error('Fehler beim Speichern');
    }
  } catch (error) {
    console.error('Error saving settings:', error);
    msgSpan.textContent = '✗ Fehler beim Speichern';
    msgSpan.style.color = 'red';
  }
}
