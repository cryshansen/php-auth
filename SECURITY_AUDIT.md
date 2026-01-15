# Code Leak Analysis Report

## Issues Found

### 🔴 CRITICAL Issues

#### 1. Hardcoded reCAPTCHA Secrets (BaseController.php)
**Location:** Lines 70-71, 85
**Issue:** Secret keys hardcoded in source code
**Impact:** Anyone can see your reCAPTCHA secret key
**Fix:** Move to environment variables ✅ FIXED

#### 2. Database Host String Literal (database.php:18)
**Location:** Line 18
**Issue:** String literals instead of constants
```php
$this->connection = new PDO("mysql:host=DB_HOST;dbname=DB_DATABASE_NAME", ...);
// Should use constants: mysql:host=" . DB_HOST . "
```
**Impact:** Database won't connect, unclear error
**Fix:** Use string interpolation with constants

#### 3. Verbose Logging of Sensitive Data (database.php)
**Location:** Lines 44, 48, 60, 68, 72, 117, 128, 152
**Issue:** Logging all database parameters including potential sensitive data
```php
file_put_contents("log.txt", "select: ".print_r($params) . "\n", FILE_APPEND);
// Logs all query parameters without filtering
```
**Impact:** Sensitive data (emails, tokens, etc.) logged to file
**Fix:** Filter sensitive data or disable debug logging in production

---

### 🟡 HIGH Priority Issues

#### 4. Variable Name Error (database.php:171)
**Location:** Line 171
**Issue:** Undefined variable `$params`
```php
file_put_contents("log.txt", "\nRaw Unable to do prepared statement: ".$query." data:" .  print_r($params, true) . "\n", FILE_APPEND);
// $params is not defined in this scope, should be $params
```
**Impact:** PHP warning/error in logs
**Fix:** Change to correct variable name

#### 5. Ob_start Usage (index-auth.php, UserController.php)
**Location:** index-auth.php:2, UserController.php:55, 172, 279
**Issue:** Output buffering started but not properly handled
```php
ob_start();  // Started but BaseController tries to get contents
```
**Impact:** Potential output buffering issues, leaks captured
**Fix:** Consolidate ob_start to one location

#### 6. Unnecessary Output Buffer Leak Detection (BaseController.php:44-51)
**Location:** Lines 44-51
**Issue:** Detecting leaks is good but writing to file on every request
```php
$buffer = ob_get_contents();
if ($buffer !== '') {
    file_put_contents("output_leak.log", ...);  // Logs every leak
}
```
**Impact:** Constant I/O operations on every request
**Fix:** Only log in development environment

---

### 🟠 MEDIUM Priority Issues

#### 7. Commented Code with Secrets (BaseController.php:73-74)
**Location:** Lines 73-74
**Issue:** Commented reCAPTCHA keys visible in code
**Impact:** Another secret exposed even though commented
**Fix:** Remove all commented credentials ✅ FIXED

#### 8. Domain Hardcoded in Comments (BaseController.php:84, 87)
**Location:** Lines 84, 87
**Issue:** Domain names mentioned in comments
```php
// set up is under booker.crystalhansenaartographic.com
```
**Impact:** Reveals domain information
**Fix:** Remove or use generic language ✅ FIXED_r of sensitive data
**Issue:** Logs user authentication details
```php
file_put_contents("auth_log.txt", "ISVALID: ".print_r($valid,true). "\n", FILE_APPEND);
// Logs success/failure with user_id
```
**Impact:** Logs contain authentication information
**Fix:** Filter what gets logged

#### 10. Test Credentials in Workflow (api-tests.yml:20)
**Location:** Line 20
**Issue:** Database password hardcoded in GitHub Actions
```yaml
MYSQL_PASSWORD: your_secure_password # Never commit real passwords
```
**Impact:** Credentials visible in GitHub Actions logs
**Fix:** Use GitHub Secrets

---

## Summary Table

| Issue | Severity | File | Line | Status |
|-------|----------|------|------|--------|
| reCAPTCHA secrets exposed | 🔴 CRITICAL | BaseController.php | 70-71, 85 | NEEDS FIX |
| DB host literal string | 🔴 CRITICAL | database.php | 18 | NEEDS FIX |
| Verbose logging of params | 🟡 HIGH | database.php | Multiple | NEEDS FIX |
| Undefined $params variable | 🟡 HIGH | database.php | 171 | NEEDS FIX |
| Output buffer management | 🟡 HIGH | UserController.php | 55, 172, 279 | NEEDS FIX |
| Commented secrets | 🟠 MEDIUM | BaseController.php | 73-74 | NEEDS FIX |
| Domain in comments | 🟠 MEDIUM | BaseController.php | 84, 87 | NEEDS FIX |
| Overly verbose logs | 🟠 MEDIUM | AuthController.php | Multiple | NEEDS FIX |
| Test credentials in CI | 🟠 MEDIUM | api-tests.yml | 20 | NEEDS FIX |

---

## Recommendations

### Immediate Actions (Today)
- [ ] Move reCAPTCHA secrets to environment variables
- [ ] Fix database.php connection string
- [ ] Remove all commented credentials
- [ ] Fix undefined $params variable

### Short Term (This Week)
- [ ] Add production flag to disable debug logging
- [ ] Filter sensitive data from logs (emails, tokens, user_ids)
- [ ] Use GitHub Secrets for CI/CD credentials
- [ ] Consolidate output buffering

### Long Term (Next Sprint)
- [ ] Implement proper logging library with levels
- [ ] Add application logging vs debug logging
- [ ] Create logging policy in CODE_POLICY.md
- [ ] Regular security audits

---

## Files to Review

- ✓ BaseController.php - Secrets, comments, logging
- ✓ database.php - Connection, logging, variables
- ✓ AuthController.php - Verbose logging
- ✓ UserController.php - Output buffering
- ✓ .github/workflows/api-tests.yml - CI credentials
- ✓ index-auth.php - Output buffering
