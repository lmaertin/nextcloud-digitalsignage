# Digital Signage for Nextcloud

A Nextcloud app for displaying digital info monitors with calendar events and image slideshows.

## Features

- **Calendar Integration**: Display upcoming events from Nextcloud calendars
- **Image Slideshow**: Automatic slideshow from a Nextcloud folder
- **Weather**: Current weather information (Open-Meteo API)
- **Public Tokens**: Create tokens for displays without login
- **Configurable**: Settings for all parameters via UI

## Installation

### Requirements

- Nextcloud 24 or higher
- PHP 7.4 or higher

### Installing in Nextcloud

1. Clone the app into your Nextcloud `apps/` folder:
   ```bash
   cd /path/to/nextcloud/apps/
   git clone https://github.com/lmaertin/nextcloud-digitalsignage.git
   ```

2. Or download the app from the Nextcloud App Store

3. Enable the app in Nextcloud settings or via CLI:
   - UI: **Settings** > **Apps** > "Digital Signage" > **Enable**
   - CLI: `php occ app:enable digitalsignage`

### Configuration

1. Open the **Digital Signage** app in Nextcloud (App overview) – settings are integrated directly on the app page.

2. Configure the following settings (via UI, no file edits needed):
   - **Display Name**: Title shown at the top of the display
   - **Calendar Sources**: Select multiple calendars from your Nextcloud calendars
   - **Image Folder**: Select folder from your Nextcloud file tree (e.g., `/Photos/Info-Monitor`)
   - **Slide Interval (seconds)**: Duration per image
   - **Weather Coordinates**: Latitude and longitude (Open-Meteo)
   - **Hide Events**: Terms as list/tags to exclude from event titles

3. Save the settings

## Usage

### Creating a Display Token

1. Open the Digital Signage app in Nextcloud
2. In the **Tokens** section, enter a name (e.g., "Entrance Monitor")
3. Click **Create Token**; the URL will be displayed
4. Copy it and open on your display device (kiosk mode recommended)
5. You can also delete existing tokens there

### Display View

The token URL opens a full-screen view without login. This can be opened on a Raspberry Pi, Android tablet, or other device in kiosk mode.

## Development

### Local Development

```bash
# Clone Repository
git clone https://github.com/lmaertin/nextcloud-digitalsignage.git
cd digitalsignage

# Create symlink in Nextcloud
ln -s $(pwd) /path/to/nextcloud/apps/digitalsignage

# Enable in Nextcloud
cd /path/to/nextcloud
php occ app:enable digitalsignage
```

### Database Schema

- No manual database setup required.
- When enabling the app, Nextcloud automatically runs migrations and creates the `oc_digitalsignage_token` table for token management.

## Architecture

### Backend (PHP)

- **PageController**: Main app page with token management
- **ApiController**: API endpoints for authenticated users
- **PublicController**: Public display page (token-based)
- **PublicApiController**: API for public displays
- **TokenController**: Token management
- **SettingsController**: Settings management

### Frontend (JavaScript)

- **display.js**: Main display view logic
  - Slideshow management
  - Weather API integration
  - Calendar rendering
  
### Data Flow

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
- [ ] Submitted to https://apps.nextcloud.com/

## License

AGPL-3.0 - See [LICENSE](LICENSE) file for details.

## Support

If you have questions or encounter issues:
- Open an issue: https://github.com/lmaertin/nextcloud-digitalsignage/issues
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
