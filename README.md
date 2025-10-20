# Healthcare Staff Management Dashboard

A web-based system for managing healthcare staff assignments, room and ward allocations, and shift scheduling.

## ✨ Features

- 🔐 User authentication with Admin/User roles
- 👥 Staff management (S/N and O/M staff types)
- 🏥 Room & Ward management
- ⏰ Shift scheduling (Morning/Evening)
- 📊 Real-time dashboard
- 📝 Activity logging
- 📱 Mobile-friendly design

## 🛠️ Built With

- PHP 7.0+
- MySQL / MariaDB
- Bootstrap 5
- JavaScript
- Font Awesome

## 🚀 Quick Start

### For Local Development (XAMPP/WAMP)

1. **Extract files** to `htdocs/healthcare-dashboard/`

2. **Create database** in phpMyAdmin:
   - Database name: `healthcare_staff`

3. **Import** `healthcare_staff.sql` file

4. **Configure** `includes/config.php`:
   ```php
   $host = "localhost";
   $db = "healthcare_staff";
   $user = "root";
   $pass = "";  // Empty for XAMPP
   ```

5. **Create admin user** in phpMyAdmin:
   ```sql
   INSERT INTO users (username, password, role) 
   VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
   ```

6. **Access**: `http://localhost/healthcare-dashboard/`
   - Username: `admin`
   - Password: `password` (change after first login!)

For detailed setup, see [QUICKSTART.md](QUICKSTART.md)

## 🌍 Deploy Online

Deploy for FREE using online hosting services. See [DEPLOYMENT.md](DEPLOYMENT.md) for:
- InfinityFree (Recommended)
- 000webhost
- AwardSpace
- Other free PHP hosting options

## 📁 Project Structure

```
healthcare-dashboard/
├── auth/              # Login/Logout pages
├── includes/          # Configuration & reusable components
├── panels/            # Staff, Room, Ward management
├── css/               # Stylesheets
├── js/                # JavaScript files
├── logs/              # Activity logging
├── dashboard.php      # Main dashboard
├── admin_dashboard.php # Admin panel
└── healthcare_staff.sql # Database schema
```

## 🎯 How to Use

### For Administrators:
1. Login with admin account
2. Go to Admin Dashboard
3. Add rooms and wards
4. Add staff members (S/N or O/M)
5. Assign staff to shifts, rooms, and wards

### For Regular Users:
1. Login with user account
2. View current shift assignments
3. Check staff allocations

## 🔒 Security Tips

- Change default password immediately
- Use strong passwords
- Keep PHP and MySQL updated
- Use HTTPS in production
- Regular database backups

## 🐛 Troubleshooting

**Database connection error?**
- Check credentials in `includes/config.php`

**Can't login?**
- Verify admin user was created in database

**Blank page?**
- Check PHP error logs
- Enable error display temporarily

## 📝 License

MIT License - Free to use and modify

## 👥 Contributing

Contributions welcome! Fork the repo and submit a pull request.

---

**Made with ❤️ for Healthcare Management**
