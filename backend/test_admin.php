<?php

/**
 * Admin Testing Script
 * 
 * This script helps test admin functionality by:
 * 1. Creating an admin user (if needed)
 * 2. Testing admin middleware
 * 
 * Usage:
 * php test_admin.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== Admin Testing Script ===\n\n";

// Check if admin user exists
$adminEmail = 'admin@skillswap.com';
$adminUser = User::where('email', $adminEmail)->first();

if ($adminUser) {
    echo "✓ Admin user found: {$adminUser->email}\n";
    echo "  - Name: {$adminUser->name}\n";
    echo "  - Is Admin: " . ($adminUser->is_admin ? 'Yes' : 'No') . "\n";
    
    if (!$adminUser->is_admin) {
        echo "\n⚠ Admin user exists but is_admin = false. Updating...\n";
        $adminUser->update(['is_admin' => true]);
        echo "✓ Updated is_admin to true\n";
    }
} else {
    echo "✗ Admin user not found. Creating...\n";
    $adminUser = User::create([
        'name' => 'Admin User',
        'email' => $adminEmail,
        'password' => Hash::make('admin123'),
        'is_admin' => true,
        'credits' => 1000,
    ]);
    echo "✓ Admin user created:\n";
    echo "  - Email: {$adminUser->email}\n";
    echo "  - Password: admin123\n";
    echo "  - Is Admin: Yes\n";
}

// List all users with admin status
echo "\n=== All Users ===\n";
$users = User::select('id', 'name', 'email', 'is_admin')->get();
foreach ($users as $user) {
    $adminBadge = $user->is_admin ? '[ADMIN]' : '[USER]';
    echo "  {$adminBadge} {$user->name} ({$user->email})\n";
}

echo "\n=== Testing Instructions ===\n";
echo "1. Login as admin:\n";
echo "   POST http://localhost:8000/api/login\n";
echo "   Body: {\"email\":\"{$adminEmail}\",\"password\":\"admin123\"}\n\n";

echo "2. Test admin endpoints (use the token from login):\n";
echo "   GET /api/admin/users\n";
echo "   GET /api/admin/transactions\n";
echo "   GET /api/payouts/all\n";
echo "   POST /api/categories\n";
echo "   DELETE /api/categories/{id}\n\n";

echo "3. Test with non-admin user (should get 403):\n";
echo "   Login with a regular user, then try admin endpoints\n\n";

echo "=== Script Complete ===\n";
