<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include functions file
require_once 'includes/functions.php';

// Display messages if they exist
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'error';
    
    // Clear the message after retrieving it
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Check if user is already logged in
if (isLoggedIn()) {
    // Redirect to home page
    header('Location: index.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Flavors at Your Fingertips - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <?php include_once 'includes/header.php'; ?>

    <main class="auth-main">
        <div class="auth-container">
            <div class="auth-header">
                <h2>Create an Account</h2>
                <p>Join Local Flavors to start ordering</p>
            </div>
            
            <?php if (isset($message)): ?>
            <div class="alert <?php echo $messageType === 'success' ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <form id="registerForm" class="auth-form" method="POST" action="register_process.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstName">First Name</label>
                        <input type="text" id="firstName" name="firstName" placeholder="Enter first name" required>
                    </div>
                    <div class="form-group">
                        <label for="lastName">Last Name</label>
                        <input type="text" id="lastName" name="lastName" placeholder="Enter last name" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="registerEmail">Email Address</label>
                    <input type="email" id="registerEmail" name="email" placeholder="name@example.com" required>
                </div>
                <div class="form-group">
                    <label for="registerPassword">Password</label>
                    <div class="input-group mb-3">
                        <input type="password" class="form-control" id="registerPassword" name="password" placeholder="••••" required>
                        <button class="btn btn-outline-secondary password-toggle" type="button">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <div class="input-group mb-3">
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="••••" required>
                        <button class="btn btn-outline-secondary password-toggle" type="button">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="(xxx) xxx-xxxx" required>
                </div>
                <div class="form-options">
                    <label class="terms">
                        <input type="checkbox" name="terms" required>
                        <span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></span>
                    </label>
                </div>
                <button type="submit" class="auth-btn">Create Account</button>
            </form>

            <div class="social-login mt-4">
                <div class="text-center mb-3">Or sign up with</div>
                <div class="d-flex justify-content-center gap-3">
                    <button class="btn btn-outline-danger d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-google"></i>
                        <span>Google</span>
                    </button>
                    <button class="btn btn-outline-primary d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-facebook"></i>
                        <span>Facebook</span>
                    </button>
                </div>
            </div>

            <div class="auth-footer mt-4 text-center">
                <p>Already have an account? <a href="login.php" class="text-decoration-none">Sign in</a></p>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p>Email: info@localfavors.com</p>
                <p>Phone: (02) 123-4567</p>
            </div>
            <div class="footer-section">
                <h3>Follow Us</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Hours</h3>
                <p>Monday - Sunday</p>
                <p>8:00 AM - 9:00 PM</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 Local Flavors. All rights reserved.</p>
        </div>
    </footer>

    <script src="js/main.js"></script>
    <script src="js/auth.js"></script>
</body>
</html> 