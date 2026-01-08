<?php
/**
 * JWT Testing Script
 * 
 * This script tests if JWT encoding and decoding works correctly
 * Run: php test_jwt.php
 */

require __DIR__ . '/vendor/autoload.php';

// Load Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test JWT
$userId = 1; // Test dengan user ID 1

echo "========================================\n";
echo "      JWT AUTHENTICATION TEST\n";
echo "========================================\n\n";

echo "Configuration:\n";
echo "- JWT_SECRET: " . (config('auth.jwt_secret') ? '✅ Set (' . strlen(config('auth.jwt_secret')) . ' chars)' : '❌ Not set') . "\n";
echo "- JWT_ALGORITHM: " . config('auth.jwt_algorithm') . "\n";
echo "- JWT_DURATION: " . config('auth.jwt_duration') . " minutes\n\n";

if (!config('auth.jwt_secret')) {
    echo "❌ ERROR: JWT_SECRET is not configured in .env file!\n";
    echo "\nPlease add to backend/.env:\n";
    echo "JWT_SECRET=your-secret-key-minimum-32-characters\n";
    echo "JWT_ALGORITHM=HS256\n";
    echo "JWT_DURATION=60\n\n";
    echo "Then run: php artisan config:clear\n";
    exit(1);
}

try {
    echo "Testing JWT Encode/Decode...\n";
    echo "----------------------------------------\n";
    
    // Encode
    echo "1. Encoding user ID: $userId\n";
    $token = App\Helpers\JWTHelper::encode($userId);
    echo "   ✅ Encode successful\n";
    echo "   Token: " . substr($token, 0, 60) . "...\n\n";
    
    // Decode
    echo "2. Decoding token...\n";
    $decoded = App\Helpers\JWTHelper::decode($token);
    echo "   ✅ Decode successful\n";
    echo "   Decoded user ID: " . $decoded . "\n\n";
    
    // Verify
    echo "3. Verification...\n";
    if ($decoded == $userId) {
        echo "   ✅ User ID matches!\n\n";
        echo "========================================\n";
        echo "✅✅✅ JWT TEST PASSED! ✅✅✅\n";
        echo "========================================\n";
        echo "JWT encoding and decoding works correctly.\n";
        echo "Regular login should work now.\n";
    } else {
        echo "   ❌ User ID mismatch!\n";
        echo "   Expected: $userId\n";
        echo "   Got: $decoded\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "\n========================================\n";
    echo "❌ JWT TEST FAILED\n";
    echo "========================================\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    if (strpos($e->getMessage(), 'Incorrect key') !== false) {
        echo "This error usually means:\n";
        echo "1. JWT_SECRET is not set properly in .env\n";
        echo "2. JWT_SECRET contains special characters that cause issues\n";
        echo "3. Config cache needs to be cleared\n\n";
        echo "Try:\n";
        echo "1. Check backend/.env for JWT_SECRET\n";
        echo "2. Run: php artisan config:clear\n";
        echo "3. Restart web server\n";
    }
    
    exit(1);
}

