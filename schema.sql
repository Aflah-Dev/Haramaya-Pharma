-- ============================================
-- HARAMAYA PHARMA - MAIN DATABASE SCHEMA
-- Admin/Staff Pharmacy Management System
-- ============================================

-- Users table (staff/admin accounts)
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    role ENUM('admin', 'pharmacist', 'cashier', 'manager') DEFAULT 'cashier',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    created_by INT NULL,
    INDEX idx_username (username),
    INDEX idx_role (role),
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product categories
CREATE TABLE IF NOT EXISTS product_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Suppliers
CREATE TABLE IF NOT EXISTS suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_supplier_name (supplier_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products
CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(50) NOT NULL UNIQUE,
    product_name VARCHAR(200) NOT NULL,
    generic_name VARCHAR(200),
    category_id INT NOT NULL,
    strength VARCHAR(50),
    dosage_form VARCHAR(50),
    unit_price DECIMAL(10,2) NOT NULL,
    reorder_level INT DEFAULT 10,
    requires_prescription BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES product_categories(category_id) ON DELETE RESTRICT,
    INDEX idx_product_code (product_code),
    INDEX idx_product_name (product_name),
    INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock batches
CREATE TABLE IF NOT EXISTS stock_batches (
    batch_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    supplier_id INT NOT NULL,
    batch_number VARCHAR(100) NOT NULL,
    quantity_received INT NOT NULL,
    quantity_remaining INT NOT NULL,
    unit_cost DECIMAL(10,2) NOT NULL,
    expiry_date DATE NOT NULL,
    received_date DATE NOT NULL,
    received_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE RESTRICT,
    FOREIGN KEY (received_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_product_batch (product_id, batch_number),
    INDEX idx_expiry_date (expiry_date),
    INDEX idx_supplier (supplier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sales
CREATE TABLE IF NOT EXISTS sales (
    sale_id INT AUTO_INCREMENT PRIMARY KEY,
    sale_number VARCHAR(50) NOT NULL UNIQUE,
    customer_name VARCHAR(100),
    customer_phone VARCHAR(20),
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    tax DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0,
    change_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    payment_method ENUM('cash', 'card', 'mobile_money') DEFAULT 'cash',
    cashier_id INT NOT NULL,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (cashier_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_sale_number (sale_number),
    INDEX idx_sale_date (sale_date),
    INDEX idx_cashier (cashier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sale items
CREATE TABLE IF NOT EXISTS sale_items (
    sale_item_id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    batch_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(sale_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT,
    FOREIGN KEY (batch_id) REFERENCES stock_batches(batch_id) ON DELETE RESTRICT,
    INDEX idx_sale_id (sale_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock adjustments
CREATE TABLE IF NOT EXISTS stock_adjustments (
    adjustment_id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    adjustment_type ENUM('increase', 'decrease', 'correction') NOT NULL,
    quantity_change INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    adjusted_by INT NOT NULL,
    adjustment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (batch_id) REFERENCES stock_batches(batch_id) ON DELETE CASCADE,
    FOREIGN KEY (adjusted_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_batch_id (batch_id),
    INDEX idx_adjustment_date (adjustment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password_hash, full_name, email, role) VALUES
('admin', '$2y$10$jqn3QpbjxKbmMK9HMiRvGepIc28Xde7KyltWrqnM33NGra4q6tZ7G', 'System Administrator', 'admin@haramayapharma.com', 'admin');

-- Insert sample categories
INSERT INTO product_categories (category_name, description) VALUES
('Antibiotics', 'Antimicrobial medications'),
('Pain Relief', 'Analgesics and anti-inflammatory drugs'),
('Vitamins', 'Vitamin and mineral supplements'),
('Cardiovascular', 'Heart and blood pressure medications'),
('Respiratory', 'Cough, cold and respiratory medications'),
('Digestive', 'Gastrointestinal medications'),
('Diabetes', 'Diabetes management medications'),
('Dermatology', 'Skin care and topical medications');

-- Activity logs for security and audit trail
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    table_affected VARCHAR(50),
    record_id INT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample supplier
INSERT INTO suppliers (supplier_name, contact_person, phone, email, address) VALUES
('Ethiopian Pharmaceuticals', 'Ato Bekele Tadesse', '+251-11-555-0001', 'sales@ethiopharma.com', 'Addis Ababa, Ethiopia'),
('Global Medical Supplies', 'Dr. Sarah Johnson', '+251-11-555-0002', 'orders@globalmed.com', 'Bole, Addis Ababa');

-- Insert sample products for testing
INSERT INTO products (product_code, product_name, generic_name, category_id, strength, dosage_form, unit_price, reorder_level) VALUES
('PARA500', 'Paracetamol 500mg', 'Paracetamol', 2, '500mg', 'Tablet', 2.50, 100),
('AMOX250', 'Amoxicillin 250mg', 'Amoxicillin', 1, '250mg', 'Capsule', 15.00, 50),
('VITC100', 'Vitamin C 100mg', 'Ascorbic Acid', 3, '100mg', 'Tablet', 5.00, 200),
('ASPIR100', 'Aspirin 100mg', 'Acetylsalicylic Acid', 2, '100mg', 'Tablet', 3.00, 150);

-- Insert sample stock batches for testing
INSERT INTO stock_batches (product_id, supplier_id, batch_number, quantity_received, quantity_remaining, unit_cost, expiry_date, received_date, received_by) VALUES
(1, 1, 'PARA001', 500, 450, 2.00, '2025-12-31', '2024-01-15', 1),
(1, 1, 'PARA002', 300, 280, 2.10, '2024-06-30', '2023-12-01', 1),
(2, 1, 'AMOX001', 200, 180, 12.00, '2025-08-15', '2024-02-01', 1),
(3, 2, 'VITC001', 1000, 950, 4.00, '2026-03-20', '2024-01-10', 1),
(4, 1, 'ASP001', 400, 350, 2.50, '2024-12-25', '2023-11-15', 1);

-- Create views for easier reporting
CREATE OR REPLACE VIEW stock_summary AS
SELECT 
    p.product_id,
    p.product_code,
    p.product_name,
    p.generic_name,
    pc.category_name,
    SUM(sb.quantity_remaining) as total_stock,
    p.reorder_level,
    MIN(sb.expiry_date) as earliest_expiry,
    COUNT(sb.batch_id) as batch_count,
    AVG(sb.unit_cost) as avg_cost
FROM products p
LEFT JOIN stock_batches sb ON p.product_id = sb.product_id AND sb.quantity_remaining > 0
LEFT JOIN product_categories pc ON p.category_id = pc.category_id
WHERE p.is_active = 1
GROUP BY p.product_id;

-- Create view for expired items
CREATE OR REPLACE VIEW expired_stock AS
SELECT 
    sb.batch_id,
    p.product_name,
    sb.batch_number,
    sb.quantity_remaining,
    sb.expiry_date,
    DATEDIFF(CURDATE(), sb.expiry_date) as days_expired,
    s.supplier_name
FROM stock_batches sb
INNER JOIN products p ON sb.product_id = p.product_id
INNER JOIN suppliers s ON sb.supplier_id = s.supplier_id
WHERE sb.expiry_date < CURDATE() 
AND sb.quantity_remaining > 0
ORDER BY sb.expiry_date ASC;