# BACKEND REST-API NewSKP Untirta


### Google SSO Authentication (January 6, 2026)

Sistem autentikasi Google SSO lengkap telah diimplementasikan dan **terintegrasi dengan pola frontend** yang sudah ada!

**📌 API Endpoint Baru:**
```
POST /api/auth/sso
```

**🔗 Integrasi dengan Frontend:**
- ✅ Menggunakan **JWT_SECRET yang sama** dengan frontend
- ✅ Token disimpan di **cookie `session_token`** (sesuai pola frontend)
- ✅ HttpOnly cookie untuk keamanan maksimal
- ✅ Compatible dengan sistem autentikasi existing


**🎯 Quick Start:**
```bash
# 1. Jalankan migrasi
php artisan migrate

# 2. Tambahkan ke .env (JWT_SECRET HARUS SAMA dengan frontend!)
JWT_SECRET=F1kweiwun9P4#$aR$p3f44GKMmpn^WS93xl@THlN38@=
JWT_ALGORITHM=HS256
JWT_DURATION=30

GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback

**✅ Fitur:**
- ✨ Google OAuth 2.0 authentication
- 🔐 Verifikasi ID token
- 👤 Registrasi user otomatis
- 🎫 JWT token generation (pakai JWT_SECRET yang sama)
- 🍪 Cookie session_token (sesuai pola frontend)
- ✉️ Email auto-verification
- 🛡️ Keamanan comprehensive
- 📖 Dokumentasi lengkap bahasa Indonesia
- 🧪 Built-in test page dengan cookie viewer

---

## 🔗 Git Remote Repositories

Repository ini terhubung ke beberapa remote repositories:

```bash
# Remote repositories yang tersedia:
origin     → https://github.com/seiranoku/NewSKP-Backend.git (HTTPS)
seiranoku  → https://github.com/seiranoku21/newskp-backend.git (HTTPS) ✅
untirta    → git@github.com:untirta-developer/newskp-backend.git (SSH) ✅
upstream   → https://github.com/seiranoku/NewSKP-Backend.git (HTTPS)
```

### Push ke Repository:

```bash
# Push ke repository seiranoku21 (HTTPS - perlu credentials)
git push seiranoku main

# Push ke repository organisasi untirta-developer (SSH - otomatis, no credentials)
git push untirta main

# Push ke repository origin (HTTPS - perlu credentials)
git push origin main

# Push ke semua repository sekaligus
git push seiranoku main && git push untirta main
```

### Credential & Authentication:

**Remote HTTPS** (`origin`, `seiranoku`, `upstream`):
- Credential helper sudah disetup untuk menyimpan username & token
- Credentials disimpan di: `~/.git-credentials`
- Gunakan **Personal Access Token** sebagai password
- Setelah input pertama kali, credentials tersimpan otomatis

**Remote SSH** (`untirta`):
- Menggunakan SSH key authentication
- SSH key: `~/.ssh/seiranoku21_github`
- Push langsung tanpa perlu input credentials

---
