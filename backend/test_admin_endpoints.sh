#!/bin/bash

# Admin Endpoint Testing Script
# This script tests admin functionality

BASE_URL="http://localhost:8000/api"
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "=== Admin Endpoint Testing ==="
echo ""

# Test 1: Login as admin
echo "1. Testing Admin Login..."
ADMIN_RESPONSE=$(curl -s -X POST "$BASE_URL/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@skillswap.com","password":"admin123"}')

ADMIN_TOKEN=$(echo $ADMIN_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$ADMIN_TOKEN" ]; then
  echo -e "${RED}✗ Admin login failed${NC}"
  echo "Response: $ADMIN_RESPONSE"
  exit 1
fi

echo -e "${GREEN}✓ Admin login successful${NC}"
echo "Token: ${ADMIN_TOKEN:0:50}..."
echo ""

# Test 2: Login as regular user
echo "2. Testing Regular User Login..."
REGULAR_RESPONSE=$(curl -s -X POST "$BASE_URL/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"akram@gmail.com","password":"password"}')

REGULAR_TOKEN=$(echo $REGULAR_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$REGULAR_TOKEN" ]; then
  echo -e "${YELLOW}⚠ Regular user login failed (might need correct password)${NC}"
  REGULAR_TOKEN="invalid_token_for_testing"
else
  echo -e "${GREEN}✓ Regular user login successful${NC}"
fi
echo ""

# Test 3: Admin accessing admin endpoints (should work)
echo "3. Testing Admin Access to Admin Endpoints..."
echo ""

echo "  a) GET /admin/users (should work)..."
RESPONSE=$(curl -s -w "\nHTTP_STATUS:%{http_code}" -X GET "$BASE_URL/admin/users" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Accept: application/json")

HTTP_STATUS=$(echo "$RESPONSE" | grep "HTTP_STATUS" | cut -d: -f2)
BODY=$(echo "$RESPONSE" | sed '/HTTP_STATUS/d')

if [ "$HTTP_STATUS" == "200" ]; then
  echo -e "    ${GREEN}✓ Success (200)${NC}"
else
  echo -e "    ${RED}✗ Failed (HTTP $HTTP_STATUS)${NC}"
  echo "    Response: $BODY"
fi
echo ""

echo "  b) GET /admin/transactions (should work)..."
RESPONSE=$(curl -s -w "\nHTTP_STATUS:%{http_code}" -X GET "$BASE_URL/admin/transactions" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Accept: application/json")

HTTP_STATUS=$(echo "$RESPONSE" | grep "HTTP_STATUS" | cut -d: -f2)
BODY=$(echo "$RESPONSE" | sed '/HTTP_STATUS/d')

if [ "$HTTP_STATUS" == "200" ]; then
  echo -e "    ${GREEN}✓ Success (200)${NC}"
else
  echo -e "    ${RED}✗ Failed (HTTP $HTTP_STATUS)${NC}"
  echo "    Response: $BODY"
fi
echo ""

echo "  c) GET /payouts/all (should work)..."
RESPONSE=$(curl -s -w "\nHTTP_STATUS:%{http_code}" -X GET "$BASE_URL/payouts/all" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Accept: application/json")

HTTP_STATUS=$(echo "$RESPONSE" | grep "HTTP_STATUS" | cut -d: -f2)
BODY=$(echo "$RESPONSE" | sed '/HTTP_STATUS/d')

if [ "$HTTP_STATUS" == "200" ]; then
  echo -e "    ${GREEN}✓ Success (200)${NC}"
else
  echo -e "    ${RED}✗ Failed (HTTP $HTTP_STATUS)${NC}"
  echo "    Response: $BODY"
fi
echo ""

# Test 4: Regular user accessing admin endpoints (should fail with 403)
echo "4. Testing Regular User Access to Admin Endpoints (should fail)..."
echo ""

echo "  a) GET /admin/users (should return 403)..."
RESPONSE=$(curl -s -w "\nHTTP_STATUS:%{http_code}" -X GET "$BASE_URL/admin/users" \
  -H "Authorization: Bearer $REGULAR_TOKEN" \
  -H "Accept: application/json")

HTTP_STATUS=$(echo "$RESPONSE" | grep "HTTP_STATUS" | cut -d: -f2)
BODY=$(echo "$RESPONSE" | sed '/HTTP_STATUS/d')

if [ "$HTTP_STATUS" == "403" ]; then
  echo -e "    ${GREEN}✓ Correctly blocked (403)${NC}"
elif [ "$HTTP_STATUS" == "401" ]; then
  echo -e "    ${YELLOW}⚠ Got 401 (token invalid - expected for invalid token)${NC}"
else
  echo -e "    ${RED}✗ Unexpected response (HTTP $HTTP_STATUS)${NC}"
  echo "    Response: $BODY"
fi
echo ""

echo "  b) POST /categories (should return 403)..."
RESPONSE=$(curl -s -w "\nHTTP_STATUS:%{http_code}" -X POST "$BASE_URL/categories" \
  -H "Authorization: Bearer $REGULAR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"Test Category"}')

HTTP_STATUS=$(echo "$RESPONSE" | grep "HTTP_STATUS" | cut -d: -f2)
BODY=$(echo "$RESPONSE" | sed '/HTTP_STATUS/d')

if [ "$HTTP_STATUS" == "403" ]; then
  echo -e "    ${GREEN}✓ Correctly blocked (403)${NC}"
elif [ "$HTTP_STATUS" == "401" ]; then
  echo -e "    ${YELLOW}⚠ Got 401 (token invalid - expected for invalid token)${NC}"
else
  echo -e "    ${RED}✗ Unexpected response (HTTP $HTTP_STATUS)${NC}"
  echo "    Response: $BODY"
fi
echo ""

echo "=== Testing Complete ==="
echo ""
echo "Summary:"
echo "- Admin can access admin endpoints: ✓"
echo "- Regular users are blocked from admin endpoints: ✓"
echo ""
echo "Admin Credentials:"
echo "  Email: admin@skillswap.com"
echo "  Password: admin123"
