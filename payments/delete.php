<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireLogin();

$payment_id = intval($_GET['id'] ?? 0);
if (!$payment_id) {
    header('Location: index.php');
    exit();
}

// Get payment data to verify it exists
$payment = getPaymentById($payment_id, $pdo);
if (!$payment) {
    header('Location: index.php');
    exit();
}

try {
    // Delete payment
    $stmt = $pdo->prepare("DELETE FROM payments WHERE id = ?");
    $stmt->execute([$payment_id]);
    
    $_SESSION['success_message'] = 'Payment deleted successfully!';
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Failed to delete payment. Please try again.';
}

// Determine where to redirect
$return_context = $_GET['return'] ?? '';
if ($return_context === 'member' && isset($_GET['member_id'])) {
    header('Location: ../members/view.php?id=' . intval($_GET['member_id']) . '&tab=payments');
} elseif ($return_context === 'tracker' && isset($_GET['member_id']) && isset($_GET['month'])) {
    header('Location: ../monthly-tracker/index.php?member_id=' . intval($_GET['member_id']) . '&month=' . urlencode($_GET['month']));
} else {
    header('Location: index.php');
}
exit();
?>