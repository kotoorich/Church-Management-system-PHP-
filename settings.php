<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Settings.php';

// Check authentication
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$settingsClass = new Settings($pdo);
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update church name
        if (isset($_POST['church_name'])) {
            $settingsClass->set('church_name', $_POST['church_name']);
        }
        
        if (isset($_POST['system_name'])) {
            $settingsClass->set('system_name', $_POST['system_name']);
        }
        
        // Handle logo upload
        if (isset($_FILES['church_logo']) && $_FILES['church_logo']['error'] === UPLOAD_ERR_OK) {
            $settingsClass->uploadLogo($_FILES['church_logo']);
        }
        
        // Handle logo deletion
        if (isset($_POST['delete_logo'])) {
            $settingsClass->deleteLogo();
        }
        
        // Update admin credentials
        if (!empty($_POST['new_username']) || !empty($_POST['new_password'])) {
            // Verify current password
            if (empty($_POST['current_password'])) {
                throw new Exception('Current password is required to change credentials.');
            }
            
            $currentUsername = $settingsClass->get('admin_username', 'admin');
            if (!$settingsClass->verifyAdminCredentials($currentUsername, $_POST['current_password'])) {
                throw new Exception('Current password is incorrect.');
            }
            
            // Update username if provided
            $newUsername = !empty($_POST['new_username']) ? $_POST['new_username'] : $currentUsername;
            
            // Update password if provided
            if (!empty($_POST['new_password'])) {
                if ($_POST['new_password'] !== $_POST['confirm_password']) {
                    throw new Exception('New passwords do not match.');
                }
                
                if (strlen($_POST['new_password']) < 6) {
                    throw new Exception('Password must be at least 6 characters long.');
                }
                
                $settingsClass->updateAdminCredentials($newUsername, $_POST['new_password']);
                
                // Update session with new username
                $_SESSION['user']['username'] = $newUsername;
            } else if ($newUsername !== $currentUsername) {
                // Just updating username, keep existing password
                $storedPassword = $settingsClass->get('admin_password');
                $settingsClass->set('admin_username', $newUsername);
                $_SESSION['user']['username'] = $newUsername;
            }
        }
        
        $message = 'Settings updated successfully!';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get current settings
$settings = $settingsClass->getAll();
$churchName = $settings['church_name'] ?? 'Grace Community Church';
$systemName = $settings['system_name'] ?? 'Church Management System';
$churchLogo = $settings['church_logo'] ?? '';
$adminUsername = $settings['admin_username'] ?? 'admin';

$page_title = 'Settings - Church Management System';
include 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/enhanced-components.css">

<div class="page-content space-y-6">
    <div>
        <h2>System Settings</h2>
        <p style="color: var(--muted-foreground);">Manage church information and admin credentials</p>
    </div>

    <?php if ($message): ?>
        <div class="p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'; ?>">
            <div class="flex items-center gap-3">
                <?php if ($messageType === 'success'): ?>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                <?php else: ?>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                <?php endif; ?>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <!-- Church Information Section -->
        <div class="stats-card">
            <div class="mb-6">
                <h3 class="flex items-center gap-2">
                    <span>⛪</span> Church Information
                </h3>
                <p class="text-sm" style="color: var(--muted-foreground);">Update your church details and branding</p>
            </div>

            <div class="grid grid-cols-1 gap-6">
                <!-- Church Logo -->
                <div>
                    <label class="block text-sm mb-3" style="color: var(--muted-foreground); font-weight: 600;">Church Logo</label>
                    
                    <div class="flex flex-col md:flex-row items-start gap-6">
                        <!-- Logo Preview -->
                        <div class="flex flex-col items-center">
                            <?php if ($churchLogo && file_exists($churchLogo)): ?>
                                <div class="relative">
                                    <img 
                                        src="<?php echo htmlspecialchars($churchLogo); ?>" 
                                        alt="Church Logo" 
                                        class="w-32 h-32 object-contain rounded-lg border-2 shadow-md"
                                        style="border-color: var(--border);"
                                    />
                                    <button
                                        type="submit"
                                        name="delete_logo"
                                        value="1"
                                        onclick="return confirm('Are you sure you want to delete the logo?');"
                                        class="absolute -top-2 -right-2 w-8 h-8 rounded-full flex items-center justify-center shadow-lg"
                                        style="background: var(--destructive); color: var(--destructive-foreground);"
                                        title="Delete Logo"
                                    >
                                        ×
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="w-32 h-32 rounded-lg flex items-center justify-center border-2 border-dashed" style="border-color: var(--border); background: var(--muted);">
                                    <span class="text-6xl">⛪</span>
                                </div>
                            <?php endif; ?>
                            <p class="text-xs mt-2" style="color: var(--muted-foreground);">Recommended: 200x200px</p>
                        </div>

                        <!-- Upload Section -->
                        <div class="flex-1">
                            <div class="border-2 border-dashed rounded-lg p-6" style="border-color: var(--border);">
                                <input
                                    type="file"
                                    id="church_logo"
                                    name="church_logo"
                                    accept="image/*"
                                    class="hidden"
                                    onchange="previewLogo(this)"
                                />
                                <label
                                    for="church_logo"
                                    class="flex flex-col items-center cursor-pointer"
                                >
                                    <svg class="w-12 h-12 mb-3" style="color: var(--muted-foreground);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    <span class="text-sm mb-1" style="font-weight: 600;">Click to upload logo</span>
                                    <span class="text-xs" style="color: var(--muted-foreground);">PNG, JPG, GIF or WEBP (max 5MB)</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Church Name -->
                <div>
                    <label for="church_name" class="block text-sm mb-2" style="color: var(--muted-foreground); font-weight: 600;">Church Name</label>
                    <input
                        type="text"
                        id="church_name"
                        name="church_name"
                        value="<?php echo htmlspecialchars($churchName); ?>"
                        required
                        class="w-full px-4 py-3 border-2 rounded-lg transition-all"
                        style="background: var(--input-background); border-color: var(--border); color: var(--foreground);"
                        onfocus="this.style.borderColor='var(--primary)'; this.style.boxShadow='0 0 0 3px rgba(var(--primary-rgb, 3, 2, 19), 0.1)';"
                        onblur="this.style.borderColor='var(--border)'; this.style.boxShadow='none';"
                        placeholder="e.g., Grace Community Church"
                    />
                </div>

                <!-- System Name -->
                <div>
                    <label for="system_name" class="block text-sm mb-2" style="color: var(--muted-foreground); font-weight: 600;">System Name</label>
                    <input
                        type="text"
                        id="system_name"
                        name="system_name"
                        value="<?php echo htmlspecialchars($systemName); ?>"
                        required
                        class="w-full px-4 py-3 border-2 rounded-lg transition-all"
                        style="background: var(--input-background); border-color: var(--border); color: var(--foreground);"
                        onfocus="this.style.borderColor='var(--primary)'; this.style.boxShadow='0 0 0 3px rgba(var(--primary-rgb, 3, 2, 19), 0.1)';"
                        onblur="this.style.borderColor='var(--border)'; this.style.boxShadow='none';"
                        placeholder="e.g., Church Management System"
                    />
                </div>
            </div>
        </div>

        <!-- Admin Credentials Section -->
        <div class="stats-card">
            <div class="mb-6">
                <h3 class="flex items-center gap-2">
                    <span>🔐</span> Admin Credentials
                </h3>
                <p class="text-sm" style="color: var(--muted-foreground);">Change your admin username and password</p>
            </div>

            <div class="p-4 rounded-lg mb-6" style="background: rgba(var(--primary-rgb, 3, 2, 19), 0.05); border: 1px solid rgba(var(--primary-rgb, 3, 2, 19), 0.1);">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 mt-0.5 flex-shrink-0" style="color: var(--primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="text-sm" style="font-weight: 600;">Current Username: <span style="color: var(--primary);"><?php echo htmlspecialchars($adminUsername); ?></span></p>
                        <p class="text-xs mt-1" style="color: var(--muted-foreground);">Enter your current password to make changes</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Current Password -->
                <div class="md:col-span-2">
                    <label for="current_password" class="block text-sm mb-2" style="color: var(--muted-foreground); font-weight: 600;">Current Password *</label>
                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        class="w-full px-4 py-3 border-2 rounded-lg transition-all"
                        style="background: var(--input-background); border-color: var(--border); color: var(--foreground);"
                        onfocus="this.style.borderColor='var(--primary)'; this.style.boxShadow='0 0 0 3px rgba(var(--primary-rgb, 3, 2, 19), 0.1)';"
                        onblur="this.style.borderColor='var(--border)'; this.style.boxShadow='none';"
                        placeholder="Enter current password to make changes"
                    />
                    <p class="text-xs mt-1" style="color: var(--muted-foreground);">Required only if changing username or password</p>
                </div>

                <!-- New Username -->
                <div>
                    <label for="new_username" class="block text-sm mb-2" style="color: var(--muted-foreground); font-weight: 600;">New Username (Optional)</label>
                    <input
                        type="text"
                        id="new_username"
                        name="new_username"
                        class="w-full px-4 py-3 border-2 rounded-lg transition-all"
                        style="background: var(--input-background); border-color: var(--border); color: var(--foreground);"
                        onfocus="this.style.borderColor='var(--primary)'; this.style.boxShadow='0 0 0 3px rgba(var(--primary-rgb, 3, 2, 19), 0.1)';"
                        onblur="this.style.borderColor='var(--border)'; this.style.boxShadow='none';"
                        placeholder="Leave blank to keep current"
                    />
                </div>

                <!-- New Password -->
                <div>
                    <label for="new_password" class="block text-sm mb-2" style="color: var(--muted-foreground); font-weight: 600;">New Password (Optional)</label>
                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        class="w-full px-4 py-3 border-2 rounded-lg transition-all"
                        style="background: var(--input-background); border-color: var(--border); color: var(--foreground);"
                        onfocus="this.style.borderColor='var(--primary)'; this.style.boxShadow='0 0 0 3px rgba(var(--primary-rgb, 3, 2, 19), 0.1)';"
                        onblur="this.style.borderColor='var(--border)'; this.style.boxShadow='none';"
                        placeholder="Leave blank to keep current"
                        minlength="6"
                    />
                </div>

                <!-- Confirm Password -->
                <div class="md:col-span-2">
                    <label for="confirm_password" class="block text-sm mb-2" style="color: var(--muted-foreground); font-weight: 600;">Confirm New Password</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="w-full px-4 py-3 border-2 rounded-lg transition-all"
                        style="background: var(--input-background); border-color: var(--border); color: var(--foreground);"
                        onfocus="this.style.borderColor='var(--primary)'; this.style.boxShadow='0 0 0 3px rgba(var(--primary-rgb, 3, 2, 19), 0.1)';"
                        onblur="this.style.borderColor='var(--border)'; this.style.boxShadow='none';"
                        placeholder="Confirm new password"
                    />
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex items-center gap-4">
            <button
                type="submit"
                class="btn-animated px-8 py-3 rounded-lg inline-flex items-center gap-3"
                style="background: var(--primary); color: var(--primary-foreground); font-weight: 600; border: none; cursor: pointer;"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Save Changes</span>
            </button>
            
            <button
                type="reset"
                class="px-8 py-3 rounded-lg border-2 transition-all"
                style="border-color: var(--border); color: var(--foreground);"
                onmouseover="this.style.background='var(--accent)';"
                onmouseout="this.style.background='transparent';"
            >
                Reset
            </button>
        </div>
    </form>
</div>

<script>
function previewLogo(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // You can add preview functionality here if needed
            console.log('Logo selected:', input.files[0].name);
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Password confirmation validation
document.querySelector('form').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword && newPassword !== confirmPassword) {
        e.preventDefault();
        alert('New passwords do not match!');
        return false;
    }
    
    if (newPassword && newPassword.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        return false;
    }
});
</script>

<?php include 'includes/footer.php'; ?>
