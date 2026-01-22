# Admin Functionality Testing Guide

## Admin User Created

An admin user has been created with the following credentials:
- **Email**: `admin@skillswap.com`
- **Password**: `admin123`
- **Status**: Admin (is_admin = true)

## Quick Test Instructions

### Option 1: Automated Test Script

1. Make sure your Laravel server is running:
   ```bash
   cd backend
   php artisan serve
   ```

2. In another terminal, run the test script:
   ```bash
   cd backend
   ./test_admin_endpoints.sh
   ```

### Option 2: Manual Testing with cURL

1. **Start Laravel server** (if not running):
   ```bash
   cd backend
   php artisan serve
   ```

2. **Login as Admin**:
   ```bash
   curl -X POST http://localhost:8000/api/login \
     -H "Content-Type: application/json" \
     -d '{"email":"admin@skillswap.com","password":"admin123"}'
   ```
   
   Copy the `token` from the response.

3. **Test Admin Endpoints** (replace `YOUR_TOKEN` with the token from step 2):

   ```bash
   # Get all users (admin only)
   curl -X GET http://localhost:8000/api/admin/users \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json"
   
   # Get all transactions (admin only)
   curl -X GET http://localhost:8000/api/admin/transactions \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json"
   
   # Get all payouts (admin only)
   curl -X GET http://localhost:8000/api/payouts/all \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json"
   
   # Create category (admin only)
   curl -X POST http://localhost:8000/api/categories \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"name":"Test Category"}'
   
   # Delete category (admin only)
   curl -X DELETE http://localhost:8000/api/categories/1 \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json"
   
   # Approve payout (admin only)
   curl -X POST http://localhost:8000/api/payouts/1/approve \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json"
   ```

4. **Test Non-Admin Access** (should return 403):
   - Login with a regular user (e.g., `akram@gmail.com`)
   - Try accessing any admin endpoint
   - Should get: `{"message":"Unauthorized","error":"Admin access required..."}`

### Option 3: Testing with Postman/Insomnia

1. **Import or create requests**:
   - Base URL: `http://localhost:8000/api`
   - Headers: `Content-Type: application/json`, `Accept: application/json`
   
2. **Login Request**:
   - Method: POST
   - URL: `/login`
   - Body (JSON):
     ```json
     {
       "email": "admin@skillswap.com",
       "password": "admin123"
     }
     ```
   - Save the `token` from response

3. **Admin Endpoints** (use token from login):
   - Add header: `Authorization: Bearer YOUR_TOKEN`
   - Test endpoints:
     - `GET /admin/users`
     - `GET /admin/transactions`
     - `GET /payouts/all`
     - `POST /categories` (with body: `{"name":"Test"}`)
     - `DELETE /categories/{id}`
     - `POST /payouts/{id}/approve`
     - `POST /payouts/{id}/reject`
     - `POST /payouts/{id}/paid`

4. **Expected Results**:
   - ✅ Admin endpoints: Should return 200/201 with data
   - ✅ Non-admin access: Should return 403 Forbidden

## Admin Endpoints Summary

### Protected by Admin Middleware:

1. **User Management**:
   - `GET /api/admin/users` - Get all users

2. **Transaction Management**:
   - `GET /api/admin/transactions` - Get all transactions
   - `PUT /api/transactions/{id}/status` - Update transaction status

3. **Payout Management**:
   - `GET /api/payouts/all` - Get all payouts
   - `POST /api/payouts/{id}/approve` - Approve payout
   - `POST /api/payouts/{id}/reject` - Reject payout
   - `POST /api/payouts/{id}/paid` - Mark payout as paid

4. **Category Management**:
   - `POST /api/categories` - Create category
   - `DELETE /api/categories/{id}` - Delete category

## Security Notes

- All admin endpoints require authentication (`auth:sanctum`)
- All admin endpoints require admin status (`admin` middleware)
- Non-admin users get 403 Forbidden when accessing admin endpoints
- Controller methods also have defensive checks (defense in depth)

## Creating Additional Admin Users

To create more admin users, you can:

1. **Using Tinker**:
   ```bash
   php artisan tinker
   $user = App\Models\User::where('email', 'user@example.com')->first();
   $user->update(['is_admin' => true]);
   ```

2. **Using SQL**:
   ```sql
   UPDATE users SET is_admin = 1 WHERE email = 'user@example.com';
   ```

3. **Run the test script again** (it will update existing users):
   ```bash
   php test_admin.php
   ```
