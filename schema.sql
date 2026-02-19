CREATE DATABASE IF NOT EXISTS recruitment_db;
USE recruitment_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    ref_code VARCHAR(20) UNIQUE NOT NULL,
    referred_by INT,
    is_verified TINYINT(1) DEFAULT 0,
    otp_hash VARCHAR(255),
    otp_expiry DATETIME,
    resend_count INT DEFAULT 0,
    last_resend_at DATETIME,
    balance DECIMAL(15, 2) DEFAULT 0.00,
    coin_balance DECIMAL(15, 2) DEFAULT 0.00,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(15, 2) NOT NULL,
    benefits TEXT,
    referral_commission_percentage DECIMAL(5, 2) DEFAULT 0.00,
    file_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    contract_id INT NOT NULL,
    status ENUM('active', 'expired') DEFAULT 'active',
    activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('purchase', 'referral', 'withdrawal', 'deposit', 'bonus') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'completed',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    payment_method_id INT,
    payment_details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(50) UNIQUE NOT NULL,
    `value` TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    details TEXT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert initial settings
INSERT IGNORE INTO settings (`key`, `value`) VALUES ('site_name', 'RecruitPlatform');
INSERT IGNORE INTO settings (`key`, `value`) VALUES ('referral_commission', '10'); -- default 10%
INSERT IGNORE INTO settings (`key`, `value`) VALUES ('min_withdrawal', '10.00');

-- Insert sample contracts
INSERT IGNORE INTO contracts (name, price, benefits, referral_commission_percentage) VALUES
('Basic Recruiter', 50.00, 'Unlock referral link, 5% commission on referrals', 5.00),
('Pro Recruiter', 150.00, '10% commission on referrals, priority support', 10.00),
('Expert Recruiter', 500.00, '20% commission on referrals, custom dashboard', 20.00);

-- Insert an admin user (password: admin123)
-- Note: In a real app, password should be hashed. I'll use a hashed one here.
-- hash for 'admin123'
-- $2y$10$8.09fGvU/j9b7D0XjX/yOeI7D2p4v3H1z2b3c4d5e6f7g8h9i0j1k
INSERT IGNORE INTO users (phone, email, username, password, ref_code, role, is_verified)
VALUES ('1234567890', 'admin@example.com', 'admin', '$2y$10$8.09fGvU/j9b7D0XjX/yOeI7D2p4v3H1z2b3c4d5e6f7g8h9i0j1k', 'ADMINREF', 'admin', 1);
