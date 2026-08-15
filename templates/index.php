          <!-- Bereich 'Show title bar' entfernt, da durch Display-Name-Option ersetzt -->
<!-- Farbsynchronisation jetzt in settings.js ausgelagert (CSP-konform) -->
<?php
style('digitalsignage', 'settings');
$l = $_['l10n'];
?>

<div id="app-content">
  <div id="app-content-wrapper" style="padding: 30px; max-width: 1400px; margin: 0 auto;">
    <div class="ds-stack">
      <div class="section ds-section">
        <h3 class="ds-section-title"><?php p($l->t('Digital Signage')); ?></h3>
        <p class="ds-section-subtitle"><?php p($l->t('Configure your digital display')); ?></p>

        <!-- General Settings -->
        <div class="ds-subsection">
          <h4 class="ds-subsection-title">⚙️ <?php p($l->t('General')); ?></h4>
          <div class="ds-form-grid">
            <div class="ds-form-group">
              <label for="display_name" class="ds-label"><?php p($l->t('Display Name')); ?></label>
              <input type="text" id="display_name" name="display_name" value="<?php p($_['display_name'] ?? $l->t('Digital Signage')); ?>" placeholder="<?php p($l->t('Digital Signage')); ?>" class="ds-input" />
              <span class="ds-hint"><?php p($l->t('Name shown on displays (visibility controlled per preset)')); ?></span>
            </div>
          </div>
          <div class="ds-form-grid">
            <div class="ds-form-group">
              <div class="ds-checkbox-row">
                <input type="checkbox" id="auto_fullscreen_prompt" name="auto_fullscreen_prompt" value="1" <?php if (isset($_['auto_fullscreen_prompt']) && $_['auto_fullscreen_prompt'] === '1') print 'checked'; ?> />
                <label for="auto_fullscreen_prompt" class="ds-label"><?php p($l->t('Auto-prompt for fullscreen')); ?></label>
              </div>
              <span class="ds-hint"><?php p($l->t('Automatically ask to enter fullscreen mode when opening the display')); ?></span>
            </div>
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
              <label for="calendar_exclude" class="ds-label"><?php p($l->t('Hide events')); ?></label>
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

        <div class="ds-subsection">
          <h4 class="ds-subsection-title">🎨 <?php p($l->t('Stylesheet')); ?></h4>

          <div class="ds-settings-group">
            <h5 class="ds-settings-group-title"><?php p($l->t('Layout')); ?></h5>
            <p class="ds-settings-group-subtitle"><?php p($l->t('Adjust column proportions for the display layout.')); ?></p>
            <div class="ds-form-grid">
              <div class="ds-form-group">
                <label for="content_split_ratio" class="ds-label"><?php p($l->t('Slideshow width percent')); ?></label>
                <input type="number" id="content_split_ratio" name="content_split_ratio" value="<?php p($_['content_split_ratio'] ?? '50'); ?>" min="50" max="85" step="1" class="ds-input" />
                <span class="ds-hint"><?php p($l->t('Width of the slideshow column. The remaining width is used for the calendar/weather column.')); ?></span>
              </div>
            </div>
            <div class="ds-subsection-actions ds-subsection-actions-end">
              <button type="button" id="reset-layout-btn" class="button ds-button-compact">
                <?php p($l->t('Reset to defaults')); ?>
              </button>
            </div>
          </div>

          <div class="ds-settings-group">
            <h5 class="ds-settings-group-title"><?php p($l->t('Colors')); ?></h5>
            <p class="ds-settings-group-subtitle"><?php p($l->t('Configure display colors. Gradient applies to title bar only.')); ?></p>
            <div class="ds-form-grid ds-form-grid-colors">
              <div class="ds-form-group ds-color-group">
                <label for="color_primary" class="ds-label"><?php p($l->t('Primary')); ?></label>
                <div class="ds-color-controls">
                  <input type="color" id="color_primary" name="color_primary" value="<?php p($_['color_primary'] ?? '#0066cc'); ?>" class="ds-input ds-color-picker" />
                  <input type="text" id="color_primary_hex" name="color_primary_hex" value="<?php p($_['color_primary'] ?? '#0066cc'); ?>" maxlength="7" class="ds-input ds-color-hex" />
                </div>
              </div>
              <div class="ds-form-group ds-color-group">
                <label for="color_bg" class="ds-label"><?php p($l->t('Background')); ?></label>
                <div class="ds-color-controls">
                  <input type="color" id="color_bg" name="color_bg" value="<?php p($_['color_bg'] ?? '#f8f9fa'); ?>" class="ds-input ds-color-picker" />
                  <input type="text" id="color_bg_hex" name="color_bg_hex" value="<?php p($_['color_bg'] ?? '#f8f9fa'); ?>" maxlength="7" class="ds-input ds-color-hex" />
                </div>
              </div>
              <div class="ds-form-group ds-color-group">
                <label for="color_text" class="ds-label"><?php p($l->t('Text')); ?></label>
                <div class="ds-color-controls">
                  <input type="color" id="color_text" name="color_text" value="<?php p($_['color_text'] ?? '#2c3e50'); ?>" class="ds-input ds-color-picker" />
                  <input type="text" id="color_text_hex" name="color_text_hex" value="<?php p($_['color_text'] ?? '#2c3e50'); ?>" maxlength="7" class="ds-input ds-color-hex" />
                </div>
              </div>
              <div class="ds-form-group ds-color-group">
                <label for="color_gradient_start" class="ds-label"><?php p($l->t('Gradient Start')); ?></label>
                <div class="ds-color-controls">
                  <input type="color" id="color_gradient_start" name="color_gradient_start" value="<?php p($_['color_gradient_start'] ?? '#0066cc'); ?>" class="ds-input ds-color-picker" />
                  <input type="text" id="color_gradient_start_hex" name="color_gradient_start_hex" value="<?php p($_['color_gradient_start'] ?? '#0066cc'); ?>" maxlength="7" class="ds-input ds-color-hex" />
                </div>
              </div>
              <div class="ds-form-group ds-color-group">
                <label for="color_gradient_end" class="ds-label"><?php p($l->t('Gradient End')); ?></label>
                <div class="ds-color-controls">
                  <input type="color" id="color_gradient_end" name="color_gradient_end" value="<?php p($_['color_gradient_end'] ?? '#3399ff'); ?>" class="ds-input ds-color-picker" />
                  <input type="text" id="color_gradient_end_hex" name="color_gradient_end_hex" value="<?php p($_['color_gradient_end'] ?? '#3399ff'); ?>" maxlength="7" class="ds-input ds-color-hex" />
                </div>
              </div>
            </div>
            <div class="ds-subsection-actions ds-subsection-actions-end">
              <button type="button" id="reset-colors-btn" class="button ds-button-compact">
                <?php p($l->t('Reset to defaults')); ?>
              </button>
            </div>
          </div>

          <div class="ds-settings-group">
            <h5 class="ds-settings-group-title"><?php p($l->t('Text sizes')); ?></h5>
            <p class="ds-settings-group-subtitle"><?php p($l->t('Set font sizes per text class using relative rem values.')); ?></p>
            <div class="ds-form-grid ds-form-grid-compact">
              <?php foreach (($_['text_size_fields'] ?? []) as $field): ?>
              <div class="ds-form-group">
                <label for="<?php p($field['configKey']); ?>" class="ds-label"><?php p($l->t($field['label'])); ?></label>
                <input type="number" id="<?php p($field['configKey']); ?>" name="<?php p($field['configKey']); ?>" value="<?php p($field['value']); ?>" placeholder="<?php p($field['default']); ?>" min="0.1" step="0.1" inputmode="decimal" class="ds-input" data-text-size-field="1" data-default-value="<?php p($field['default']); ?>" />
              </div>
              <?php endforeach; ?>
            </div>
            <div class="ds-subsection-actions ds-subsection-actions-end">
              <button type="button" id="reset-text-sizes-btn" class="button ds-button-compact">
                <?php p($l->t('Reset to defaults')); ?>
              </button>
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
            <label class="ds-label"><?php p($l->t('Control link')); ?></label>
            <a id="weather-map-link" class="button" href="https://www.openstreetmap.org/?mlat=<?php p($_['weather_latitude'] ?? '52.3758'); ?>&mlon=<?php p($_['weather_longitude'] ?? '9.9747'); ?>&zoom=14" target="_blank" rel="noopener">
              <?php p($l->t('Open OpenStreetMap with these coordinates')); ?>
            </a>
            <span class="ds-hint"><?php p($l->t('Opens in new tab.')); ?></span>
          </div>
        </div>

        <div class="ds-subsection">
          <h4 class="ds-subsection-title">🎬 <?php p($l->t('Media / Slideshow presets')); ?></h4>
          <span class="ds-hint" style="display: block; margin-bottom: 1rem;"><?php p($l->t('Manage media folders (images & videos), crop mode, fullscreen slideshow and interval per preset.')); ?></span>
          <div class="ds-form-grid">
            <input type="hidden" id="preset-id" value="" />
            <div class="ds-form-group">
              <label for="preset-name" class="ds-label"><?php p($l->t('Preset name')); ?></label>
              <input type="text" id="preset-name" placeholder="<?php p($l->t('Media / Slideshow preset name')); ?>" class="ds-input" />
            </div>

            <div class="ds-form-group">
              <label for="preset-image-folder" class="ds-label"><?php p($l->t('Media folder')); ?></label>
              <select id="preset-image-folder" class="ds-input">
                <option value=""><?php p($l->t('Folders are loading...')); ?></option>
              </select>
            </div>

            <div class="ds-form-group">
              <label for="preset-image-fit-mode" class="ds-label"><?php p($l->t('Crop mode')); ?></label>
              <select id="preset-image-fit-mode" class="ds-input">
                <option value="cover"><?php p($l->t('Fill (crop if needed)')); ?></option>
                <option value="contain"><?php p($l->t('Fit complete (with background)')); ?></option>
              </select>
            </div>

            <div class="ds-form-group">
              <label for="preset-image-order-mode" class="ds-label"><?php p($l->t('Playback order')); ?></label>
              <select id="preset-image-order-mode" class="ds-input">
                <option value="shuffle"><?php p($l->t('Shuffle')); ?></option>
                <option value="filename"><?php p($l->t('By filename')); ?></option>
              </select>
              <span class="ds-hint"><?php p($l->t('Shuffle mixes media files, By filename uses ascending filename order.')); ?></span>
            </div>

            <div class="ds-form-group">
              <label for="preset-slide-interval" class="ds-label"><?php p($l->t('Slide interval (seconds)')); ?></label>
              <input type="number" id="preset-slide-interval" value="10" min="5" max="300" class="ds-input" />
              <span class="ds-hint"><?php p($l->t('Duration per image. Videos play to completion automatically.')); ?></span>
            </div>

            <div class="ds-form-group">
              <div class="ds-checkbox-row">
                <input type="checkbox" id="preset-fullscreen-slideshow" value="1" />
                <label for="preset-fullscreen-slideshow" class="ds-label"><?php p($l->t('Fullscreen slideshow mode')); ?></label>
              </div>
              <span class="ds-hint"><?php p($l->t('Hide time, weather and calendar - show only media in fullscreen')); ?></span>
            </div>

            <div class="ds-form-group">
              <div class="ds-checkbox-row">
                <input type="checkbox" id="preset-show-display-name" value="1" checked />
                <label for="preset-show-display-name" class="ds-label"><?php p($l->t('Show display name in header')); ?></label>
              </div>
              <span class="ds-hint"><?php p($l->t('Display the configured display name at the top of the screen')); ?></span>
            </div>
          </div>
          <div class="ds-inline-actions">
            <button class="button primary" id="save-preset-btn"><?php p($l->t('Save preset')); ?></button>
            <button class="button" id="cancel-preset-edit-btn" style="display:none;"><?php p($l->t('Cancel edit')); ?></button>
          </div>
          <div id="presets-container" class="ds-tokens-list"><?php p($l->t('Loading...')); ?></div>
        </div>

      </div>

      <div class="section ds-section">
        <h3 class="ds-section-title">📺 <?php p($l->t('Displays')); ?></h3>

        <div class="ds-subsection">
          <h4 class="ds-subsection-title">➕ <?php p($l->t('Create new display')); ?></h4>
          <div class="ds-token-create">
            <input type="text" id="token-name" placeholder="<?php p($l->t('Internal display label (e.g. reception screen)')); ?>" class="ds-input" />
            <button class="button primary" id="create-token-btn"><?php p($l->t('Create display')); ?></button>
          </div>
          <span class="ds-hint"><?php p($l->t('Used only to distinguish displays in the admin UI.')); ?></span>
        </div>

        <div class="ds-subsection">
          <h4 class="ds-subsection-title">📋 <?php p($l->t('Existing displays')); ?></h4>
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
      data-list-url="<?php p($_['url_generator']->linkToRoute('digitalsignage.token.list')); ?>"
      data-create-url="<?php p($_['url_generator']->linkToRoute('digitalsignage.token.create')); ?>"
          data-activate-preset-url="<?php p($_['url_generator']->linkToRoute('digitalsignage.token.activatePreset', ['id' => 'DISPLAY_ID'])); ?>"
      data-delete-url="<?php p($_['url_generator']->linkToRoute('digitalsignage.token.delete', ['id' => 'TOKEN_ID'])); ?>"
          data-preset-list-url="<?php p($_['url_generator']->linkToRoute('digitalsignage.preset.list')); ?>"
          data-preset-create-url="<?php p($_['url_generator']->linkToRoute('digitalsignage.preset.create')); ?>"
          data-preset-update-url="<?php p($_['url_generator']->linkToRoute('digitalsignage.preset.update', ['id' => 'PRESET_ID'])); ?>"
          data-preset-delete-url="<?php p($_['url_generator']->linkToRoute('digitalsignage.preset.delete', ['id' => 'PRESET_ID'])); ?>"
      data-csrf-token="<?php p($_['requesttoken']); ?>">
  </div>

  <?php script('digitalsignage', 'settings'); ?>
