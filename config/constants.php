<?php
// Site configuration constants
define('SITE_NAME', 'Grace Community Church');
define('SITE_SUBTITLE', 'Church Management System');
define('SITE_URL', 'http://localhost/church_management');

// File upload settings
define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/church_management/assets/images/members/');
define('UPLOAD_URL', SITE_URL . '/assets/images/members/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Pagination settings
define('MEMBERS_PER_PAGE', 6);
define('PAYMENTS_PER_PAGE', 8);

// Default theme
define('DEFAULT_THEME', 'light');

// Available themes
$themes = [
    'light' => ['name' => 'Light', 'icon' => '☀️'],
    'dark' => ['name' => 'Dark', 'icon' => '🌙'],
    'blue' => ['name' => 'Ocean Blue', 'icon' => '🌊'],
    'purple' => ['name' => 'Royal Purple', 'icon' => '💜'],
    'green' => ['name' => 'Forest Green', 'icon' => '🌿'],
    'orange' => ['name' => 'Sunset Orange', 'icon' => '🧡']
];

// Payment types
$payment_types = [
    'Tithe', 'Offering', 'Dues', 'Building Fund', 'Mission', 
    'Special Offering', 'Youth Ministry', 'Music Ministry', 'Other'
];

// Payment methods with icons
$payment_methods = [
    'Cash' => '💵',
    'Mobile Money' => '📱',
    'Bank Transfer' => '🏦',
    'Check' => '📄',
    'Card' => '💳',
    'Other' => '🔄'
];

// Common professions for autocomplete
$professions = [
    'Teacher', 'Engineer', 'Doctor', 'Nurse', 'Lawyer', 'Accountant', 'Pastor', 'Business Owner',
    'Farmer', 'Banker', 'Police Officer', 'Carpenter', 'Electrician', 'Chef', 'Driver',
    'Student', 'Retired', 'Civil Servant', 'Trader', 'Mechanic', 'Tailor', 'Hair Dresser',
    'Pharmacist', 'Architect', 'Software Developer', 'Marketing Manager', 'Sales Representative'
];
?>