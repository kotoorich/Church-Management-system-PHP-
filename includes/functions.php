```php
<?php
// Utility functions for the church management system

// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit();
    }
}

function login($username, $password, $pdo) {
    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

// Theme functions
function getCurrentTheme($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'theme'");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : 'light';
    } catch (Exception $e) {
        return 'light';
    }
}

function setTheme($theme, $pdo) {
    try {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'theme'");
        $stmt->execute([$theme]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// File upload functions
function uploadImage($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    $upload_dir = UPLOAD_DIR;
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filepath;
    }
    
    return false;
}

// Formatting functions
function formatCurrency($amount) {
    return '₵' . number_format($amount, 0);
}

function formatDate($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

function formatDateWithDay($date) {
    return date('l', strtotime($date));
}

// Member functions
function getMemberById($id, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getMemberPayments($member_id, $pdo) {
    $stmt = $pdo->prepare("
        SELECT p.*, m.name as member_name, m.email as member_email 
        FROM payments p 
        JOIN members m ON p.member_id = m.id 
        WHERE p.member_id = ? 
        ORDER BY p.payment_date DESC
    ");
    $stmt->execute([$member_id]);
    return $stmt->fetchAll();
}

function getTotalDonations($member_id, $pdo) {
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE member_id = ?");
    $stmt->execute([$member_id]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

// Payment functions
function getPaymentById($payment_id, $pdo) {
    $stmt = $pdo->prepare("
        SELECT p.*, m.name as member_name, m.email as member_email 
        FROM payments p 
        JOIN members m ON p.member_id = m.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$payment_id]);
    return $stmt->fetch();
}

// Dashboard statistics
function getDashboardStats($pdo) {
    // Total members
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM members");
    $total_members = $stmt->fetch()['total'];
    
    // Total donations
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM payments");
    $total_donations = $stmt->fetch()['total'] ?? 0;
    
    // This month payments count
    $stmt = $pdo->query("
        SELECT COUNT(*) as total 
        FROM payments 
        WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) 
        AND YEAR(payment_date) = YEAR(CURRENT_DATE())
    ");
    $this_month_payments = $stmt->fetch()['total'];
    
    // Average donation
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM payments");
    $payment_count = $stmt->fetch()['count'];
    $average_donation = $payment_count > 0 ? $total_donations / $payment_count : 0;
    
    return [
        'total_members' => $total_members,
        'total_donations' => $total_donations,
        'this_month_payments' => $this_month_payments,
        'average_donation' => $average_donation
    ];
}

// Pagination helper
function getPaginationData($total_items, $current_page, $items_per_page) {
    $total_pages = ceil($total_items / $items_per_page);
    $offset = ($current_page - 1) * $items_per_page;
    
    return [
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => $offset,
        'items_per_page' => $items_per_page,
        'total_items' => $total_items
    ];
}

// Search and filter helpers
function buildMemberSearchQuery($search, $status_filter) {
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    if ($status_filter !== 'all') {
        $where_conditions[] = "status = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
    
    return ['where' => $where_clause, 'params' => $params];
}

function buildPaymentSearchQuery($search = '', $type_filter = 'all') {
    $where_parts = [];
    $params = [];
    
    if (!empty($search)) {
        $where_parts[] = "(m.name LIKE ? OR p.type LIKE ? OR p.description LIKE ? OR p.amount LIKE ?)";
        $search_param = '%' . $search . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    if ($type_filter !== 'all') {
        $where_parts[] = "p.type = ?";
        $params[] = $type_filter;
    }
    
    $where_clause = '';
    if (!empty($where_parts)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_parts);
    }
    
    return [
        'where' => $where_clause,
        'params' => $params
    ];
}

// Sanitization helpers
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^[\+\-\s\(\)\d]+$/', $phone);
}

// Member status badge
function getMemberStatusBadge($status) {
    if ($status === 'active') {
        return '<span class="px-3 py-1 text-xs rounded-full font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">active</span>';
    } else {
        return '<span class="px-3 py-1 text-xs rounded-full font-medium bg-muted text-muted-foreground">inactive</span>';
    }
}

// Payment method with icon
function getPaymentMethodWithIcon($method) {
    $icons = [
        'Cash' => '💵 Cash',
        'Mobile Money' => '📱 Mobile Money', 
        'Bank Transfer' => '🏦 Bank Transfer',
        'Check' => '📄 Check',
        'Card' => '💳 Card',
        'Other' => '🔄 Other'
    ];
    
    return $icons[$method] ?? $method;
}

// Generate member initials for avatar
function getMemberInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
    }
    return substr($initials, 0, 2);
}

// Add missing payment constants and functions
if (!defined('PAYMENTS_PER_PAGE')) {
    define('PAYMENTS_PER_PAGE', 8);
}
```