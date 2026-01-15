# PHP Auth Application Setup

## Quick Start

### Option 1: Complete Setup (Recommended)
```bash
bash setup.sh
```

This will:
- ✓ Verify PHP version
- ✓ Install composer dependencies
- ✓ Create and initialize database
- ✓ Set file permissions

### Option 2: Database Only
```bash
bash setup-database.sh
```

This will:
- ✓ Check MySQL connection
- ✓ Create database
- ✓ Import schema
- ✓ Verify tables

## Manual Setup Steps

### 1. Install Dependencies
```bash
cd PHPMailer
composer install
cd ..
```

### 2. Create Database
```bash
mysql -u root -p
```

```sql
CREATE DATABASE IF NOT EXISTS serverdatabase;
```

### 3. Import Schema
```bash
mysql -u crystal9_admin1968 -p serverdatabase < database-schema.sql
```

### 4. Update Database Credentials
Edit `inc/config.php` with your database details:
```php
define("DB_HOST", "localhost");
define("DB_USERNAME", "your_username");
define("DB_PASSWORD", "your_password");
define("DB_DATABASE_NAME", "serverdatabase");
```

### 5. Start Development Server
```bash
php -S localhost:8000
```

### 6. Run Tests
```bash
# Unit tests
php tests/run-tests.php

# API endpoint tests
bash tests/api-tests.sh
```

## Database Schema

The database includes these tables:

| Table | Purpose |
|-------|---------|
| `users` | User profiles (email, name, verification status) |
| `user_authentication` | Password hashes and login attempts |
| `email_verification` | Email verification tokens |
| `password_reset_tokens` | Password reset tokens |
| `auth_telemetry` | Event logging for analytics |

### Sample Data
Two test users are included:
- `unverified@example.com` - Account not verified
- `verified@example.com` - Account verified

Use these for testing signin flows.

## Environment Variables (Optional)

You can set these for the setup scripts:

```bash
export DB_HOST="localhost"
export DB_USERNAME="admindb"
export DB_PASSWORD="your_secure_database_password" # Replace with your actual password
export DB_NAME="serverdatabase"
```

Then run:
```bash
bash setup-database.sh
```

## Troubleshooting

### MySQL Connection Error
```bash
# Check if MySQL is running
mysql -u root -p -e "SELECT 1"

# On macOS with Homebrew
brew services start mysql
```

### Permission Denied
```bash
# Make scripts executable
chmod +x setup.sh setup-database.sh tests/api-tests.sh
```

### PHP Server Won't Start
```bash
# Check if port 8000 is available
lsof -i :8000

# Use different port
php -S localhost:8001
```

### Composer Not Found
```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

## File Structure
```
php-auth/
├── controller/           # API controllers
├── model/               # Database models
├── inc/                 # Configuration & bootstrap
├── tests/               # Test files
├── .github/             # GitHub Actions & docs
├── database-schema.sql  # Database schema
├── setup.sh             # Complete setup script
├── setup-database.sh    # Database-only setup
├── CODE_POLICY.md       # Development guidelines
└── README.md            # This file
```

## Next Steps

1. ✓ Run setup: `bash setup.sh`
2. ✓ Start server: `php -S localhost:8000`
3. ✓ Run tests: `php tests/run-tests.php`
4. ✓ Read guidelines: [CODE_POLICY.md](CODE_POLICY.md)
5. ✓ View testing docs: [tests/README.md](tests/README.md)

## Support

- See [CODE_POLICY.md](CODE_POLICY.md) for development guidelines
- See [tests/README.md](tests/README.md) for testing documentation
- See [.github/CONTRIBUTING.md](.github/CONTRIBUTING.md) for contribution guidelines
