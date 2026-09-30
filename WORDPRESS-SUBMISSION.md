# WordPress.org Submission Checklist

Your plugin is now ready for submission! Use this checklist for the final steps.

## ✅ Pre-Submission Verification

Before submitting, verify these items:

- [x] Plugin header is complete and correct
- [x] `readme.txt` follows WordPress format
- [x] LICENSE file is included (GPLv3)
- [x] All required sections in readme.txt:
  - [x] Description
  - [x] Installation
  - [x] Features
  - [x] External Services (Monnify API documented)
  - [x] Changelog
- [x] External service properly documented with:
  - [x] What the service does
  - [x] What data is sent
  - [x] When data is sent
  - [x] Links to Terms of Service
  - [x] Links to Privacy Policy
- [x] No sensitive files in repository
- [x] Code follows WordPress standards
- [x] Proper security practices implemented

## 📋 Submission Steps

### Option 1: Direct WordPress.org Submission (Easiest)

1. Go to https://wordpress.org/plugins/submit/
2. Provide plugin information
3. Upload ZIP or connect GitHub repository
4. Wait for review (3-5 business days)

### Option 2: GitHub to SVN Automation

1. Create WordPress.org account
2. Set up GitHub Actions (see PUBLISHING.md)
3. Tag releases in GitHub (`git tag v1.0.0`)
4. Automatic sync to WordPress.org SVN

## 📝 Creating a Release

To create a new release:

1. Update version number in:
   ```
   monnify-tec.php  (line 22)
   readme.txt       (line 6 and Changelog)
   ```

2. Commit and tag:
   ```bash
   git add .
   git commit -m "Release version X.Y.Z"
   git tag -a vX.Y.Z -m "Release version X.Y.Z"
   git push origin main vX.Y.Z
   ```

3. The plugin will automatically deploy to WordPress.org (if using GitHub Actions)

## 🔍 What the Review Team Looks For

### Security ✓
- Proper input sanitization
- Output escaping
- Nonce verification
- HMAC webhook verification
- No dangerous functions (eval, exec, etc.)

### Quality ✓
- WordPress coding standards
- Proper dependency handling
- Clear documentation
- Error handling
- Works with stated requirements

### Compatibility ✓
- WordPress 6.6+
- PHP 7.4+
- The Events Calendar integration
- Event Tickets add-on

## 📞 After Approval

Once approved:

1. Monitor WordPress.org support forum
2. Keep plugin updated with latest WordPress versions
3. Maintain backward compatibility when possible
4. Update changelog for each release

## 🎯 Important Notes

- **Plugin Slug:** `monnify-for-events-calendar` (set by WordPress.org)
- **Main File:** `monnify-tec.php` (WordPress.org will find this automatically)
- **Text Domain:** `monnify-for-events-calendar` (for translations)
- **Requires:** The Events Calendar + Event Tickets
- **License:** GPLv3 or later

## 📚 Additional Resources

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [Plugin Security Best Practices](https://developer.wordpress.org/plugins/security/)
- [Plugin Review Guidelines](https://developer.wordpress.org/plugins/wordpress-org-plugin-guidelines/)

---

**Ready to submit?** Visit https://wordpress.org/plugins/submit/
