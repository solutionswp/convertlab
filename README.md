# ConvertLab

A lightweight, eCommerce-focused WordPress plugin designed to help store owners increase conversions through popups, opt-ins, lead capture, behavioral targeting, and actionable insights.

## Features

- **Popup Builder**: Drag-and-drop builder with React SPA in WordPress admin
- **Lead Capture**: Store leads locally with export and integration capabilities
- **Smart Triggers**: Time delay, scroll percentage, exit intent, and page targeting
- **Analytics**: Track impressions, conversions, and conversion rates
- **GitHub Updates**: Automatic updates via GitHub releases
- **Lightweight**: Fast, privacy-friendly, and optimized for performance

## Installation

1. Download the plugin zip file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the zip file and activate
4. Navigate to ConvertLab → Popups to create your first popup

## Development

### Prerequisites

- Node.js 18+ and npm
- WordPress 5.8+
- PHP 7.4+

### Building Assets

The popup builder uses React with Vite. To build the admin assets:

```bash
cd assets/js/admin-builder
npm install
npm run build
```

For development with hot reload:

```bash
npm run dev
```

### Project Structure

```
convertlab/
├── assets/
│   ├── css/
│   │   └── popup.css          # Frontend popup styles
│   └── js/
│       ├── popup-loader.js   # Frontend popup loader
│       └── admin-builder/    # React SPA for popup builder
│           ├── components/
│           ├── App.jsx
│           └── main.jsx
├── src/
│   ├── Admin/                # Admin functionality
│   ├── API/                  # REST API endpoints
│   ├── Frontend/             # Frontend rendering
│   └── Utils/                # Utility classes
├── templates/
│   └── popups/               # Popup templates
├── convertlab.php            # Main plugin file
├── uninstall.php             # Uninstall handler
└── README.md
```

## How Updates Work

ConvertLab uses a GitHub-based update mechanism:

1. Create a new release tag (e.g., `v1.0.1`)
2. GitHub Actions automatically builds and releases the plugin
3. An `update.json` file is generated with version and download URL
4. The plugin checks the update.json URL (configured in Settings)
5. WordPress shows update notification when a new version is available

### Setting Up Updates

1. Go to ConvertLab → Settings
2. Enter your `update.json` URL (e.g., `https://raw.githubusercontent.com/solutionswp/convertlab/main/update.json`)
3. The plugin will check for updates automatically

## Creating Templates

Templates are stored in `templates/popups/` as JSON files. Each template should have:

- `name`: Template name
- `description`: Template description
- `design`: Design settings (title, text, colors, etc.)
- `fields`: Form fields configuration
- `triggers`: Display trigger settings
- `thank_you`: Thank you message configuration

## REST API Endpoints

- `GET /wp-json/convertlab/v1/popup/{id}` - Get popup configuration
- `POST /wp-json/convertlab/v1/lead/submit` - Submit a lead
- `POST /wp-json/convertlab/v1/event` - Record impression or conversion
- `POST /wp-json/convertlab/v1/popup/save` - Save popup (admin only)

## Security

- All inputs are sanitized and validated
- Nonce verification for admin actions
- Capability checks for admin functions
- Prepared statements for database queries
- REST API permission callbacks

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

GPL v2 or later

## Support

For issues and feature requests, please use the GitHub issue tracker.

