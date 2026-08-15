# Changelog

<!-- markdownlint-disable MD024 -->

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.7.0] - 2026-08-15

### Added

- **Widget selection** ([#6](https://github.com/lmaertin/nextcloud-digitalsignage/issues/6)): Configure slideshow, weather, and calendar visibility independently per preset.
- **Event descriptions per preset** ([#7](https://github.com/lmaertin/nextcloud-digitalsignage/issues/7)): Choose independently for each preset whether calendar descriptions are displayed.
- **Header title source**: Choose the global display name, the preset name or no title per preset.
- **Image prefetching**: The next slideshow image is loaded in the background to reduce flickering and visible image buildup during transitions.
- **Adaptive layouts**: The public display adjusts its layout to the selected widgets, including asymmetric weather columns and full-height single-widget layouts.
- **Backward-compatible preset migration**: Existing presets receive all three widgets enabled by default.
- **Calendar event descriptions**: Added an opt-in setting to show sanitized event descriptions below calendar details.

### Changed

- **Responsive public display**: Hidden widgets no longer leave loading placeholders or consume layout space.
- **Preset summaries**: The settings UI now shows which widgets are active for each preset.
- **Asset cache handling**: Updated display and settings assets are loaded with release cache-busting while preserving Nextcloud translations.
- **Calendar layout protection**: Event descriptions are limited to three visible lines to keep displays readable.

### Technical

- Added preset fields `show_slideshow`, `show_weather` and `show_calendar`.
- Added preset field `header_title_source` with backward-compatible `global`, `preset` and `none` values.
- Added database migration `Version1400Date20260815000000`.
- Added database migration `Version1500Date20260815000000` for per-preset event descriptions.
- Added database migration `Version1600Date20260815000000` for per-preset header title sources.
- Added widget-aware layout combinations for desktop and portrait/mobile displays.
- Thanks to [@FahrJo](https://github.com/FahrJo) for the first contribution to the project in [PR #5](https://github.com/lmaertin/nextcloud-digitalsignage/pull/5), which introduced image prefetching for smoother slideshow transitions.

### Fixed

- **Nextcloud 34 compatibility** ([#8](https://github.com/lmaertin/nextcloud-digitalsignage/issues/8)): Replaced deprecated global server lookups with injected public APIs.
- **Controller user ID injection**: Renamed the deprecated `UserId` service alias to `userId`.
- **Public display security values**: Use Nextcloud-provided request tokens and CSP nonces in templates.
- **Preset update state**: Widget selections remain unchanged when an existing preset is updated.
- **Hidden widget placeholders**: Disabled widgets no longer show their initial loading messages.
- **Responsive overflow**: Prevented calendar content and mobile layouts from creating an oversized lower page margin.

## [0.6.1] - 2026-04-21

### Fixed

- **Settings save error**: Fixed JavaScript error that prevented saving settings due to reference to removed `show_display_name` checkbox element

## [0.6.0] - 2026-04-21

### Added

- **Video support**: Display MP4, WebM, MOV, and MKV video files in slideshows
  - Videos play automatically and advance to the next item when finished
  - Mix images and videos in the same folder seamlessly
  - Native HTML5 video playback with muted autoplay
  - Videos respect crop mode settings (fill/fit) using CSS object-fit
  - Supported video formats: `.mp4`, `.webm`, `.mov`, `.mkv`
- **Nextcloud 34 compatibility**: Extended maximum supported version to Nextcloud 34

### Changed

- **UI terminology update**: Replaced "Images" with "Media" throughout the interface to reflect video support

### Technical

- Enhanced `PublicApiController::getImages()` to detect video file extensions and return `type` field (`image` or `video`)
- Rewritten `display.js` slideshow engine:
  - Dynamic element creation (video elements vs. background images)
  - Event-driven auto-advance for videos (`ended`, `error` events)
  - Separate timing logic for images (interval-based) and videos (self-timed)
  - Proper cleanup of video elements and timers on transition
- Added error handling to skip to next item if video fails to load
- Supported image formats (unchanged): `.jpg`, `.jpeg`, `.png`, `.gif`, `.webp`

## [0.5.8] - 2026-04-20

### Added

- German formal variant (de_DE) for formal "Sie" address form alongside informal "du" variant (de)
- Screenshot URLs in info.xml for proper App Store presentation
- Documentation links in info.xml for user and admin guides
- Enhanced app description with feature highlights and use cases

### Changed

- Modernized app store description with emojis and better formatting

### Fixed

- Removed incorrect `<repair-steps>` from info.xml (database migrations are auto-detected, not repair steps)

## [0.5.6] - 2026-04-20

### Changed

- Display name visibility setting moved from global app config to per-preset configuration
- Each preset can now independently control whether to show or hide the display name

### Fixed

- Fixed null pointer errors in fullscreen slideshow mode when weather or calendar elements are not present in the DOM
- Fullscreen slideshow mode no longer throws JavaScript errors when weather or calendar data fails to load

## [0.5.4] - 2026-04-15

### Fixed

- Migration index names were shortened to avoid MySQL/MariaDB identifier-length errors during schema updates.
- Locale fallback now resolves reliably to English for unsupported app languages.
- Fullscreen labels and prompt texts are now fully localized through the app l10n pipeline.

### Added

- Spanish and Italian translation catalogs were added (es, it).

## [0.5.1] - 2026-04-06

### Added

- Per-text-class font size settings for display title, clock, weather and calendar content
- Style settings grouped into layout, colors and text sizes with dedicated reset actions
- Runtime font-size CSS variables so already open public displays can apply typography changes directly

### Changed

- Default split layout width is now 50 percent for a balanced standard layout
- Layout wording in the settings UI was tightened and aligned with the remaining width-based configuration
- README and app metadata were refreshed for the current preset, layout and typography feature set

### Fixed

- Resetting text sizes now restores the configured base defaults instead of legacy scaled values
- Legacy text-scaling paths and outdated translation strings were removed from settings, runtime config and API payloads
- The main settings header no longer uses an emoji label

## [0.5.0] - 2026-04-06

### Added

- Preset management for image and slideshow settings
- French translation updated and aligned with the current feature set
- Dutch translation added for the current feature set
- Separate view token and control token per display
- Remote preset switching through a public control API
- Per-display active preset assignment in the settings UI
- Playback order setting for presets with shuffle and filename order
- Display revision polling so public displays reload after remote changes
- Public config responses including active preset and revision information
- Configurable slideshow width percentage for the standard split layout
- Runtime application of layout changes on already open public displays
- New preset entity, mapper, service and controller
- New display config service to resolve effective settings from global config and active preset
- Database migrations for presets, control tokens, active preset assignment, revision tracking and image order mode
- Unit tests for presets and extended token handling

### Changed

- The visible display title is now defined globally; display entries only use internal labels in the admin UI
- Image and slideshow settings were moved out of the global settings section into presets
- Public display rendering now uses the active preset for image folder, crop mode, interval, fullscreen slideshow mode and playback order
- The settings UI now distinguishes global settings, slideshow presets and displays more clearly
- The settings UI now uses a more consistent layout, title treatment and translation coverage

### Fixed

- JSON error handling for preset requests now returns readable API errors instead of HTML fallbacks
- Broken or missing preset image order values are normalized automatically
- Localization files for German and English were repaired after malformed prefixes caused translation fallback issues
- Display slideshow behavior now supports stable sequential playback by filename and reshuffled playback cycles
- Invalid translation JSON files were repaired so server-side localization works reliably again
- Missing translations for display-management texts were added to English and German catalogs

## [0.0.7] - 2026-02-01

### Added

- Fullscreen toggle button integrated into display header
- Optional auto-prompt for fullscreen mode on page load (configurable setting)
- Chrome/Safari compatibility with webkit fullscreen API prefixes
- Internationalized fullscreen dialog using Nextcloud user language
- Auto-hide functionality for fullscreen button with mouse movement detection

## [0.0.6] - 2026-02-01

### Added

- Autocomplete for event exclusion based on actual calendar event titles
- Customizable display colors (Primary, Background, Text, Gradient Start/End)
- Toggle to show/hide display name on screen

## [0.0.5] - 2026-01-30

### Changed

- Version bump, updated README/Changelog
- Removed: appinfo/database.xml (no longer supported by Nextcloud)

## [0.0.4] - 2026-01-27

### Changed

- Metadata clean up

## [0.0.3] - 2026-01-26

### Added

- Small thumbnail for main screenshot (App Store preview loads faster)
- Further screenshots for app store

## [0.0.2] - 2026-01-26

### Added

- Automated and API-compliant App Store upload via GitHub Actions
- Nightly/pre-release support for App Store uploads
- PNG screenshots optimized and added to app description

### Changed

- Internal scripts and obsolete files removed from release package
- Build and signing process improved and documented

## [0.0.1] - 2026-01-24

### Added

- Initial public release for Nextcloud App Store
