#!/bin/bash

# =====================================================
# PHP Auth Application - Full Setup Script
# =====================================================
# Usage: bash setup.sh
# This script does a complete setup for development

set -e  # Exit on error

echo "=========================================="
echo "PHP Auth Application - Complete Setup"
echo "=========================================="
echo ""

# Check PHP version
echo "Checking PHP version..."
PHP_VERSION=$(php -v | grep "PHP" | awk '{print $2}')
echo "✓ PHP $PHP_VERSION"
echo ""

# Check for Composer
echo "Checking Composer..."
if ! command -v composer &> /dev/null; then
    echo "❌ Composer not found. Please install Composer first."
    echo "   Visit: https://getcomposer.org/download/"
    exit 1
fi
echo "✓ Composer found"
echo ""

# Install PHPMailer dependencies
echo "Installing PHPMailer dependencies..."
if [ -d "PHPMailer" ]; then
    cd PHPMailer
    composer install
    cd ..
    echo "✓ PHPMailer dependencies installed"
else
    echo "⚠ PHPMailer directory not found"
fi
echo ""

# Setup database
echo "Setting up database..."
if bash setup-database.sh; then
    echo "✓ Database setup complete"
else
    echo "❌ Database setup failed"
    exit 1
fi
echo ""

# Check file permissions
echo "Setting up file permissions..."
chmod +x tests/api-tests.sh
chmod +x .github/scripts/security-check.sh
echo "✓ Permissions set"
echo ""

# Summary
echo "=========================================="
echo "✓ Setup Complete!"
echo "=========================================="
echo ""
echo "You're ready to start developing!"
echo ""
echo "Quick start:"
echo "  php -S localhost:8000"
echo ""
echo "Then in another terminal:"
echo "  php tests/run-tests.php"
echo "  bash tests/api-tests.sh"
echo ""
echo "Documentation:"
echo "  - CODE_POLICY.md"
echo "  - tests/README.md"
echo "  - .github/CONTRIBUTING.md"
echo ""
