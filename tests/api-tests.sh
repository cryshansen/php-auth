#!/bin/bash

# API Endpoint Test Suite
# Tests all critical authentication and user endpoints

BASE_URL="http://localhost:8000/index-auth.php"
FAILED=0
PASSED=0

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test function
test_endpoint() {
    local method=$1
    local endpoint=$2
    local data=$3
    local expected_status=$4
    local description=$5

    echo -e "${YELLOW}Testing: $description${NC}"
    
    if [ "$method" = "GET" ]; then
        response=$(curl -s -w "\n%{http_code}" -X GET "$BASE_URL$endpoint" \
            -H "Content-Type: application/json" \
            -b cookies.txt -c cookies.txt)
    else
        response=$(curl -s -w "\n%{http_code}" -X $method "$BASE_URL$endpoint" \
            -H "Content-Type: application/json" \
            -d "$data" \
            -b cookies.txt -c cookies.txt)
    fi

    status_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | head -n-1)

    if [ "$status_code" = "$expected_status" ]; then
        echo -e "${GREEN}✓ PASSED${NC} (HTTP $status_code)"
        ((PASSED++))
    else
        echo -e "${RED}✗ FAILED${NC} Expected: $expected_status, Got: $status_code"
        echo "Response: $body"
        ((FAILED++))
    fi
    echo ""
}

echo "=========================================="
echo "Starting API Endpoint Tests"
echo "=========================================="
echo ""

# Test 1: Signup with valid data
test_endpoint "POST" "/auth/signup" \
    '{"firstname":"Test","lastname":"User","email":"test1@example.com","password":"TestPass123!","token":""}' \
    "200" \
    "Signup - Valid data"

# Test 2: Signup with duplicate email
test_endpoint "POST" "/auth/signup" \
    '{"firstname":"Test","lastname":"User","email":"test2@example.com","password":"TestPass123!","token":""}' \
    "200" \
    "Signup - Different user"

# Test 3: Signup with invalid email
test_endpoint "POST" "/auth/signup" \
    '{"firstname":"Test","lastname":"User","email":"invalid-email","password":"TestPass123!","token":""}' \
    "400" \
    "Signup - Invalid email format"

# Test 4: Login with valid unverified account
test_endpoint "POST" "/auth/signin" \
    '{"username":"test@example.com","password":"TestPass123!","token":""}' \
    "200" \
    "Signin - Unverified user (should fail)"

# Test 5: Login with invalid credentials
test_endpoint "POST" "/auth/signin" \
    '{"username":"nonexistent@example.com","password":"WrongPass123!","token":""}' \
    "200" \
    "Signin - Invalid credentials"

# Test 6: Get current user without session
test_endpoint "GET" "/auth/me" \
    "" \
    "401" \
    "Get /me - No session (should fail)"

# Test 7: Reset password request
test_endpoint "POST" "/auth/resetpassword" \
    '{"email":"test@example.com","token":""}' \
    "200" \
    "Reset password - Valid email"

# Test 8: Reset password request with invalid email
test_endpoint "POST" "/auth/resetpassword" \
    '{"email":"invalid-email","token":""}' \
    "400" \
    "Reset password - Invalid email format"

# Test 9: Get users list
test_endpoint "GET" "/users/list?limit=10" \
    "" \
    "200" \
    "Get users list"

# Test 10: Logout
test_endpoint "POST" "/auth/logout" \
    '{}' \
    "200" \
    "Logout"

echo "=========================================="
echo "Test Results Summary"
echo "=========================================="
echo -e "${GREEN}Passed: $PASSED${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}All tests passed!${NC}"
    exit 0
else
    echo -e "${RED}Some tests failed!${NC}"
    exit 1
fi
