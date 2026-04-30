-- Church Management System Database Setup with Sample Data
-- Matches React App.tsx sample data EXACTLY

-- Create Database
CREATE DATABASE IF NOT EXISTS church_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE church_management;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS members;
DROP TABLE IF EXISTS users;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: password)
INSERT INTO users (username, password) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Members Table
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
    INDEX idx_email (email),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments Table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_type VARCHAR(100) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_date DATE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    INDEX idx_member (member_id),
    INDEX idx_date (payment_date),
    INDEX idx_type (payment_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Sample Members (matches React sample data)
INSERT INTO members (name, email, phone, profession, digital_address, house_address, membership_date, status) VALUES
('John Smith', 'john.smith@email.com', '(555) 123-4567', 'Teacher', 'GA-123-4567', '123 Main St, East Legon', '2020-01-15', 'active'),
('Mary Johnson', 'mary.johnson@email.com', '(555) 987-6543', 'Nurse', 'GA-456-7890', '456 Oak Ave, Adenta', '2019-06-20', 'active'),
('David Wilson', 'david.wilson@email.com', '(555) 456-7890', 'Engineer', 'GA-789-0123', '789 Pine Rd, Tema', '2021-03-10', 'inactive'),
('Sarah Brown', 'sarah.brown@email.com', '(555) 234-5678', 'Doctor', 'GA-234-5678', '234 Elm St, Osu', '2020-08-22', 'active'),
('Michael Davis', 'michael.davis@email.com', '(555) 345-6789', 'Accountant', 'GA-345-6789', '345 Maple Dr, Cantonments', '2021-05-14', 'active'),
('Jennifer Williams', 'jennifer.williams@email.com', '(555) 567-8901', 'Lawyer', 'GA-567-8901', '567 Cedar Ln, Labone', '2019-11-30', 'active'),
('Robert Jones', 'robert.jones@email.com', '(555) 678-9012', 'Business Owner', 'GA-678-9012', '678 Birch Ave, Airport', '2020-03-18', 'active'),
('Linda Garcia', 'linda.garcia@email.com', '(555) 789-0123', 'Pastor', 'GA-789-0123', '789 Spruce St, Dzorwulu', '2018-07-25', 'active'),
('James Martinez', 'james.martinez@email.com', '(555) 890-1234', 'Farmer', 'GA-890-1234', '890 Willow Rd, Achimota', '2021-09-10', 'active'),
('Patricia Anderson', 'patricia.anderson@email.com', '(555) 901-2345', 'Banker', 'GA-901-2345', '901 Poplar Way, Ridge', '2020-12-05', 'active'),
('Christopher Taylor', 'christopher.taylor@email.com', '(555) 012-3456', 'Police Officer', 'GA-012-3456', '012 Ash Ct, Spintex', '2019-04-15', 'active'),
('Barbara Thomas', 'barbara.thomas@email.com', '(555) 123-4567', 'Carpenter', 'GA-123-4567', '123 Oak St, Madina', '2021-01-20', 'active');

-- Insert Sample Payments (matches React sample data + more)
INSERT INTO payments (member_id, amount, payment_type, payment_method, payment_date, description) VALUES
-- John Smith payments
(1, 100.00, 'Tithe', 'Cash', '2024-01-07', 'Weekly tithe'),
(1, 50.00, 'Offering', 'Mobile Money', '2024-01-07', 'Sunday offering'),
(1, 80.00, 'Mission', 'Mobile Money', '2024-01-14', 'Mission fund'),
(1, 100.00, 'Tithe', 'Cash', '2024-01-21', 'Weekly tithe'),

-- Mary Johnson payments
(2, 200.00, 'Building Fund', 'Bank Transfer', '2024-01-06', 'Monthly building fund contribution'),
(2, 75.00, 'Tithe', 'Cash', '2024-01-06', 'Weekly tithe'),
(2, 150.00, 'Tithe', 'Bank Transfer', '2024-01-13', 'Monthly tithe'),

-- David Wilson payments
(3, 25.00, 'Dues', 'Mobile Money', '2024-01-05', 'Monthly membership dues'),

-- Sarah Brown payments
(4, 150.00, 'Tithe', 'Bank Transfer', '2024-01-08', 'Monthly tithe'),
(4, 30.00, 'Youth Ministry', 'Cash', '2024-01-15', 'Youth ministry support'),

-- Michael Davis payments
(5, 120.00, 'Tithe', 'Bank Transfer', '2024-01-10', 'Weekly tithe'),
(5, 50.00, 'Special Offering', 'Cash', '2024-01-17', 'Special service offering'),

-- Jennifer Williams payments
(6, 200.00, 'Building Fund', 'Bank Transfer', '2024-01-12', 'Building fund contribution');

-- Update payment counts to match React
-- Total should be 12 visible payments matching the React sample