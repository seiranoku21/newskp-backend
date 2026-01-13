# Google SSO Implementation - Quick Start Guide

## 📋 Overview

This implementation provides a complete Google Single Sign-On (SSO) authentication system for the NewSKP Backend application. Users can authenticate using their Google accounts, and the system will automatically create accounts for new users.

## 🚀 Quick Start

### 1. Install Dependencies

The required Google API client is already installed in `composer.json`:

```bash
composer install
```

### 2. Run Database Migration

Add the required database columns:

```bash
php artisan migrate
```

This will add:
- `auth_provider` column to store authentication provider (e.g., 'google')
- `email_verified_at` column for email verification timestamp

### 3. Configure Environment Variables

Add to your `.env` file:

```env
GOOGLE_CLIENT_ID=your-google-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

See [ENVIRONMENT_SETUP.md](./ENVIRONMENT_SETUP.md) for detailed instructions on obtaining Google credentials.

### 4. Clear Configuration Cache

```bash
php artisan config:clear
php artisan config:cache
```

## 📁 Files Created/Modified

### New Files Created:

1. **`app/Http/Requests/SsoLoginRequest.php`**
   - Request validation for SSO login
   - Validates provider, id_token, email, and name fields

2. **`database/migrations/2024_01_06_000001_add_auth_provider_to_users_table.php`**
   - Adds `auth_provider` and `email_verified_at` columns to users table

3. **`docs/SSO_AUTHENTICATION.md`**
   - Complete API documentation
   - Request/response formats
   - Error handling
   - Security considerations

4. **`docs/ENVIRONMENT_SETUP.md`**
   - Step-by-step guide for Google Cloud Console setup
   - Environment variable configuration

5. **`docs/README_SSO.md`** (this file)
   - Quick start guide
   - Implementation overview

### Modified Files:

1. **`app/Http/Controllers/AuthController.php`**
   - Added `sso()` method for Google SSO authentication
   - Implements ID token verification
   - Handles user creation/lookup
   - Generates JWT tokens

2. **`app/Models/Users.php`**
   - Added `auth_provider` and `email_verified_at` to fillable fields

3. **`config/services.php`**
   - Added Google service configuration

4. **`routes/api.php`**
   - Added `POST /api/auth/sso` route

## 🔐 API Endpoint

### POST `/api/auth/sso`

**Request:**
```json
{
  "provider": "google",
  "id_token": "eyJhbGciOiJSUzI1NiIsImtpZCI6IjY...",
  "email": "user@untirta.ac.id",
  "name": "User Name"
}
```

**Response (Success):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user": {
    "id": 5,
    "username": "user@untirta.ac.id",
    "email": "user@untirta.ac.id",
    "name": "User Name"
  }
}
```

See [SSO_AUTHENTICATION.md](./SSO_AUTHENTICATION.md) for complete API documentation.

## 🔄 Authentication Flow

```
1. User clicks "Sign in with Google" on frontend
   ↓
2. Google authentication popup appears
   ↓
3. User authenticates with Google
   ↓
4. Frontend receives ID token from Google
   ↓
5. Frontend sends POST request to /api/auth/sso with:
   - provider: "google"
   - id_token: [Google ID token]
   - email: [user email]
   - name: [user name]
   ↓
6. Backend verifies ID token with Google API
   ↓
7. Backend checks if user exists in database
   ↓
8. If user doesn't exist:
   - Create new user account
   - Set email as username
   - Generate random password
   - Auto-verify email
   - Assign default role (role_id = 2)
   ↓
9. Generate JWT token for user
   ↓
10. Return token and user info to frontend
   ↓
11. Frontend stores token and redirects to dashboard
```

## 🛠️ Configuration Options

### Auto-Registration

**Current Setting:** Enabled (Option A)

New users are automatically registered when they sign in with Google.

**To Disable Auto-Registration:**

Edit `app/Http/Controllers/AuthController.php` around line 132:

```php
// Comment out Option A (lines 120-133)
// Uncomment Option B (lines 135-138)

// Option B: Return error if user not found
return response()->json([
    'error' => 'User not found',
    'message' => 'No user account found with this email address'
], 404);
```

### Default User Role

**Current Setting:** role_id = 2

New users are assigned role ID 2 by default.

**To Change Default Role:**

Edit `app/Http/Controllers/AuthController.php` line 132:

```php
$user->user_role_id = 2; // Change this value
```

## 🧪 Testing

### Using cURL

```bash
curl -X POST http://localhost:8000/api/auth/sso \
  -H "Content-Type: application/json" \
  -d '{
    "provider": "google",
    "id_token": "YOUR_GOOGLE_ID_TOKEN",
    "email": "test@untirta.ac.id",
    "name": "Test User"
  }'
```

### Using Postman

1. Method: POST
2. URL: `http://localhost:8000/api/auth/sso`
3. Headers:
   - `Content-Type: application/json`
4. Body (raw JSON):
```json
{
  "provider": "google",
  "id_token": "YOUR_GOOGLE_ID_TOKEN",
  "email": "test@untirta.ac.id",
  "name": "Test User"
}
```

## 🔒 Security Features

1. **ID Token Verification:** All Google ID tokens are verified with Google API
2. **Email Matching:** Email in token must match provided email
3. **Auto Email Verification:** Emails from Google are automatically verified
4. **Secure Password:** Random 32-character password for SSO users
5. **JWT Token:** Secure JWT tokens with expiration
6. **Provider Tracking:** `auth_provider` field tracks authentication method

## 📱 Frontend Integration

### React/Next.js Example

```javascript
import { GoogleLogin } from '@react-oauth/google';

function LoginPage() {
  const handleGoogleSuccess = async (credentialResponse) => {
    try {
      const response = await fetch('http://localhost:8000/api/auth/sso', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          provider: 'google',
          id_token: credentialResponse.credential,
          email: credentialResponse.email,
          name: credentialResponse.name
        })
      });
      
      const data = await response.json();
      
      if (response.ok) {
        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));
        window.location.href = '/dashboard';
      } else {
        alert(data.message);
      }
    } catch (error) {
      console.error('Login failed:', error);
    }
  };

  return (
    <GoogleLogin
      onSuccess={handleGoogleSuccess}
      onError={() => console.log('Login Failed')}
    />
  );
}
```

### Vue.js Example

```vue
<template>
  <div>
    <GoogleLogin :callback="handleGoogleLogin" />
  </div>
</template>

<script>
export default {
  methods: {
    async handleGoogleLogin(response) {
      try {
        const result = await fetch('http://localhost:8000/api/auth/sso', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            provider: 'google',
            id_token: response.credential,
            email: response.email,
            name: response.name
          })
        });
        
        const data = await result.json();
        
        if (result.ok) {
          localStorage.setItem('token', data.token);
          this.$router.push('/dashboard');
        }
      } catch (error) {
        console.error('Login error:', error);
      }
    }
  }
}
</script>
```

## 🐛 Troubleshooting

### Common Issues

| Issue | Cause | Solution |
|-------|-------|----------|
| "Invalid ID token" | Token expired or invalid | Generate new token from Google Sign-In |
| "Google ID token verification failed" | Wrong GOOGLE_CLIENT_ID | Verify Client ID in .env matches Google Console |
| "Email mismatch" | Email mismatch between token and request | Ensure correct email is sent |
| "User not found" (404) | Auto-registration disabled | Enable auto-registration or create user manually |

### Debug Mode

Enable debug mode in `.env` (development only):

```env
APP_DEBUG=true
```

Check logs:
```bash
tail -f storage/logs/laravel.log
```

## 📊 Database Schema Changes

### Users Table

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| auth_provider | VARCHAR(50) | Yes | NULL | Authentication provider (google, local, etc.) |
| email_verified_at | TIMESTAMP | Yes | NULL | Email verification timestamp |

## 🔄 Migration Commands

```bash
# Run migration
php artisan migrate

# Rollback migration
php artisan migrate:rollback

# Check migration status
php artisan migrate:status
```

## 📚 Additional Resources

- [Google Sign-In Documentation](https://developers.google.com/identity/sign-in/web)
- [Google Cloud Console](https://console.cloud.google.com/)
- [Laravel Passport Documentation](https://laravel.com/docs/passport)
- [JWT Authentication](https://jwt.io/)

## 🤝 Support

For issues or questions:
1. Check the [SSO_AUTHENTICATION.md](./SSO_AUTHENTICATION.md) for detailed API documentation
2. Check Laravel logs: `storage/logs/laravel.log`
3. Review Google Cloud Console for API errors
4. Ensure all environment variables are properly set

## ✅ Checklist

Before going to production:

- [ ] Install dependencies (`composer install`)
- [ ] Run migrations (`php artisan migrate`)
- [ ] Set up Google Cloud Console project
- [ ] Add environment variables to `.env`
- [ ] Clear config cache (`php artisan config:clear`)
- [ ] Test SSO login flow
- [ ] Configure CORS for frontend domain
- [ ] Enable HTTPS in production
- [ ] Set up rate limiting
- [ ] Review security settings
- [ ] Test error handling
- [ ] Document frontend integration

---

**Version:** 1.0.0  
**Last Updated:** January 6, 2026  
**Author:** NewSKP Development Team

