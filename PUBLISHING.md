# Publishing Guide for Monnify for The Events Calendar

This guide outlines the steps to publish the plugin on WordPress.org.

## Pre-Publication Checklist

### Code Quality
- [x] Plugin follows WordPress coding standards
- [x] Proper sanitization and escaping of user inputs
- [x] Security: Webhook signature verification using HMAC-SHA512
- [x] Security: Server-side transaction verification (no client trust)
- [x] Proper nonce and permission handling (via The Events Calendar framework)
- [x] External service properly documented (Monnify API)

### Files
- [x] `readme.txt` - Complete with all required sections
- [x] `LICENSE` - GPL v3 license file included
- [x] `.gitignore` - Development files excluded from git
- [x] `.distignore` - Files excluded from SVN distribution
- [x] Plugin header - Complete with all required fields

### Documentation
- [x] External services section in readme.txt
- [x] Installation instructions
- [x] Feature list
- [x] Changelog

## Step-by-Step Publishing Process

### 1. Prepare the Repository

Before submitting to WordPress.org, ensure all changes are committed:

```bash
git add -A
git commit -m "Prepare plugin for WordPress.org publication"
```

### 2. Create WordPress.org Account

1. Go to https://wordpress.org/plugins/
2. Click "Create your plugin page"
3. Sign in or create a WordPress.org account if needed

### 3. Submit Plugin for Review

1. Visit https://wordpress.org/plugins/submit/
2. Fill in the plugin information:
   - **Plugin Name:** Monnify for The Events Calendar
   - **Plugin URL:** https://github.com/yourusername/Monnify-WP-Events-Plugin
   - **Description:** Add-on for The Events Calendar that allows you to accept payments for event tickets via Monnify
   - **Plugin Directory URL slug:** monnify-for-events-calendar

3. Upload the plugin as a ZIP file or connect your GitHub repository

### 4. Plugin SVN Setup (if using manual submission)

1. Create a WordPress.org account and confirm ownership
2. Check out the plugin repository:
   ```bash
   svn co https://plugins.svn.wordpress.org/monnify-for-events-calendar/trunk
   ```

3. Copy plugin files to trunk (excluding files in .distignore)

4. Commit to SVN:
   ```bash
   svn add .
   svn commit -m "Initial release of Monnify for The Events Calendar v1.0.0"
   ```

### 5. GitHub to SVN Automation (Recommended)

Set up GitHub Actions to automatically sync your GitHub repository to WordPress.org SVN:

1. Create `.github/workflows/wordpress-plugin-deploy.yml`:
   ```yaml
   name: Deploy to WordPress.org
   
   on:
     push:
       tags:
         - '*'
   
   jobs:
     deploy:
       runs-on: ubuntu-latest
       steps:
         - uses: actions/checkout@v2
         - name: Build
           run: |
             npm install
             npm run build
         - name: WordPress Plugin Deploy
           uses: 10up/action-wordpress-plugin-deploy@stable
           env:
             SVN_USERNAME: ${{ secrets.SVN_USERNAME }}
             SVN_PASSWORD: ${{ secrets.SVN_PASSWORD }}
             SLUG: monnify-for-events-calendar
   ```

2. Add to GitHub repository secrets:
   - `SVN_USERNAME`: Your WordPress.org username
   - `SVN_PASSWORD`: Your WordPress.org password

### 6. Create Release Tags

When releasing a new version:

```bash
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0
```

Update version in:
- `monnify-tec.php` (Version comment)
- `readme.txt` (Stable tag and Changelog)

## Plugin Review Guidelines

The WordPress.org review team will check:

1. **Security**
   - ✅ External service properly documented
   - ✅ Proper input sanitization
   - ✅ HMAC webhook verification
   - ✅ No eval() or dangerous functions
   - ✅ Database queries use prepared statements (via framework)

2. **Functionality**
   - ✅ Plugin only activates with required plugins
   - ✅ Proper error handling
   - ✅ Works with stated requirements (WordPress 6.6+, PHP 7.4+)

3. **Code Quality**
   - ✅ Follows WordPress coding standards
   - ✅ Proper namespace usage
   - ✅ Uses proper WordPress APIs and hooks

4. **Documentation**
   - ✅ Installation instructions clear
   - ✅ External services documented
   - ✅ Dependencies clearly stated

## Post-Publication

1. Monitor the WordPress.org support forum
2. Keep changelog updated in `readme.txt`
3. Test plugin updates before release
4. Maintain compatibility with latest WordPress versions

## Resources

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [Plugin Security Best Practices](https://developer.wordpress.org/plugins/security/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [The Events Calendar Documentation](https://evnt.is/home)
