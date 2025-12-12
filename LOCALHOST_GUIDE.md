# 🏠 Running Haramaya Pharma on Localhost

## 🚀 Quick Start (Already Running!)

Your application is currently running at: **http://localhost:8000**

### 🔑 Login Credentials
- **Username:** `admin`
- **Password:** `admin123`

## 📋 How to Access

1. **Open your web browser**
2. **Go to:** http://localhost:8000
3. **Login with:** admin / admin123

## 🔧 Manual Start/Stop Commands

### Start the Application
```bash
cd haramaya

# Start MySQL (if not running)
sudo systemctl start mysql

# Start PHP development server
php -S localhost:8000
```

### Stop the Application
```bash
# Stop PHP server: Press Ctrl+C in terminal
# Stop MySQL (optional)
sudo systemctl stop mysql
```

## 🌐 Application Features Available

### 📊 Dashboard
- Overview of inventory, sales, alerts
- Quick access to all modules

### 💊 Inventory Management
- Add/edit products
- Manage categories
- Track stock levels
- Expiry date monitoring

### 🛒 Point of Sale (POS)
- Process sales transactions
- Generate receipts
- Customer management

### 👥 User Management
- Admin, pharmacist, cashier roles
- User permissions
- Activity logging

### 📈 Reports
- Sales reports
- Stock reports
- Expiry alerts
- Financial summaries

### 🚚 Supplier Management
- Supplier information
- Purchase tracking

## 🔍 Database Management

### Check Database Status
```bash
php database_check.php
```

### Access Database Directly
```bash
mysql -u root haramaya_pharma
```

## 🛠️ Troubleshooting

### If localhost:8000 doesn't work:
1. **Check if PHP server is running:**
   ```bash
   ps aux | grep php
   ```

2. **Restart PHP server:**
   ```bash
   cd haramaya
   php -S localhost:8000
   ```

### If database connection fails:
1. **Start MySQL:**
   ```bash
   sudo systemctl start mysql
   ```

2. **Check database:**
   ```bash
   php database_check.php
   ```

### If login doesn't work:
- Username: `admin`
- Password: `admin123`
- Clear browser cache/cookies

## 📱 Mobile Access

Access from other devices on your network:
1. **Find your IP:** `ip addr show`
2. **Use:** http://YOUR_IP:8000
3. **Example:** http://192.168.1.100:8000

## 🔒 Security Notes

- This is for development/testing only
- For production, use proper web server (Apache/Nginx)
- Change default admin password
- Use HTTPS in production

## 📞 Support

If you encounter issues:
1. Check the terminal for error messages
2. Verify MySQL is running
3. Ensure port 8000 is not blocked
4. Check file permissions

**Your pharmacy management system is ready to use!** 🎉