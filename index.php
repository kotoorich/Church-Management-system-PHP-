<?php
/**
 * Login Page - Dynamic Authentication
 */
session_start();
require_once 'config/database.php';
require_once 'classes/Settings.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit();
}

$settingsClass = new Settings($pdo);

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Verify credentials against database
    if ($settingsClass->verifyAdminCredentials($username, $password)) {
        $_SESSION['user'] = ['username' => $username];
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Invalid username or password';
    }
}

// Get church information for display
$churchName = $settingsClass->get('church_name', 'Grace Community Church');
$systemName = $settingsClass->get('system_name', 'Church Management System');
$churchLogo = $settingsClass->get('church_logo', '');
$adminUsername = $settingsClass->get('admin_username', 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Church Management System</title>
    <link rel="stylesheet" href="assets/css/tailwind-offline.css">
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="assets/css/themes.css">
</head>
<body>
    <div class="min-h-screen bg-gradient-to-br from-primary/5 via-background to-primary/10 flex items-center justify-center p-4" style="background: linear-gradient(to bottom right, color-mix(in srgb, var(--primary) 5%, transparent), var(--background), color-mix(in srgb, var(--primary) 10%, transparent));">
        <div class="w-full max-w-md">
            <div class="bg-card rounded-2xl border shadow-2xl overflow-hidden backdrop-blur-sm" style="background: var(--card); border-color: var(--border);">
                <!-- Header -->
                <div class="p-8 text-center" style="background: linear-gradient(to right, var(--primary), color-mix(in srgb, var(--primary) 80%, transparent));">
                    <div class="w-20 h-20 rounded-full mx-auto mb-4 flex items-center justify-center" style="background: rgba(255, 255, 255, 0.2);">
                        <?php if ($churchLogo && file_exists($churchLogo)): ?>
                            <img src="<?php echo htmlspecialchars($churchLogo); ?>" alt="Church Logo" class="w-16 h-16 object-contain rounded-full" />
                        <?php else: ?>
                            <span class="text-3xl">⛪</span>
                        <?php endif; ?>
                    </div>
                    <h1 class="text-2xl mb-2" style="color: var(--primary-foreground);">
                        <?php echo htmlspecialchars($churchName); ?>
                    </h1>
                    <p style="color: color-mix(in srgb, var(--primary-foreground) 80%, transparent);">
                        <?php echo htmlspecialchars($systemName); ?>
                    </p>
                </div>

                <!-- Login Form -->
                <div class="p-8">
                    <div class="text-center mb-6">
                        <h2 class="text-xl mb-2">Welcome Back</h2>
                        <p style="color: var(--muted-foreground);">Please sign in to continue</p>
                    </div>

                    <form method="POST" class="space-y-6">
                        <div class="space-y-1">
                            <label for="username" class="block text-sm mb-2" style="color: var(--muted-foreground); font-weight: var(--font-weight-medium);">
                                Username
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5" style="color: var(--muted-foreground);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <input
                                    id="username"
                                    name="username"
                                    type="text"
                                    required
                                    class="w-full pl-10 pr-4 py-3 rounded-lg transition-all duration-200"
                                    style="background: var(--input-background); border: 1px solid var(--border); color: var(--foreground);"
                                    placeholder="Enter your username"
                                    onfocus="this.style.borderColor='var(--primary)'; this.style.boxShadow='0 0 0 2px color-mix(in srgb, var(--primary) 20%, transparent)';"
                                    onblur="this.style.borderColor='var(--border)'; this.style.boxShadow='none';"
                                />
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label for="password" class="block text-sm mb-2" style="color: var(--muted-foreground); font-weight: var(--font-weight-medium);">
                                Password
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5" style="color: var(--muted-foreground);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    required
                                    class="w-full pl-10 pr-4 py-3 rounded-lg transition-all duration-200"
                                    style="background: var(--input-background); border: 1px solid var(--border); color: var(--foreground);"
                                    placeholder="Enter your password"
                                    onfocus="this.style.borderColor='var(--primary)'; this.style.boxShadow='0 0 0 2px color-mix(in srgb, var(--primary) 20%, transparent)';"
                                    onblur="this.style.borderColor='var(--border)'; this.style.boxShadow='none';"
                                />
                            </div>
                        </div>

                        <?php if ($error): ?>
                            <div class="p-4 rounded-lg text-sm flex items-center gap-3" style="background: color-mix(in srgb, var(--destructive) 10%, transparent); border: 1px solid color-mix(in srgb, var(--destructive) 20%, transparent); color: var(--destructive);">
                                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        <?php endif; ?>

                        <button
                            type="submit"
                            class="group relative w-full py-3.5 rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 active:translate-y-0 focus:outline-none"
                            style="background: linear-gradient(to right, var(--primary), color-mix(in srgb, var(--primary) 80%, transparent)); color: var(--primary-foreground); font-weight: var(--font-weight-medium);"
                            onmouseover="this.style.background='linear-gradient(to right, color-mix(in srgb, var(--primary) 90%, transparent), color-mix(in srgb, var(--primary) 70%, transparent))';"
                            onmouseout="this.style.background='linear-gradient(to right, var(--primary), color-mix(in srgb, var(--primary) 80%, transparent))';"
                        >
                            <div class="flex items-center justify-center gap-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                </svg>
                                <span>Sign In</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </button>
                    </form>

                    <div class="mt-8 p-4 rounded-lg border" style="background: color-mix(in srgb, var(--muted) 30%, transparent); border-color: var(--border);">
                        <div class="text-center">
                            <p class="text-sm mb-3" style="color: var(--muted-foreground); font-weight: var(--font-weight-medium);">Admin Login</p>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div class="rounded-lg p-3 border" style="background: color-mix(in srgb, var(--background) 50%, transparent); border-color: var(--border);">
                                    <p style="color: var(--muted-foreground);">Username</p>
                                    <p class="font-mono" style="font-weight: var(--font-weight-medium); color: var(--foreground);"><?php echo htmlspecialchars($adminUsername); ?></p>
                                </div>
                                <div class="rounded-lg p-3 border" style="background: color-mix(in srgb, var(--background) 50%, transparent); border-color: var(--border);">
                                    <p style="color: var(--muted-foreground);">Password</p>
                                    <p class="font-mono" style="font-weight: var(--font-weight-medium); color: var(--foreground);">••••••••</p>
                                </div>
                            </div>
                            <p class="text-xs mt-3" style="color: var(--muted-foreground);">Default password: <strong>password</strong></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Theme Switcher -->
            <div class="mt-6 flex justify-center">
                <?php include 'includes/theme-switcher.php'; ?>
            </div>
        </div>
    </div>

    <script src="includes/theme-script.js"></script>
</body>
</html>