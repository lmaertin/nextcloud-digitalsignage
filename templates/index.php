          <!-- Bereich 'Show title bar' entfernt, da durch Display-Name-Option ersetzt -->
<!-- Farbsynchronisation jetzt in settings.js ausgelagert (CSP-konform) -->
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

        <!-- General Settings -->
        <div class="ds-subsection">
          <h4 class="ds-subsection-title">⚙️ <?php p($l->t('General')); ?></h4>
          <div class="ds-form-grid">
            <div class="ds-form-group">
              <label for="display_name" class="ds-label">📺 <?php p($l->t('Display Name')); ?></label>
              <div style="display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" id="show_display_name" name="show_display_name" value="1" <?php if (!isset($_['show_display_name']) || $_['show_display_name'] === '1') print 'checked'; ?> />
                <input type="text" id="display_name" name="display_name" value="<?php p($_['display_name'] ?? $l->t('Digital Signage')); ?>" placeholder="<?php p($l->t('Digital Signage')); ?>" class="ds-input" />
              </div>
              <span class="ds-hint"><?php p($l->t('Name for this display (shown on display). Uncheck to hide.')); ?></span>
            </div>
          </div>
          <div class="ds-form-grid">
            <div class="ds-form-group">
              <div style="display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" id="auto_fullscreen_prompt" name="auto_fullscreen_prompt" value="1" <?php if (isset($_['auto_fullscreen_prompt']) && $_['auto_fullscreen_prompt'] === '1') print 'checked'; ?> />
                <label for="auto_fullscreen_prompt" class="ds-label" style="margin: 0;"><?php p($l->t('Auto-prompt for fullscreen')); ?></label>
              </div>
              <span class="ds-hint"><?php p($l->t('Automatically ask to enter fullscreen mode when opening the display')); ?></span>
            </div>
          </div>
          <div class="ds-form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <div class="ds-form-group" style="display: flex; flex-direction: column; gap: 0.3rem;">
              <label for="color_primary" class="ds-label" style="font-size: 0.9rem;"><?php p($l->t('Primary')); ?></label>
              <div style="display: flex; gap: 0.5rem; align-items: center;">
                <input type="color" id="color_primary" name="color_primary" value="<?php p($_['color_primary'] ?? '#0066cc'); ?>" class="ds-input" style="width: 50px; height: 35px; padding: 2px;" />
                <input type="text" id="color_primary_hex" name="color_primary_hex" value="<?php p($_['color_primary'] ?? '#0066cc'); ?>" maxlength="7" class="ds-input" style="width: 80px; font-size: 0.85rem;" />
              </div>
            </div>
            <div class="ds-form-group" style="display: flex; flex-direction: column; gap: 0.3rem;">
              <label for="color_bg" class="ds-label" style="font-size: 0.9rem;"><?php p($l->t('Background')); ?></label>
              <div style="display: flex; gap: 0.5rem; align-items: center;">
                <input type="color" id="color_bg" name="color_bg" value="<?php p($_['color_bg'] ?? '#f8f9fa'); ?>" class="ds-input" style="width: 50px; height: 35px; padding: 2px;" />
                <input type="text" id="color_bg_hex" name="color_bg_hex" value="<?php p($_['color_bg'] ?? '#f8f9fa'); ?>" maxlength="7" class="ds-input" style="width: 80px; font-size: 0.85rem;" />
              </div>
            </div>
            <div class="ds-form-group" style="display: flex; flex-direction: column; gap: 0.3rem;">
              <label for="color_text" class="ds-label" style="font-size: 0.9rem;"><?php p($l->t('Text')); ?></label>
              <div style="display: flex; gap: 0.5rem; align-items: center;">
                <input type="color" id="color_text" name="color_text" value="<?php p($_['color_text'] ?? '#2c3e50'); ?>" class="ds-input" style="width: 50px; height: 35px; padding: 2px;" />
                <input type="text" id="color_text_hex" name="color_text_hex" value="<?php p($_['color_text'] ?? '#2c3e50'); ?>" maxlength="7" class="ds-input" style="width: 80px; font-size: 0.85rem;" />
              </div>
            </div>
            <div class="ds-form-group" style="display: flex; flex-direction: column; gap: 0.3rem;">
              <label for="color_gradient_start" class="ds-label" style="font-size: 0.9rem;"><?php p($l->t('Gradient Start')); ?></label>
              <div style="display: flex; gap: 0.5rem; align-items: center;">
                <input type="color" id="color_gradient_start" name="color_gradient_start" value="<?php p($_['color_gradient_start'] ?? '#0066cc'); ?>" class="ds-input" style="width: 50px; height: 35px; padding: 2px;" />
                <input type="text" id="color_gradient_start_hex" name="color_gradient_start_hex" value="<?php p($_['color_gradient_start'] ?? '#0066cc'); ?>" maxlength="7" class="ds-input" style="width: 80px; font-size: 0.85rem;" />
              </div>
            </div>
            <div class="ds-form-group" style="display: flex; flex-direction: column; gap: 0.3rem;">
              <label for="color_gradient_end" class="ds-label" style="font-size: 0.9rem;"><?php p($l->t('Gradient End')); ?></label>
              <div style="display: flex; gap: 0.5rem; align-items: center;">
                <input type="color" id="color_gradient_end" name="color_gradient_end" value="<?php p($_['color_gradient_end'] ?? '#3399ff'); ?>" class="ds-input" style="width: 50px; height: 35px; padding: 2px;" />
                <input type="text" id="color_gradient_end_hex" name="color_gradient_end_hex" value="<?php p($_['color_gradient_end'] ?? '#3399ff'); ?>" maxlength="7" class="ds-input" style="width: 80px; font-size: 0.85rem;" />
              </div>
            </div>
          </div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-top: -0.5rem;">
            <span class="ds-hint"><?php p($l->t('Configure display colors. Gradient applies to title bar only.')); ?></span>
            <button type="button" id="reset-colors-btn" class="button" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
              <?php p($l->t('Reset to defaults')); ?>
            </button>
          </div>
        </div>

        <!-- Calendar -->
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
                <!-- Tags will be inserted here -->
              </div>
              <div class="ds-tag-input-group">
                <input type="text" id="calendar-exclude-input" list="event-titles-list" placeholder="<?php p($l->t('Enter term and press Enter')); ?>" class="ds-input" />
                <datalist id="event-titles-list"></datalist>
                <button type="button" id="add-exclude-btn" class="button"><?php p($l->t('+ Add')); ?></button>
              </div>
              <input type="hidden" id="calendar_exclude" name="calendar_exclude" value="<?php p($_['calendar_exclude'] ?? '[]'); ?>" />
              <span class="ds-hint"><?php p($l->t('Events containing these terms will be hidden')); ?></span>
            </div>
          </div>
        </div>

        <!-- Images / Slideshow -->
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
              <label for="slide_interval" class="ds-label"><?php p($l->t('Slide interval (seconds)')); ?></label>
              <input type="number" id="slide_interval" name="slide_interval" value="<?php p($_['slide_interval'] ?? '10'); ?>" min="5" max="300" class="ds-input" />
              <span class="ds-hint"><?php p($l->t('How long each image is shown (5-300 seconds)')); ?></span>
            </div>

            <div class="ds-form-group">
              <label for="image_fit_mode" class="ds-label"><?php p($l->t('Crop mode')); ?></label>
              <select id="image_fit_mode" name="image_fit_mode" class="ds-input">
                <option value="cover" <?php if (!isset($_['image_fit_mode']) || $_['image_fit_mode'] === 'cover') print 'selected'; ?>><?php p($l->t('Fill (crop if needed)')); ?></option>
                <option value="contain" <?php if (isset($_['image_fit_mode']) && $_['image_fit_mode'] === 'contain') print 'selected'; ?>><?php p($l->t('Fit complete (with background)')); ?></option>
              </select>
              <span class="ds-hint"><?php p($l->t('Fill crops images to fill screen, Fit shows complete image')); ?></span>
            </div>

            <div class="ds-form-group">
              <label for="text_scale" class="ds-label"><?php p($l->t('Text scale')); ?></label>
              <input type="number" id="text_scale" name="text_scale" value="<?php p($_['text_scale'] ?? '1.0'); ?>" min="0.5" max="3.0" step="0.1" class="ds-input" />
              <span class="ds-hint"><?php p($l->t('Text scale factor (0.5 - 3.0, default: 1.0)')); ?></span>
            </div>

            <div class="ds-form-group">
              <div style="display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" id="fullscreen_slideshow" name="fullscreen_slideshow" value="1" <?php if (isset($_['fullscreen_slideshow']) && $_['fullscreen_slideshow'] === '1') print 'checked'; ?> />
                <label for="fullscreen_slideshow" class="ds-label" style="margin: 0;"><?php p($l->t('Fullscreen slideshow mode')); ?></label>
              </div>
              <span class="ds-hint"><?php p($l->t('Hide time, weather and calendar - show only images in fullscreen')); ?></span>
            </div>
          </div>
        </div>

        <!-- Weather -->
        <div class="ds-subsection">
          <h4 class="ds-subsection-title">🌦️ <?php p($l->t('Weather')); ?></h4>
          <span class="ds-hint" style="display: block; margin-bottom: 1rem;"><?php p($l->t('Coordinates for weather display (uses Open-Meteo API)')); ?></span>
          <div class="ds-form-grid">
            <div class="ds-form-group">
              <label for="weather_latitude" class="ds-label"><?php p($l->t('Latitude')); ?></label>
              <input type="text" id="weather_latitude" name="weather_latitude" value="<?php p($_['weather_latitude'] ?? '52.3758'); ?>" placeholder="52.3758" class="ds-input" />
            </div>

            <div class="ds-form-group">
              <label for="weather_longitude" class="ds-label"><?php p($l->t('Longitude')); ?></label>
              <input type="text" id="weather_longitude" name="weather_longitude" value="<?php p($_['weather_longitude'] ?? '9.9747'); ?>" placeholder="9.9747" class="ds-input" />
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

        <!-- Save bar at the end -->
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

  <?php script('digitalsignage', 'settings'); ?>
