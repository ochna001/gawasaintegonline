<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug: Print session variables
echo '<!-- DEBUG INFO: ';
echo 'Session Data: ';
print_r($_SESSION);
echo ' -->';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$firstName = $isLoggedIn ? $_SESSION['first_name'] : '';
$isAdmin = $isLoggedIn && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Debug: Show admin status
echo '<!-- DEBUG: isLoggedIn: ' . ($isLoggedIn ? 'true' : 'false') . ' -->';
echo '<!-- DEBUG: isAdmin: ' . ($isAdmin ? 'true' : 'false') . ' -->';
echo '<!-- DEBUG: user_role: ' . ($_SESSION['user_role'] ?? 'not set') . ' -->';
?>
<?php if ($isAdmin): ?>
<link rel="stylesheet" href="/gawasainteg/css/admin-header.css">
<?php endif; ?>
<header class="header">
    <nav class="nav-container">
        <div class="logo">
            <a href="index.html" style="display: flex; align-items: center;">
                <img src="assets/logo.png" alt="Local Flavors Logo" class="logo-image" style="margin-right: 10px;">
                <h1 style="margin: 0;">LOCAL FLAVORS</h1>
            </a>
        </div>
        <div class="nav-links">
            <a href="index.html" <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' || basename($_SERVER['PHP_SELF']) == 'index.html') ? 'class="active"' : ''; ?>>Home</a>
            <a href="menu.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'menu.php') ? 'class="active"' : ''; ?>>Menu</a>
            
            <?php if ($isLoggedIn): ?>
                <a href="profile.php" class="account-link <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>"><?php echo $firstName; ?></a>
            <?php else: ?>
                <a href="account.html" class="account-link <?php echo (basename($_SERVER['PHP_SELF']) == 'account.html') ? 'active' : ''; ?>">Account</a>
            <?php endif; ?>
            
            <a href="cart.php" class="cart-icon <?php echo (basename($_SERVER['PHP_SELF']) == 'cart.html' || basename($_SERVER['PHP_SELF']) == 'cart.php') ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count" id="cartCount">0</span>
            </a>
            
            <!-- Admin link moved outside auth-links container to prevent it from being hidden by JavaScript -->
            <?php if ($isAdmin): ?>
                <a href="/gawasainteg/admin/index.php" class="admin-link"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a>
            <?php endif; ?>
            
            <?php if ($isLoggedIn): ?>
                <div class="auth-links">
                    <a href="logout.php" class="logout-link">Logout</a>
                </div>
            <?php else: ?>
                <div class="auth-links">
                    <a href="login.php" class="login-link">Login</a>
                    <a href="register.php" class="register-link">Register</a>
                </div>
            <?php endif; ?>
        </div>
        <button class="mobile-menu-btn">
            <i class="fas fa-bars"></i>
        </button>
    </nav>
</header>
