@echo off
title Smart Government Scheme Tracker - Auto-Sync Server
color 0A

echo ==============================================================================
echo   SMART GOVERNMENT SCHEME ELIGIBILITY AND BENEFIT TRACKER
echo   Academic Context: B.Tech. II Year I Sem - AIML & B Section
echo   Subject: Database Management System (DBMS)
echo   Database: SQLite (Automatic 2-Way Sync between Browser and Disk File)
echo   NO BACKEND: Zero PHP, Node.js, Express, MySQL, or XAMPP Required
echo ==============================================================================
echo.
echo [1/3] Checking environment and Python...
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Python is not found in PATH!
    pause
    exit /b 1
)

echo [2/3] Setting working directory...
cd /d "%~dp0"
if exist "smart-government-scheme-tracker\index.html" (
    cd smart-government-scheme-tracker
)

echo [3/3] Launching Web Portal with Automatic Database Synchronization...
start "" "http://localhost:5000/index.html"

echo.
echo ==============================================================================
echo Automatic Database Sync Server running at http://localhost:5000/
echo - Automatically loads government_scheme_tracker.db from disk on page refresh!
echo - Automatically writes browser changes to government_scheme_tracker.db on disk!
echo - Edit in sqlite3.exe or DB Browser -> Refresh page -> Zero clicks needed!
echo Press CTRL+C in this window to stop the server.
echo ==============================================================================
if exist "sync_server.py" (
    python sync_server.py 5000
) else (
    python -m http.server 5000
)
pause
