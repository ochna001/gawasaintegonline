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
if (!isset($_POST['order_id']) || !is_numeric($_POST['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

$order_id = (int) $_POST['order_id'];
$reason = isset($_POST['reason']) ? sanitizeInput($_POST['reason']) : 'Customer requested cancellation';

// Get order details to verify it's cancellable
$order = getOrderDetails($order_id, $user_id);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found or not authorized to cancel']);
    exit;
}

// Check if order can be cancelled (only pending or processing orders)
if ($order['status'] !== 'pending' && $order['status'] !== 'processing') {
    echo json_encode(['success' => false, 'message' => 'This order cannot be cancelled because it is already ' . $order['status']]);
    exit;
}

// Cancel the order
$result = cancelOrder($order_id, $user_id, $reason);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel order. Please try again later.']);
}
?>
