#!/bin/bash

# =====================================================
# PHP Auth Application - Database Setup Script
# =====================================================
# Usage: bash setup-database.sh
# This script initializes the database with the proper schema

set -e  # Exit on error

echo "=========================================="
echo "PHP Auth Database Setup"
echo "=========================================="
echo ""

# Load credentials from config (optional)
DB_HOST=${DB_HOST:-"localhost"}
DB_USERNAME=${DB_USERNAME:-"username"}
DB_PASSWORD=${DB_PASSWORD:-""} # Use environment variable, do not hardcode
DB_NAME=${DB_NAME:-"databasename"}

echo "Database Configuration:"
echo "  Host: $DB_HOST"
echo "  Database: $DB_NAME"
echo "  User: $DB_USERNAME"
echo ""

# Check if MySQL is running
echo "Checking MySQL connection..."
if ! mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1" &> /dev/null; then
    echo "❌ Error: Cannot connect to MySQL"
    echo "   Make sure MySQL is running and credentials are correct"
    exit 1
fi
echo "✓ MySQL connection successful"
echo ""

# Create database if it doesn't exist
echo "Creating database if it doesn't exist..."
mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"
echo "✓ Database ready"
echo ""

# Run schema
echo "Importing database schema..."
if mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_NAME" < database-schema.sql; then
    echo "✓ Schema imported successfully"
else
    echo "❌ Error: Failed to import schema"
    exit 1
fi
echo ""

# Verify tables
echo "Verifying tables..."
TABLE_COUNT=$(mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_NAME" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME';" | tail -1)

echo "✓ Found $TABLE_COUNT tables:"
mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_NAME" -e "SHOW TABLES;"

echo ""
echo "=========================================="
echo "✓ Database setup complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "  1. Update database credentials in inc/config.php (if needed)"
echo "  2. Start PHP server: php -S localhost:8000"
echo "  3. Run tests: php tests/run-tests.php"
echo ""
