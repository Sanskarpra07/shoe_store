@echo off
rem ================================================================
rem  StepStyle - One-click setup for a new machine (Windows + XAMPP)
rem  Run this file from the project folder, or double-click it.
rem ================================================================
title StepStyle Setup
cd /d "%~dp0"

if not exist "sql\setup.sql" (
    echo [ERROR] sql\setup.sql not found. Run this file from the project root folder.
    pause
    exit /b 1
)

rem ---- Locate the MySQL client ----
set "MYSQL="
if exist "C:\xampp\mysql\bin\mysql.exe" set "MYSQL=C:\xampp\mysql\bin\mysql.exe"
if not defined MYSQL (
    where mysql >nul 2>nul
    if not errorlevel 1 set "MYSQL=mysql.exe"
)
if not defined MYSQL (
    echo [ERROR] MySQL client not found.
    echo         Install XAMPP at C:\xampp, open "XAMPP Control Panel" and click Start on MySQL.
    pause
    exit /b 1
)

echo [1/3] Checking MySQL is running ...
"%MYSQL%" -u root -e "SELECT 1" >nul 2>nul
if errorlevel 1 (
    echo [ERROR] Cannot connect to MySQL ^(root, no password^).
    echo         Start MySQL in "XAMPP Control Panel", then run this file again.
    pause
    exit /b 1
)

rem ---- Reset existing database? ----
"%MYSQL%" -u root -N -e "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='shoe_store_db'" >nul 2>nul
if not errorlevel 1 (
    echo.
    echo Database "shoe_store_db" already exists.
    choice /c YN /m "Reset it to fresh sample data (Y/N)"
    if errorlevel 2 (
        echo Skipped. Opening your store...
        goto open
    )
    echo Dropping existing database...
    "%MYSQL%" -u root -e "DROP DATABASE IF EXISTS shoe_store_db"
)

echo [2/3] Creating database and importing sample data ...
"%MYSQL%" -u root < "sql\setup.sql"
if errorlevel 1 (
    echo [ERROR] Import failed.
    pause
    exit /b 1
)
echo        Done. 12 products, 5 categories, 5 brands imported.

:open
echo [3/3] Opening your store in the browser ...
rem Build the web path from the folder location
set "WEBPATH="
for /f "delims=" %%i in ('powershell -NoProfile -Command "$p=(Resolve-Path -LiteralPath '%~dp0').Path; $d='C:\xampp\htdocs'; if($p.StartsWith($d)){ ($p.Substring($d.Length) -replace '\\','/') } else { '' }"') do set "WEBPATH=%%i"
start "" "http://localhost%WEBPATH%/install.php"
echo.
echo Setup finished. The installer page should have opened in your browser.
echo  - Frontend: http://localhost%WEBPATH%/
echo  - Admin:    http://localhost%WEBPATH%/admin/login.php
echo  - Admin login: username "admin"  password "password"
echo.
pause