@echo off
title Smart Government Scheme Tracker - SQLite Command Line
color 0B
cd /d "%~dp0"

echo ==============================================================================
echo   SMART GOVERNMENT SCHEME TRACKER - SQLITE CONSOLE (sqlite3.exe)
echo   Academic Context: B.Tech. II Year I Sem - AIML & B Section
echo   Connected Database: government_scheme_tracker.db
echo ==============================================================================
echo.
echo   HELPFUL SQLITE COMMANDS FOR YOUR DEMO:
echo   - .tables                   : List all 8 relational tables
echo   - .schema <table_name>      : View table structure and constraints
echo   - SELECT * FROM Citizen;    : View all citizen records
echo   - SELECT * FROM Scheme;     : View all welfare schemes
echo   - UPDATE Citizen SET ...;   : Modify any database record
echo   - INSERT INTO ...;          : Insert new records
echo   - .exit                     : Exit console
echo.
echo ==============================================================================
echo.

sqlite3.exe government_scheme_tracker.db -cmd ".headers on" -cmd ".mode box"
