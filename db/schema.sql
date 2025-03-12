-- Database Schema for Web Call Credit App

-- Create database (Run this manually if not already created through cPanel)
-- CREATE DATABASE webcall_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE webcall_app;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone_number VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    credits DECIMAL(10, 2) DEFAULT 0.00,
    is_phone_verified TINYINT(1) DEFAULT 0,
    verification_code VARCHAR(10) NULL,
    auto_topup TINYINT(1) DEFAULT 0,
    topup_package INT DEFAULT 1,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Credit packages table
CREATE TABLE credit_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    credits INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default credit packages
INSERT INTO credit_packages (name, credits, price) VALUES 
('Basic', 1000, 10.00),
('Standard', 2500, 20.00),
('Premium', 7000, 50.00),
('Ultimate', 20000, 100.00);

-- Calls table
CREATE TABLE calls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    destination_number VARCHAR(20) NOT NULL,
    duration INT DEFAULT 0, -- in seconds
    status VARCHAR(20) DEFAULT 'completed',
    credits_used DECIMAL(10, 2) DEFAULT 0.00,
    twilio_call_sid VARCHAR(50) NULL,
    started_at TIMESTAMP NULL,
    ended_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_id INT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    credits DECIMAL(10, 2) NOT NULL,
    stripe_payment_id VARCHAR(100) NULL,
    coupon_id INT NULL,
    discount_amount DECIMAL(10, 2) DEFAULT 0.00,
    status VARCHAR(20) DEFAULT 'pending',
    is_auto_topup TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES credit_packages(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Coupons table
CREATE TABLE coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10, 2) NOT NULL,
    min_purchase DECIMAL(10, 2) DEFAULT 0.00,
    max_discount DECIMAL(10, 2) NULL,
    expiration_date DATE NULL,
    is_active TINYINT(1) DEFAULT 1,
    usage_limit INT NULL,
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Coupon Usage table
CREATE TABLE coupon_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    user_id INT NOT NULL,
    payment_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Verification codes table
CREATE TABLE verification_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code VARCHAR(10) NOT NULL,
    type VARCHAR(20) DEFAULT 'phone', -- phone, email, etc.
    expires_at TIMESTAMP NULL,
    is_used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- System settings table
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES 
('credit_multiplier', '200'), -- Multiply Twilio cost by this factor
('min_credits_for_topup', '100'), -- Minimum credits before auto top-up
('site_name', 'Web Call Credit App'),
('support_email', 'support@example.com');

-- Create admin user (change credentials before deploying)
-- Password is 'admin123' (hashed)
INSERT INTO users (name, email, phone_number, password, credits, is_phone_verified, is_admin) VALUES 
('Admin User', 'admin@example.com', '+1234567890', '$2y$10$u.hGXSINI9ZMDk7z8zVKY.K6KlE.RsJ3l/.ZvRy0VOj4V4QeXEamy', 10000, 1, 1); 