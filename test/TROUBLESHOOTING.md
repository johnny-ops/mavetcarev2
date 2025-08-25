# MavetCare Troubleshooting Guide

## Issue: "This page isn't working right now" on index.php

### Step 1: Check XAMPP Status
1. Open XAMPP Control Panel
2. Make sure **Apache** and **MySQL** are running (should show green)
3. If not running, click "Start" for both services

### Step 2: Test Database Connection
1. Open your browser and go to: `http://localhost/mavetcarev2/test_connection.php`
2. This will show you if PHP and database are working

### Step 3: Import Database (if needed)
If the database doesn't exist:
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click "Import" in the top menu
3. Choose the file: `mavetcare_db.sql`
4. Click "Go" to import the database

### Step 4: Check File Permissions
Make sure all PHP files are readable by the web server.

### Step 5: Common Issues & Solutions

#### Issue: Database Connection Failed
- **Solution**: Make sure MySQL is running in XAMPP
- **Solution**: Check if database `mavetcare_db` exists in phpMyAdmin

#### Issue: PHP Errors
- **Solution**: Check XAMPP error logs at `C:\xampp\apache\logs\error.log`
- **Solution**: Make sure PHP is enabled in XAMPP

#### Issue: Session Problems
- **Solution**: Clear browser cookies and cache
- **Solution**: Restart Apache in XAMPP

### Step 6: Test Profile Page
1. Go to: `http://localhost/mavetcarev2/profile.php`
2. If you see database errors, follow Step 3 above
3. The profile page now has better error handling

## Color Theme Update
✅ **Profile page has been updated** to match your system's green theme (`#8BC34A`)

## Quick Fix Commands
If you're comfortable with command line:
```bash
# Start XAMPP services
cd C:\xampp
xampp_start.exe

# Or manually start services
apache_start.bat
mysql_start.bat
```

## Still Having Issues?
1. Check the test file: `http://localhost/mavetcarev2/test_connection.php`
2. Look at XAMPP error logs
3. Make sure you're accessing via `http://localhost/mavetcarev2/` not `file://`
