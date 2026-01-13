# Integrasi Google SSO dengan Frontend

## 📋 Overview

Dokumen ini menjelaskan bagaimana Google SSO terintegrasi dengan frontend yang sudah ada, menggunakan JWT token dan cookie `session_token`.

---

## 🔐 Konfigurasi JWT

Backend menggunakan **JWT (JSON Web Token)** yang sama dengan sistem autentikasi yang sudah ada di frontend.

### Environment Variables

Pastikan `JWT_SECRET` di `.env` sama dengan yang digunakan frontend:

```env
JWT_SECRET=F1kweiwun9P4#$aR$p3f44GKMmpn^WS93xl@THlN38@=
JWT_ALGORITHM=HS256
JWT_DURATION=30
```

> **PENTING:** Nilai `JWT_SECRET` harus **sama persis** antara frontend dan backend!

### Konfigurasi di `config/auth.php`

```php
'jwt_secret' => env('JWT_SECRET', ''),
'jwt_algorithm' => env('JWT_ALGORITHM', 'HS256'),
'jwt_duration' => env('JWT_DURATION', 30), // dalam menit
```

---

## 🍪 Cookie Session Token

### Pola yang Digunakan

Backend SSO sekarang mengikuti pola frontend dengan menyimpan JWT token di **cookie** bernama `session_token`.

### Konfigurasi Cookie

| Parameter | Nilai | Keterangan |
|-----------|-------|------------|
| Nama | `session_token` | Sesuai dengan pola frontend |
| Value | JWT Token | Token yang di-generate dengan JWT_SECRET |
| Duration | 30 menit | Sesuai JWT_DURATION di config |
| Path | `/` | Berlaku untuk semua path |
| HttpOnly | `true` | Keamanan - tidak bisa diakses JavaScript |
| Secure | `false` (dev) / `true` (prod) | HTTPS only di production |
| SameSite | `lax` | CSRF protection |

---

## 🔄 Flow Autentikasi

### 1. User Login dengan Google

```
Frontend → Google Sign-In → Dapat ID Token
   ↓
POST /api/auth/sso
{
  "provider": "google",
  "id_token": "eyJhbGciOiJSUzI1NiIsImtpZCI6IjY...",
  "email": "user@untirta.ac.id",
  "name": "Nama User"
}
   ↓
Backend:
1. Verifikasi ID token dengan Google API
2. Cek/buat user di database
3. Generate JWT token dengan JWT_SECRET
4. Set cookie session_token
5. Return response
   ↓
Frontend:
1. Terima response dengan token
2. Cookie session_token otomatis tersimpan
3. Redirect ke dashboard
```

### 2. Request Selanjutnya

Setelah login, setiap request ke backend akan otomatis membawa cookie `session_token`:

```
GET /api/users
Cookie: session_token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

Backend akan membaca cookie ini untuk autentikasi.

---

## 📡 API Response

### Success Response (200 OK)

```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user": {
    "id": 5,
    "username": "user@untirta.ac.id",
    "email": "user@untirta.ac.id",
    "name": "Nama User"
  }
}
```

**HTTP Headers:**
```
Set-Cookie: session_token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...; 
            Path=/; 
            Max-Age=1800; 
            HttpOnly; 
            SameSite=Lax
```

---

## 🔨 Implementasi Frontend

### Option 1: Menggunakan Fetch API

```javascript
async function handleGoogleLogin(googleResponse) {
  try {
    const response = await fetch('http://localhost:8000/api/auth/sso', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      credentials: 'include', // PENTING: untuk mengirim/menerima cookies
      body: JSON.stringify({
        provider: 'google',
        id_token: googleResponse.credential,
        email: googleResponse.email,
        name: googleResponse.name
      })
    });
    
    const data = await response.json();
    
    if (response.ok) {
      console.log('Login berhasil!');
      console.log('Token:', data.token);
      console.log('User:', data.user);
      
      // Cookie session_token sudah otomatis tersimpan
      // Redirect ke dashboard
      window.location.href = '/dashboard?sso=google';
    } else {
      console.error('Login gagal:', data.message);
      alert(data.message);
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Terjadi kesalahan saat login');
  }
}
```

### Option 2: Menggunakan Axios

```javascript
import axios from 'axios';

// Konfigurasi axios untuk mengirim credentials
axios.defaults.withCredentials = true;

async function handleGoogleLogin(googleResponse) {
  try {
    const response = await axios.post(
      'http://localhost:8000/api/auth/sso',
      {
        provider: 'google',
        id_token: googleResponse.credential,
        email: googleResponse.email,
        name: googleResponse.name
      }
    );
    
    console.log('Login berhasil!');
    console.log('Token:', response.data.token);
    console.log('User:', response.data.user);
    
    // Cookie session_token sudah otomatis tersimpan
    // Redirect ke dashboard
    window.location.href = '/dashboard?sso=google';
    
  } catch (error) {
    console.error('Login gagal:', error.response?.data);
    alert(error.response?.data?.message || 'Login gagal');
  }
}
```

### Option 3: Menggunakan Library yang Sudah Ada

Jika frontend sudah punya fungsi untuk hit API, pastikan:

```javascript
// Pastikan request include credentials
const config = {
  withCredentials: true,  // untuk axios
  credentials: 'include', // untuk fetch
};
```

---

## 🔍 Verifikasi di Browser

### Cara Cek Cookie

1. Buka **Developer Tools** (F12)
2. Tab **Application** (Chrome) atau **Storage** (Firefox)
3. Sidebar **Cookies** → pilih domain Anda
4. Cari cookie bernama `session_token`

**Struktur Cookie:**

| Name | Value | Domain | Path | Expires | HttpOnly | SameSite |
|------|-------|--------|------|---------|----------|----------|
| session_token | eyJ0eXAiOiJK... | localhost | / | [30 min dari sekarang] | ✓ | Lax |

### Cara Decode JWT Token

Untuk melihat isi token (development only):

```javascript
// Di Console Browser
function parseJwt(token) {
  const base64Url = token.split('.')[1];
  const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
  const jsonPayload = decodeURIComponent(
    atob(base64).split('').map(c => {
      return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
    }).join('')
  );
  return JSON.parse(jsonPayload);
}

// Ambil token dari cookie
const cookies = document.cookie.split(';');
const sessionToken = cookies.find(c => c.includes('session_token'));
if (sessionToken) {
  const token = sessionToken.split('=')[1];
  console.log('Token payload:', parseJwt(token));
}
```

**Output contoh:**
```json
{
  "iss": "http://localhost:8000",
  "aud": "http://localhost:8000",
  "iat": 1704528000,
  "nbf": 1704528010,
  "exp": 1704529800,
  "data": 5
}
```

- `data`: User ID
- `exp`: Expiration time (Unix timestamp)
- `iat`: Issued at time

---

## 🔒 Keamanan

### CORS Configuration

Pastikan CORS sudah dikonfigurasi dengan benar di `config/cors.php`:

```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    
    'allowed_methods' => ['*'],
    
    'allowed_origins' => [
        'http://localhost:3000',    // Frontend dev
        'https://yourdomain.com',   // Production
    ],
    
    'allowed_origins_patterns' => [],
    
    'allowed_headers' => ['*'],
    
    'exposed_headers' => [],
    
    'max_age' => 0,
    
    'supports_credentials' => true, // PENTING: harus true untuk cookies
];
```

### HTTPS di Production

Di production, update konfigurasi cookie untuk HTTPS:

**File:** `app/Http/Controllers/AuthController.php` (line 151)

```php
// Development
$cookie = cookie(
    'session_token',
    $token,
    config('auth.jwt_duration'),
    '/',
    null,
    false, // secure = false untuk HTTP
    true,
    false,
    'lax'
);

// Production - ganti menjadi:
$cookie = cookie(
    'session_token',
    $token,
    config('auth.jwt_duration'),
    '/',
    config('app.domain'), // domain production
    true,  // secure = true untuk HTTPS
    true,
    false,
    'lax'
);
```

Atau lebih baik, gunakan environment:

```php
$cookie = cookie(
    'session_token',
    $token,
    config('auth.jwt_duration'),
    '/',
    config('app.domain'),
    config('app.env') === 'production', // auto detect
    true,
    false,
    'lax'
);
```

---

## 🧪 Testing

### Test 1: Verifikasi JWT Secret

```bash
# Di terminal backend
php artisan tinker

>>> config('auth.jwt_secret')
=> "F1kweiwun9P4#$aR$p3f44GKMmpn^WS93xl@THlN38@="

>>> exit
```

Pastikan output sama dengan `JWT_SECRET` di frontend.

### Test 2: Generate Token Manual

```bash
php artisan tinker

>>> $user = App\Models\Users::find(1);
>>> $token = App\Helpers\JWTHelper::encode($user->user_id);
>>> echo $token;

>>> exit
```

### Test 3: Test SSO Endpoint

```bash
# Test dengan cURL (akan menerima Set-Cookie header)
curl -v -X POST http://localhost:8000/api/auth/sso \
  -H "Content-Type: application/json" \
  -d '{
    "provider": "google",
    "id_token": "YOUR_GOOGLE_ID_TOKEN",
    "email": "test@untirta.ac.id",
    "name": "Test User"
  }'
```

Cari di output:
```
< Set-Cookie: session_token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

### Test 4: Test di Browser

1. Buka `http://localhost:8000/test-sso-login.html`
2. Masukkan Google Client ID
3. Klik "Initialize Google Sign-In"
4. Login dengan Google
5. Buka Developer Tools → Application → Cookies
6. Verifikasi cookie `session_token` ada

---

## 🔄 Logout

Untuk logout, hapus cookie `session_token`:

### Backend Endpoint (Opsional)

```php
// Di AuthController.php
public function logout(Request $request) {
    // Hapus cookie
    $cookie = cookie(
        'session_token',
        '',
        -1, // expired
        '/',
        null,
        false,
        true,
        false,
        'lax'
    );
    
    return response()->json([
        'message' => 'Logged out successfully'
    ])->cookie($cookie);
}
```

### Frontend

```javascript
// Option 1: Via API
async function logout() {
  await fetch('http://localhost:8000/api/auth/logout', {
    method: 'POST',
    credentials: 'include'
  });
  window.location.href = '/login';
}

// Option 2: Hapus cookie manual (JavaScript)
function logout() {
  document.cookie = 'session_token=; Max-Age=0; path=/;';
  window.location.href = '/login';
}
```

---

## 🐛 Troubleshooting

### Issue 1: Cookie Tidak Tersimpan

**Penyebab:**
- CORS tidak dikonfigurasi dengan benar
- `credentials: 'include'` tidak diset di frontend
- Domain tidak sesuai

**Solusi:**
```javascript
// Frontend - pastikan ada credentials
fetch(url, {
  credentials: 'include' // HARUS ADA
});

// Backend - pastikan CORS supports_credentials = true
// config/cors.php
'supports_credentials' => true,
```

### Issue 2: Token Tidak Valid

**Penyebab:**
- JWT_SECRET berbeda antara frontend dan backend

**Solusi:**
```bash
# Verifikasi JWT_SECRET sama persis
# Frontend: cek di config
# Backend: 
php artisan tinker
>>> config('auth.jwt_secret')
```

### Issue 3: CORS Error

**Error di Console:**
```
Access to fetch at 'http://localhost:8000/api/auth/sso' from origin 
'http://localhost:3000' has been blocked by CORS policy
```

**Solusi:**
1. Tambahkan frontend origin ke `config/cors.php`
2. Set `supports_credentials` ke `true`
3. Clear cache: `php artisan config:clear`

---

## 📚 Referensi

- [Dokumentasi JWT Helper](../app/Helpers/JWTHelper.php)
- [Dokumentasi API SSO](./SSO_AUTHENTICATION.md)
- [Laravel Cookies](https://laravel.com/docs/10.x/responses#cookies)
- [MDN - HTTP Cookies](https://developer.mozilla.org/en-US/docs/Web/HTTP/Cookies)

---

## ✅ Checklist Integrasi

Sebelum deploy, pastikan:

- [ ] `JWT_SECRET` sama antara frontend dan backend
- [ ] CORS dikonfigurasi dengan benar
- [ ] `supports_credentials` = true di CORS config
- [ ] Frontend request menggunakan `credentials: 'include'`
- [ ] Cookie `session_token` tersimpan setelah login
- [ ] Token bisa di-decode dan berisi user_id
- [ ] Logout menghapus cookie dengan benar
- [ ] HTTPS diaktifkan di production
- [ ] Cookie secure flag = true di production

---

**Last Updated:** January 6, 2026  
**Version:** 1.0.0

