# Digital Signage for Nextcloud

A Nextcloud app for displaying digital info monitors with calendar events and image slideshows.

## Features

- 📅 **Calendar Integration**: Display upcoming events from Nextcloud calendars
- 🖼️ **Image Slideshow**: Automatic slideshow from a Nextcloud folder
- 🌤️ **Weather**: Current weather information (Open-Meteo API)
- 🔗 **Public Tokens**: Create tokens for displays without login
- ⚙️ **Configurable**: Settings for all parameters via UI

## Installation

### Requirements

- Nextcloud 24 or higher
- PHP 7.4 or higher

### Installing in Nextcloud

1. Clone the app into your Nextcloud `apps/` folder:
   ```bash
   cd /path/to/nextcloud/apps/
   git clone https://github.com/nak-lehrte/digitalsignage.git
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
git clone https://github.com/nak-lehrte/digitalsignage.git
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

## License

AGPL-3.0

## Support

If you have questions or encounter issues, please open an issue in the GitHub repository.
