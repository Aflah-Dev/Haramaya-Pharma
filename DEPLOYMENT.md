# Haramaya Pharma - Deployment Guide

## Heroku Deployment

### Prerequisites
1. Verify your Heroku account at https://heroku.com/verify
2. Install Heroku CLI (already done)
3. Login to Heroku: `heroku login`

### Steps
1. **Create Heroku App**
   ```bash
   heroku create your-app-name
   ```

2. **Add Database Add-on**
   ```bash
   heroku addons:create cleardb:ignite
   ```

3. **Set Environment Variables**
   ```bash
   heroku config:set APP_ENV=production
   heroku config:set APP_TIMEZONE=Africa/Addis_Ababa
   heroku config:set SESSION_TIMEOUT=3600
   ```

4. **Deploy**
   ```bash
   git push heroku main
   ```

5. **Import Database Schema**
   ```bash
   heroku run php database_check.php
   ```

### Database Setup
After deployment, you'll need to:
1. Get database URL: `heroku config:get DATABASE_URL`
2. Import schema using the database check script
3. Create admin user (already included in schema.sql)

## Alternative Deployment Options

### 1. Railway (Free Tier Available)
```bash
npm install -g @railway/cli
railway login
railway init
railway up
```

### 2. Render (Free Tier Available)
1. Connect GitHub repository
2. Set build command: `composer install`
3. Set start command: `vendor/bin/heroku-php-apache2`

### 3. DigitalOcean App Platform
1. Connect GitHub repository
2. Configure environment variables
3. Add MySQL database

### 4. Traditional VPS/Shared Hosting
1. Upload files via FTP/SFTP
2. Create MySQL database
3. Import schema.sql
4. Update .env file with database credentials

## Login Credentials
- **Username:** admin
- **Password:** admin123

## Environment Variables Required
- `DB_HOST` - Database host
- `DB_NAME` - Database name  
- `DB_USER` - Database username
- `DB_PASS` - Database password
- `APP_ENV` - Application environment (production)
- `APP_TIMEZONE` - Timezone (Africa/Addis_Ababa)
- `SESSION_TIMEOUT` - Session timeout in seconds (3600)

## Post-Deployment Checklist
1. ✅ Database connection working
2. ✅ Admin login functional
3. ✅ All modules accessible
4. ✅ File permissions correct
5. ✅ SSL certificate configured (recommended)