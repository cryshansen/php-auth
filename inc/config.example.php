<?php
/**
 * Configuration File
 * 
 * IMPORTANT: This file should NOT be committed to version control!
 * Copy config.example.php to config.php and update with your credentials.
 * 
 * Add config.php to .gitignore
 */

// Database Configuration
define("DB_HOST", getenv('DB_HOST') ?: "localhost");
define("DB_USERNAME", getenv('DB_USERNAME') ?: "root");
define("DB_PASSWORD", getenv('DB_PASSWORD') ?: "");
define("DB_DATABASE_NAME", getenv('DB_DATABASE_NAME') ?: "php_auth");

// Application Configuration
define("APP_ENV", getenv('APP_ENV') ?: "development");
define("APP_DEBUG", getenv('APP_DEBUG') ?: true);

// Email Configuration (optional)
define("MAIL_FROM", getenv('MAIL_FROM') ?: "noreply@example.com");
define("MAIL_HOST", getenv('MAIL_HOST') ?: "smtp.example.com");
define("MAIL_USERNAME", getenv('MAIL_USERNAME') ?: "");
define("MAIL_PASSWORD", getenv('MAIL_PASSWORD') ?: "");

// Session Configuration
define("SESSION_LIFETIME", getenv('SESSION_LIFETIME') ?: 3600); // 1 hour
define("SESSION_SECURE", getenv('SESSION_SECURE') ?: false);

/**
 * NEVER commit actual credentials to version control!
 * 
 * For development:
 *   1. Copy config.example.php to config.php
 *   2. Update config.php with your local credentials
 *   3. config.php is in .gitignore (not committed)
 * 
 * For production:
 *   1. Use environment variables (set on server/container)
 *   2. Load from secure configuration management
 *   3. Never expose credentials in code
 */
?>
