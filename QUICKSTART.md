# Quick Start Guide

Get your Healthcare Dashboard running in **5 minutes**!

## 🖥️ Local Development (XAMPP/WAMP/MAMP)

### Prerequisites

- XAMPP, WAMP, or MAMP installed
- Basic knowledge of running a local server

### Steps

#### 1️⃣ Extract Files

Copy project files to your server folder:
- **XAMPP**: `C:\xampp\htdocs\healthcare\`
- **WAMP**: `C:\wamp64\www\healthcare\`
- **MAMP**: `/Applications/MAMP/htdocs/healthcare/`

#### 2️⃣ Start Services

Start **Apache** and **MySQL** from your control panel

#### 3️⃣ Create Database

1. Open browser → `http://localhost/phpmyadmin`
2. Click **"New"**
3. Database name: `healthcare_staff`
4. Collation: `utf8mb4_unicode_ci`
5. Click **"Create"**

#### 4️⃣ Import Database

1. Click on `healthcare_staff` database
2. Click **"Import"** tab
3. Click **"Choose File"**
4. Select `healthcare_staff.sql` from project folder
5. Click **"Go"** at bottom
6. Wait for success message ✅

#### 5️⃣ Configure Connection

1. Open `includes/config.php` in text editor
2. Update if needed:

```php
$host = "localhost";
$db = "healthcare_staff";
$user = "root";
$pass = "";  // Leave empty for XAMPP/WAMP, use "root" for MAMP
```

#### 6️⃣ Create Admin User

Go to phpMyAdmin → SQL tab → Run this:

```sql
INSERT INTO users (username, password, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
```

#### 7️⃣ Access Application

Open browser: `http://localhost/healthcare/`

**Login:**
- Username: `admin`
- Password: `password`

**⚠️ Change password immediately!**

---

## 🌐 Deploy Online (InfinityFree)

### Steps

#### 1️⃣ Create Account

Go to [infinityfree.com](https://infinityfree.com) → Sign Up

#### 2️⃣ Create Hosting

1. Create new account
2. Choose subdomain (e.g., `yoursite.free.nf`)
3. Wait for activation (2-5 minutes)

#### 3️⃣ Upload Files

1. Open **File Manager**
2. Go to `htdocs` folder
3. Delete default files
4. Upload your project files (or upload as ZIP and extract)

#### 4️⃣ Create Database

1. Go to **MySQL Databases**
2. Create database: `healthcare`
3. Create user with strong password
4. Add user to database with ALL PRIVILEGES
5. **Copy** full database name and credentials!

#### 5️⃣ Import Database

1. Open **phpMyAdmin**
2. Select your database
3. Import `healthcare_staff.sql`

#### 6️⃣ Update Config

Edit `htdocs/includes/config.php`:

```php
$host = "localhost";
$db = "epiz_xxxxx_healthcare";     // Your FULL database name
$user = "epiz_xxxxx_admin";        // Your FULL username  
$pass = "your_password";            // Your password
```

#### 7️⃣ Create Admin

In phpMyAdmin → SQL → Run:

```sql
INSERT INTO users (username, password, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
```

#### 8️⃣ Access Site

Visit: `http://yoursite.free.nf`

Login with: `admin` / `password`

---

## ✅ First Login Checklist

After logging in:

1. **Change Password** (Profile/Settings)
2. Go to **Admin Dashboard**
3. Add **Rooms** (Room Panel)
4. Add **Wards** (Ward Panel)  
5. Add **Staff** (Staff Panel)
6. **Assign** staff to shifts, rooms, and wards
7. View **Dashboard** to see assignments

---

## 🐛 Common Issues

### "Database connection failed"
**Fix:** Check database name, username, and password in `config.php`

### "Access denied for user"
**Fix:** Make sure database user has ALL PRIVILEGES

### "Table doesn't exist"  
**Fix:** Re-import `healthcare_staff.sql`

### Blank white page
**Fix:** Enable error display in `config.php`:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Can't login
**Fix:** Make sure admin user was created in database

---

## 📚 Next Steps

- Read full [README.md](README.md) for features
- Check [DEPLOYMENT.md](DEPLOYMENT.md) for more hosting options
- Customize the system for your needs

---

## 🆘 Still Stuck?

1. Check error logs (in cPanel or XAMPP control panel)
2. Re-read steps carefully
3. Create issue on GitHub with error details

---

**You're all set! Enjoy your Healthcare Dashboard! 🎉**
