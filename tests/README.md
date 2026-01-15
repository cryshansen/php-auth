# Testing Documentation

## Overview
This directory contains automated tests for the PHP Auth application endpoints and unit tests.

## Running Tests Locally

### Prerequisites
- PHP 8.1+
- MySQL 8.0+
- Composer (for PHPMailer)
- curl (for API tests)

### Setup

1. **Install dependencies:**
```bash
cd PHPMailer
composer install
cd ..
```

2. **Set up test database:**
```bash
mysql -u root -p < database-schema.sql
```

3. **Start PHP server:**
```bash
php -S localhost:8000
```

### Running Unit Tests

Test core functionality like email validation, password hashing, and sessions:

```bash
php tests/run-tests.php
```

**What it tests:**
- Email validation with `filter_var()`
- Password hashing with `password_hash()` and verification
- Session management and variable storage
- Database connection

### Running API Endpoint Tests

Test all HTTP endpoints with curl:

```bash
bash tests/api-tests.sh
```

**What it tests:**
- ✓ POST `/auth/signup` - User registration
- ✓ POST `/auth/signin` - User login (valid/invalid/unverified)
- ✓ GET `/auth/me` - Get current user (with/without session)
- ✓ POST `/auth/logout` - Logout
- ✓ POST `/auth/resetpassword` - Password reset
- ✓ GET `/users/list` - Get users list

## Automated Testing (GitHub Actions)

### Workflows

#### 1. **API Tests** (`.github/workflows/api-tests.yml`)
Runs on every push and pull request.

**Steps:**
1. Set up PHP 8.1
2. Install dependencies
3. Start MySQL service
4. Set up test database
5. Start PHP server
6. Run unit tests (`tests/run-tests.php`)
7. Run API endpoint tests (`tests/api-tests.sh`)
8. PHP linting (syntax check)
9. Upload test logs as artifacts

#### 2. **Code Quality** (`.github/workflows/code-quality.yml`)
Checks code quality and security.

**Checks:**
- PHP syntax linting
- Hardcoded credentials detection
- SQL injection pattern detection
- Debug code detection
- Security vulnerabilities

## Test Results

After running tests, check the output for:

- ✓ **PASSED**: Endpoint returned expected status code
- ✗ **FAILED**: Endpoint returned unexpected status code or body format
- **Response body**: JSON response from the endpoint

### Example Output
```
==========================================
Testing: Signin - Unverified user (should fail)
✓ PASSED (HTTP 200)

Testing: Signin - Invalid credentials
✓ PASSED (HTTP 200)

==========================================
Test Results Summary
==========================================
Passed: 10
Failed: 0

All tests passed!
```

## Troubleshooting

### MySQL Connection Issues
```bash
# Check MySQL is running
mysql -u your_username -p"your_password" -e "SELECT 1" # Use actual credentials, never commit them
```

### PHP Server Not Starting
```bash
# Check port 8000 is available
lsof -i :8000
# Kill if needed
kill -9 <PID>
```

### API Tests Failing
1. Ensure PHP server is running on `localhost:8000`
2. Check database is properly set up
3. Review response bodies in test output
4. Check `auth_log.txt` and `e_log.txt` for errors

## Continuous Integration

### Requirements for PRs
Before submitting a pull request, ensure:

1. ✓ All endpoint tests pass
2. ✓ Unit tests pass
3. ✓ PHP linting passes
4. ✓ No hardcoded credentials
5. ✓ Code quality checks pass

### Viewing CI Results
GitHub Actions results are visible in the PR checks section. Click "Details" to view:
- Full test output
- Error messages
- Coverage reports
- Artifact logs

## Adding New Tests

### Adding Unit Tests
Edit `tests/run-tests.php`:
```php
private function testNewFeature() {
    echo "Running New Feature Tests...\n";
    
    // Your test code here
    if ($condition) {
        $this->pass("Feature description");
    } else {
        $this->fail("Feature description");
    }
}

// Add to run() method:
$this->testNewFeature();
```

### Adding API Tests
Edit `tests/api-tests.sh`:
```bash
test_endpoint "POST" "/endpoint/path" \
    '{"param":"value"}' \
    "200" \
    "Test description"
```

## Database Schema

Create `database-schema.sql` with:
```sql
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    firstname VARCHAR(100),
    lastname VARCHAR(100),
    is_verified TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_authentication (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    password_hash VARCHAR(255),
    failed_attempts INT DEFAULT 0,
    lock_until DATETIME,
    ip_address VARCHAR(45),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Add more tables as needed
```

## Performance Testing

For load testing endpoints, use Apache Bench:
```bash
ab -n 1000 -c 100 http://localhost:8000/auth/signin
```

Or wrk:
```bash
wrk -t4 -c100 -d30s http://localhost:8000/auth/signin
```

## Security Testing

Manual security checks:
```bash
# Check for SQL injection vulnerabilities
grep -r "mysql_query\|\$_GET.*mysql" .

# Check for hardcoded credentials
grep -r "password\s*=\s*['\"]" .

# Check for XSS vulnerabilities
grep -r "echo.*\$_GET\|echo.*\$_POST" .
```

Automated checks run in `.github/workflows/code-quality.yml`

## References

- [PHP Testing Best Practices](https://www.php.net/manual/en/function.password-hash.php)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [OWASP Testing Guide](https://owasp.org/www-project-web-security-testing-guide/)
