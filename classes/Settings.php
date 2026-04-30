<?php
/**
 * Settings Class
 * Manages system settings for Church Management System
 */

class Settings {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get a setting value by key
     */
    public function get($key, $default = null) {
        $stmt = $this->pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        return $result ? $result['setting_value'] : $default;
    }
    
    /**
     * Get all settings as an associative array
     */
    public function getAll() {
        $stmt = $this->pdo->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    }
    
    /**
     * Set a setting value
     */
    public function set($key, $value) {
        $stmt = $this->pdo->prepare("
            INSERT INTO settings (setting_key, setting_value) 
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = ?
        ");
        
        return $stmt->execute([$key, $value, $value]);
    }
    
    /**
     * Update multiple settings at once
     */
    public function updateMultiple($settings) {
        $this->pdo->beginTransaction();
        
        try {
            foreach ($settings as $key => $value) {
                $this->set($key, $value);
            }
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
    
    /**
     * Verify admin credentials
     */
    public function verifyAdminCredentials($username, $password) {
        $storedUsername = $this->get('admin_username', 'admin');
        $storedPassword = $this->get('admin_password');
        
        if ($username !== $storedUsername) {
            return false;
        }
        
        return password_verify($password, $storedPassword);
    }
    
    /**
     * Update admin credentials
     */
    public function updateAdminCredentials($username, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $this->set('admin_username', $username);
        $this->set('admin_password', $hashedPassword);
        
        return true;
    }
    
    /**
     * Upload and save logo
     */
    public function uploadLogo($file) {
        // Validate file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid file type. Please upload JPG, PNG, GIF, or WEBP.');
        }
        
        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('File too large. Maximum size is 5MB.');
        }
        
        // Create uploads directory if it doesn't exist
        $uploadDir = __DIR__ . '/../uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'logo_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Delete old logo if exists
            $oldLogo = $this->get('church_logo');
            if ($oldLogo && file_exists(__DIR__ . '/../' . $oldLogo)) {
                unlink(__DIR__ . '/../' . $oldLogo);
            }
            
            // Save new logo path
            $relativePath = 'uploads/' . $filename;
            $this->set('church_logo', $relativePath);
            
            return $relativePath;
        } else {
            throw new Exception('Failed to upload file.');
        }
    }
    
    /**
     * Delete logo
     */
    public function deleteLogo() {
        $logo = $this->get('church_logo');
        
        if ($logo && file_exists(__DIR__ . '/../' . $logo)) {
            unlink(__DIR__ . '/../' . $logo);
        }
        
        $this->set('church_logo', '');
        return true;
    }
}
