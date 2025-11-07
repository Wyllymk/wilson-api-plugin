# Wilson API Challenge Plugin

A modern, secure WordPress plugin that retrieves and displays data from a remote API with intelligent caching, custom Gutenberg block, admin interface, and WP-CLI support.

## Features

- **AJAX Endpoint**: Accessible to both logged-in and non-logged-in users
- **Intelligent Caching**: API is never called more than once per hour
- **Custom Gutenberg Block**: Display API data with customizable column visibility
- **Admin Interface**: WP Mail SMTP-style admin page with refresh functionality
- **WP-CLI Support**: Force refresh data via command line
- **Security First**: Proper sanitization, validation, and nonce verification
- **Modern Architecture**: OOP, PSR-4 autoloading, Composer support
- **Internationalization**: All strings are translatable

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- Node.js 14+ and npm (for block development)

## Installation

1. Clone or download this repository to your WordPress plugins directory:

   ```bash
   cd wp-content/plugins
   git clone <repository-url> wilson-api-plugin
   ```

2. Install PHP dependencies (optional, autoloading works without Composer):

   ```bash
   cd wilson-api-plugin
   composer install
   ```

3. Install Node dependencies and build the block:

   ```bash
   npm install
   npm run build
   ```

4. Activate the plugin through the WordPress admin panel or via WP-CLI:
   ```bash
   wp plugin activate wilson-api-plugin
   ```

## Usage

### AJAX Endpoint

The plugin provides an AJAX endpoint that can be accessed without authentication:

```javascript
fetch("/wp-admin/admin-ajax.php?action=wilson_api_get_data")
  .then((response) => response.json())
  .then((data) => console.log(data));
```

### Gutenberg Block

1. Edit any page or post
2. Add the "Wilson API Data Table" block
3. Configure column visibility in the block settings panel
4. Toggle header visibility as needed

### Admin Page

1. Navigate to **Wilson API Data** in the WordPress admin menu
2. View the API data in a formatted table
3. Click **Refresh Data** to force a fresh fetch from the API
4. View cache information and API details

### WP-CLI Command

Force refresh the API data:

```bash
wp wilson-api refresh
```

This command will:

- Mark the data for force refresh
- Fetch fresh data from the API
- Display the number of items retrieved
- Show cache expiration information

## Development

### File Structure

```
wilson-api-plugin/
├── assets/
│   ├── css/
│   │   ├── admin.css          # Admin page styles
│   │   └── block.css          # Block frontend styles
│   └── js/
│       ├── admin.js           # Admin page JavaScript
│       └── block.js           # Block frontend JavaScript
├── build/                     # Compiled block assets
├── languages/                 # Translation files
├── src/
│   ├── Admin/
│   │   └── AdminPage.php      # Admin page handler
│   ├── Api/
│   │   ├── AjaxHandler.php    # AJAX endpoint handler
│   │   └── ApiClient.php      # API communication & caching
│   ├── Blocks/
│   │   └── DataTableBlock.php # Block registration
│   ├── CLI/
│   │   └── RefreshCommand.php # WP-CLI command
│   ├── Core/
│   │   └── Plugin.php         # Main plugin class
│   └── blocks/
│       └── data-table/
│           └── block.js       # Block editor code
├── composer.json              # PHP dependencies
├── package.json               # Node dependencies
├── webpack.config.js          # Webpack configuration
├── wilson-api-plugin.php      # Main plugin file
└── README.md                  # This file
```

### Building Assets

Development mode (watch for changes):

```bash
npm start
```

Production build:

```bash
npm run build
```

### Code Standards

The plugin follows WordPress coding standards. To check your code:

```bash
composer phpcs
```

To automatically fix issues:

```bash
composer phpcbf
```

## API Endpoint

The plugin fetches data from:

```
https://miusage.com/v1/challenge/1/
```

## Caching Strategy

The plugin implements intelligent caching:

- Data is cached for 1 hour after fetching
- Cache timestamp is stored separately for precise age calculation
- AJAX endpoint always returns cached data if available and valid
- Force refresh can be triggered via:
  - Admin page refresh button
  - WP-CLI command
  - Programmatically via API
- Stale cache fallback: If API call fails, returns cached data even if expired

## Security

The plugin implements multiple security measures:

- **Input Validation**: All user input is validated
- **Output Escaping**: All output is properly escaped to prevent XSS
- **Nonce Verification**: Admin actions require valid nonces
- **Capability Checks**: Admin functions require `manage_options` capability
- **Data Sanitization**: All API data is recursively sanitized
- **HTTPS**: SSL verification is enabled for API requests

## Internationalization

The plugin is fully translatable. Text domain: `wilson-api-plugin`

To create translations:

1. Generate POT file using WP-CLI or Poedit
2. Create .po and .mo files for your language
3. Place in the `languages/` directory

## Troubleshooting

### Block not appearing

- Ensure you've run `npm run build`
- Clear your browser cache
- Check browser console for JavaScript errors

### API not fetching data

- Check WordPress debug log for errors
- Verify the API endpoint is accessible from your server
- Check if caching is working correctly

### WP-CLI command not found

- Ensure WP-CLI is installed and accessible
- Verify the plugin is activated
- Try: `wp cli has-command wilson-api refresh`

## Support

For issues, questions, or contributions, please contact Wilson or refer to the plugin documentation.

## License

GPL v2 or later

## Credits

Developed by Wilson as part of the MiUsage API Challenge.
