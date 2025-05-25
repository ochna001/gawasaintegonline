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
$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
$user_id = $_SESSION['user_id'];

// Validate data
if ($item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit;
}

// Remove item from cart
$result = removeFromCart($user_id, $item_id);

if ($result) {
    // Return updated cart items
    $cart_items = getCartItems($user_id);
    echo json_encode(['success' => true, 'message' => 'Item removed from cart', 'cart_items' => $cart_items]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove item from cart']);
}
?>
