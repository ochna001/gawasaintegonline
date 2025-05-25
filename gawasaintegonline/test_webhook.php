<?php
/**
 * PayMongo Webhook Test
 * 
 * This script simulates a PayMongo webhook request to test the webhook handler
 */

// Include necessary files
require_once 'includes/functions.php';
require_once 'includes/paymongo/api.php';
require_once 'includes/paymongo/order_handler.php';

// Log start of test
file_put_contents('webhook_test.log', date('Y-m-d H:i:s') . " - Starting webhook test\n", FILE_APPEND);

// Get the most recent order from the database
global $conn;
$query = "SELECT * FROM orders WHERE paymongo_session_id IS NOT NULL ORDER BY order_id DESC LIMIT 1";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    echo "No orders with PayMongo session ID found";
    file_put_contents('webhook_test.log', date('Y-m-d H:i:s') . " - No orders found with PayMongo session ID\n", FILE_APPEND);
    exit;
}

// Get the order details
$order = mysqli_fetch_assoc($result);
$orderId = $order['order_id'];
$sessionId = $order['paymongo_session_id'];

echo "<h1>Testing Webhook for Order #$orderId</h1>";
echo "<p>Current Status: " . $order['status'] . "</p>";
echo "<p>Current Payment Status: " . $order['payment_status'] . "</p>";
echo "<p>PayMongo Session ID: " . $sessionId . "</p>";

// Create a simulated webhook event payload
$simulatedEvent = [
    'data' => [
        'id' => $sessionId,
        'attributes' => [
            'type' => 'checkout_session.completed',
            'data' => [
                'attributes' => [
                    'payment_intent_id' => $sessionId,
                    'status' => 'paid'
                ]
            ]
        ]
    ]
];

// Set up the test event
$testPayload = json_encode([
    'data' => [
        'id' => 'evt_' . uniqid(),
        'attributes' => [
            'type' => 'checkout_session.completed',
            'data' => $simulatedEvent
        ]
    ]
]);

file_put_contents('webhook_test.log', date('Y-m-d H:i:s') . " - Test payload: " . $testPayload . "\n", FILE_APPEND);

// Log the webhook test details
file_put_contents('webhook_test.log', date('Y-m-d H:i:s') . " - Testing webhook for order #$orderId with session ID $sessionId\n", FILE_APPEND);

// Directly test the updateOrderFromPayMongo function
echo "<h2>Testing updateOrderFromPayMongo function</h2>";
$result = updateOrderFromPayMongo($sessionId);

echo "<pre>";
var_dump($result);
echo "</pre>";

// Get the updated order details
$query = "SELECT * FROM orders WHERE order_id = $orderId";
$result = mysqli_query($conn, $query);
$updatedOrder = mysqli_fetch_assoc($result);

echo "<h2>Results</h2>";
echo "<p>Updated Status: " . $updatedOrder['status'] . "</p>";
echo "<p>Updated Payment Status: " . $updatedOrder['payment_status'] . "</p>";

if ($updatedOrder['payment_status'] === 'paid') {
    echo "<div style='color: green; font-weight: bold;'>SUCCESS: Order payment status updated to paid</div>";
} else {
    echo "<div style='color: red; font-weight: bold;'>FAILED: Order payment status not updated to paid</div>";
    
    // For debugging, let's directly try updating the order
    echo "<h2>Manual Update Test</h2>";
    
    // Try a direct SQL update as a last resort
    $updateQuery = "UPDATE orders SET payment_status = 'paid', status = 'processing' WHERE order_id = $orderId";
    $updateResult = mysqli_query($conn, $updateQuery);
    
    if ($updateResult) {
        echo "<div style='color: green;'>Manual database update succeeded</div>";
    } else {
        echo "<div style='color: red;'>Manual database update failed: " . mysqli_error($conn) . "</div>";
    }
}

// Log end of test
file_put_contents('webhook_test.log', date('Y-m-d H:i:s') . " - Webhook test completed\n", FILE_APPEND);



// Show the PayMongo API logs
echo "<h2>Recent PayMongo Logs</h2>";
$logFile = 'paymongo_debug.log';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $logLines = array_slice(explode("\n", $logs), -30); // Get last 30 lines
    echo "<pre>" . implode("\n", $logLines) . "</pre>";
} else {
    echo "<p>No log file found</p>";
}
?>
