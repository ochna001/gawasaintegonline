<?php
require_once 'includes/functions.php';

// Set header to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get cart items
$cart_items = getCartItems($user_id);

// Return cart items
echo json_encode(['success' => true, 'cart_items' => $cart_items]);
?>
