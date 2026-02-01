
# Digital Signage for Nextcloud

A Nextcloud app for displaying digital info monitors with calendar events and image slideshows.

![Digital Signage Display](img/screenshot-display.png)

## Recent Changes

### [0.0.7]

- Fullscreen toggle button integrated into display header
- Optional auto-prompt for fullscreen mode (configurable)
- Chrome/Safari compatibility with webkit API
- Internationalized fullscreen dialog
- Auto-hide button with mouse movement detection

## Features

- **Calendar integration**: Display upcoming events from multiple Nextcloud calendars
- **Image slideshow**: Automated slideshow from a Nextcloud folder
- **Fullscreen mode**: One-click fullscreen toggle with optional auto-prompt on page load
- **Customizable appearance**: Configure colors (primary, background, text, gradient) and toggle display title visibility
- **Smart event filtering**: Autocomplete-enabled event exclusion based on actual calendar titles
- **Weather information**: Real-time weather data via Open-Meteo API
- **Public tokens**: Secure display access without login
- **Multi-language support**: English and German translations
- **Configurable settings**: All settings managed through intuitive UI

See [CHANGELOG.md](CHANGELOG.md) for all release notes.

## Installation

### Requirements

- Nextcloud 24–32
- PHP 7.4 or higher

### Installing in Nextcloud

1. Download the app from the Nextcloud App Store

2. Or clone the app into your Nextcloud `apps/` folder:

   ```bash
   cd /path/to/nextcloud/apps/
   git clone https://github.com/lmaertin/nextcloud-digitalsignage.git
   ```

3. Enable the app in Nextcloud settings or via CLI:
   - UI: **Settings** > **Apps** > "Digital Signage" > **Enable**
   - CLI: `php occ app:enable digitalsignage`

### Configuration

1. Open the **Digital Signage** app in Nextcloud (App overview) – settings are integrated directly on the app page.

2. Configure the following settings (via UI, no file edits needed):
   
   **Display Settings:**
   - **Display Name**: Title shown at the top of the display (can be toggled off)
   - **Show Display Name**: Checkbox to show or hide the display name on screen
   - **Auto-prompt for fullscreen**: When enabled, displays an optional dialog asking users to activate fullscreen mode when opening the display
     - Useful for kiosk setups and dedicated display devices
     - Can be declined without affecting functionality
   
   **Color Customization:**
   - **Primary Color**: Main accent color for UI elements
   - **Background Color**: Display background color
   - **Text Color**: Color for event and information text
   - **Gradient Start/End**: Header gradient colors for title bar
   - Colors can be reset to defaults with one click
   
   **Content Sources:**
   - **Calendar Sources**: Select multiple calendars from your Nextcloud calendars
   - **Image Folder**: Select folder from your Nextcloud file tree (e.g., `/Photos/Info-Monitor`)
   - **Slide Interval (seconds)**: Duration per image in slideshow
   
   **Event Filtering:**
   - **Hide Events**: Exclude specific events by title using autocomplete suggestions
   - Autocomplete shows actual event titles from your configured calendars
   - Add multiple exclusion terms as tags
   
   **Weather:**
   - **Weather Coordinates**: Latitude and longitude for Open-Meteo API

3. Save the settings

![Settings Screen 1](img/screenshot-settings-1.png)
![Settings Screen 2](img/screenshot-settings-2.png)
![Settings Screen 3](img/screenshot-settings-3.png)

## Usage

### Creating a Display Token

1. Open the Digital Signage app in Nextcloud
2. In the **Tokens** section, enter a name (e.g., "Entrance Monitor")
3. Click **Create Token**; the URL will be displayed
4. Copy it and open on your display device (kiosk mode recommended)
5. You can also delete existing tokens there

![Token Management](img/screenshot-tokens.png)

### Display View

The token URL opens a full-screen view without login. This can be opened on a Raspberry Pi, Android tablet, or other device in kiosk mode.

**Fullscreen Features:**
- **Manual Toggle**: Click the fullscreen button (⛶) in the top-right corner of the header to enter/exit fullscreen mode
- **Auto-prompt**: If enabled in settings, a dialog will appear asking to activate fullscreen mode when the page loads
- **Keyboard Shortcut**: Press `ESC` to exit fullscreen mode
- **Browser Compatibility**: Works in Chrome, Firefox, Safari, and Edge with webkit prefix support

**Kiosk Mode Tips:**
- Enable "Auto-prompt for fullscreen" in settings for automatic fullscreen activation
- Use browser kiosk mode for dedicated displays (e.g., `chromium-browser --kiosk --app=URL`)
- Fullscreen button auto-hides after 5 seconds of inactivity and reappears on mouse movement

## Development

### Local Development

```bash
# Clone Repository
git clone https://github.com/lmaertin/nextcloud-digitalsignage.git
cd digitalsignage

# Configure Git hooks (required for pre-commit checks)
git config core.hooksPath .githooks

# Create symlink in Nextcloud
ln -s $(pwd) /path/to/nextcloud/apps/digitalsignage

# Enable in Nextcloud
cd /path/to/nextcloud
php occ app:enable digitalsignage
```

### Pre-Commit Hooks

Before committing, the following checks are automatically executed:

1. **PHP Syntax Check** - validates all PHP files for syntax errors
2. **PHPUnit Tests** (optional) - runs unit tests if Docker container is available

The pre-commit hook requires:
- PHP CLI (always required)
- Docker and Nextcloud container (optional, tests will be skipped if unavailable)

To skip the hooks (not recommended):
```bash
git commit --no-verify
```

### Database Schema

- No manual database setup required.
- When enabling the app, Nextcloud automatically runs migrations and creates the `oc_digitalsignage_token` table for token management.

## Architecture (overview)

### Backend (PHP)

- PageController: main app page with token management
- ApiController: API endpoints for authenticated users
- PublicController: public display page (token-based)
- PublicApiController: API for public displays
- TokenController: token management
- SettingsController: settings management

### Frontend (JavaScript)

- display.js: main display view logic
  - Slideshow management
  - Weather API integration
  - Calendar rendering

### Data flow

1. Admin configures settings in the app
2. User creates a display token in the app
3. Display opens public URL with token
4. PublicApiController validates token and returns data:
    - Config (locale, intervals, etc.)
    - Calendar entries from Nextcloud Calendar API
    - Image list from Nextcloud Files API
5. JavaScript loads and displays the data

## APIs

### Authenticated APIs

- `GET /apps/digitalsignage/api/config` - Configuration
- `GET /apps/digitalsignage/api/calendar` - Calendar entries
- `GET /apps/digitalsignage/api/images` - Image list
- `GET /apps/digitalsignage/api/image?id=<file_id>` - Single image

### Public APIs (Token required)

- `GET /apps/digitalsignage/api/public/{token}/config`
- `GET /apps/digitalsignage/api/public/{token}/calendar`
- `GET /apps/digitalsignage/api/public/{token}/images`
- `GET /apps/digitalsignage/api/public/{token}/image?id=<file_id>`

## Security

### Token Security

- Tokens are generated using cryptographically secure random bytes
- Each token is unique and hashed in the database
- Tokens are user-specific and cannot be used by other users
- Expired or revoked tokens can be deleted via the UI

### API Security

- All authenticated endpoints require Nextcloud user login
- Public APIs require valid token validation
- CSRF tokens are validated on all state-changing operations
- Content Security Policy restricts external API access to whitelisted domains
- External weather API (Open-Meteo) is HTTPS-only

### Best Practices

- Do not share display tokens in unsecured channels
- Rotate tokens regularly if compromised
- Use HTTPS in production for all display URLs
- Consider network isolation for production displays

## Troubleshooting

### Display shows blank

- Check calendar configuration is saved
- Verify calendar has events
- Check image folder is selected and contains images
- Open browser console (F12) for API errors

### Weather not loading

- Verify latitude/longitude are set correctly
- Check internet connection for Open-Meteo API access
- Weather API requires HTTPS connection

### Images not loading

- Ensure folder path is correct (`/path/to/folder`)
- Verify user has read permissions to folder
- Check images are in supported format (JPG, PNG, GIF, WebP)

### Token issues

- Verify token is correct (copy from UI)
- Ensure token hasn't expired or been deleted
- Check public display URL includes correct token parameter

## Publishing to App Store

See [CODE_SIGNING.md](CODE_SIGNING.md) for instructions on code signing and publishing to the Nextcloud App Store.

### Checklist for App Store Submission

- [ ] Code signing certificate generated and CSR submitted
- [ ] Screenshots added to `img/` folder (see [img/SCREENSHOTS.md](img/SCREENSHOTS.md))
- [ ] Version number updated in `appinfo/info.xml`
- [ ] CHANGELOG.md updated with release notes
- [ ] Release tagged in Git
- [ ] App archive created and signed
- [ ] Submitted to <https://apps.nextcloud.com/>

## GitHub Secrets for Release and App Store Upload

For automated releases and App Store uploads, the following GitHub Secrets are required:

- **CODESIGN_KEY**: Private key for code signing (content of `certificates/digitalsignage.key`). Used in the release workflow to sign the release archive. Never make this public.
- **NC_APPSTORE_TOKEN**: API token for the Nextcloud App Store (generate in the App Store developer portal). Used in the upload workflow to automatically upload the signed release.

**Function:**
- `CODESIGN_KEY` is written to a file in the workflow and used by OpenSSL for signing.
- `NC_APPSTORE_TOKEN` is passed as an environment variable to the upload script and authenticates the API request to the App Store.

**How to set the secrets:**
- GitHub Repository → Settings → Secrets and variables → Actions → New repository secret
- Name: `CODESIGN_KEY`, Value: (content of `digitalsignage.key`)
- Name: `NC_APPSTORE_TOKEN`, Value: (your App Store API token)

The secrets are never stored in the repository and are only accessible to GitHub Actions.

## License

AGPL-3.0 - See [LICENSE](LICENSE) file for details.

## Support

If you have questions or encounter issues:

- Open an issue: <https://github.com/lmaertin/nextcloud-digitalsignage/issues>
- Check documentation in this README
- Review the [CHANGELOG](CHANGELOG.md) for recent changes
- See Troubleshooting section above

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes with clear commit messages
4. Submit a pull request

## Authors

See [AUTHORS](AUTHORS) file for list of contributors.
