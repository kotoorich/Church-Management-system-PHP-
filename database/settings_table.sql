-- Settings Table for Church Management System
-- Run this SQL to add settings functionality

CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES
('church_name', 'Grace Community Church'),
('church_logo', ''),
('admin_username', 'admin'),
('admin_password', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'), -- password: 'password'
('system_name', 'Church Management System')
ON DUPLICATE KEY UPDATE setting_key=setting_key;
