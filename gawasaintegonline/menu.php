<?php
// Include functions file
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get all categories
$query = "SELECT * FROM categories ORDER BY name";
$result = $conn->query($query);
$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Function to get products by category
function getProductsByCategory($category_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM products WHERE category_id = ? AND available = 1");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Flavors at Your Fingertips - Menu</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/menu.css">
    <link rel="stylesheet" href="css/cart-sync.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include_once 'includes/header.php'; ?>

    <main class="menu-main">
        <section class="menu-header">
            <h2>Our Menu</h2>
            <div class="menu-filters">
                <input type="text" id="search" placeholder="Search dishes...">
                <div class="category-filters">
                    <button class="filter-btn active" data-category="all">All</button>
                    <?php foreach ($categories as $category): ?>
                    <button class="filter-btn" data-category="<?php echo $category['name']; ?>"><?php echo ucfirst($category['name']); ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="menu-grid">
            <?php foreach ($categories as $category): ?>
            <!-- <?php echo ucfirst($category['name']); ?> Section -->
            <div class="menu-category" data-category="<?php echo $category['name']; ?>">
                <h2><?php echo ucfirst($category['name']); ?></h2>
                <div class="dish-grid">
                    <?php 
                    $products = getProductsByCategory($category['category_id']);
                    foreach ($products as $product): 
                    ?>
                    <div class="dish-card">
                        <img src="<?php echo $product['image_path']; ?>" alt="<?php echo $product['name']; ?>" class="dish-image">
                        <h3><?php echo $product['name']; ?></h3>
                        <p><?php echo $product['description']; ?></p>
                        <span class="price">₱<?php echo number_format($product['price'], 2); ?></span>
                        <button class="add-to-cart" data-id="<?php echo $product['product_id']; ?>" data-name="<?php echo $product['name']; ?>" data-price="<?php echo $product['price']; ?>" data-image="<?php echo $product['image_path']; ?>">Add to Cart</button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </section>
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

    <!-- Debug script to help identify the issue -->
    <script>
    console.log('🔎 DEBUG: Scripts loading in menu.php');
    window.debugCartAddition = true; // Flag to enable debug messages
    
    // Add click tracking to monitor all click handlers
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🔎 Adding click monitoring to add-to-cart buttons');
        const buttons = document.querySelectorAll('.add-to-cart');
        buttons.forEach((btn, i) => {
            btn.setAttribute('data-debug-id', `button-${i}`);
            btn.addEventListener('click', function(e) {
                console.log(`🔎 Button ${this.getAttribute('data-debug-id')} clicked`);
                // Log the current cart before any handlers run
                const currentCart = JSON.parse(localStorage.getItem('cart')) || [];
                console.log('🔎 Cart BEFORE any handlers:', JSON.stringify(currentCart));
                
                // Use setTimeout to check cart after all handlers complete
                setTimeout(() => {
                    const updatedCart = JSON.parse(localStorage.getItem('cart')) || [];
                    console.log('🔎 Cart AFTER all handlers:', JSON.stringify(updatedCart));
                    console.log(`🔎 ITEMS ADDED: ${updatedCart.length - currentCart.length}`);
                }, 100);
            }, {capture: true}); // Use capture to ensure this runs before other handlers
        });
    });
    </script>
    
    <!-- Load debug script first to fix cart functionality -->
    <script src="js/menu-debug.js?v=<?php echo time(); ?>"></script>
    
    <!-- Load the same scripts as menu.html which works correctly, with cache-busting -->
    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script src="js/menu.js?v=<?php echo time(); ?>"></script>
    <script src="js/header.js?v=<?php echo time(); ?>"></script>
</body>
</html>
