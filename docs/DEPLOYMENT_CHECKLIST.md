# Google SSO Deployment Checklist

## 📋 Pre-Deployment Checklist

Use this checklist to ensure proper deployment of the Google SSO authentication feature.

---

## 🔧 Backend Setup

### Step 1: Dependencies
- [ ] Verify `google/apiclient` is in `composer.json`
- [ ] Run `composer install` to ensure all dependencies are installed
- [ ] Verify installation: `composer show google/apiclient`

### Step 2: Database Migration
- [ ] Review migration file: `database/migrations/2024_01_06_000001_add_auth_provider_to_users_table.php`
- [ ] Backup database (production only)
- [ ] Run migration: `php artisan migrate`
- [ ] Verify columns added:
  ```sql
  DESCRIBE users;
  -- Should show: auth_provider, email_verified_at
  ```

### Step 3: Environment Configuration
- [ ] Add to `.env` file:
  ```env
  GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
  GOOGLE_CLIENT_SECRET=your-client-secret
  GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
  ```
- [ ] Verify config loads: `php artisan tinker` → `config('services.google.client_id')`
- [ ] Clear config cache: `php artisan config:clear`
- [ ] Cache new config: `php artisan config:cache`

### Step 4: File Verification
- [ ] Verify `app/Http/Controllers/AuthController.php` has `sso()` method
- [ ] Verify `app/Http/Requests/SsoLoginRequest.php` exists
- [ ] Verify `app/Models/Users.php` has updated fillable fields
- [ ] Verify `config/services.php` has Google configuration
- [ ] Verify `routes/api.php` has `/api/auth/sso` route

### Step 5: Permissions
- [ ] Ensure storage directory is writable: `chmod -R 775 storage`
- [ ] Ensure bootstrap/cache is writable: `chmod -R 775 bootstrap/cache`

---

## 🌐 Google Cloud Console Setup

### Step 1: Create/Select Project
- [ ] Go to [Google Cloud Console](https://console.cloud.google.com/)
- [ ] Create new project or select existing
- [ ] Note project name/ID

### Step 2: Enable APIs
- [ ] Navigate to "APIs & Services" → "Library"
- [ ] Search for "Google+ API"
- [ ] Click "Enable"

### Step 3: Configure OAuth Consent Screen
- [ ] Go to "APIs & Services" → "OAuth consent screen"
- [ ] Select user type (External/Internal)
- [ ] Fill required information:
  - [ ] App name
  - [ ] User support email
  - [ ] Developer contact email
- [ ] Add scopes (email, profile)
- [ ] Add test users (development)
- [ ] Save configuration

### Step 4: Create OAuth Credentials
- [ ] Go to "APIs & Services" → "Credentials"
- [ ] Click "Create Credentials" → "OAuth 2.0 Client ID"
- [ ] Select "Web application"
- [ ] Configure:
  - [ ] Name: "NewSKP Web Client"
  - [ ] Authorized JavaScript origins:
    ```
    http://localhost:3000
    http://localhost:8000
    https://your-production-domain.com
    ```
  - [ ] Authorized redirect URIs:
    ```
    http://localhost:8000/auth/google/callback
    https://your-production-domain.com/auth/google/callback
    ```
- [ ] Click "Create"
- [ ] Copy Client ID and Client Secret

### Step 5: Update Environment
- [ ] Paste credentials into `.env` file
- [ ] Clear and cache config again

---

## 🧪 Testing Phase

### Local Testing

#### Test 1: Configuration Test
```bash
# Test config loads
php artisan tinker
>>> config('services.google.client_id')
# Should output your client ID
>>> exit
```
- [ ] Config loads successfully

#### Test 2: Migration Test
```sql
-- Check database structure
DESCRIBE users;
SELECT * FROM migrations WHERE migration LIKE '%auth_provider%';
```
- [ ] Columns exist in database

#### Test 3: Route Test
```bash
# Test route exists
php artisan route:list | grep "auth/sso"
```
- [ ] Route is registered

#### Test 4: HTML Test Page
- [ ] Start Laravel server: `php artisan serve`
- [ ] Open browser: `http://localhost:8000/test-sso-login.html`
- [ ] Enter Google Client ID
- [ ] Click "Initialize Google Sign-In"
- [ ] Click "Sign in with Google" button
- [ ] Authenticate with Google account
- [ ] Verify success response with token

#### Test 5: cURL Test
```bash
# Replace YOUR_GOOGLE_ID_TOKEN with actual token from Google Sign-In
curl -X POST http://localhost:8000/api/auth/sso \
  -H "Content-Type: application/json" \
  -d '{
    "provider": "google",
    "id_token": "YOUR_GOOGLE_ID_TOKEN",
    "email": "test@untirta.ac.id",
    "name": "Test User"
  }'
```
- [ ] Returns 200 with token and user data

#### Test 6: Error Scenarios
Test each error case:
- [ ] Invalid provider (use "facebook" instead of "google")
- [ ] Missing id_token
- [ ] Invalid email format
- [ ] Wrong email (different from token)
- [ ] Expired token

#### Test 7: Database Verification
```sql
-- Check if user was created
SELECT user_id, username, email, auth_provider, email_verified_at 
FROM users 
WHERE auth_provider = 'google'
ORDER BY user_id DESC 
LIMIT 1;
```
- [ ] User created successfully
- [ ] `auth_provider` is "google"
- [ ] `email_verified_at` is set

#### Test 8: JWT Token Test
- [ ] Copy token from response
- [ ] Use token in authenticated endpoint
- [ ] Verify token works for API calls

### Integration Testing with Frontend

- [ ] Frontend can call `/api/auth/sso` endpoint
- [ ] CORS configured correctly
- [ ] Token received and stored
- [ ] User redirected after login
- [ ] Authenticated requests work with token
- [ ] Error messages displayed properly

---

## 🔒 Security Verification

### Environment Security
- [ ] `.env` file is NOT in version control
- [ ] `.env` is in `.gitignore`
- [ ] Production uses different credentials from development
- [ ] `APP_DEBUG=false` in production

### Google Console Security
- [ ] Authorized origins restricted to actual domains
- [ ] Authorized redirect URIs restricted to actual paths
- [ ] API key restrictions configured (if using API keys)
- [ ] OAuth consent screen properly configured

### Application Security
- [ ] HTTPS enabled in production
- [ ] CORS properly configured in `config/cors.php`
- [ ] Rate limiting enabled on auth endpoints
- [ ] SQL injection protection (Laravel ORM used)
- [ ] XSS protection enabled
- [ ] CSRF protection enabled

---

## 🚀 Production Deployment

### Pre-Deployment
- [ ] All tests pass
- [ ] Code reviewed
- [ ] Documentation reviewed
- [ ] Backup database
- [ ] Backup `.env` file

### Deployment Steps
1. [ ] Pull/deploy code to production server
2. [ ] Update `.env` with production Google credentials
3. [ ] Run: `composer install --no-dev`
4. [ ] Run: `php artisan migrate --force`
5. [ ] Run: `php artisan config:clear`
6. [ ] Run: `php artisan config:cache`
7. [ ] Run: `php artisan route:cache`
8. [ ] Restart PHP-FPM/web server

### Post-Deployment Verification
- [ ] Test endpoint with production URL
- [ ] Verify Google Sign-In works
- [ ] Check logs for errors: `tail -f storage/logs/laravel.log`
- [ ] Verify database connections
- [ ] Test with real user account
- [ ] Monitor error rates

### Rollback Plan (If Needed)
- [ ] Revert code deployment
- [ ] Rollback migration: `php artisan migrate:rollback --step=1`
- [ ] Restore database backup
- [ ] Clear caches
- [ ] Document issues for debugging

---

## 📊 Monitoring Setup

### Logging
- [ ] Laravel logs configured: `config/logging.php`
- [ ] Google API errors logged
- [ ] Failed authentication attempts logged
- [ ] Database errors logged

### Monitoring Checklist
- [ ] Set up error monitoring (Sentry, Bugsnag, etc.)
- [ ] Monitor authentication success rate
- [ ] Monitor API response times
- [ ] Set up alerts for failed logins
- [ ] Monitor Google API quota usage

### Metrics to Track
- [ ] Total SSO login attempts
- [ ] Successful SSO logins
- [ ] Failed SSO logins (by error type)
- [ ] New user registrations via SSO
- [ ] Average response time
- [ ] Token generation success rate

---

## 📝 Documentation Checklist

- [ ] API documentation reviewed (`docs/SSO_AUTHENTICATION.md`)
- [ ] Setup guide reviewed (`docs/ENVIRONMENT_SETUP.md`)
- [ ] Quick start guide reviewed (`docs/README_SSO.md`)
- [ ] Team trained on new authentication flow
- [ ] Support team aware of error messages
- [ ] Frontend team has integration examples

---

## 🔄 Ongoing Maintenance

### Monthly Tasks
- [ ] Review authentication logs
- [ ] Check Google API quota usage
- [ ] Review failed login attempts
- [ ] Update documentation if needed

### Quarterly Tasks
- [ ] Review and rotate Google API credentials
- [ ] Update dependencies: `composer update`
- [ ] Security audit
- [ ] Performance review

### Yearly Tasks
- [ ] Review OAuth consent screen
- [ ] Update terms of service links
- [ ] Review user roles and permissions
- [ ] Comprehensive security audit

---

## 🆘 Troubleshooting Quick Reference

| Issue | Check | Solution |
|-------|-------|----------|
| "Invalid ID token" | `.env` GOOGLE_CLIENT_ID | Verify matches Google Console |
| CORS error | `config/cors.php` | Add frontend domain |
| "User not found" | AuthController | Enable auto-registration |
| Database error | Migration status | Run `php artisan migrate` |
| Config not loading | Cache | Run `php artisan config:clear` |
| Route not found | Route cache | Run `php artisan route:clear` |
| 500 Internal Error | Laravel logs | Check `storage/logs/laravel.log` |

---

## ✅ Final Sign-Off

Before marking as complete:

### Development Environment
- [ ] All tests pass
- [ ] Documentation complete
- [ ] Code reviewed
- [ ] No linter errors

### Staging Environment
- [ ] Deployed successfully
- [ ] All tests pass
- [ ] Performance acceptable
- [ ] Error handling verified

### Production Environment
- [ ] Deployed successfully
- [ ] Real user testing completed
- [ ] Monitoring in place
- [ ] Team trained

### Sign-Off
- [ ] Developer: _________________ Date: _______
- [ ] Reviewer: _________________ Date: _______
- [ ] QA: _______________________ Date: _______
- [ ] DevOps: ___________________ Date: _______

---

## 📞 Emergency Contacts

In case of critical issues:

- **Backend Lead:** [Name/Contact]
- **Frontend Lead:** [Name/Contact]
- **DevOps Lead:** [Name/Contact]
- **Security Team:** [Contact]

---

## 📚 Quick Links

- [Google Cloud Console](https://console.cloud.google.com/)
- [Google Sign-In Documentation](https://developers.google.com/identity/sign-in/web)
- [Laravel Documentation](https://laravel.com/docs)
- Internal Documentation: `docs/` folder

---

**Last Updated:** January 6, 2026  
**Version:** 1.0.0  
**Status:** Ready for Deployment

---

**Good luck with your deployment! 🚀**

