<?php
// Logout functionality
session_start();

require_once 'config/database.php';
require_once 'classes/Auth.php';

$auth = new Auth($pdo);
$auth->logout();

// Redirect to login page
header('Location: index.php?message=' . urlencode('Successfully logged out'));
exit();
?>