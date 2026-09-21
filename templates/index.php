          <!-- Bereich 'Show title bar' entfernt, da durch Display-Name-Option ersetzt -->
<!-- Farbsynchronisation jetzt in settings.js ausgelagert (CSP-konform) -->
<?php
$l = $_['l10n'];
$assetVersion = static fn (string $relativePath): string => (string)@filemtime(__DIR__ . '/../' . $relativePath) ?: '0';
?>

<link rel="stylesheet" href="<?php p($_['url_generator']->linkTo('digitalsignage', 'css/settings.css')); ?>?v=<?php p($assetVersion('css/settings.css')); ?>" />

<div id="app-content">
  <div id="app-content-wrapper" class="ds-page-shell">
    <div class="ds-stack">
      <div class="section ds-section">
        <h3 class="ds-section-title"><?php p($l->t('Digital Signage')); ?></h3>
        <p class="ds-section-subtitle"><?php p($l->t('Configure your digital display')); ?></p>

        <!-- General Settings -->
        <div class="ds-subsection">
          <h4 class="ds-subsection-title"><?php p($l->t('General')); ?></h4>
          <div class="ds-form-grid">
            <div class="ds-form-group">
              <div class="ds-checkbox-row">
                <input type="checkbox" id="auto_fullscreen_prompt" name="auto_fullscreen_prompt" value="1" <?php if (isset($_['auto_fullscreen_prompt']) && $_['auto_fullscreen_prompt'] === '1') print 'checked'; ?> />
                <label for="auto_fullscreen_prompt" class="ds-label"><?php p($l->t('Auto-prompt for fullscreen')); ?></label>
              </div>
              <span class="ds-hint"><?php p($l->t('Automatically ask to enter fullscreen mode when opening the display')); ?></span>
            </div>
            <div class="ds-form-group">
              <label for="image_refresh_interval_minutes" class="ds-label"><?php p($l->t('Image refresh interval (minutes)')); ?></label>
              <input type="number" id="image_refresh_interval_minutes" name="image_refresh_interval_minutes" value="<?php p($_['image_refresh_interval_minutes'] ?? '15'); ?>" min="0" step="1" class="ds-input" />
              <span class="ds-hint"><?php p($l->t('Refreshes the slideshow when the media folder changes. Set 0 to disable polling.')); ?></span>
            </div>
          </div>
        </div>

        <div class="ds-subsection">
          <h4 class="ds-subsection-title"><?php p($l->t('Stylesheet')); ?></h4>

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

          <div class="ds-settings-group">
            <h5 class="ds-settings-group-title"><?php p($l->t('Instant message styling')); ?></h5>
            <p class="ds-settings-group-subtitle"><?php p($l->t('Configure how the overlay for instant messages (sent via the control API) is displayed on all screens.')); ?></p>
            <div class="ds-form-grid ds-form-grid-compact">
              <div class="ds-form-group ds-color-group">
                <label for="message_bg_color" class="ds-label"><?php p($l->t('Background')); ?></label>
                <div class="ds-color-controls">
                  <input type="color" id="message_bg_color" name="message_bg_color" value="<?php p($_['message_bg_color'] ?? '#0066cc'); ?>" class="ds-input ds-color-picker" />
                  <input type="text" id="message_bg_color_hex" name="message_bg_color_hex" value="<?php p($_['message_bg_color'] ?? '#0066cc'); ?>" maxlength="7" class="ds-input ds-color-hex" />
                </div>
              </div>
              <div class="ds-form-group">
                <label for="message_bg_opacity" class="ds-label"><?php p($l->t('Background opacity')); ?> <span id="message_bg_opacity_value" class="ds-hint"><?php p($_['message_bg_opacity'] ?? '50'); ?>%</span></label>
                <input type="range" id="message_bg_opacity" value="<?php p($_['message_bg_opacity'] ?? '50'); ?>" min="0" max="100" step="1" class="ds-input ds-range" />
              </div>
              <div class="ds-form-group ds-color-group">
                <label for="message_text_color" class="ds-label"><?php p($l->t('Text color')); ?></label>
                <div class="ds-color-controls">
                  <input type="color" id="message_text_color" name="message_text_color" value="<?php p($_['message_text_color'] ?? '#ffffff'); ?>" class="ds-input ds-color-picker" />
                  <input type="text" id="message_text_color_hex" name="message_text_color_hex" value="<?php p($_['message_text_color'] ?? '#ffffff'); ?>" maxlength="7" class="ds-input ds-color-hex" />
                </div>
              </div>
              <div class="ds-form-group">
                <label for="message_font_size" class="ds-label"><?php p($l->t('Font size (rem)')); ?></label>
                <input type="number" id="message_font_size" value="<?php p($_['message_font_size'] ?? '1.0'); ?>" min="0.5" max="4" step="0.1" inputmode="decimal" class="ds-input" />
              </div>
              <div class="ds-form-group">
                <label for="message_width_percent" class="ds-label"><?php p($l->t('Width (percent of screen)')); ?></label>
                <input type="number" id="message_width_percent" value="<?php p($_['message_width_percent'] ?? '88'); ?>" min="20" max="100" step="1" class="ds-input" />
              </div>
              <div class="ds-form-group">
                <label for="message_position" class="ds-label"><?php p($l->t('Position')); ?></label>
                <select id="message_position" class="ds-input">
                  <option value="top" <?php if (($_['message_position'] ?? 'top') === 'top') p('selected'); ?>><?php p($l->t('Top')); ?></option>
                  <option value="middle" <?php if (($_['message_position'] ?? 'top') === 'middle') p('selected'); ?>><?php p($l->t('Middle')); ?></option>
                  <option value="bottom" <?php if (($_['message_position'] ?? 'top') === 'bottom') p('selected'); ?>><?php p($l->t('Bottom')); ?></option>
                </select>
              </div>
            </div>
            <div class="ds-message-preview-screen" id="message-style-preview-screen">
              <div class="ds-message-preview-bubble" id="message-style-preview-bubble"><?php p($l->t('Live preview: the meeting room is reserved until 16:00.')); ?></div>
            </div>
            <div class="ds-subsection-actions ds-subsection-actions-end">
              <button type="button" id="reset-message-style-btn" class="button ds-button-compact">
                <?php p($l->t('Reset to defaults')); ?>
              </button>
            </div>
          </div>
        </div>

        <div class="ds-subsection ds-preset-section">
          <h4 class="ds-subsection-title"><?php p($l->t('Media / Slideshow presets')); ?></h4>
          <span class="ds-hint ds-section-hint"><?php p($l->t('Configure media, calendar, display and widget behavior for each preset.')); ?></span>
          <div class="ds-preset-list-heading"><?php p($l->t('Existing presets')); ?></div>
          <div id="presets-container" class="ds-tokens-list"><?php p($l->t('Loading...')); ?></div>
          <div class="ds-subsection ds-preset-create-section">
            <button class="button primary" id="new-preset-btn"><?php p($l->t('New preset')); ?></button>
          </div>
          <div class="ds-preset-editor ds-object-editor" id="preset-editor" hidden>
            <div class="ds-form-grid ds-form-grid-compact">
            <input type="hidden" id="preset-id" value="" />
            <div class="ds-preset-group-title"><?php p($l->t('Basics')); ?></div>
            <div class="ds-form-group">
              <label for="preset-name" class="ds-label"><?php p($l->t('Preset name')); ?></label>
              <input type="text" id="preset-name" placeholder="<?php p($l->t('Media / Slideshow preset name')); ?>" class="ds-input" />
            </div>

            <div class="ds-preset-group-title"><?php p($l->t('Media')); ?></div>
            <div class="ds-form-group">
              <label for="preset-image-folder" class="ds-label"><?php p($l->t('Media folder')); ?></label>
              <select id="preset-image-folder" class="ds-input">
                <option value=""><?php p($l->t('Folders are loading...')); ?></option>
              </select>
            </div>

            <div class="ds-form-group">
              <div class="ds-checkbox-row">
                <input type="checkbox" id="preset-recursive-media" value="1" />
                <label for="preset-recursive-media" class="ds-label"><?php p($l->t('Include media from subfolders')); ?></label>
              </div>
              <span class="ds-hint"><?php p($l->t('Also search supported media files in subfolders of the selected media folder.')); ?></span>
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
              <label for="preset-image-fit-mode" class="ds-label"><?php p($l->t('Crop mode')); ?></label>
              <select id="preset-image-fit-mode" class="ds-input">
                <option value="cover"><?php p($l->t('Fill (crop if needed)')); ?></option>
                <option value="contain"><?php p($l->t('Fit complete (with background)')); ?></option>
              </select>
            </div>

            <div class="ds-preset-group-title"><?php p($l->t('Display behavior')); ?></div>
            <div class="ds-form-group">
              <label for="preset-header-title-source" class="ds-label"><?php p($l->t('Header title')); ?></label>
              <select id="preset-header-title-source" class="ds-input">
                <option value="global"><?php p($l->t('Display name')); ?></option>
                <option value="preset"><?php p($l->t('Preset name')); ?></option>
                <option value="none"><?php p($l->t('No title')); ?></option>
              </select>
              <span class="ds-hint"><?php p($l->t('Choose which title is shown in the display header for this preset')); ?></span>
            </div>

            <div class="ds-preset-group-title"><?php p($l->t('Calendar')); ?></div>
            <div class="ds-form-group ds-preset-calendar-field">
              <label for="preset-calendar-names" class="ds-label"><?php p($l->t('Calendar sources')); ?></label>
              <select id="preset-calendar-names" multiple class="ds-input ds-multiselect">
                <option value=""><?php p($l->t('Loading calendars...')); ?></option>
              </select>
              <span class="ds-hint"><?php p($l->t('Select the calendars shown when this preset is active.')); ?></span>
            </div>

            <div class="ds-form-group ds-preset-calendar-field">
              <label for="preset-calendar-exclude-input" class="ds-label"><?php p($l->t('Hide events')); ?></label>
              <div class="ds-tag-input-group">
                <input type="text" id="preset-calendar-exclude-input" list="event-titles-list" placeholder="<?php p($l->t('Enter term and press Enter')); ?>" class="ds-input" />
                <datalist id="event-titles-list"></datalist>
                <button type="button" id="add-exclude-btn" class="button"><?php p($l->t('+ Add')); ?></button>
              </div>
              <div id="preset-calendar-exclude-tags" class="ds-tag-container"></div>
              <input type="hidden" id="preset-calendar-exclude" value="[]" />
              <span class="ds-hint"><?php p($l->t('Events containing these terms will be hidden for this preset')); ?></span>
            </div>

            <div class="ds-form-group">
              <div class="ds-checkbox-row">
                <input type="checkbox" id="preset-show-event-description" value="1" />
                <label for="preset-show-event-description" class="ds-label"><?php p($l->t('Show event descriptions')); ?></label>
              </div>
              <span class="ds-hint"><?php p($l->t('Show a short description below calendar events, limited to three lines')); ?></span>
            </div>

            <div class="ds-preset-group-title"><?php p($l->t('Widgets')); ?></div>
            <div class="ds-form-group ds-form-group-full ds-preset-widgets-field">
              <div class="ds-checkbox-row">
                <input type="checkbox" id="preset-show-slideshow" value="1" checked />
                <label for="preset-show-slideshow" class="ds-label"><?php p($l->t('Show slideshow')); ?></label>
              </div>
              <div class="ds-checkbox-row">
                <input type="checkbox" id="preset-show-weather" value="1" checked />
                <label for="preset-show-weather" class="ds-label"><?php p($l->t('Show weather')); ?></label>
              </div>
              <div class="ds-checkbox-row">
                <input type="checkbox" id="preset-show-calendar" value="1" checked />
                <label for="preset-show-calendar" class="ds-label"><?php p($l->t('Show calendar')); ?></label>
              </div>
              <span class="ds-hint"><?php p($l->t('Enable at least one widget for this preset')); ?></span>
            </div>
            <div class="ds-editor-actions">
              <button class="button primary" id="save-preset-btn"><?php p($l->t('Save preset')); ?></button>
              <button class="button" id="cancel-preset-edit-btn" style="display:none;"><?php p($l->t('Cancel edit')); ?></button>
            </div>
          </div>
        </div>

      </div>

      <div class="section ds-section ds-displays-section">
        <h4 class="ds-subsection-title"><?php p($l->t('Displays')); ?></h4>
        <p class="ds-section-subtitle"><?php p($l->t('Create and manage public screens with their own name, location, timezone, weather settings and active preset.')); ?></p>

        <div class="ds-subsection ds-display-section">
          <h4 class="ds-subsection-title ds-subsection-title-plain"><?php p($l->t('Existing displays')); ?></h4>
          <datalist id="timezone-options">
            <?php foreach (($_['time_zones'] ?? []) as $timeZone): ?>
              <option value="<?php p($timeZone); ?>"></option>
            <?php endforeach; ?>
          </datalist>
          <div id="tokens-container" class="ds-tokens-list"><?php p($l->t('Loading...')); ?></div>
          <div class="ds-subsection ds-display-create-section">
            <button class="button primary" id="new-display-btn"><?php p($l->t('New display')); ?></button>
          </div>
          <div class="ds-display-create-editor ds-object-editor" id="display-create-editor" hidden>
            <label for="token-name" class="ds-label"><?php p($l->t('Display name')); ?></label>
            <input type="text" id="token-name" placeholder="<?php p($l->t('Internal display label (e.g. reception screen)')); ?>" class="ds-input" />
            <div class="token-row token-location-search-row">
              <label class="token-row-label" for="display-create-location"><?php p($l->t('Search location')); ?></label>
              <div class="token-location-search-controls">
                <input class="ds-input" id="display-create-location" data-create-location-query placeholder="<?php p($l->t('City or address')); ?>" />
                <small><?php p($l->t('Press Enter to search. Location data provided by')); ?> <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a>.</small>
              </div>
            </div>
            <div class="token-row">
              <label class="token-row-label" for="display-create-timezone"><?php p($l->t('Display timezone')); ?></label>
              <input class="ds-input" id="display-create-timezone" data-create-timezone list="timezone-options" placeholder="<?php p($l->t('Nextcloud timezone')); ?>" />
            </div>
            <div class="token-row token-coordinate-row">
              <label class="token-row-label" for="display-create-latitude"><?php p($l->t('Weather latitude')); ?></label>
              <input class="ds-input" id="display-create-latitude" data-create-latitude type="number" min="-90" max="90" step="any" />
              <label class="token-row-label" for="display-create-longitude"><?php p($l->t('Weather longitude')); ?></label>
              <input class="ds-input" id="display-create-longitude" data-create-longitude type="number" min="-180" max="180" step="any" />
            </div>
            <div class="token-row">
              <label class="token-row-label" for="display-create-preset"><?php p($l->t('Active preset')); ?></label>
              <select class="ds-input" id="display-create-preset" data-create-preset></select>
            </div>
            <div class="display-editor-actions ds-editor-actions">
              <button class="button primary" id="create-token-btn"><?php p($l->t('Save')); ?></button>
              <button class="button" id="cancel-display-create-btn"><?php p($l->t('Cancel edit')); ?></button>
            </div>
          </div>
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
      data-update-url="<?php p($_['url_generator']->linkToRoute('digitalsignage.token.update', ['id' => 'DISPLAY_ID'])); ?>"
      data-clone-url="<?php p($_['url_generator']->linkToRoute('digitalsignage.token.clone', ['id' => 'DISPLAY_ID'])); ?>"
          data-activate-preset-url="<?php p($_['url_generator']->linkToRoute('digitalsignage.token.activatePreset', ['id' => 'DISPLAY_ID'])); ?>"
      data-delete-url="<?php p($_['url_generator']->linkToRoute('digitalsignage.token.delete', ['id' => 'TOKEN_ID'])); ?>"
          data-preset-list-url="<?php p($_['url_generator']->linkToRoute('digitalsignage.preset.list')); ?>"
          data-preset-create-url="<?php p($_['url_generator']->linkToRoute('digitalsignage.preset.create')); ?>"
          data-preset-update-url="<?php p($_['url_generator']->linkToRoute('digitalsignage.preset.update', ['id' => 'PRESET_ID'])); ?>"
          data-preset-delete-url="<?php p($_['url_generator']->linkToRoute('digitalsignage.preset.delete', ['id' => 'PRESET_ID'])); ?>"
          data-preset-clone-url="<?php p($_['url_generator']->linkToRoute('digitalsignage.preset.clone', ['id' => 'PRESET_ID'])); ?>"
          data-translation-display-timezone="<?php p($l->t('Display timezone')); ?>"
          data-translation-nextcloud-timezone="<?php p($l->t('Nextcloud timezone')); ?>"
          data-translation-weather-latitude="<?php p($l->t('Weather latitude')); ?>"
          data-translation-weather-longitude="<?php p($l->t('Weather longitude')); ?>"
          data-translation-nextcloud-weather-location="<?php p($l->t('Nextcloud Weather location')); ?>"
          data-translation-save-display-settings="<?php p($l->t('Save display settings')); ?>"
          data-translation-update-display="<?php p($l->t('Update display')); ?>"
          data-translation-saved="<?php p($l->t('Saved')); ?>"
          data-translation-error-saving-display-settings="<?php p($l->t('Error saving display settings')); ?>"
          data-translation-search-location="<?php p($l->t('Search location')); ?>"
          data-translation-city-or-address="<?php p($l->t('City or address')); ?>"
          data-translation-search="<?php p($l->t('Search')); ?>"
          data-translation-location-results="<?php p($l->t('Location results')); ?>"
          data-translation-select-location="<?php p($l->t('Select location')); ?>"
          data-translation-no-locations-found="<?php p($l->t('No locations found')); ?>"
          data-translation-location-search-failed="<?php p($l->t('Location search failed')); ?>"
          data-translation-location-data-provided-by="<?php p($l->t('Location data provided by')); ?>"
      data-csrf-token="<?php p($_['requesttoken']); ?>">
  </div>

  <?php \OCP\Util::addTranslations('digitalsignage'); ?>
  <script nonce="<?php p($_['cspNonce']); ?>" src="<?php p($_['url_generator']->linkTo('digitalsignage', 'js/settings.js')); ?>?v=<?php p($assetVersion('js/settings.js')); ?>"></script>
