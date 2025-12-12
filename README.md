# Haramaya Pharma Management System

A comprehensive pharmacy management system built with PHP, MySQL, and modern web technologies.

## 🏥 System Overview

Haramaya Pharma Management System is a complete solution for pharmacy operations including:
- **Inventory Management** - Track stock levels, expiry dates, and batch information
- **Point of Sale (POS)** - Process sales with receipt generation
- **User Management** - Admin, Pharmacist, and Cashier roles
- **Alert System** - Low stock and expiry warnings
- **Reporting** - Sales, stock, and expiry reports

## 📋 Features

### Core Functionality
- ✅ **Dashboard** - Key metrics and system overview
- ✅ **Point of Sale** - Complete POS system with receipt printing
- ✅ **Inventory Management** - Stock tracking with FEFO (First Expiry, First Out)
- ✅ **User Management** - Role-based access control
- ✅ **Alert System** - Real-time warnings for stock and expiry
- ✅ **Reporting** - Comprehensive business reports

### Security Features
- ✅ **Authentication** - Secure login with session management
- ✅ **CSRF Protection** - Cross-site request forgery prevention
- ✅ **Input Sanitization** - XSS prevention
- ✅ **Role-Based Access** - Admin, Pharmacist, Cashier permissions
- ✅ **Activity Logging** - Complete audit trail

### User Interface
- ✅ **Responsive Design** - Works on desktop, tablet, and mobile
- ✅ **Modern UI** - Clean, professional interface
- ✅ **Real-time Alerts** - Visual notifications and badges
- ✅ **Professional Receipts** - Print-ready sales receipts

## 🚀 Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

### Setup Steps

1. **Database Setup**
   ```sql
   CREATE DATABASE haramaya_pharma;
   ```

2. **Import Schema**
   ```bash
   mysql -u root -p haramaya_pharma < schema.sql
   ```

3. **Configure Environment**
   - Copy `.env` file and update database credentials
   - Ensure web server has write permissions to `uploads/` directory

4. **Access System**
   - Navigate to your web server URL
   - Default login: `admin` / `admin123`

## 👥 User Roles

### Administrator
- Full system access
- User management
- System configuration
- All reports and analytics

### Pharmacist
- Product management
- Stock management
- Prescription handling
- Sales operations
- Reports access

### Cashier
- Point of sale operations
- View stock levels
- Basic sales reports
- Customer transactions

## 📊 System Modules

### 1. Dashboard (`/modules/dashboard/`)
- System overview and key metrics
- Critical alerts and notifications
- Quick access to common functions

### 2. Point of Sale (`/modules/sales/`)
- Complete POS system
- Receipt generation
- Sales history
- Payment processing

### 3. Inventory Management (`/modules/stock/`)
- Stock level monitoring
- Batch tracking with expiry dates
- Low stock alerts
- Stock adjustments

### 4. User Management (`/modules/users/`)
- User account management
- Role assignment
- Activity logging
- Permission control

### 5. Alert System (`/modules/alerts/`)
- Real-time stock warnings
- Expiry date monitoring
- Critical alert notifications
- System health monitoring

### 6. Management Center (`/modules/management/`)
- Centralized administration
- Quick access to all management functions
- System statistics and overview

## 🔧 Technical Details

### Architecture
- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Security**: CSRF protection, XSS prevention, secure sessions

### File Structure
```
haramaya/
├── assets/           # CSS, JS, images
├── config/           # Database configuration
├── includes/         # Core PHP functions
├── modules/          # Application modules
├── templates/        # Header, footer, sidebar
├── uploads/          # File uploads
├── .env             # Environment configuration
├── index.php        # Main entry point
└── schema.sql       # Database schema
```

### Database Schema
- **users** - System user accounts
- **products** - Product catalog
- **stock_batches** - Inventory with batch tracking
- **sales** - Sales transactions
- **sale_items** - Individual sale items
- **suppliers** - Supplier information
- **product_categories** - Product categorization
- **activity_logs** - System audit trail

## 🚨 Alert System

The system provides comprehensive monitoring:

- **Expired Items** - Products past expiry date
- **Critical Expiry** - Items expiring within 30 days
- **Low Stock** - Products below reorder level
- **Out of Stock** - Products with zero inventory

Alerts appear in:
- Dashboard banners
- Sidebar badges
- Header notifications
- Dedicated alerts page

## 📱 Responsive Design

The system is fully responsive and works on:
- Desktop computers
- Tablets
- Mobile phones
- Print (receipts)

## 🔒 Security

- Secure password hashing
- Session management
- CSRF token protection
- Input validation and sanitization
- Role-based access control
- Activity logging

## 📞 Support

For technical support or questions about the Haramaya Pharma Management System, please contact the development team.

## 📄 License

This system is developed for Haramaya Pharma. All rights reserved.

---

**Haramaya Pharma Management System v1.0**  
Professional Pharmacy Management Solution