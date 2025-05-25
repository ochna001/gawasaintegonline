<?php
/**
 * Force Payment Update Script
 * 
 * This script manually updates a specific order to be marked as paid
 */

// Include necessary files
require_once 'includes/functions.php';
require_once 'includes/paymongo/api.php';
require_once 'includes/paymongo/order_handler.php';

// Log the start of script
paymongo_log("Starting force payment update script");

// Get the order ID from URL or use the most recent order
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;

global $conn;

// If no order ID provided, get the most recent order
if (!$orderId) {
    $query = "SELECT order_id FROM orders ORDER BY order_id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $order = mysqli_fetch_assoc($result);
        $orderId = $order['order_id'];
    } else {
        echo "No orders found in the database";
        exit;
    }
}

echo "<h1>Force Payment Update for Order #$orderId</h1>";

// Get the current order details
$query = "SELECT * FROM orders WHERE order_id = $orderId";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    echo "<div style='color: red;'>Order #$orderId not found</div>";
    exit;
}

$order = mysqli_fetch_assoc($result);
$sessionId = $order['paymongo_session_id'];

echo "<h2>Current Order Status</h2>";
echo "<ul>";
echo "<li>Status: " . $order['status'] . "</li>";
echo "<li>Payment Status: " . $order['payment_status'] . "</li>";
echo "<li>PayMongo Session ID: " . ($sessionId ?: "Not set") . "</li>";
echo "</ul>";

// Check if already paid
if ($order['payment_status'] === 'paid') {
    echo "<div style='color: green;'>Order is already marked as paid. No update needed.</div>";
    exit;
}

// Try to update using the normal function if we have a session ID
if ($sessionId) {
    echo "<h2>Trying to update via updateOrderFromPayMongo function</h2>";
    $result = updateOrderFromPayMongo($sessionId);
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    // Check if the update was successful
    $query = "SELECT payment_status FROM orders WHERE order_id = $orderId";
    $checkResult = mysqli_query($conn, $query);
    $updatedOrder = mysqli_fetch_assoc($checkResult);
    
    if ($updatedOrder['payment_status'] === 'paid') {
        echo "<div style='color: green;'>Successfully updated order status to paid using updateOrderFromPayMongo</div>";
        
        // Show recent logs
        echo "<h2>Recent PayMongo Logs</h2>";
        $logFile = 'paymongo_debug.log';
        if (file_exists($logFile)) {
            $logs = file_get_contents($logFile);
            $logLines = array_slice(explode("\n", $logs), -30); // Get last 30 lines
            echo "<pre>" . implode("\n", $logLines) . "</pre>";
        }
        
        exit;
    } else {
        echo "<div style='color: orange;'>Update via updateOrderFromPayMongo failed. Trying direct database update...</div>";
    }
}

// If the normal update fails or we don't have a session ID, try direct database update
echo "<h2>Forcing payment update via direct database update</h2>";

// Force update the order status to paid
$updateQuery = "UPDATE orders SET 
                status = 'processing',
                payment_status = 'paid'
                WHERE order_id = $orderId";
                
$updateResult = mysqli_query($conn, $updateQuery);

if ($updateResult) {
    paymongo_log("Forced payment status update for order #$orderId to paid");
    echo "<div style='color: green;'>Successfully forced order payment status to paid via direct database update</div>";
} else {
    paymongo_log("Failed to force payment status update for order #$orderId: " . mysqli_error($conn), 'ERROR');
    echo "<div style='color: red;'>Failed to update order status: " . mysqli_error($conn) . "</div>";
}

// Show recent logs
echo "<h2>Recent PayMongo Logs</h2>";
$logFile = 'paymongo_debug.log';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $logLines = array_slice(explode("\n", $logs), -30); // Get last 30 lines
    echo "<pre>" . implode("\n", $logLines) . "</pre>";
}
?>
