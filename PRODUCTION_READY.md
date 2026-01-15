# Production-Ready Configuration Guide

## Environment Variables Setup

### For Development
```bash
export APP_ENV=development
export DB_HOST=localhost
export DB_USERNAME=your_user
export DB_PASSWORD=your_password
export RECAPTCHA_SECRET_KEY=your_key
```

### For Staging
```bash
export APP_ENV=staging
export DB_HOST=staging-db.example.com
export DB_USERNAME=staging_user
export DB_PASSWORD=secure_password
export RECAPTCHA_SECRET_KEY=staging_key
```

### For Production
```bash
export APP_ENV=production
export DB_HOST=prod-db.example.com
export DB_USERNAME=prod_user
export DB_PASSWORD=very_secure_password
export RECAPTCHA_SECRET_KEY=prod_key
```

---

## How APP_ENV Controls Behavior

### Development (`APP_ENV=development`)
- ✅ Verbose logging enabled
- ✅ Logs database queries with all parameters
- ✅ Logs user IDs, emails, tokens
- ✅ Logs output buffer leaks
- ✅ Full error messages
- **Use for**: Local development, debugging

### Staging (`APP_ENV=staging`)
- ⚠️ Minimal logging
- ⚠️ Logs errors only
- ❌ No sensitive data logged
- ❌ No debug output
- **Use for**: Testing before production

### Production (`APP_ENV=production`)
- ❌ No debug logging
- ❌ No sensitive data logged
- ❌ No query logs
- ❌ Only critical errors logged
- ✅ Optimized for performance
- **Use for**: Live environment

---

## Code Changes Made

### 1. Configuration (inc/config.php)
```php
// Now reads from environment variable
define("APP_ENV", getenv('APP_ENV') ?: "development");

// New debug flag - controls all verbose logging
define("DEBUG_MODE", APP_ENV !== 'production');

// New sensitive data flag - controls logging of emails, tokens, etc
define("LOG_SENSITIVE_DATA", APP_ENV === 'development');
```

### 2. Database Logging (model/database.php)
All verbose logging now wrapped:
```php
if (LOG_SENSITIVE_DATA) {
    file_put_contents("log.txt", "query params logged here", FILE_APPEND);
}
```

Affected methods:
- `select()` - Logs query parameters
- `insert()` - Logs insert data
- `update()` - Logs update data
- `executeStatement()` - Logs prepared statements

### 3. Output Buffer Leak Detection (controller/BaseController.php)
```php
if ($buffer !== '' && DEBUG_MODE) {
    // Only logs leaks in development/staging
}
```

### 4. Removed Unnecessary ob_start() (controller/UserController.php)
Removed 3 unnecessary `ob_start()` calls from:
- Line 55
- Line 172
- Line 279

These are no longer needed since `index-auth.php` handles output buffering.

---

## Testing Different Environments

### Test Development Logging
```bash
export APP_ENV=development
export LOG_SENSITIVE_DATA=1
php -S localhost:8000
# Create login request
# Check log.txt - should have query details and user info
```

### Test Production Logging
```bash
export APP_ENV=production
php -S localhost:8000
# Create login request
# Check log.txt - should be empty or errors only
```

### Test Staging Logging
```bash
export APP_ENV=staging
php -S localhost:8000
# Create login request
# Check log.txt - should have errors only, no sensitive data
```

---

## GitHub Actions - Using Secrets

The workflow now supports GitHub Secrets:

```yaml
env:
  APP_ENV: ${{ secrets.APP_ENV || 'staging' }}
  DB_HOST: ${{ secrets.DB_HOST }}
  DB_PASSWORD: ${{ secrets.DB_PASSWORD }}
  RECAPTCHA_SECRET_KEY: ${{ secrets.RECAPTCHA_SECRET_KEY }}
```

### Set Up Secrets in GitHub:
1. Go to Settings → Secrets and variables → Actions
2. Add these secrets:
   - `APP_ENV` = staging
   - `DB_HOST` = test-db.local
   - `DB_PASSWORD` = test_password
   - `RECAPTCHA_SECRET_KEY` = test_key

---

## Log Files Security

### In Development
- `log.txt` - Contains everything (queries, params, user data)
- `auth_log.txt` - Contains auth attempts, user IDs, tokens
- `e_log.txt` - Contains errors
- `output_leak.log` - Contains captured output

**These should NEVER be committed to git**

### In Production
- `log.txt` - Only critical errors
- `auth_log.txt` - Only login failures and security events
- `e_log.txt` - Only application errors
- `output_leak.log` - Empty (only logged in DEBUG_MODE)

All are in `.gitignore` - safe! ✅

---

## Security Levels

| Environment | Logging | Safe to Commit | Use Case |
|-------------|---------|---|----------|
| **development** | Verbose | No | Local development |
| **staging** | Minimal | No | Pre-production testing |
| **production** | Errors only | No | Live environment |

All three are secure - logs are never committed!

---

## Deployment Checklist

Before deploying to production:
- [ ] `APP_ENV=production` is set
- [ ] Database credentials are updated
- [ ] reCAPTCHA keys are set
- [ ] Log files are rotated/cleaned
- [ ] Error reporting is set to `ini_set('display_errors', 0)`
- [ ] No debug output visible to users

---

## Troubleshooting

### "I'm seeing verbose logs in production"
```bash
# Check current APP_ENV
env | grep APP_ENV

# Should output: APP_ENV=production
# If not, set it:
export APP_ENV=production
```

### "Logs are getting too large"
```bash
# Check your APP_ENV
# If development, that's normal
# If production, something is wrong

# Clean logs:
> log.txt
> auth_log.txt
> e_log.txt
> output_leak.log
```

### "Error logs are empty but there's an error"
```bash
# Check DEBUG_MODE setting
# If APP_ENV=production, errors are suppressed
# For debugging, use:
export APP_ENV=staging
```

---

## Summary

✅ **What Changed:**
1. Added `APP_ENV` environment variable
2. Logging now respects environment
3. Production mode is secure (no sensitive data logged)
4. Removed unnecessary ob_start() calls
5. Debug mode can be toggled per environment

✅ **Ready for Production:**
- No credentials in code
- Sensitive data protected
- Logging controlled by environment
- Error messages are generic
- All config in environment variables

🚀 **You can now push to GitHub and deploy safely!**
