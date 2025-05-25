<?php
/**
 * Direct PayMongo Checkout
 * A simpler approach without AJAX
 */

// Include necessary files
require_once 'includes/functions.php';
require_once 'includes/paymongo/api.php';
require_once 'includes/paymongo/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log function for debugging
function debug_log($message) {
    error_log(date('Y-m-d H:i:s') . " - PayMongo Debug: $message\n", 3, 'paymongo_debug.log');
}

// Create a test order
function createTestOrder() {
    debug_log("Creating test order");
    
    // Test order data
    $orderData = [
        'order_id' => 'test_' . time(),
        'items' => [
            [
                'name' => 'Test Product',
                'quantity' => 1,
                'price' => 300.00,
                'description' => 'Test product for debugging'
            ]
        ],
        'delivery_fee' => 50.00,
        'customer' => [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '09123456789'
        ]
    ];
    
    try {
        debug_log("Calling PayMongoAPI::createCheckoutSession");
        $result = PayMongoAPI::createCheckoutSession($orderData);
        debug_log("API call result: " . json_encode($result));
        
        if ($result['success'] && !empty($result['checkout_url'])) {
            debug_log("Success! Redirecting to: " . $result['checkout_url']);
            header("Location: " . $result['checkout_url']);
            exit;
        } else {
            debug_log("API call failed: " . json_encode($result));
            echo "<div style='color:red;'>Error: " . ($result['message'] ?? 'Unknown error') . "</div>";
            echo "<pre>" . print_r($result, true) . "</pre>";
        }
    } catch (Exception $e) {
        debug_log("Exception: " . $e->getMessage());
        echo "<div style='color:red;'>Exception: " . $e->getMessage() . "</div>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// If we're doing a test, create the order directly
if (isset($_GET['test']) && $_GET['test'] === 'yes') {
    createTestOrder();
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direct PayMongo Test</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .container {
            max-width: 800px;
            margin: 100px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .btn {
            background-color: #E65100;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Direct PayMongo Test</h1>
        <p>This page will test the PayMongo integration directly without using AJAX.</p>
        <p>Click the button below to create a test checkout session and redirect to PayMongo:</p>
        
        <a href="?test=yes" class="btn">Create PayMongo Checkout</a>
        
        <div style="margin-top: 30px;">
            <h3>Troubleshooting Tips:</h3>
            <ul>
                <li>Make sure XAMPP/Apache is running properly</li>
                <li>Check that PHP is correctly configured</li>
                <li>Verify your PayMongo API keys in includes/paymongo/config.php</li>
                <li>Look for errors in paymongo_debug.log after clicking the button</li>
            </ul>
        </div>
        
        <a href="checkout.php" style="display:block; margin-top:30px; color:#E65100;">← Back to Checkout</a>
    </div>
</body>
</html>
