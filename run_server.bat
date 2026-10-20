@echo off
title MIB Photoman Development Server
echo ====================================================================
echo   MIB PHOTOMAN PH - Web Scheduling and Customization System (25%%)
echo   Inverted Monochromatic Suit-and-Tie Aesthetic
echo ====================================================================
echo.
echo Starting PHP built-in web server...
echo Point your browser to: http://localhost:8000
echo.
php -S localhost:8000 -t "%~dp0"
if errorlevel 1 (
    echo.
    echo [ERROR] 'php' command not found in default PATH.
    echo Attempting to run via WinGet installed PHP location...
    "%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe" -S localhost:8000 -t "%~dp0"
)
pause
