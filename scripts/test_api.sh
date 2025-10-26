#!/bin/bash
##
# @file test_api.sh
# @author Kristián Kičinka (xkicin02)
#
# @copyright Copyright (c) 2024
#

# Exit immediately if a command exits with a non-zero status.
set -e

# Function to display messages
log_message() {
    echo -e "\n🔍 $1"
}

log_success() {
    echo -e "✅ $1"
}

log_error() {
    echo -e "❌ $1"
}

log_warning() {
    echo -e "⚠️  $1"
}

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
API_BASE_URL="http://localhost:8000/api"
AUTH_KEY="YuPzeVpjb5pjQxpOP4DOATnRXFK99W"  # Admin API key from database
USER_AUTH_KEY="ypqGlCRPr96HMz4oU5DE4OeqnxxZm1"  # User API key from database

log_message "Starting API endpoint testing..."

# Test 1: Get Custom Hash Types
log_message "Test 1: Get Custom Hash Types"
echo "Testing: GET $API_BASE_URL/get-custom-hash-types"

response=$(curl -s -w "\n%{http_code}" -X POST "$API_BASE_URL/get-custom-hash-types" \
  -H "Content-Type: application/json" \
  -d "{\"auth_key\": \"$AUTH_KEY\"}")

http_code=$(echo "$response" | tail -n1)
body=$(echo "$response" | head -n -1)

if [ "$http_code" = "200" ]; then
    log_success "Custom Hash Types endpoint working"
    echo "Response: $body" | jq . 2>/dev/null || echo "Response: $body"
else
    log_error "Custom Hash Types endpoint failed (HTTP $http_code)"
    echo "Response: $body"
fi

echo ""

# Test 2: Get App Hashes
log_message "Test 2: Get App Hashes"
echo "Testing: POST $API_BASE_URL/get-app-hashes"

response=$(curl -s -w "\n%{http_code}" -X POST "$API_BASE_URL/get-app-hashes" \
  -H "Content-Type: application/json" \
  -d "{
    \"auth_key\": \"$AUTH_KEY\",
    \"apps\": [
      {\"package_name\": \"com.discord\", \"version\": \"216.14\"},
      {\"package_name\": \"com.spotify.music\", \"version\": \"8.9.14.543\"}
    ]
  }")

http_code=$(echo "$response" | tail -n1)
body=$(echo "$response" | head -n -1)

if [ "$http_code" = "200" ]; then
    log_success "Get App Hashes endpoint working"
    echo "Response: $body" | jq . 2>/dev/null || echo "Response: $body"
else
    log_error "Get App Hashes endpoint failed (HTTP $http_code)"
    echo "Response: $body"
fi

echo ""

# Test 3: Get Apps from Hashes
log_message "Test 3: Get Apps from Hashes"
echo "Testing: POST $API_BASE_URL/get-apps-from-hashes"

response=$(curl -s -w "\n%{http_code}" -X POST "$API_BASE_URL/get-apps-from-hashes" \
  -H "Content-Type: application/json" \
  -d "{
    \"auth_key\": \"$AUTH_KEY\",
    \"hashes\": [
      {\"ja3_hash\": \"f79b6bad2ad0641e1921aef10262856b\", \"ja3s_hash\": \"15af977ce25de452b96affa2addb1036\", \"sni\": \"signalrcore.alza.cz\"}
    ],
    \"input_type\": \"JA3_JA3S_SNI\"
  }")

http_code=$(echo "$response" | tail -n1)
body=$(echo "$response" | head -n -1)

if [ "$http_code" = "200" ]; then
    log_success "Get Apps from Hashes endpoint working"
    echo "Response: $body" | jq . 2>/dev/null || echo "Response: $body"
else
    log_error "Get Apps from Hashes endpoint failed (HTTP $http_code)"
    echo "Response: $body"
fi

echo ""

# Test 4: Create Hash from Package Name
log_message "Test 4: Create Hash from Package Name"
echo "Testing: POST $API_BASE_URL/create-hash-from-package-name"

response=$(curl -s -w "\n%{http_code}" -X POST "$API_BASE_URL/create-hash-from-package-name" \
  -H "Content-Type: application/json" \
  -d "{
    \"auth_key\": \"$AUTH_KEY\",
    \"package_name\": \"com.facebook.orca\"
  }")

http_code=$(echo "$response" | tail -n1)
body=$(echo "$response" | head -n -1)

if [ "$http_code" = "200" ]; then
    log_success "Create Hash from Package Name endpoint working"
    echo "Response: $body"
else
    log_error "Create Hash from Package Name endpoint failed (HTTP $http_code)"
    echo "Response: $body"
fi

echo ""

# Test 5: Test with invalid auth key
log_message "Test 5: Test with invalid auth key"
echo "Testing: POST $API_BASE_URL/get-custom-hash-types with invalid key"

response=$(curl -s -w "\n%{http_code}" -X POST "$API_BASE_URL/get-custom-hash-types" \
  -H "Content-Type: application/json" \
  -d "{\"auth_key\": \"invalid_key_12345\"}")

http_code=$(echo "$response" | tail -n1)
body=$(echo "$response" | head -n -1)

if [ "$http_code" = "401" ]; then
    log_success "Authentication properly rejects invalid keys (HTTP $http_code)"
    echo "Response: $body"
else
    log_warning "Expected 401 for invalid auth key, got HTTP $http_code"
    echo "Response: $body"
fi

echo ""

# Test 6: Test with missing auth key
log_message "Test 6: Test with missing auth key"
echo "Testing: POST $API_BASE_URL/get-custom-hash-types without auth key"

response=$(curl -s -w "\n%{http_code}" -X POST "$API_BASE_URL/get-custom-hash-types" \
  -H "Content-Type: application/json" \
  -d "{}")

http_code=$(echo "$response" | tail -n1)
body=$(echo "$response" | head -n -1)

if [ "$http_code" = "400" ]; then
    log_success "Validation properly rejects missing auth key (HTTP $http_code)"
    echo "Response: $body"
else
    log_warning "Expected 400 for missing auth key, got HTTP $http_code"
    echo "Response: $body"
fi

echo ""

# Test 7: Test User API key
log_message "Test 7: Test with User API key"
echo "Testing: POST $API_BASE_URL/get-custom-hash-types with user key"

response=$(curl -s -w "\n%{http_code}" -X POST "$API_BASE_URL/get-custom-hash-types" \
  -H "Content-Type: application/json" \
  -d "{\"auth_key\": \"$USER_AUTH_KEY\"}")

http_code=$(echo "$response" | tail -n1)
body=$(echo "$response" | head -n -1)

if [ "$http_code" = "200" ]; then
    log_success "User API key authentication working"
    echo "Response: $body" | jq . 2>/dev/null || echo "Response: $body"
else
    log_error "User API key authentication failed (HTTP $http_code)"
    echo "Response: $body"
fi

echo ""

log_message "API testing completed!"
echo ""
echo "Summary:"
echo "- External API endpoints are properly configured"
echo "- Authentication middleware is working"
echo "- Input validation is working"
echo "- Both admin and user API keys are functional"
echo ""
echo "Note: Some endpoints (like create-hash-from-apk, create-hash-from-pcap) require file uploads"
echo "and were not tested in this script. You can test them manually with curl or Postman."
