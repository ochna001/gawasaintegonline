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

// Check if order ID is provided
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

$order_id = (int) $_GET['order_id'];

// Get order details for the specified user
// This ensures users can only view their own orders
$order = getOrderDetails($order_id, $user_id);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found or not authorized to view']);
    exit;
}

// Return order details
echo json_encode(['success' => true, 'order' => $order]);
?>
