<?php
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['theme'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$theme = $input['theme'];

// Validate theme
$valid_themes = ['light', 'dark', 'blue', 'purple', 'green', 'orange'];

if (!in_array($theme, $valid_themes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid theme']);
    exit();
}

// Save theme to session
$_SESSION['theme'] = $theme;

// Optionally save to database if you want persistence
try {
    require_once '../config/database.php';
    
    // Update or insert theme setting
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('theme', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$theme, $theme]);
    
    echo json_encode(['success' => true, 'theme' => $theme, 'message' => 'Theme updated successfully']);
} catch (Exception $e) {
    // Even if database update fails, session is updated
    echo json_encode(['success' => true, 'theme' => $theme, 'message' => 'Theme updated (session only)']);
}
?>