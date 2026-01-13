# Environment Setup for Google SSO

## Required Environment Variables

Add these variables to your `.env` file:

```env
# Google SSO Configuration
GOOGLE_CLIENT_ID=your-google-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

## How to Get Google Credentials

### Step 1: Go to Google Cloud Console
1. Visit [Google Cloud Console](https://console.cloud.google.com/)
2. Sign in with your Google account

### Step 2: Create or Select a Project
1. Click on the project dropdown at the top
2. Click "New Project" or select an existing one
3. Give it a name (e.g., "NewSKP Untirta")

### Step 3: Enable Required APIs
1. In the left sidebar, go to "APIs & Services" → "Library"
2. Search for "Google+ API" or "Google Sign-In API"
3. Click on it and click "Enable"

### Step 4: Configure OAuth Consent Screen
1. Go to "APIs & Services" → "OAuth consent screen"
2. Choose "External" user type (or "Internal" if using Google Workspace)
3. Fill in the required information:
   - App name: NewSKP Untirta
   - User support email: your-email@untirta.ac.id
   - Developer contact email: your-email@untirta.ac.id
4. Click "Save and Continue"
5. Add scopes if needed (email, profile are usually sufficient)
6. Add test users if in development mode

### Step 5: Create OAuth 2.0 Credentials
1. Go to "APIs & Services" → "Credentials"
2. Click "Create Credentials" → "OAuth 2.0 Client ID"
3. Choose "Web application" as application type
4. Set the name (e.g., "NewSKP Web Client")
5. Add Authorized JavaScript origins:
   ```
   http://localhost:3000
   http://localhost:8000
   https://your-production-domain.com
   ```
6. Add Authorized redirect URIs:
   ```
   http://localhost:8000/auth/google/callback
   https://your-production-domain.com/auth/google/callback
   ```
7. Click "Create"

### Step 6: Copy Credentials
1. You'll see a popup with your Client ID and Client Secret
2. Copy these values to your `.env` file:
   ```env
   GOOGLE_CLIENT_ID=123456789-abcdefghijklmnopqrstuvwxyz.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=GOCSPX-aBcDeFgHiJkLmNoPqRsTuVwXyZ
   ```

## Production Configuration

For production, update the values in your `.env` file:

```env
GOOGLE_CLIENT_ID=your-production-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-production-client-secret
GOOGLE_REDIRECT_URI=https://api.yourapp.com/auth/google/callback
```

## Security Notes

1. **Never commit `.env` file to version control**
2. **Keep Client Secret confidential**
3. **Use different credentials for development and production**
4. **Regularly rotate secrets in production**
5. **Restrict API key usage by IP or domain when possible**

## Testing the Configuration

After setting up, you can test if the configuration is correct:

```bash
# Check if config is loaded
php artisan config:cache
php artisan config:clear

# Test in tinker
php artisan tinker
>>> config('services.google.client_id')
```

Should output your Google Client ID.

## Troubleshooting

### Issue: "Client ID not found"
- **Solution:** Make sure you've added the variables to `.env` and cleared config cache

### Issue: "Redirect URI mismatch"
- **Solution:** Ensure the redirect URI in Google Console exactly matches your application URL

### Issue: "Access blocked: This app's request is invalid"
- **Solution:** Make sure OAuth consent screen is properly configured

---

**Last Updated:** January 6, 2026

