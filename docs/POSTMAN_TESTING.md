# 🧪 Testing Google SSO dengan Postman

## ⚠️ Penting: Kenapa SSO Asli Tidak Bisa Ditest di Postman

Endpoint `/api/auth/sso` yang asli **TIDAK BISA** ditest langsung di Postman karena:

1. **Google ID Token** hanya bisa didapat dari Google Sign-In (frontend)
2. Token hanya valid beberapa menit dan harus dari Google yang asli
3. Token tidak bisa dibuat manual atau di-fake

Oleh karena itu, saya sudah membuat **endpoint testing khusus** untuk development.

---

## 🔧 Endpoint Testing (Development Only)

### POST `/api/auth/sso-test`

Endpoint ini **bypass Google verification** dan hanya untuk keperluan testing di development.

**⚠️ WARNING:** Endpoint ini **HANYA AKTIF** di environment `local` atau `development`!

---

## 📡 Cara Test di Postman

### Step 1: Setup Request

**Method:** `POST`

**URL:** 
```
http://localhost:8000/api/auth/sso-test
```

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

### Step 2: Request Body

```json
{
  "email": "test@untirta.ac.id",
  "name": "Test User"
}
```

**Parameters:**
- `email` (required): Email user untuk login/register
- `name` (optional): Nama user (default: "Test User")

### Step 3: Send Request

Click tombol **Send**

### Step 4: Expected Response

**Status:** `200 OK`

**Body:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0OjgwMDAiLCJhdWQiOiJodHRwOi8vbG9jYWxob3N0OjgwMDAiLCJpYXQiOjE3MDQ1MjgwMDAsIm5iZiI6MTcwNDUyODAxMCwiZXhwIjoxNzA0NTI5ODAwLCJkYXRhIjo1fQ.xxx",
  "user": {
    "id": 5,
    "username": "test@untirta.ac.id",
    "email": "test@untirta.ac.id",
    "name": "Test User"
  },
  "note": "This is a test endpoint without Google verification"
}
```

**Headers:**
```
Set-Cookie: session_token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...; 
            Path=/; 
            Max-Age=1800; 
            HttpOnly; 
            SameSite=Lax
```

---

## 🍪 Cara Lihat Cookie di Postman

### Step 1: Klik Tab "Cookies"

Setelah send request, klik tab **"Cookies"** di bawah response body.

### Step 2: Pilih Domain

Pilih domain `localhost:8000` (atau domain backend Anda).

### Step 3: Lihat Cookie

Anda akan melihat cookie dengan struktur:

| Name | Value | Domain | Path | Expires | HttpOnly |
|------|-------|--------|------|---------|----------|
| session_token | eyJ0eXAiOiJK... | localhost | / | [30 min] | ✓ |

### Step 4: Copy Cookie Value

Klik pada value cookie `session_token`, lalu copy.

---

## 🔄 Test Request dengan Cookie

### Setelah login, test endpoint yang membutuhkan autentikasi:

**Method:** `GET`

**URL:** 
```
http://localhost:8000/api/users
```

**Headers:**
```
Accept: application/json
```

**Cookies:**

Postman akan **otomatis** mengirim cookie `session_token` untuk domain yang sama.

Atau bisa manual tambahkan di Headers:
```
Cookie: session_token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

---

## 📸 Screenshot Guide Postman

### 1. Setup Request

```
POST http://localhost:8000/api/auth/sso-test
Headers:
  Content-Type: application/json
  Accept: application/json
Body (raw JSON):
{
  "email": "test@untirta.ac.id",
  "name": "Test User"
}
```

### 2. Send & Lihat Response

✅ Status: 200 OK  
✅ Body: JSON dengan token dan user  
✅ Cookies: session_token tersimpan  

### 3. Lihat Cookies

Tab Cookies → Domain: localhost:8000 → Cookie: session_token

---

## 🧪 Test Scenarios

### Scenario 1: First Time Login (Auto-Register)

**Request:**
```json
{
  "email": "newuser@untirta.ac.id",
  "name": "New User"
}
```

**Expected:**
- Status: 200 OK
- User baru dibuat di database
- Cookie session_token di-set
- Return user data

### Scenario 2: Login User yang Sudah Ada

**Request:**
```json
{
  "email": "existing@untirta.ac.id",
  "name": "Existing User"
}
```

**Expected:**
- Status: 200 OK
- Load user dari database
- Cookie session_token di-set
- Return user data existing

### Scenario 3: Invalid Email

**Request:**
```json
{
  "email": "invalid-email",
  "name": "Test"
}
```

**Expected:**
- Status: 400 Bad Request
- Error: "Invalid email"

### Scenario 4: Missing Email

**Request:**
```json
{
  "name": "Test"
}
```

**Expected:**
- Status: 400 Bad Request
- Error: "Invalid email"

---

## 🔐 Test di Production

Di production, endpoint `/api/auth/sso-test` akan **OTOMATIS DISABLED**.

**Response di Production:**
```json
{
  "error": "Forbidden",
  "message": "This endpoint is only available in development environment"
}
```

Untuk test di production, Anda **HARUS** menggunakan:
1. Endpoint asli `/api/auth/sso` 
2. Dengan Google ID Token yang valid dari Google Sign-In

---

## 🛠️ Postman Collection

### Import Collection Ini:

```json
{
  "info": {
    "name": "NewSKP - Google SSO",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "SSO Test (Development)",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Content-Type",
            "value": "application/json"
          },
          {
            "key": "Accept",
            "value": "application/json"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"email\": \"test@untirta.ac.id\",\n  \"name\": \"Test User\"\n}"
        },
        "url": {
          "raw": "http://localhost:8000/api/auth/sso-test",
          "protocol": "http",
          "host": ["localhost"],
          "port": "8000",
          "path": ["api", "auth", "sso-test"]
        }
      }
    },
    {
      "name": "SSO Real (Google)",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Content-Type",
            "value": "application/json"
          },
          {
            "key": "Accept",
            "value": "application/json"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"provider\": \"google\",\n  \"id_token\": \"YOUR_GOOGLE_ID_TOKEN\",\n  \"email\": \"user@untirta.ac.id\",\n  \"name\": \"User Name\"\n}"
        },
        "url": {
          "raw": "http://localhost:8000/api/auth/sso",
          "protocol": "http",
          "host": ["localhost"],
          "port": "8000",
          "path": ["api", "auth", "sso"]
        }
      }
    },
    {
      "name": "Get Users (With Auth)",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Accept",
            "value": "application/json"
          }
        ],
        "url": {
          "raw": "http://localhost:8000/api/users",
          "protocol": "http",
          "host": ["localhost"],
          "port": "8000",
          "path": ["api", "users"]
        }
      }
    }
  ]
}
```

**Cara Import:**
1. Buka Postman
2. Klik **Import**
3. Paste JSON di atas
4. Klik **Import**

---

## 🔍 Debugging

### Cek Cookie di Browser

Jika test di browser, buka Console dan jalankan:

```javascript
// Lihat semua cookies
console.log(document.cookie);

// Decode token
function decodeToken() {
  const cookies = document.cookie.split(';');
  const sessionToken = cookies.find(c => c.includes('session_token'));
  if (sessionToken) {
    const token = sessionToken.split('=')[1];
    const payload = JSON.parse(atob(token.split('.')[1]));
    console.log('Token Payload:', payload);
  }
}
decodeToken();
```

### Cek di Laravel Log

```bash
tail -f storage/logs/laravel.log
```

### Verify JWT Token

```bash
php artisan tinker

>>> $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...";
>>> $userId = App\Helpers\JWTHelper::decode($token);
>>> echo $userId;
>>> $user = App\Models\Users::find($userId);
>>> echo $user->email;
>>> exit
```

---

## ❓ FAQ

### Q: Kenapa saya tidak bisa test endpoint `/api/auth/sso` di Postman?

**A:** Karena endpoint itu memerlukan Google ID Token yang asli dari Google Sign-In. ID Token tidak bisa dibuat manual. Gunakan endpoint `/api/auth/sso-test` untuk testing.

### Q: Apakah endpoint `/api/auth/sso-test` aman?

**A:** Endpoint ini **hanya aktif di development** (`APP_ENV=local` atau `development`). Di production, endpoint ini otomatis disabled dan return error 403.

### Q: Bagaimana cara test yang real dengan Google?

**A:** Gunakan halaman test di `http://localhost:8000/test-sso-login.html` atau implementasikan Google Sign-In di frontend Anda.

### Q: Cookie tidak tersimpan di Postman?

**A:** 
1. Cek tab "Cookies" di Postman
2. Enable cookies: Settings → General → Cookies → Enable
3. Atau copy cookie manual dari response headers

### Q: Bagaimana cara menggunakan cookie untuk request selanjutnya?

**A:** Postman otomatis mengirim cookies untuk domain yang sama. Atau tambahkan manual di Headers:
```
Cookie: session_token=YOUR_TOKEN
```

---

## ✅ Checklist Testing

- [ ] Endpoint `/api/auth/sso-test` bisa diakses
- [ ] Request dengan email valid return 200 OK
- [ ] Cookie `session_token` tersimpan di Postman
- [ ] Token bisa di-decode dan berisi user_id
- [ ] User baru ter-create di database (first login)
- [ ] User existing tidak duplicate (second login)
- [ ] Request selanjutnya membawa cookie otomatis
- [ ] Invalid email return 400 error
- [ ] Missing email return 400 error

---

**Last Updated:** January 6, 2026  
**Environment:** Development Only

