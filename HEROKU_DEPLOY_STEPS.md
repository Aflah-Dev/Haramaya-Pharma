# Heroku Deployment - Complete Guide

## 🔐 Account Verification Required
Your Heroku account needs verification before creating apps. This is a one-time process.

**Action Required:** Visit https://heroku.com/verify and add payment information (no charges for free tier usage).

## 📋 Once Verified - Run These Commands:

### 1. Create Heroku App
```bash
cd haramaya
heroku create haramaya-pharma-system
```

### 2. Add MySQL Database
```bash
heroku addons:create cleardb:ignite
```

### 3. Configure Environment Variables
```bash
heroku config:set APP_ENV=production
heroku config:set APP_TIMEZONE=Africa/Addis_Ababa  
heroku config:set SESSION_TIMEOUT=3600
```

### 4. Deploy Application
```bash
git push heroku main
```

### 5. Setup Database Schema
```bash
# Check if database is connected
heroku run php database_check.php

# If needed, import schema manually
heroku pg:psql < schema.sql
```

### 6. Open Your App
```bash
heroku open
```

## 🔍 Troubleshooting

### Check Logs
```bash
heroku logs --tail
```

### Check Config
```bash
heroku config
```

### Restart App
```bash
heroku restart
```

## 📊 Expected Result
- App URL: https://haramaya-pharma-system.herokuapp.com
- Login: admin / admin123
- Full pharmacy management system online

## 🆓 Alternative: Manual Upload to Free Hosting

If you prefer not to verify Heroku account:

1. **Download project files**
2. **Upload to free PHP hosting** (InfinityFree, 000webhost, etc.)
3. **Create MySQL database** on hosting panel
4. **Import schema.sql** via phpMyAdmin
5. **Update .env** with hosting database credentials

Your application is 100% ready for deployment!