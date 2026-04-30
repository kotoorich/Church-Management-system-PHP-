<?php
// Get current page for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- Sidebar Navigation -->
<aside class="w-64 bg-white shadow-sm border-r border-gray-200 flex flex-col">
    <nav class="p-4 space-y-2 flex-1">
        <!-- Dashboard -->
        <a href="<?php echo SITE_URL; ?>/dashboard.php" 
           class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
            <span class="mr-3">📊</span>
            Dashboard
        </a>
        
        <!-- Members -->
        <a href="<?php echo SITE_URL; ?>/members.php" 
           class="nav-link <?php echo $current_page === 'members' || $current_page === 'view_member' || $current_page === 'add_member' ? 'active' : ''; ?>">
            <span class="mr-3">👥</span>
            Members
        </a>
        
        <!-- Payments -->
        <a href="<?php echo SITE_URL; ?>/payments.php" 
           class="nav-link <?php echo $current_page === 'payments' || $current_page === 'add_payment' ? 'active' : ''; ?>">
            <span class="mr-3">💰</span>
            Payments
        </a>
        
        <!-- Monthly Tracker -->
        <a href="<?php echo SITE_URL; ?>/monthly_tracker.php" 
           class="nav-link <?php echo $current_page === 'monthly_tracker' ? 'active' : ''; ?>">
            <span class="mr-3">📅</span>
            Monthly Tracker
        </a>
        
        <!-- Settings (future feature) -->
        <a href="#" class="nav-link opacity-50 cursor-not-allowed">
            <span class="mr-3">⚙️</span>
            Settings
            <span class="ml-auto text-xs bg-gray-200 text-gray-600 px-2 py-1 rounded">Soon</span>
        </a>
    </nav>
    
    <!-- Bottom section -->
    <div class="p-4 border-t border-gray-200">
        <a href="<?php echo SITE_URL; ?>/logout.php" 
           onclick="return confirm('Are you sure you want to logout?')"
           class="w-full flex items-center px-3 py-2 rounded-lg transition-colors text-red-600 hover:bg-red-50">
            <span class="mr-3">🚪</span>
            Logout
        </a>
    </div>
</aside>