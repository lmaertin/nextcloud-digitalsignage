<div id="app-content">
<div id="app-content-wrapper">
  <style>
    #app-content {
      background: #f8f9fa;
      min-height: 100vh;
    }
    #app-content-wrapper {
      padding: 2rem;
      max-width: 1000px;
      margin: 0 auto;
    }
    .digitalsignage-container {
      background: var(--color-main-background);
      padding: 2rem;
      border-radius: var(--border-radius-large);
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .digitalsignage-container h2 {
      margin-bottom: 1.5rem;
      color: var(--color-main-text);
      font-size: 1.8rem;
      font-weight: 600;
    }
    .digitalsignage-container h3 {
      margin-bottom: 1rem;
      color: var(--color-main-text);
      font-size: 1.3rem;
      font-weight: 600;
    }
    .token-list {
      margin-top: 2rem;
    }
    .token-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem;
      margin-bottom: 0.5rem;
      background: var(--color-background-hover);
      border-radius: var(--border-radius);
      border: 1px solid var(--color-border);
    }
    .token-item strong {
      color: var(--color-main-text);
      font-weight: 600;
    }
    .token-url {
      font-size: 0.9rem;
      color: var(--color-text-maxcontrast);
      word-break: break-all;
      margin-top: 0.25rem;
      font-family: monospace;
    }
    .button {
      padding: 0.6rem 1.2rem;
      background: var(--color-primary);
      color: var(--color-primary-text);
      border: none;
      border-radius: var(--border-radius);
      cursor: pointer;
      font-weight: 600;
      transition: background 0.2s;
    }
    .button:hover {
      background: var(--color-primary-hover);
    }
    .button-delete {
      background: var(--color-error);
      color: white;
    }
    .button-delete:hover {
      background: #c82333;
    }
    input[type="text"] {
      width: 100%;
      padding: 0.6rem;
      border: 2px solid var(--color-border-dark);
      border-radius: var(--border-radius);
      margin-bottom: 1rem;
      background: var(--color-main-background);
      color: var(--color-main-text);
      font-size: 1rem;
    }
    input[type="text"]:focus {
      outline: none;
      border-color: var(--color-primary);
    }
    .create-form {
      margin-bottom: 2rem;
      padding: 1.5rem;
      background: var(--color-background-dark);
      border-radius: var(--border-radius-large);
      border: 1px solid var(--color-border);
    }
    .settings-section {
      margin-bottom: 2rem;
      padding: 1.5rem;
      background: var(--color-background-dark);
      border-radius: var(--border-radius-large);
      border: 1px solid var(--color-border);
    }
    .settings-row {
      margin-bottom: 1rem;
    }
    .settings-row label {
      display: block;
      margin-bottom: 0.3rem;
      color: var(--color-main-text);
      font-weight: 600;
    }
    .settings-row input[type="text"],
    .settings-row input[type="number"],
    .settings-row textarea {
      width: 100%;
      padding: 0.6rem;
      border: 2px solid var(--color-border-dark);
      border-radius: var(--border-radius);
      background: var(--color-main-background);
      color: var(--color-main-text);
      font-size: 1rem;
    }
    .settings-row textarea {
      min-height: 60px;
      font-family: monospace;
      resize: vertical;
    }
    .settings-hint {
      font-size: 0.85rem;
      color: var(--color-text-maxcontrast);
      margin-top: 0.2rem;
      margin-bottom: 0;
    }
    .subsection {
      margin-bottom: 1.5rem;
    }
  </style>
  <div class="digitalsignage-container">
  <div class="container">
    <h2>Digital Signage</h2>

    <div class="settings-section">
      <h3>⚙️ Display Settings</h3>

      <div class="subsection">
        <div class="settings-row">
          <label for="calendar_name">📅 Calendar Name:</label>
          <input type="text" id="calendar_name" name="calendar_name" value="<?php p($_['calendar_name'] ?? 'personal'); ?>" placeholder="personal" />
          <p class="settings-hint">Name of your Nextcloud calendar to display</p>
        </div>

        <div class="settings-row">
          <label for="image_folder">🖼️ Image Folder Path:</label>
          <input type="text" id="image_folder" name="image_folder" value="<?php p($_['image_folder'] ?? '/Fotos'); ?>" placeholder="/Fotos/Info-Monitor" />
          <p class="settings-hint">Path to image folder in your Nextcloud (e.g. /Fotos/Info-Monitor)</p>
        </div>
      </div>

      <div class="subsection">
        <div class="settings-row">
          <label for="slide_interval">⏱️ Slide Interval (seconds):</label>
          <input type="number" id="slide_interval" name="slide_interval" value="<?php p($_['slide_interval'] ?? '10'); ?>" min="5" max="300" />
          <p class="settings-hint">How long each image is displayed (5-300 seconds)</p>
        </div>

        <div class="settings-row">
          <label for="weather_latitude">🌦️ Weather Location - Latitude:</label>
          <input type="text" id="weather_latitude" name="weather_latitude" value="<?php p($_['weather_latitude'] ?? '52.3758'); ?>" placeholder="52.3758" />
        </div>

        <div class="settings-row">
          <label for="weather_longitude">🌦️ Weather Location - Longitude:</label>
          <input type="text" id="weather_longitude" name="weather_longitude" value="<?php p($_['weather_longitude'] ?? '9.9747'); ?>" placeholder="9.9747" />
          <p class="settings-hint">Coordinates for weather display (uses Open-Meteo API)</p>
        </div>

        <div class="settings-row">
          <label for="calendar_exclude">🚫 Exclude Events (JSON):</label>
          <textarea id="calendar_exclude" name="calendar_exclude" placeholder='["restmülltonne","Gottesdienst"]'><?php p($_['calendar_exclude'] ?? '[]'); ?></textarea>
          <p class="settings-hint">Event titles to hide (case-insensitive, JSON array)</p>
        </div>
      </div>

      <button class="button" id="save-settings-btn">💾 Save Settings</button>
      <span id="settings-msg" style="margin-left: 1rem; font-weight: 600;"></span>
    </div>

    <div class="create-form">
      <h3>🔑 Create New Display Token</h3>
      <input type="text" id="token-name" placeholder="Display name (e.g., Main Hall Monitor)" />
      <button class="button" id="create-token-btn">Create Token</button>
    </div>

    <div class="token-list">
      <h3>📺 Existing Tokens</h3>
      <div id="tokens-container">Loading...</div>
    </div>
  </div>

  <!-- Data attributes for JavaScript -->
  <div style="display:none;"
       data-list-url="<?php p(\OC::$server->getURLGenerator()->linkToRoute('digitalsignage.token.list')); ?>"
       data-create-url="<?php p(\OC::$server->getURLGenerator()->linkToRoute('digitalsignage.token.create')); ?>"
       data-delete-url="<?php p(\OC::$server->getURLGenerator()->linkToRoute('digitalsignage.token.delete', ['id' => 'TOKEN_ID'])); ?>"
       data-csrf-token="<?php p(\OC::$server->getCSRFTokenManager()->getToken()->getEncryptedValue()); ?>">
  </div>

    <div id="ds-i18n" style="display:none;"
      data-copy="<?php p($l->t('Copy')); ?>"
      data-copy-url="<?php p($l->t('Copy URL')); ?>"
      data-delete="<?php p($l->t('Delete')); ?>"
      data-delete-token="<?php p($l->t('Delete token')); ?>"
      data-copied="<?php p($l->t('Copied!')); ?>"
      data-no-tokens="<?php p($l->t('No tokens yet')); ?>"
      data-error-loading-tokens="<?php p($l->t('Error loading tokens')); ?>"
      data-please-enter-name="<?php p($l->t('Please enter a name for the token')); ?>"
      data-token-created="<?php p($l->t('Token created successfully! URL: %s', [ '{url}' ])); ?>"
      data-error-creating-token="<?php p($l->t('Error creating token')); ?>"
      data-error-deleting-token="<?php p($l->t('Error deleting token')); ?>"
      data-confirm-delete="<?php p($l->t('Are you sure you want to delete this token?')); ?>"
      data-select-folder="<?php p($l->t('Select folder')); ?>"
      data-no-exclude-terms="<?php p($l->t('No exclude terms yet')); ?>"
      data-error-loading-folders="<?php p($l->t('Error loading folders')); ?>"
      data-settings-saved="<?php p($l->t('Settings saved successfully')); ?>"
      data-settings-error="<?php p($l->t('Error saving settings')); ?>">
    </div>

  <?php script('digitalsignage', 'token-management'); ?>
  </div>
</div>
</div>
