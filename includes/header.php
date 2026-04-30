<?php
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

// Get church settings for display
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Settings.php';
$settingsClass = new Settings($pdo);
$churchName = $settingsClass->get('church_name', 'Grace Community Church');
$systemName = $settingsClass->get('system_name', 'Church Management System');
$churchLogo = $settingsClass->get('church_logo', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Church Management System'; ?></title>
    <link rel="stylesheet" href="assets/css/tailwind-offline.css">
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="assets/css/themes.css">
</head>
<body class="min-h-screen" style="background: var(--background);">
    
    <!-- Header - Matches React exactly -->
    <header class="shadow-sm" style="background: var(--card); border-bottom: 1px solid var(--border);">
        <div class="px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <?php if ($churchLogo && file_exists($churchLogo)): ?>
                    <img src="<?php echo htmlspecialchars($churchLogo); ?>" alt="Church Logo" class="w-12 h-12 object-contain" />
                <?php endif; ?>
                <div>
                    <h1><?php echo htmlspecialchars($churchName); ?></h1>
                    <p style="color: var(--muted-foreground);"><?php echo htmlspecialchars($systemName); ?></p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right text-sm">
                    <p style="font-weight: var(--font-weight-medium);">Welcome, <?php echo htmlspecialchars($_SESSION['user']['username']); ?></p>
                    <p style="color: var(--muted-foreground);">Church Administrator</p>
                </div>
                
                <!-- Theme Switcher -->
                <?php include __DIR__ . '/theme-switcher.php'; ?>
                
                <!-- Logout Button -->
                <a href="logout.php" class="flex items-center gap-2 px-3 py-2 rounded-lg transition-colors" style="color: var(--destructive);" onmouseover="this.style.background='color-mix(in srgb, var(--destructive) 10%, transparent)';" onmouseout="this.style.background='transparent';">
                    <span>🚪</span>
                    <span class="hidden sm:inline">Logout</span>
                </a>
            </div>
        </div>
    </header>

    <div class="flex">
        <!-- Sidebar - Matches React exactly -->
        <aside class="w-64 shadow-sm border-r flex flex-col" style="background: var(--card); border-color: var(--border); height: calc(100vh - 80px);">
            <nav class="p-4 space-y-2 flex-1">
                <a href="dashboard.php" class="nav-item w-full flex items-center px-3 py-2 rounded-lg transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" style="color: var(--foreground);">
                    <span class="mr-3">📊</span>
                    Dashboard
                </a>
                <a href="members.php" class="nav-item w-full flex items-center px-3 py-2 rounded-lg transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'members.php' || basename($_SERVER['PHP_SELF']) == 'add_member.php' || basename($_SERVER['PHP_SELF']) == 'view_member.php' ? 'active' : ''; ?>" style="color: var(--foreground);">
                    <span class="mr-3">👥</span>
                    Members
                </a>
                <a href="payments.php" class="nav-item w-full flex items-center px-3 py-2 rounded-lg transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active' : ''; ?>" style="color: var(--foreground);">
                    <span class="mr-3">💰</span>
                    Payments
                </a>
                <a href="monthly_tracker.php" class="nav-item w-full flex items-center px-3 py-2 rounded-lg transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'monthly_tracker.php' ? 'active' : ''; ?>" style="color: var(--foreground);">
                    <span class="mr-3">📅</span>
                    Monthly Tracker
                </a>
                <a href="settings.php" class="nav-item w-full flex items-center px-3 py-2 rounded-lg transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" style="color: var(--foreground);">
                    <span class="mr-3">⚙️</span>
                    Settings
                </a>
            </nav>
        </aside>
        
        <!-- Main Content Area -->
        <main class="flex-1 p-6" style="background: var(--background);">

<style>
.nav-item:hover {
    background: var(--accent);
}

.nav-item.active {
    background: var(--primary);
    color: var(--primary-foreground);
}
</style>

<script src="includes/theme-script.js"></script>