# Deployment Guide - Healthcare Staff Management Dashboard

Simple guide to deploy your Healthcare Dashboard online for **FREE**.

## 🌟 Recommended: InfinityFree

**InfinityFree** is the best free hosting for PHP websites with no ads!

### Why InfinityFree?

- ✅ **100% Free Forever**
- ✅ Unlimited bandwidth & storage
- ✅ PHP 7.4 & MySQL support
- ✅ Free subdomain or use your own domain
- ✅ No ads on your site
- ✅ cPanel control panel
- ✅ phpMyAdmin included

### 🚀 Deploy to InfinityFree

#### Step 1: Create Account

1. Go to [infinityfree.com](https://infinityfree.com)
2. Click **"Sign Up"**
3. Fill in your email and create password
4. Verify your email

#### Step 2: Create Hosting Account

1. Login to InfinityFree control panel
2. Click **"Create Account"**
3. Choose a subdomain (e.g., `healthcare-dashboard.free.nf`) or use your domain
4. Click **"Create Account"**
5. Wait 2-5 minutes for account activation

#### Step 3: Upload Files

1. In your hosting control panel, click **"File Manager"** or **"Online File Manager"**
2. Navigate to `htdocs` folder (this is your website root)
3. **Delete** any existing files (like default index.html)
4. Click **"Upload"**
5. Upload ALL your project files:
   - Option A: Upload as ZIP and extract
   - Option B: Upload files individually

#### Step 4: Create Database

1. Go back to control panel
2. Click **"MySQL Databases"**
3. Under "Create Database":
   - Database name: `healthcare` (it will become `epiz_xxxxx_healthcare`)
   - Click **"Create Database"**
4. Under "MySQL Users":
   - Username: `admin` (it will become `epiz_xxxxx_admin`)
   - Password: (generate strong password)
   - Click **"Create User"**
5. Under "Add User To Database":
   - Select user and database
   - Click **"Add"**
   - Grant **ALL PRIVILEGES**

**📝 Important**: Copy your full database name, username, and password!

#### Step 5: Import Database

1. In control panel, click **"phpMyAdmin"**
2. Login with your database credentials
3. Select your database from left sidebar
4. Click **"Import"** tab
5. Click **"Choose File"** and select `healthcare_staff.sql`
6. Scroll down and click **"Go"**
7. Wait for success message

#### Step 6: Configure Database Connection

1. In File Manager, navigate to `htdocs/includes/`
2. Find `config.php` and click **"Edit"**
3. Update these lines:

```php
$host = "localhost";
$db = "epiz_xxxxx_healthcare";      // Your FULL database name
$user = "epiz_xxxxx_admin";         // Your FULL username
$pass = "your_password_here";        // Your database password
```

4. Save the file

#### Step 7: Create Admin User

1. Go to phpMyAdmin
2. Select your database
3. Click **"SQL"** tab
4. Paste this query:

```sql
INSERT INTO users (username, password, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
```

5. Click **"Go"**

#### Step 8: Access Your Site

Visit your website: `http://your-subdomain.free.nf`

**Login with:**
- Username: `admin`
- Password: `password`

**⚠️ IMPORTANT: Change password immediately after first login!**

---

## 🎯 Alternative Free Hosting Options

### 1. 000webhost

**Features:**
- Free hosting with 300 MB storage
- 3 GB bandwidth
- PHP & MySQL support
- Website builder

**Steps:**
1. Sign up at [000webhost.com](https://www.000webhost.com)
2. Create website
3. Upload files via File Manager
4. Create database and import SQL
5. Update `config.php`

### 2. AwardSpace

**Features:**
- 1 GB storage
- PHP & MySQL support
- No ads
- Free subdomain

**Steps:**
1. Sign up at [awardspace.com](https://www.awardspace.com)
2. Create hosting account
3. Upload files via File Manager or FTP
4. Create MySQL database
5. Import SQL file
6. Configure `config.php`

### 3. FreeHosting.com

**Features:**
- 10 GB storage
- PHP & MySQL support
- Free subdomain

**Steps:**
1. Sign up at [freehosting.com](https://www.freehosting.com)
2. Setup hosting account
3. Upload project files
4. Create and import database
5. Update configuration

### 4. Byet.host (ByetHost)

**Features:**
- Unlimited storage & bandwidth
- PHP & MySQL
- Free subdomain

**Steps:**
1. Sign up at [byet.host](https://byet.host)
2. Create account
3. Use cPanel to upload files
4. Setup MySQL database
5. Configure connection

---

## 📱 Deploy on Your Phone (Android)

Yes, you can deploy from your phone!

### Using InfinityFree Mobile

1. **Open browser** on your phone
2. **Visit** [infinityfree.com](https://infinityfree.com)
3. **Create account** (same as desktop steps)
4. **Upload files**:
   - Download your project as ZIP
   - Use File Manager in browser
   - Upload ZIP file
   - Extract in `htdocs` folder
5. **Create database** using phpMyAdmin
6. **Edit config.php** directly in File Manager
7. **Done!** Access your site

---

## 🔧 Troubleshooting

### ❌ "Database connection failed"

**Fix:**
1. Double-check database credentials in `config.php`
2. Make sure you used the FULL database name (with prefix)
3. Test database connection in phpMyAdmin

### ❌ "404 Not Found"

**Fix:**
1. Make sure files are in `htdocs` folder
2. Check that `index.php` or `dashboard.php` exists
3. Clear browser cache

### ❌ "500 Internal Server Error"

**Fix:**
1. Check file permissions (usually 644 for files, 755 for folders)
2. Look for syntax errors in PHP files
3. Check error logs in hosting control panel

### ❌ "Access Denied for user"

**Fix:**
1. Verify database user has all privileges
2. Make sure you're using correct username and password
3. Database host should be "localhost"

### ❌ "Table doesn't exist"

**Fix:**
1. Re-import `healthcare_staff.sql` file
2. Make sure you selected correct database before importing
3. Check that all tables were created successfully

---

## 🔐 Security Checklist

After deployment:

- [ ] Change default admin password
- [ ] Use strong database password
- [ ] Remove or secure `insert_user.php` file
- [ ] Enable HTTPS if available (some free hosts offer free SSL)
- [ ] Test all functionality
- [ ] Setup regular database backups

---

## 💡 Pro Tips

1. **Use Free SSL**: Most free hosts offer free SSL certificates. Enable it!

2. **Custom Domain**: You can use your own domain (if you have one) instead of subdomain

3. **Regular Backups**: Download database backup weekly from phpMyAdmin

4. **Monitor Usage**: Check your hosting usage to stay within free limits

5. **Upgrade Later**: Start free, upgrade to paid hosting when you need more resources

---

## 📊 Comparison Table

| Host | Storage | Bandwidth | Ads | cPanel | SSL |
|------|---------|-----------|-----|--------|-----|
| **InfinityFree** | Unlimited | Unlimited | No | Yes | Yes |
| 000webhost | 300 MB | 3 GB | No | Limited | Yes |
| AwardSpace | 1 GB | 5 GB | No | Yes | Yes |
| FreeHosting | 10 GB | 250 GB | No | Yes | Yes |

---

## 🆘 Need Help?

**If you get stuck:**

1. Check the troubleshooting section above
2. Read InfinityFree support docs
3. Check hosting provider's FAQ
4. Create issue on GitHub with error details

---

## 🎉 Success?

If you successfully deployed, congratulations! 🎊

**Next steps:**
1. Change admin password
2. Add your staff, rooms, and wards
3. Start managing your healthcare team!

---

**Happy Deploying! 🚀**
