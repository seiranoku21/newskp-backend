# SSO Authentication API Documentation

## Overview
This document describes the Google SSO (Single Sign-On) authentication implementation for the NewSKP Backend application.

## Backend API Endpoint

### POST `/api/auth/sso`

**Purpose:** Authenticate users who login via Google SSO provider

**URL:** `http://your-domain.com/api/auth/sso`

**Method:** `POST`

**Content-Type:** `application/json`

---

## Request Format

### Headers
```
Content-Type: application/json
Accept: application/json
```

### Request Body
```json
{
  "provider": "google",
  "id_token": "eyJhbGciOiJSUzI1NiIsImtpZCI6IjY...",
  "email": "ukon@untirta.ac.id",
  "name": "Zainul Furqon"
}
```

### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| provider | string | Yes | Authentication provider. Currently only supports "google" |
| id_token | string | Yes | Google ID token received from Google Sign-In |
| email | string | Yes | User's email address |
| name | string | No | User's display name |

---

## Response Format

### Success Response (200 OK)

**When user exists or is auto-registered:**

```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user": {
    "id": 5,
    "username": "ukon@untirta.ac.id",
    "email": "ukon@untirta.ac.id",
    "name": "Zainul Furqon"
  }
}
```

### Error Responses

#### 1. Validation Error (422 Unprocessable Entity)
```json
{
  "error": "Validation failed",
  "message": "The email field is required.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

#### 2. Invalid Provider (400 Bad Request)
```json
{
  "error": "Invalid provider",
  "message": "Unsupported authentication provider"
}
```

#### 3. Invalid ID Token (401 Unauthorized)
```json
{
  "error": "Invalid ID token",
  "message": "Google ID token verification failed"
}
```

#### 4. Email Mismatch (401 Unauthorized)
```json
{
  "error": "Email mismatch",
  "message": "The email in the token does not match the provided email"
}
```

#### 5. User Not Found (404 Not Found)
**Note:** This error only occurs if auto-registration is disabled

```json
{
  "error": "User not found",
  "message": "No user account found with this email address"
}
```

#### 6. Server Error (500 Internal Server Error)
```json
{
  "error": "SSO authentication failed",
  "message": "Detailed error message"
}
```

---

## Backend Implementation Logic

### 1. Request Validation
- Validates that all required fields are present
- Ensures provider is "google"
- Validates email format

### 2. Google ID Token Verification
- Uses Google API Client to verify the ID token
- Ensures token is valid and issued by Google
- Extracts payload information from token

### 3. Email Verification
- Compares email from token payload with provided email
- Ensures they match for security

### 4. User Lookup/Creation
- Searches database for user with matching email
- **Option A (Current):** Auto-registers new user if not found
  - Creates user with email as username
  - Sets a random secure password
  - Auto-verifies email
  - Assigns default role (role_id = 2)
  - Sets auth_provider = 'google'
- **Option B (Alternative):** Returns 404 error if user not found

### 5. Token Generation
- Generates JWT token using existing JWTHelper
- Returns token and user information

---

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
GOOGLE_CLIENT_ID=your-google-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://your-domain.com/auth/google/callback
```

### Getting Google Credentials

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable Google+ API
4. Go to Credentials → Create Credentials → OAuth 2.0 Client ID
5. Configure consent screen
6. Add authorized JavaScript origins and redirect URIs
7. Copy Client ID and Client Secret to `.env`

---

## Database Schema

### Users Table Additions

The migration adds the following columns to the `users` table:

```php
// Column: auth_provider
// Type: VARCHAR(50), NULLABLE
// Description: Stores authentication provider (e.g., 'google', 'local')

// Column: email_verified_at  
// Type: TIMESTAMP, NULLABLE
// Description: Timestamp when email was verified
```

### Running the Migration

```bash
php artisan migrate
```

---

## Frontend Integration Example

### Using Axios (JavaScript/TypeScript)

```javascript
import axios from 'axios';

// After successful Google Sign-In
async function handleGoogleLogin(googleUser) {
  try {
    const idToken = googleUser.getAuthResponse().id_token;
    const profile = googleUser.getBasicProfile();
    
    const response = await axios.post('http://your-domain.com/api/auth/sso', {
      provider: 'google',
      id_token: idToken,
      email: profile.getEmail(),
      name: profile.getName()
    });
    
    // Store token
    localStorage.setItem('token', response.data.token);
    
    // Store user info
    localStorage.setItem('user', JSON.stringify(response.data.user));
    
    console.log('Login successful:', response.data);
    
    // Redirect to dashboard or home page
    window.location.href = '/dashboard';
    
  } catch (error) {
    console.error('SSO Login failed:', error.response?.data);
    alert(error.response?.data?.message || 'Login failed');
  }
}
```

### Using Fetch API

```javascript
// After successful Google Sign-In
async function handleGoogleLogin(googleResponse) {
  try {
    const response = await fetch('http://your-domain.com/api/auth/sso', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        provider: 'google',
        id_token: googleResponse.credential,
        email: googleResponse.email,
        name: googleResponse.name
      })
    });
    
    const data = await response.json();
    
    if (!response.ok) {
      throw new Error(data.message || 'Login failed');
    }
    
    // Store token and user info
    localStorage.setItem('token', data.token);
    localStorage.setItem('user', JSON.stringify(data.user));
    
    // Redirect
    window.location.href = '/dashboard';
    
  } catch (error) {
    console.error('SSO Login failed:', error);
    alert(error.message);
  }
}
```

---

## Security Considerations

1. **ID Token Verification:** Always verify ID tokens on the backend. Never trust tokens verified only on the frontend.

2. **HTTPS Required:** Always use HTTPS in production to protect tokens during transmission.

3. **Token Storage:** Store JWT tokens securely:
   - Use httpOnly cookies when possible
   - If using localStorage, be aware of XSS vulnerabilities
   - Consider using secure, httpOnly cookies for token storage

4. **CORS Configuration:** Ensure your backend CORS configuration allows requests from your frontend domain.

5. **Rate Limiting:** Implement rate limiting on the `/api/auth/sso` endpoint to prevent abuse.

6. **Token Expiration:** JWT tokens have expiration times configured in `config/auth.php`. Implement token refresh mechanisms if needed.

---

## Testing

### Using cURL

```bash
curl -X POST http://your-domain.com/api/auth/sso \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "provider": "google",
    "id_token": "YOUR_GOOGLE_ID_TOKEN",
    "email": "test@untirta.ac.id",
    "name": "Test User"
  }'
```

### Using Postman

1. Set method to `POST`
2. URL: `http://your-domain.com/api/auth/sso`
3. Headers:
   - `Content-Type: application/json`
   - `Accept: application/json`
4. Body (raw JSON):
```json
{
  "provider": "google",
  "id_token": "YOUR_GOOGLE_ID_TOKEN",
  "email": "test@untirta.ac.id",
  "name": "Test User"
}
```

---

## Troubleshooting

### Common Issues

#### 1. "Invalid ID token" Error
- **Cause:** Token expired or invalid
- **Solution:** Generate a new ID token from Google Sign-In

#### 2. "Google ID token verification failed"
- **Cause:** Incorrect GOOGLE_CLIENT_ID in .env
- **Solution:** Verify the Client ID matches your Google Console settings

#### 3. "Email mismatch" Error
- **Cause:** Email in token doesn't match provided email
- **Solution:** Ensure you're sending the correct email from frontend

#### 4. User Creation Fails
- **Cause:** Database constraints or missing fields
- **Solution:** Check database schema and ensure all required fields are present

---

## Additional Notes

- The default user role is set to `2` for auto-registered users. Adjust this in `AuthController.php` line 132 based on your role system.
- Auto-registration is currently enabled. To disable it, uncomment the "Option B" code block in `AuthController.php` (lines 134-137).
- The JWT token format and expiration are controlled by `config/auth.php`.
- Email is automatically verified for Google SSO users.

---

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Enable debug mode in `.env`: `APP_DEBUG=true` (development only)
3. Check Google Cloud Console for API quotas and errors

---

**Last Updated:** January 6, 2026
**Version:** 1.0.0

