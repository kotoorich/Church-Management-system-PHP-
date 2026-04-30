-- Church Management System Database Schema
-- Create database
CREATE DATABASE IF NOT EXISTS church_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE church_management;

-- Users table for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Members table
CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    profession VARCHAR(100),
    digital_address VARCHAR(50),
    house_address TEXT NOT NULL,
    membership_date DATE NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    image_url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_name (name),
    INDEX idx_email (email)
);

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_type ENUM('Tithe', 'Offering', 'Dues', 'Building Fund', 'Mission', 'Special Offering', 'Youth Ministry', 'Music Ministry', 'Other') NOT NULL,
    payment_method ENUM('Cash', 'Mobile Money', 'Bank Transfer', 'Check', 'Card', 'Other') NOT NULL,
    payment_date DATE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    INDEX idx_member_id (member_id),
    INDEX idx_payment_date (payment_date),
    INDEX idx_payment_type (payment_type)
);

-- Settings table for themes and app settings
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value VARCHAR(100) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user (password: 'password')
INSERT INTO users (username, password, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@church.com');

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES 
('theme', 'light'),
('site_name', 'Grace Community Church'),
('members_per_page', '6'),
('payments_per_page', '8');

-- Sample members data
INSERT INTO members (name, email, phone, profession, digital_address, house_address, membership_date, status) VALUES
('John Smith', 'john.smith@email.com', '(555) 123-4567', 'Teacher', 'GA-123-4567', '123 Main St, East Legon', '2020-01-15', 'active'),
('Mary Johnson', 'mary.johnson@email.com', '(555) 987-6543', 'Nurse', 'GA-456-7890', '456 Oak Ave, Adenta', '2019-06-20', 'active'),
('David Wilson', 'david.wilson@email.com', '(555) 456-7890', 'Engineer', 'GA-789-0123', '789 Pine Rd, Tema', '2021-03-10', 'active'),
('Sarah Brown', 'sarah.brown@email.com', '(555) 321-6547', 'Banker', 'GA-321-6547', '321 Cedar Ave, Accra', '2018-11-05', 'active'),
('Michael Davis', 'michael.davis@email.com', '(555) 654-3210', 'Doctor', 'GA-654-3210', '654 Elm St, Kumasi', '2022-02-28', 'inactive');

-- Sample payments data
INSERT INTO payments (member_id, amount, payment_type, payment_method, payment_date, description) VALUES
(1, 100.00, 'Tithe', 'Cash', '2024-01-07', 'Weekly tithe'),
(1, 50.00, 'Offering', 'Mobile Money', '2024-01-07', 'Sunday offering'),
(2, 200.00, 'Building Fund', 'Bank Transfer', '2024-01-06', 'Monthly building fund contribution'),
(2, 75.00, 'Tithe', 'Cash', '2024-01-06', 'Weekly tithe'),
(3, 25.00, 'Dues', 'Mobile Money', '2024-01-05', 'Monthly membership dues'),
(4, 150.00, 'Tithe', 'Bank Transfer', '2024-01-05', 'Monthly tithe'),
(4, 30.00, 'Youth Ministry', 'Cash', '2024-01-04', 'Youth ministry support'),
(1, 80.00, 'Mission', 'Mobile Money', '2024-01-04', 'Mission fund'),
(2, 120.00, 'Special Offering', 'Card', '2024-01-03', 'New Year special offering'),
(3, 45.00, 'Offering', 'Cash', '2024-01-03', 'Sunday offering'),
(5, 90.00, 'Tithe', 'Bank Transfer', '2024-01-02', 'January tithe'),
(1, 60.00, 'Music Ministry', 'Mobile Money', '2024-01-01', 'Music ministry donation');