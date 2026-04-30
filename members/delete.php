<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireLogin();

$member_id = intval($_GET['id'] ?? 0);
if (!$member_id) {
    header('Location: index.php');
    exit();
}

// Get member data
$member = getMemberById($member_id, $pdo);
if (!$member) {
    header('Location: index.php');
    exit();
}

try {
    // Delete member's image file if it exists
    if ($member['image_url'] && file_exists('../' . $member['image_url'])) {
        unlink('../' . $member['image_url']);
    }
    
    // Delete member (payments will be deleted automatically due to foreign key constraint)
    $stmt = $pdo->prepare("DELETE FROM members WHERE id = ?");
    $stmt->execute([$member_id]);
    
    $_SESSION['success_message'] = 'Member deleted successfully!';
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Failed to delete member. Please try again.';
}

header('Location: index.php');
exit();
?>