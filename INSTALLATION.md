# Installation Guide - Wilson API Challenge Plugin

This guide will walk you through installing and setting up the Wilson API Challenge Plugin.

## Prerequisites

Before you begin, ensure you have:

- WordPress 6.0 or higher installed
- PHP 7.4 or higher
- Access to your WordPress installation directory
- Node.js 14+ and npm installed (for building the Gutenberg block)
- Composer installed (optional, for dependency management)

## Step-by-Step Installation

### 1. Upload Plugin Files

**Option A: Via Git**

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone <repository-url> wilson-api-plugin
cd wilson-api-plugin
```

**Option B: Manual Upload**

1. Download the plugin ZIP file
2. Extract to `wp-content/plugins/wilson-api-plugin/`

### 2. Install Dependencies

#### Install PHP Dependencies (Optional)

The plugin includes a fallback autoloader, so Composer is optional. However, for development tools:

```bash
cd wilson-api-plugin
composer install
```

If you don't have Composer, the plugin will work fine without it using the built-in autoloader.

#### Install Node Dependencies (Required for Block)

```bash
npm install
```

This installs all necessary packages for building the Gutenberg block.

### 3. Build the Gutenberg Block

```bash
npm run build
```

This compiles the React code and creates production-ready JavaScript files in the `build/` directory.

**For Development:**

```bash
npm start
```

This starts a development server that watches for changes and automatically rebuilds.

### 4. Activate the Plugin

**Via WordPress Admin:**

1. Log in to WordPress admin
2. Go to **Plugins > Installed Plugins**
3. Find "Wilson API Challenge Plugin"
4. Click **Activate**

**Via WP-CLI:**

```bash
wp plugin activate wilson-api-plugin
```

### 5. Verify Installation

After activation, you should see:

1. **Admin Menu**: A new "Wilson API Data" menu item in the WordPress admin sidebar
2. **Block Editor**: "Wilson API Data Table" block available in the block inserter
3. **WP-CLI**: Command available via `wp wilson-api refresh`

## Verifying the Installation

### Test the Admin Page

1. Navigate to **Wilson API Data** in the admin menu
2. You should see data fetched from the API displayed in a table
3. Click **Refresh Data** to test the refresh functionality

### Test the Gutenberg Block

1. Create or edit a post/page
2. Add a new block (+)
3. Search for "Wilson API Data Table"
4. Insert the block
5. The block should load and display data from the API
6. Check block settings (right sidebar) to toggle columns

### Test the WP-CLI Command

```bash
wp wilson-api refresh
```

Expected output:

```
Success: Data marked for refresh. The cache will be bypassed on the next request.
Fetching fresh data from API...
Success: Successfully fetched X items from API.
Cache will expire in: 1 hour
```

### Test the AJAX Endpoint

You can test the AJAX endpoint using curl:

```bash
curl 'https://your-site.com/wp-admin/admin-ajax.php?action=wilson_api_get_data'
```

Or using JavaScript in the browser console:

```javascript
fetch("/wp-admin/admin-ajax.php?action=wilson_api_get_data")
  .then((r) => r.json())
  .then((data) => console.log(data));
```

## Common Issues and Solutions

### Issue: Block doesn't appear in the editor

**Solution:**

1. Make sure you ran `npm run build`
2. Clear your browser cache
3. Check the browser console for JavaScript errors
4. Verify the `build/` directory exists and contains `block.js`

### Issue: "Plugin does not have a valid header"

**Solution:**

- Ensure the plugin directory is named `wilson-api-plugin`
- Verify `wilson-api-plugin.php` is in the root of the plugin directory
- Check that the file starts with the correct plugin header

### Issue: WP-CLI command not found

**Solution:**

1. Verify WP-CLI is installed: `wp --version`
2. Make sure the plugin is activated
3. Try: `wp cli has-command wilson-api`

### Issue: API data not loading

**Solution:**

1. Check WordPress debug log for errors
2. Verify your server can reach `https://miusage.com/v1/challenge/1/`
3. Test the URL directly in your browser
4. Check PHP error logs

### Issue: Composer errors

**Solution:**

- The plugin works without Composer dependencies
- If you need dev tools, ensure you have PHP 7.4+ and Composer 2.0+
- Try: `composer install --ignore-platform-reqs`

## File Permissions

Ensure proper file permissions:

```bash
# Plugin directory
chmod 755 wilson-api-plugin

# PHP files
chmod 644 wilson-api-plugin/*.php
chmod 644 wilson-api-plugin/src/**/*.php

# Make sure web server can read
chown -R www-data:www-data wilson-api-plugin
```

## Updating the Plugin

### Via Git

```bash
cd wp-content/plugins/wilson-api-plugin
git pull origin main
npm install  # If package.json changed
npm run build
```

### Manual Update

1. Deactivate the plugin
2. Replace plugin files (don't delete, to preserve any data)
3. Run `npm install` and `npm run build` if needed
4. Reactivate the plugin

## Uninstallation

### Clean Uninstall

1. Deactivate the plugin
2. Delete the plugin files
3. (Optional) Clean up database:
   - Remove option: `wilson_api_cache_duration`
   - Remove transients: `wilson_api_data`, `wilson_api_data_timestamp`, `wilson_api_force_refresh`

### Via WP-CLI

```bash
wp plugin deactivate wilson-api-plugin
wp plugin delete wilson-api-plugin
```

## Development Setup

For development, you may want to:

1. Install development dependencies:

   ```bash
   composer install
   npm install
   ```

2. Start the development server:

   ```bash
   npm start
   ```

3. Enable WordPress debugging in `wp-config.php`:

   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

4. Run code standards checks:
   ```bash
   composer phpcs
   npm run lint:js
   ```

## Need Help?

If you encounter any issues not covered here:

1. Check the WordPress debug log
2. Review browser console for JavaScript errors
3. Verify all system requirements are met
4. Contact Wilson for support

## Next Steps

After installation:

1. Review the [README.md](README.md) for usage instructions
2. Check the code documentation in the source files
3. Explore the admin interface
4. Test the Gutenberg block
5. Try the WP-CLI command

Happy coding! 🚀
