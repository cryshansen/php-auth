# Environment Variables Setup Guide

## Overview
Your application now uses environment variables for all sensitive data instead of hardcoding them.

## Local Development Setup

### Step 1: Copy the Example File
```bash
cp .env.example .env
```

### Step 2: Edit .env with Your Credentials
```bash
nano .env  # or your preferred editor
```

### Step 3: Set Your Values
```env
# Database
DB_HOST=localhost
DB_USERNAME=crystal9_admin1968
DB_PASSWORD=your_actual_password_here
DB_DATABASE_NAME=crystal9_test

# reCAPTCHA
RECAPTCHA_SITE_KEY=your_site_key_here
RECAPTCHA_SECRET_KEY=your_secret_key_here

# Email
MAIL_FROM=noreply@example.com
MAIL_USERNAME=your_email@example.com
MAIL_PASSWORD=your_email_password_here
```

### Step 4: Load Environment Variables

**Option A: In PHP (add to inc/bootstrap.php)**
```php
// Load .env file
if (file_exists(__DIR__ . '/../.env')) {
    $envFile = __DIR__ . '/../.env';
    $env = parse_ini_file($envFile);
    foreach ($env as $key => $value) {
        if (!getenv($key)) {
            putenv("$key=$value");
        }
    }
}
```

**Option B: In Shell (before running PHP)**
```bash
export DB_HOST=localhost
export DB_USERNAME=crystal9_admin1968
export DB_PASSWORD=your_password
export RECAPTCHA_SECRET_KEY=your_key
```

**Option C: Docker Compose**
```yaml
services:
  app:
    environment:
      - DB_HOST=mysql
      - DB_USERNAME=${DB_USERNAME}
      - DB_PASSWORD=${DB_PASSWORD}
      - RECAPTCHA_SECRET_KEY=${RECAPTCHA_SECRET_KEY}
```

## Environment Variables Reference

### Database
- `DB_HOST` - MySQL server hostname (default: localhost)
- `DB_USERNAME` - Database username
- `DB_PASSWORD` - Database password
- `DB_DATABASE_NAME` - Database name

### Application
- `APP_ENV` - Environment: development, staging, or production
- `APP_DEBUG` - Enable debug output (true/false)
- `APP_URL` - Application base URL

### reCAPTCHA
- `RECAPTCHA_SITE_KEY` - Frontend reCAPTCHA key
- `RECAPTCHA_SECRET_KEY` - Backend reCAPTCHA verification key

### Email
- `MAIL_FROM` - Sender email address
- `MAIL_HOST` - SMTP server hostname
- `MAIL_PORT` - SMTP server port (usually 587 or 465)
- `MAIL_USERNAME` - SMTP username
- `MAIL_PASSWORD` - SMTP password
- `MAIL_ENCRYPTION` - Encryption type: tls or ssl

### Session
- `SESSION_LIFETIME` - Session timeout in seconds (default: 3600)
- `SESSION_SECURE` - Use secure cookies (true/false)
- `SESSION_DOMAIN` - Session cookie domain

### Security
- `ACCOUNT_LOCKOUT_ATTEMPTS` - Failed login attempts before lockout
- `ACCOUNT_LOCKOUT_DURATION` - Lockout duration in seconds

## GitHub Actions / CI/CD Setup

### Setting Secrets in GitHub

1. Go to your repository
2. **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret**
4. Add these secrets:

| Secret Name | Value |
|-------------|-------|
| `DB_HOST` | localhost |
| `DB_USERNAME` | crystal9_admin1968 |
| `DB_PASSWORD` | your_secure_password |
| `DB_NAME` | crystal9_test |
| `DB_ROOT_PASSWORD` | root_password |
| `RECAPTCHA_SECRET_KEY` | your_key |

### Using Secrets in Workflows

```yaml
jobs:
  test:
    runs-on: ubuntu-latest
    env:
      DB_HOST: ${{ secrets.DB_HOST }}
      DB_USERNAME: ${{ secrets.DB_USERNAME }}
      DB_PASSWORD: ${{ secrets.DB_PASSWORD }}
      RECAPTCHA_SECRET_KEY: ${{ secrets.RECAPTCHA_SECRET_KEY }}
```

## Production Deployment

### Option 1: Environment File
```bash
# On your server
cp .env.example .env
# Edit with production credentials
nano .env
```

### Option 2: System Environment Variables
```bash
# In your web server config (Apache, Nginx)
SetEnv DB_HOST production-mysql-host
SetEnv DB_USERNAME production_user
SetEnv DB_PASSWORD production_secure_password
```

### Option 3: Container Orchestration (Docker/Kubernetes)
```yaml
# docker-compose.yml
services:
  app:
    image: myapp:latest
    environment:
      DB_HOST: ${PROD_DB_HOST}
      DB_USERNAME: ${PROD_DB_USERNAME}
      DB_PASSWORD: ${PROD_DB_PASSWORD}
```

```bash
# kubernetes secrets
kubectl create secret generic app-secrets \
  --from-literal=DB_PASSWORD=secure_password \
  --from-literal=RECAPTCHA_SECRET_KEY=key
```

## Accessing Variables in Code

### In PHP:
```php
// Using getenv()
$dbHost = getenv('DB_HOST');
$dbPassword = getenv('DB_PASSWORD');

// With fallback/default
$dbHost = getenv('DB_HOST') ?: 'localhost';

// In config files
define("DB_HOST", getenv('DB_HOST') ?: "localhost");
define("RECAPTCHA_SECRET", getenv('RECAPTCHA_SECRET_KEY') ?: '');
```

### In Controllers:
```php
public function verifyCaptcha($response) {
    $secret = getenv('RECAPTCHA_SECRET_KEY');
    if (!$secret) {
        throw new Exception("reCAPTCHA secret not configured");
    }
    // Use $secret for verification
}
```

## Security Best Practices

### ✅ DO:
- ✓ Use environment variables for all secrets
- ✓ Never commit .env files
- ✓ Keep .env in .gitignore
- ✓ Use different credentials per environment
- ✓ Rotate credentials regularly
- ✓ Use strong, random passwords
- ✓ Restrict file permissions: `chmod 600 .env`

### ❌ DON'T:
- ✗ Hardcode credentials in source files
- ✗ Commit .env files to git
- ✗ Share .env files via email/chat
- ✗ Use weak passwords
- ✗ Leave test credentials in production
- ✗ Log sensitive values

## Troubleshooting

### "Error: DB_HOST not defined"
```php
// Check that environment variable is loaded
echo getenv('DB_HOST');  // Should output your host

// Or set it explicitly
putenv("DB_HOST=localhost");
```

### "Connection failed" after setting env vars
```bash
# Verify environment variables are set
env | grep DB_

# Check PHP can read them
php -r "echo getenv('DB_HOST');"
```

### Secrets not available in GitHub Actions
1. Make sure secrets are in the repository settings
2. Secret names are case-sensitive
3. Use `${{ secrets.SECRET_NAME }}` syntax
4. Workflow must be on the default branch

## Additional Resources

- [Environment Variables in PHP](https://www.php.net/manual/en/function.getenv.php)
- [GitHub Actions Secrets](https://docs.github.com/en/actions/security-guides/encrypted-secrets)
- [Twelve Factor App - Config](https://12factor.net/config)
- [OWASP - Secrets Management](https://owasp.org/www-community/Secrets_Management)

## Questions?

Check [CONFIGURATION.md](CONFIGURATION.md) for more configuration information.
