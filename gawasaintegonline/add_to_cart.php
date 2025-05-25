<?php
require_once 'includes/functions.php';

// Set header to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get request data
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
$price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
$user_id = $_SESSION['user_id'];

// Validate data
if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit;
}

if ($price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid price']);
    exit;
}

// Add item to cart
$result = addToCart($user_id, $product_id, $quantity, $price);

if ($result) {
    // Return updated cart items
    $cart_items = getCartItems($user_id);
    echo json_encode(['success' => true, 'message' => 'Item added to cart', 'cart_items' => $cart_items]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add item to cart']);
}
?>
