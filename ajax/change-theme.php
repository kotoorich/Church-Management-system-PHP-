<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $theme = $input['theme'] ?? '';
    
    if (isset($themes[$theme])) {
        $success = setTheme($theme, $pdo);
        echo json_encode(['success' => $success]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid theme']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>