# Changelog

<!-- markdownlint-disable MD024 -->

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
