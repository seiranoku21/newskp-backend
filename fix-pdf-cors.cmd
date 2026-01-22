@echo off
REM ============================================================================
REM Fix PDF CORS - Clear Cache and Optimize (Windows)
REM ============================================================================
REM Script ini membersihkan cache dan optimize Laravel setelah perubahan CORS
REM Jalankan setelah pull/update kode terkait PDF preview fix
REM ============================================================================

echo.
echo ========================================
echo    Fix PDF CORS - Clear Cache
echo ========================================
echo.

REM Step 1: Clear Config Cache
echo [1/5] Clearing config cache...
php artisan config:clear
if %errorlevel% neq 0 (
    echo ERROR: Failed to clear config cache
    exit /b 1
)
echo SUCCESS: Config cache cleared
echo.

REM Step 2: Clear Route Cache
echo [2/5] Clearing route cache...
php artisan route:clear
if %errorlevel% neq 0 (
    echo ERROR: Failed to clear route cache
    exit /b 1
)
echo SUCCESS: Route cache cleared
echo.

REM Step 3: Clear Application Cache
echo [3/5] Clearing application cache...
php artisan cache:clear
if %errorlevel% neq 0 (
    echo ERROR: Failed to clear application cache
    exit /b 1
)
echo SUCCESS: Application cache cleared
echo.

REM Step 4: Clear View Cache
echo [4/5] Clearing view cache...
php artisan view:clear
if %errorlevel% neq 0 (
    echo ERROR: Failed to clear view cache
    exit /b 1
)
echo SUCCESS: View cache cleared
echo.

REM Step 5: List routes to verify
echo [5/5] Verifying routes...
php artisan route:list | findstr "uploads/files"
if %errorlevel% equ 0 (
    echo SUCCESS: File upload routes found
) else (
    echo WARNING: File upload routes not found
)
echo.

REM Success message
echo ========================================
echo    PDF CORS Fix Applied Successfully!
echo ========================================
echo.
echo Next steps:
echo 1. Test PDF preview di browser
echo 2. Check browser console untuk errors
echo 3. Hard refresh browser (Ctrl+Shift+R)
echo.
echo Happy coding!
echo.

pause
