<?php
require_once 'includes/functions.php';

// Set header to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Check if we have a last order ID in session
if (isset($_SESSION['last_order_id'])) {
    $order_id = $_SESSION['last_order_id'];
    
    // Get order details
    $order = getOrderDetails($order_id, $user_id);
    
    if ($order) {
        // Success
        echo json_encode(['success' => true, 'order' => $order]);
        
        // Remove from session after retrieving once
        unset($_SESSION['last_order_id']);
        unset($_SESSION['last_order_number']);
        exit;
    }
}

// If we don't have a last order ID or couldn't find the order,
// try to get the most recent order for the user
$orders = getUserOrders($user_id);

if (!empty($orders)) {
    $last_order = $orders[0]; // First order is the most recent
    $order = getOrderDetails($last_order['order_id'], $user_id);
    
    if ($order) {
        echo json_encode(['success' => true, 'order' => $order]);
        exit;
    }
}

// No orders found
echo json_encode(['success' => false, 'message' => 'No orders found']);
?>
