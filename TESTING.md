# Testing Guide - Wilson API Challenge Plugin

This document provides comprehensive testing procedures to verify all functionality of the Wilson API Challenge Plugin.

## Test Environment Setup

### Prerequisites

- Fresh WordPress installation (6.0+)
- PHP 7.4+
- Plugin installed and activated
- WP-CLI installed (for CLI tests)
- Browser with developer tools

### Enable Debug Mode

Add to `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
```

## Test Suite

### 1. Installation Tests

#### Test 1.1: Plugin Activation

**Procedure:**

1. Navigate to Plugins page
2. Find "Wilson API Challenge Plugin"
3. Click "Activate"

**Expected Result:**

- Plugin activates without errors
- "Wilson API Data" menu appears in admin sidebar
- No PHP errors in debug log

**Status:** ☐ Pass ☐ Fail

---

#### Test 1.2: File Structure

**Procedure:**

1. Check plugin directory structure

**Expected Result:**
All required files present:

- ☐ `wilson-api-plugin.php`
- ☐ `composer.json`
- ☐ `package.json`
- ☐ `README.md`
- ☐ `src/` directory
- ☐ `assets/` directory
- ☐ `build/` directory (after build)

**Status:** ☐ Pass ☐ Fail

---

### 2. AJAX Endpoint Tests

#### Test 2.1: Endpoint Accessibility (Logged Out)

**Procedure:**

1. Log out of WordPress
2. Open browser console
3. Run:

```javascript
fetch("/wp-admin/admin-ajax.php?action=wilson_api_get_data")
  .then((r) => r.json())
  .then((data) => console.log(data));
```

**Expected Result:**

- Response status: 200
- JSON response with `success: true`
- `data.data` contains API data
- No authentication required

**Status:** ☐ Pass ☐ Fail

---

#### Test 2.2: Endpoint Accessibility (Logged In)

**Procedure:**

1. Log in to WordPress
2. Open browser console
3. Run same fetch command as above

**Expected Result:**

- Response status: 200
- JSON response with `success: true`
- Same data as logged-out test

**Status:** ☐ Pass ☐ Fail

---

#### Test 2.3: Caching Mechanism

**Procedure:**

1. Clear all caches (WP-CLI: `wp transient delete --all`)
2. Make first AJAX request, note timestamp
3. Wait 5 seconds
4. Make second AJAX request, note timestamp
5. Compare `cache_info.cache_age` values

**Expected Result:**

- First request: `cache_age` near 0
- Second request: `cache_age` approximately 5 seconds
- Data is identical in both requests
- External API called only once

**Verification:**
Check debug log for API call count.

**Status:** ☐ Pass ☐ Fail

---

#### Test 2.4: Cache Expiration

**Procedure:**

1. Set transient expiration for testing:

```php
set_transient('wilson_api_data', $test_data, 5); // 5 seconds
set_transient('wilson_api_data_timestamp', time(), 5);
```

2. Make AJAX request
3. Wait 6 seconds
4. Make another AJAX request

**Expected Result:**

- First request returns cached data
- Second request fetches fresh data
- New cache created with updated timestamp

**Status:** ☐ Pass ☐ Fail

---

### 3. Admin Page Tests

#### Test 3.1: Admin Page Access

**Procedure:**

1. Log in as administrator
2. Click "Wilson API Data" in admin menu

**Expected Result:**

- Page loads without errors
- Header displays "Wilson API Data"
- Data table displays API data
- Refresh button present
- Cache info displayed (if cache exists)

**Status:** ☐ Pass ☐ Fail

---

#### Test 3.2: Admin Page Refresh Button

**Procedure:**

1. Navigate to admin page
2. Click "Refresh Data" button
3. Wait for response

**Expected Result:**

- Button shows "Refreshing..." state
- Button is disabled during request
- Success notice appears
- Page reloads or data updates
- Cache timestamp updates

**Status:** ☐ Pass ☐ Fail

---

#### Test 3.3: Admin Page Non-Admin Access

**Procedure:**

1. Create user with Subscriber role
2. Log in as subscriber
3. Try to access `/wp-admin/admin.php?page=wilson-api-data`

**Expected Result:**

- Error: "You do not have sufficient permissions"
- User cannot see admin page

**Status:** ☐ Pass ☐ Fail

---

#### Test 3.4: Admin Page Data Display

**Procedure:**

1. Navigate to admin page
2. Inspect table structure

**Expected Result:**

- Data displayed in organized table
- Headers properly formatted
- Values properly escaped
- Complex data (arrays/objects) shown as JSON
- Responsive design works on mobile

**Status:** ☐ Pass ☐ Fail

---

### 4. Gutenberg Block Tests

#### Test 4.1: Block Registration

**Procedure:**

1. Edit a post/page
2. Click "Add block" (+)
3. Search "Wilson API"

**Expected Result:**

- "Wilson API Data Table" block appears
- Block icon shows database icon
- Block description present

**Status:** ☐ Pass ☐ Fail

---

#### Test 4.2: Block Insertion

**Procedure:**

1. Insert "Wilson API Data Table" block
2. Wait for data to load

**Expected Result:**

- Loading spinner appears
- Data loads and displays in table
- No JavaScript errors in console
- Block settings panel available

**Status:** ☐ Pass ☐ Fail

---

#### Test 4.3: Column Visibility Toggle

**Procedure:**

1. Insert block
2. Open block settings (right sidebar)
3. Expand "Column Visibility" panel
4. Toggle various columns off/on

**Expected Result:**

- Columns immediately show/hide
- At least one column toggle present
- Changes persist when switching between posts
- Warning shown if all columns disabled

**Status:** ☐ Pass ☐ Fail

---

#### Test 4.4: Header Visibility Toggle

**Procedure:**

1. Insert block
2. Toggle "Show Table Header" in settings

**Expected Result:**

- Header row shows/hides immediately
- No layout issues
- Change persists

**Status:** ☐ Pass ☐ Fail

---

#### Test 4.5: Block Frontend Display

**Procedure:**

1. Insert block in post
2. Save/publish post
3. View post on frontend

**Expected Result:**

- Block displays on frontend
- Data loads via AJAX
- Loading state shows initially
- Table renders with data
- Responsive on mobile

**Status:** ☐ Pass ☐ Fail

---

#### Test 4.6: Multiple Blocks

**Procedure:**

1. Insert 3 "Wilson API Data Table" blocks
2. Configure each differently (column visibility)
3. Save and view frontend

**Expected Result:**

- All blocks display correctly
- Each maintains its own settings
- No JavaScript conflicts
- All blocks fetch data successfully

**Status:** ☐ Pass ☐ Fail

---

### 5. WP-CLI Tests

#### Test 5.1: Command Registration

**Procedure:**

```bash
wp wilson-api
```

**Expected Result:**

- Command displays help
- Shows available subcommands
- "refresh" subcommand listed

**Status:** ☐ Pass ☐ Fail

---

#### Test 5.2: Refresh Command

**Procedure:**

```bash
wp wilson-api refresh
```

**Expected Result:**

- Success message about marking for refresh
- Data fetched from API
- Item count displayed
- Cache expiration info shown
- No PHP errors

**Status:** ☐ Pass ☐ Fail

---

#### Test 5.3: Command Forces Cache Bypass

**Procedure:**

1. Make AJAX request, note data
2. Run `wp wilson-api refresh`
3. Immediately make another AJAX request

**Expected Result:**

- WP-CLI command fetches fresh data
- AJAX request returns newly cached data
- Cache timestamp is recent

**Status:** ☐ Pass ☐ Fail

---

### 6. Security Tests

#### Test 6.1: XSS Prevention

**Procedure:**

1. Modify API response to include `<script>alert('XSS')</script>`
2. View data in admin page and block

**Expected Result:**

- Script tags are escaped
- No alert popup
- Data displayed as text, not executed

**Status:** ☐ Pass ☐ Fail

---

#### Test 6.2: CSRF Protection (Admin Refresh)

**Procedure:**

1. Make AJAX refresh request without nonce
2. Make request with invalid nonce

**Expected Result:**

- Request rejected
- Error message about security check
- Data not refreshed

**Status:** ☐ Pass ☐ Fail

---

#### Test 6.3: Authorization Check

**Procedure:**

1. Log in as Subscriber
2. Attempt to call refresh AJAX endpoint

**Expected Result:**

- Error: 403 Forbidden
- Message about insufficient permissions

**Status:** ☐ Pass ☐ Fail

---

#### Test 6.4: SQL Injection (N/A)

**Note:** Plugin doesn't use custom database queries, but verify no SQL is constructed from user input.

**Status:** ☐ Pass ☐ Fail ☐ N/A

---

### 7. Error Handling Tests

#### Test 7.1: API Endpoint Unavailable

**Procedure:**

1. Block API endpoint (firewall/hosts file)
2. Make AJAX request

**Expected Result:**

- Error message displayed
- Stale cache returned (if available)
- Error logged
- No PHP fatal errors

**Status:** ☐ Pass ☐ Fail

---

#### Test 7.2: Invalid API Response

**Procedure:**

1. Mock API to return invalid JSON
2. Make AJAX request

**Expected Result:**

- Error message about invalid JSON
- Stale cache returned (if available)
- User-friendly error message

**Status:** ☐ Pass ☐ Fail

---

#### Test 7.3: API Returns 500 Error

**Procedure:**

1. Mock API to return 500 status
2. Make AJAX request

**Expected Result:**

- Error about invalid response code
- Graceful degradation
- Error logged

**Status:** ☐ Pass ☐ Fail

---

### 8. Performance Tests

#### Test 8.1: Caching Reduces API Calls

**Procedure:**

1. Monitor network tab
2. Make 10 AJAX requests within 1 minute

**Expected Result:**

- Only 1 external API call made
- Subsequent requests served from cache
- Response time < 100ms for cached requests

**Status:** ☐ Pass ☐ Fail

---

#### Test 8.2: Page Load Performance

**Procedure:**

1. Add block to page
2. Measure page load time (Lighthouse/GTmetrix)

**Expected Result:**

- Page load time < 3 seconds
- No blocking scripts
- Async data loading

**Status:** ☐ Pass ☐ Fail

---

### 9. Compatibility Tests

#### Test 9.1: PHP Version Compatibility

**Procedure:**
Test on PHP 7.4, 8.0, 8.1, 8.2

**Expected Result:**

- No PHP errors on any version
- All features work
- No deprecated function warnings

**Status:**

- PHP 7.4: ☐ Pass ☐ Fail
- PHP 8.0: ☐ Pass ☐ Fail
- PHP 8.1: ☐ Pass ☐ Fail
- PHP 8.2: ☐ Pass ☐ Fail

---

#### Test 9.2: WordPress Version Compatibility

**Procedure:**
Test on WordPress 6.0, 6.1, 6.2, 6.3, 6.4

**Expected Result:**

- Plugin activates successfully
- All features work
- Block renders correctly

**Status:** ☐ Pass ☐ Fail

---

#### Test 9.3: Theme Compatibility

**Procedure:**
Test with various themes (Twenty Twenty-Four, Astra, etc.)

**Expected Result:**

- Block displays correctly
- No CSS conflicts
- Responsive on all themes

**Status:** ☐ Pass ☐ Fail

---

### 10. Internationalization Tests

#### Test 10.1: Translatable Strings

**Procedure:**

1. Check all user-facing strings have `__()` or `_e()`
2. Verify text domain is correct: `wilson-api-plugin`

**Expected Result:**

- All strings wrapped in translation functions
- Correct text domain used
- POT file can be generated

**Status:** ☐ Pass ☐ Fail

---

## Test Results Summary

| Category        | Tests Passed | Tests Failed | Pass Rate |
| --------------- | ------------ | ------------ | --------- |
| Installation    | /            | /            | %         |
| AJAX Endpoint   | /            | /            | %         |
| Admin Page      | /            | /            | %         |
| Gutenberg Block | /            | /            | %         |
| WP-CLI          | /            | /            | %         |
| Security        | /            | /            | %         |
| Error Handling  | /            | /            | %         |
| Performance     | /            | /            | %         |
| Compatibility   | /            | /            | %         |
| i18n            | /            | /            | %         |
| **TOTAL**       | /            | /            | %         |

## Known Issues

Document any known issues or limitations:

1.
2.
3.

## Testing Tools Used

- [ ] Browser DevTools
- [ ] WP-CLI
- [ ] PHP Debug Log
- [ ] Network Monitor
- [ ] Lighthouse
- [ ] PHP CodeSniffer
- [ ] PHPUnit (if applicable)

## Tester Information

- **Tester Name:** ******\_\_\_\_******
- **Date:** ******\_\_\_\_******
- **Environment:** ******\_\_\_\_******
- **WordPress Version:** ******\_\_\_\_******
- **PHP Version:** ******\_\_\_\_******
- **Browser:** ******\_\_\_\_******

## Notes

Additional observations or comments:

---

## Automated Testing

For continuous integration, consider adding:

```bash
# PHP Syntax Check
find . -name "*.php" -exec php -l {} \;

# Code Standards
composer phpcs

# JavaScript Linting
npm run lint:js

# Unit Tests (if implemented)
composer test
```

---

**Remember:** Update this document as new features are added or tests modified.
