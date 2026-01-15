# PHP Auth Application - Code Policy

## Overview
This document defines the code policies, standards, and best practices for the PHP Auth application. All contributors must follow these guidelines.

## Table of Contents
1. [HTTP Status Codes](#http-status-codes)
2. [API Response Format](#api-response-format)
3. [Authentication & Verification](#authentication--verification)
4. [Input Validation](#input-validation)
5. [Security Requirements](#security-requirements)
6. [Database Operations](#database-operations)
7. [Error Handling](#error-handling)
8. [Logging Policy](#logging-policy)
9. [Testing Requirements](#testing-requirements)
10. [Code Review Checklist](#code-review-checklist)

---

## HTTP Status Codes

Use standard HTTP status codes consistently:

| Code | Usage | Example |
|------|-------|---------|
| **200** | Success | Login, user data retrieval |
| **400** | Bad Request | Invalid email format, missing fields |
| **401** | Unauthorized | No session, user not verified |
| **422** | Unprocessable Entity | Captcha failure, business logic validation |
| **500** | Server Error | Database errors, uncaught exceptions |

### Special Case: Login Endpoint
The `/auth/signin` endpoint returns **200 for both success and failure** to allow the frontend to reliably parse error messages without fetch throwing errors. The response body contains `success: true/false` to indicate the outcome.

---

## API Response Format

### Success Response
```json
{
  "success": true,
  "message": "Action completed successfully",
  "data": {
    "id": 123,
    "email": "user@example.com"
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Descriptive error message that can be shown to user"
}
```

### Requirements
- Always include `success` field (boolean)
- Always include `message` field (string)
- Optional `data` field for additional info
- No internal error details exposed
- Consistent field names across all endpoints

---

## Authentication & Verification

### Session Management
```php
// After successful login
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['authenticated_at'] = time();
```

### Protected Endpoint Template
```php
public function protectedAction() {
    // Check session
    if (empty($_SESSION['user_id'])) {
        $this->sendOutput(
            json_encode(["success" => false, "message" => "Unauthenticated"]),
            ['Content-Type: application/json', 'HTTP/1.1 401 Unauthorized']
        );
        return;
    }
    
    // Verify user exists and is verified
    $user = (new AuthenticationModel())->getUserById($_SESSION['user_id']);
    
    if (!$user || !$user[0]['is_verified']) {
        $this->sendOutput(
            json_encode(["success" => false, "message" => "Email not verified"]),
            ['Content-Type: application/json', 'HTTP/1.1 401 Unauthorized']
        );
        return;
    }
    
    // Safe to proceed with user data
}
```

### Email Verification
- New users must verify email before login succeeds
- Return error: `"Please verify your email first."`
- Never create session for unverified users
- Verification tokens expire after 24 hours

---

## Input Validation

### Email Validation
```php
$email = trim(strtolower($data['username']));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return [
        'success' => false,
        'message' => 'Invalid email address.'
    ];
}
```

### Password Requirements (suggested)
- Minimum 8 characters
- Must contain uppercase letter
- Must contain number
- Consider special characters

### Sanitization
```php
// Always trim user inputs
$firstname = trim($data['firstname'] ?? '');
$lastname = trim($data['lastname'] ?? '');
$email = trim(strtolower($data['email'] ?? ''));
```

---

## Security Requirements

### Password Handling
```php
// CORRECT: Hash before storage
$hash = password_hash($password, PASSWORD_DEFAULT);

// CORRECT: Verify during login
if (password_verify($user_password, $stored_hash)) {
    // Login successful
}

// WRONG: Never store plain text passwords
// WRONG: Never use md5() or sha1()
```

### Credentials Management
```php
// WRONG: Never hardcode credentials
define("DB_PASSWORD", "hardcoded_password");

// CORRECT: Use environment variables
$password = getenv('DB_PASSWORD');
// Or configuration files excluded from git
```

### Account Lockout
```php
// Lock account after 4 failed attempts for 24 hours
if ($failed_attempts >= 4) {
    $lockUntil = (new DateTime())->modify('+24 hours');
    // Update database with lock_until timestamp
}
```

### Session Security
- Use secure session cookies (HTTPS only in production)
- Set `HttpOnly` flag to prevent JavaScript access
- Set `SameSite` attribute to prevent CSRF
- Consider session regeneration after login

---

## Database Operations

### Prepared Statements (REQUIRED)
```php
// CORRECT: Prepared statement with placeholders
$sql = "SELECT * FROM users WHERE email = :email";
$data = ['email' => $email];
$result = $this->select($sql, $data);

// WRONG: String concatenation
$sql = "SELECT * FROM users WHERE email = '$email'"; // SQL INJECTION!
```

### Parameter Binding
```php
$sql = "UPDATE users SET firstname = :firstname, is_verified = :is_verified WHERE user_id = :user_id";
$data = [
    'firstname' => $firstname,
    'is_verified' => 1,
    'user_id' => $user_id
];
$result = $this->update($sql, $data);
```

### Error Handling
```php
try {
    $result = $this->executeQuery($sql, $data);
} catch (PDOException $e) {
    file_put_contents("e_log.txt", "Database error: " . $e->getMessage() . "\n", FILE_APPEND);
    return ['success' => false, 'message' => 'Database error. Please try again.'];
}
```

---

## Error Handling

### Try/Catch Pattern
```php
try {
    $authenticationModel = new AuthenticationModel();
    $result = $authenticationModel->signinUser($email, $password);
    
    if ($result['success']) {
        $_SESSION['user_id'] = $result['user_id'];
        $responseData = json_encode($result);
        $strHeader = 'HTTP/1.1 200 OK';
    } else {
        $responseData = json_encode($result);
        $strHeader = 'HTTP/1.1 200 OK';
    }
} catch (Error $e) {
    file_put_contents("e_log.txt", "Error: " . $e->getMessage() . "\n", FILE_APPEND);
    
    $strErrorDesc = 'Something went wrong! Please contact support.';
    $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
}
```

### Error Messages
- **User-friendly**: "Invalid email or password"
- **Not**: "Database connection error: SQLSTATE[HY000]"
- **Log detailed errors** for debugging
- **Return generic errors** to frontend

---

## Logging Policy

### Approved Log Files
- `auth_log.txt` - Authentication-related operations
- `e_log.txt` - Error logs
- `log.txt` - General application logs

### What to Log
```php
file_put_contents("auth_log.txt", "User login attempt for: " . $email . "\n", FILE_APPEND);
file_put_contents("auth_log.txt", "Password verification: " . ($verified ? "SUCCESS" : "FAILED") . "\n", FILE_APPEND);
file_put_contents("e_log.txt", "Database error: " . $e->getMessage() . "\n", FILE_APPEND);
```

### What NOT to Log
```php
// WRONG: Never log passwords
file_put_contents("auth_log.txt", "Password: $password\n", FILE_APPEND);

// WRONG: Never log full password hashes (they could be attacked)
file_put_contents("auth_log.txt", "Hash: $password_hash\n", FILE_APPEND);

// WRONG: Never log sensitive tokens
file_put_contents("auth_log.txt", "JWT: $token\n", FILE_APPEND);
```

### Log Maintenance
- Review logs regularly for suspicious activity
- Rotate logs in production (weekly/monthly)
- Never commit log files with sensitive data
- Add `.gitignore` entries for log files

---

## Testing Requirements

### Before Submitting PR
- [ ] All endpoint tests pass
- [ ] Unit tests pass  
- [ ] PHP linting passes
- [ ] No hardcoded credentials
- [ ] Security checks pass
- [ ] Code review checklist complete

### Test Coverage
Test these scenarios for each endpoint:

**Authentication Endpoints:**
- Valid login with verified user
- Invalid credentials
- Unverified user login attempt
- Locked account
- Valid password reset request
- Session creation/destruction

**User Endpoints:**
- Create user with valid data
- Create user with duplicate email
- Invalid email format
- Get user with valid session
- Get user without session
- Update profile

### Running Tests
```bash
# Unit tests
php tests/run-tests.php

# API endpoint tests
bash tests/api-tests.sh

# PHP linting
php -l controller/*.php model/*.php
```

---

## Code Review Checklist

Before approving a PR, verify:

- [ ] **No SQL Injection**: All queries use prepared statements
- [ ] **Input Validated**: Email, passwords, and fields checked
- [ ] **Error Handling**: Try/catch blocks, proper JSON responses
- [ ] **Authentication**: Session checks on protected endpoints
- [ ] **Verification**: Email verification enforced
- [ ] **HTTP Codes**: Correct status codes used
- [ ] **Response Format**: Consistent JSON structure
- [ ] **No Hardcoded Secrets**: Credentials not in code
- [ ] **Passwords Hashed**: Never plain text storage
- [ ] **Account Lockout**: Failed attempt tracking
- [ ] **CORS Headers**: Set correctly
- [ ] **Tests Passing**: CI checks green
- [ ] **Documentation**: Changes documented

---

## Common Pitfalls

### ❌ WRONG - Returning 401 on login failure
```php
$strHeader = 'HTTP/1.1 401 Unauthorized';  // BAD for login
```

### ✓ CORRECT - Returning 200 on login attempt
```php
$strHeader = 'HTTP/1.1 200 OK';  // GOOD for login
// success field in response indicates outcome
```

### ❌ WRONG - Inconsistent response format
```php
// Login response
{"success": true, "message": "..."}

// Error response
{"status": "error", "message": "..."}  // INCONSISTENT
```

### ✓ CORRECT - Consistent response format
```json
{"success": true, "message": "..."}
{"success": false, "message": "..."}
```

### ❌ WRONG - Allowing unverified users
```php
// No verification check in protected endpoint
public function meGetAction() {
    $user = $this->getUserById($_SESSION['user_id']);
    return $user;  // What if user is not verified?
}
```

### ✓ CORRECT - Verifying user status
```php
public function meGetAction() {
    if (!$user['is_verified']) {
        return ['success' => false, 'message' => 'Email not verified'];
    }
    return $user;
}
```

---

## Questions?

Refer to:
- [CONTRIBUTING.md](.github/CONTRIBUTING.md)
- [Tests Documentation](tests/README.md)
- GitHub Issues for clarification
- Code comments for implementation details
