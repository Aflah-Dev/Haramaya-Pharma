#!/bin/bash

# Haramaya Pharma - Heroku Deployment Script
# Run this after verifying your Heroku account

echo "🚀 Deploying Haramaya Pharma to Heroku..."

# Check if logged in to Heroku
if ! heroku auth:whoami > /dev/null 2>&1; then
    echo "❌ Please login to Heroku first: heroku login"
    exit 1
fi

# Create Heroku app
echo "📱 Creating Heroku app..."
heroku create haramaya-pharma-system

# Add MySQL database
echo "🗄️ Adding MySQL database..."
heroku addons:create cleardb:ignite

# Set environment variables
echo "⚙️ Setting environment variables..."
heroku config:set APP_ENV=production
heroku config:set APP_TIMEZONE=Africa/Addis_Ababa
heroku config:set SESSION_TIMEOUT=3600

# Deploy to Heroku
echo "🚀 Deploying application..."
git push heroku main

# Check database connection
echo "🔍 Checking database connection..."
heroku run php database_check.php

# Open the app
echo "🌐 Opening your app..."
heroku open

echo "✅ Deployment complete!"
echo "Login credentials: admin / admin123"