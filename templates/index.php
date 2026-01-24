<?php
style('digitalsignage', 'settings');
$l = \OC::$server->getL10N('digitalsignage');
?>

<div id="app-content">
  <div id="app-content-wrapper" style="padding: 30px; max-width: 1400px; margin: 0 auto;">
    <div class="ds-stack">
      <div class="section ds-section">
        <h3 class="ds-section-title">📺 <?php p($l->t('Digital Signage')); ?></h3>
        <p class="ds-section-subtitle"><?php p($l->t('Configure your digital display')); ?></p>

        <!-- Allgemeine Einstellungen -->
        <div class="ds-subsection">
          <h4 class="ds-subsection-title">⚙️ <?php p($l->t('General')); ?></h4>
          <div class="ds-form-grid">
            <div class="ds-form-group">
              <label for="display_name" class="ds-label">📺 <?php p($l->t('Display Name')); ?></label>
              <input type="text" id="display_name" name="display_name" value="<?php p($_['display_name'] ?? $l->t('Digital Signage')); ?>" placeholder="<?php p($l->t('Digital Signage')); ?>" class="ds-input" />
              <span class="ds-hint"><?php p($l->t('Name for this display (shown on display)')); ?></span>
            </div>
          </div>
        </div>

        <!-- Kalender -->
        <div class="ds-subsection">
          <h4 class="ds-subsection-title">📅 <?php p($l->t('Calendar')); ?></h4>
          <div class="ds-form-grid">
            <div class="ds-form-group ds-form-group-full">
              <label for="calendar_names" class="ds-label"><?php p($l->t('Calendar sources')); ?></label>
              <select id="calendar_names" name="calendar_names" multiple class="ds-input ds-multiselect" data-current-value="<?php p($_['calendar_names'] ?? '[]'); ?>">
                <option value=""><?php p($l->t('Loading calendars...')); ?></option>
              </select>
              <span class="ds-hint"><?php p($l->t('Hold Ctrl/Cmd for multiple selection')); ?></span>
            </div>

            <div class="ds-form-group ds-form-group-full">
              <label for="calendar_exclude" class="ds-label">🚫 <?php p($l->t('Hide events')); ?></label>
              <div id="calendar-exclude-tags" class="ds-tag-container">
                <!-- Tags werden hier eingefügt -->
              </div>
              <div class="ds-tag-input-group">
                <input type="text" id="calendar-exclude-input" placeholder="<?php p($l->t('Enter term and press Enter')); ?>" class="ds-input" />
                <button type="button" id="add-exclude-btn" class="button"><?php p($l->t('+ Add')); ?></button>
              </div>
              <input type="hidden" id="calendar_exclude" name="calendar_exclude" value="<?php p($_['calendar_exclude'] ?? '[]'); ?>" />
              <span class="ds-hint"><?php p($l->t('Terms that should not be displayed in event titles')); ?></span>
            </div>
          </div>
        </div>

        <!-- Bilder/Slideshow -->
        <div class="ds-subsection">
          <h4 class="ds-subsection-title">🖼️ <?php p($l->t('Images / Slideshow')); ?></h4>
          <div class="ds-form-grid">
            <div class="ds-form-group">
              <label for="image_folder" class="ds-label"><?php p($l->t('Image folder')); ?></label>
              <select id="image_folder" name="image_folder" class="ds-input" data-current-value="<?php p($_['image_folder'] ?? ''); ?>">
                <option value=""><?php p($l->t('Folders are loading...')); ?></option>
              </select>
              <span class="ds-hint"><?php p($l->t('Path to the image folder in your Nextcloud')); ?></span>
            </div>

            <div class="ds-form-group">
              <label for="slide_interval" class="ds-label">⏱️ <?php p($l->t('Slide interval (seconds)')); ?></label>
              <input type="number" id="slide_interval" name="slide_interval" value="<?php p($_['slide_interval'] ?? '10'); ?>" min="5" max="300" class="ds-input" />
              <span class="ds-hint"><?php p($l->t('How long each image is shown (5-300 seconds)')); ?></span>
            </div>
          </div>
        </div>

        <!-- Wetter -->
        <div class="ds-subsection">
          <h4 class="ds-subsection-title">🌦️ <?php p($l->t('Weather')); ?></h4>
          <div class="ds-form-grid">
            <div class="ds-form-group">
              <label for="weather_latitude" class="ds-label"><?php p($l->t('Latitude')); ?></label>
              <input type="text" id="weather_latitude" name="weather_latitude" value="<?php p($_['weather_latitude'] ?? '52.3758'); ?>" placeholder="52.3758" class="ds-input" />
            </div>

            <div class="ds-form-group">
              <label for="weather_longitude" class="ds-label"><?php p($l->t('Longitude')); ?></label>
              <input type="text" id="weather_longitude" name="weather_longitude" value="<?php p($_['weather_longitude'] ?? '9.9747'); ?>" placeholder="9.9747" class="ds-input" />
              <span class="ds-hint"><?php p($l->t('Coordinates for weather display (uses Open-Meteo API)')); ?></span>
            </div>
          </div>

          <div class="ds-form-group ds-form-group-full">
            <label class="ds-label">📍 <?php p($l->t('Control link')); ?></label>
            <a id="weather-map-link" class="button" href="https://www.openstreetmap.org/?mlat=<?php p($_['weather_latitude'] ?? '52.3758'); ?>&mlon=<?php p($_['weather_longitude'] ?? '9.9747'); ?>&zoom=14" target="_blank" rel="noopener">
              <?php p($l->t('Open OpenStreetMap with these coordinates')); ?>
            </a>
            <span class="ds-hint"><?php p($l->t('Opens in new tab.')); ?></span>
          </div>
        </div>

      </div>

      <div class="section ds-section">
        <h3 class="ds-section-title">🔑 <?php p($l->t('Tokens')); ?></h3>

        <div class="ds-subsection">
          <h4 class="ds-subsection-title"><?php p($l->t('Create new display token')); ?></h4>
          <div class="ds-token-create">
            <input type="text" id="token-name" placeholder="<?php p($l->t('Display name (e.g. reception screen)')); ?>" class="ds-input" />
            <button class="button primary" id="create-token-btn"><?php p($l->t('Create token')); ?></button>
          </div>
        </div>

        <div class="ds-subsection">
          <h4 class="ds-subsection-title"><?php p($l->t('Existing tokens')); ?></h4>
          <div id="tokens-container" class="ds-tokens-list"><?php p($l->t('Loading...')); ?></div>
        </div>

        <!-- Save Bar am Ende -->
        <div class="ds-save-bar">
          <button class="button primary" id="save-settings-btn"><?php p($l->t('Save')); ?></button>
          <span id="settings-msg" class="ds-message"></span>
        </div>
      </div>
    </div>
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

  <?php script('digitalsignage', 'settings'); ?>
    </div>
  </div>
</div>
