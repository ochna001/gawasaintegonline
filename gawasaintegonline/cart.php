<?php
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Flavors at Your Fingertips - Cart</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/cart.css">
    <link rel="stylesheet" href="css/cart-sync.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="cart-main">
        <h2>Your Cart</h2>
        
        <div class="cart-container">
            <div class="cart-items">
                <!-- Cart items will be populated by JavaScript -->
            </div>
            
            <div class="cart-summary">
                <h3>Order Summary</h3>
                <div class="summary-item">
                    <span>Subtotal:</span>
                    <span class="subtotal">₱0.00</span>
                </div>
                <div class="summary-item">
                    <span>Delivery Fee:</span>
                    <span>₱50.00</span>
                </div>
                <div class="summary-item total">
                    <span>Total:</span>
                    <span class="total-amount">₱0.00</span>
                </div>
            </div>

            <div class="cart-actions">
                <button class="continue-shopping">Continue Shopping</button>
                <button class="checkout-btn">Proceed to Checkout</button>
            </div>
        </div>

        <div class="empty-cart" style="display: none;">
            <i class="fas fa-shopping-cart"></i>
            <h2>Your cart is empty</h2>
            <p>Looks like you haven't added any items to your cart yet.</p>
            <a href="menu.php" class="cta-button">Browse Menu</a>
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

    <!-- Debug mode script to track cart processing -->
    <script>
    console.log('🛒 Cart debug mode active');
    window.cartDebug = true;
    </script>
    
    <!-- Load scripts with cache-busting -->
    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script src="js/cart.js?v=<?php echo time(); ?>"></script>
    <script src="js/header.js?v=<?php echo time(); ?>"></script>
</body>
</html>
