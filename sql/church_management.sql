-- Church Management System Database
-- Database: church_management

CREATE DATABASE IF NOT EXISTS church_management;
USE church_management;

-- Members table
CREATE TABLE members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    profession VARCHAR(100),
    digital_address VARCHAR(50),
    house_address TEXT NOT NULL,
    membership_date DATE NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    image_url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Payments table
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type VARCHAR(50) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_date DATE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

-- Users table for authentication
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    role ENUM('admin', 'user') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Settings table for themes and configurations
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user (password: 'password')
INSERT INTO users (username, password, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@church.com', 'admin');

-- Insert default theme setting
INSERT INTO settings (setting_key, setting_value) VALUES ('theme', 'light');

-- Sample members data
INSERT INTO members (name, email, phone, profession, digital_address, house_address, membership_date, status) VALUES
('John Smith', 'john.smith@email.com', '(555) 123-4567', 'Teacher', 'GA-123-4567', '123 Main St, East Legon', '2020-01-15', 'active'),
('Mary Johnson', 'mary.johnson@email.com', '(555) 987-6543', 'Nurse', 'GA-456-7890', '456 Oak Ave, Adenta', '2019-06-20', 'active'),
('David Wilson', 'david.wilson@email.com', '(555) 456-7890', 'Engineer', 'GA-789-0123', '789 Pine Rd, Tema', '2021-03-10', 'inactive');

-- Sample payments data
INSERT INTO payments (member_id, amount, type, payment_method, payment_date, description) VALUES
(1, 100.00, 'Tithe', 'Cash', '2024-01-07', 'Weekly tithe'),
(1, 50.00, 'Offering', 'Mobile Money', '2024-01-07', 'Sunday offering'),
(2, 200.00, 'Building Fund', 'Bank Transfer', '2024-01-06', 'Monthly building fund contribution'),
(2, 75.00, 'Tithe', 'Cash', '2024-01-06', 'Weekly tithe'),
(3, 25.00, 'Dues', 'Mobile Money', '2024-01-05', 'Monthly membership dues');