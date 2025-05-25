<?php
/**
 * PayMongo Debug Tool
 * This file helps debug PayMongo integration issues
 */

// Set error reporting to maximum level for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the necessary files
require_once 'includes/functions.php';
require_once 'includes/paymongo/api.php';
require_once 'includes/paymongo/config.php';

// Only set JSON content type when handling the API test
if (isset($_GET['test']) && $_GET['test'] === 'checkout') {
    header('Content-Type: application/json');
}

// Function to create a test checkout session
function createTestCheckoutSession() {
    // Create a simple test order
    $orderData = [
        'order_id' => 'test_' . time(),
        'items' => [
            [
                'name' => 'Test Product',
                'quantity' => 1,
                'price' => 100.00,
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
        // Call the PayMongo API directly
        $result = PayMongoAPI::createCheckoutSession($orderData);
        
        // Add extra debug info
        $result['debug'] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'config' => [
                'website_url' => PAYMONGO_WEBSITE_URL,
                'api_url' => PAYMONGO_API_URL,
                'mode' => PAYMONGO_MODE
            ]
        ];
        
        return $result;
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
}

// Check if we should perform the test
if (isset($_GET['test']) && $_GET['test'] === 'checkout') {
    $result = createTestCheckoutSession();
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

// Default action - show the debug interface
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayMongo Debug Tool</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .debug-container {
            max-width: 800px;
            margin: 80px auto 40px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .debug-title {
            color: #E65100;
            margin-bottom: 20px;
        }
        .debug-button {
            background-color: #E65100;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 10px 0;
        }
        .debug-result {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            white-space: pre-wrap;
            overflow-x: auto;
        }
        .debug-back {
            display: block;
            margin-top: 20px;
            color: #E65100;
            text-decoration: none;
        }
        .debug-section {
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav-container">
            <div class="logo">
                <a href="index.html" style="display: flex; align-items: center;">
                    <img src="assets/logo.png" alt="Local Flavors Logo" class="logo-image" style="margin-right: 10px;">
                    <h1 style="margin: 0;">LOCAL FLAVORS</h1>
                </a>
            </div>
        </nav>
    </header>

    <div class="debug-container">
        <h2 class="debug-title">PayMongo Debug Tool</h2>
        
        <div class="debug-section">
            <h3>1. Test PayMongo Checkout Session</h3>
            <p>Click the button below to create a test checkout session with PayMongo:</p>
            <button id="testCheckout" class="debug-button">Create Test Checkout Session</button>
            <div id="checkoutResult" class="debug-result" style="display: none;"></div>
        </div>
        
        <div class="debug-section">
            <h3>2. PHP Configuration</h3>
            <p>PHP Version: <?php echo PHP_VERSION; ?></p>
            <p>PayMongo Mode: <?php echo PAYMONGO_MODE; ?></p>
            <p>Website URL: <?php echo PAYMONGO_WEBSITE_URL; ?></p>
        </div>
        
        <div class="debug-section">
            <h3>3. Server Info</h3>
            <p>Server Software: <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
            <p>Document Root: <?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
            <p>Remote Address: <?php echo $_SERVER['REMOTE_ADDR']; ?></p>
        </div>
        
        <a href="checkout.php" class="debug-back">← Back to Checkout</a>
    </div>

    <script>
        document.getElementById('testCheckout').addEventListener('click', function() {
            this.disabled = true;
            this.textContent = 'Testing...';
            
            const resultElement = document.getElementById('checkoutResult');
            resultElement.style.display = 'block';
            resultElement.textContent = 'Contacting PayMongo API...';
            
            fetch('debug_paymongo.php?test=checkout')
                .then(response => response.json())
                .then(data => {
                    console.log('Debug result:', data);
                    resultElement.textContent = JSON.stringify(data, null, 2);
                    
                    this.disabled = false;
                    this.textContent = 'Create Test Checkout Session';
                    
                    // If successful, add a redirect button
                    if (data.success && data.checkout_url) {
                        const redirectBtn = document.createElement('button');
                        redirectBtn.className = 'debug-button';
                        redirectBtn.style.marginLeft = '10px';
                        redirectBtn.textContent = 'Go to Checkout URL';
                        redirectBtn.addEventListener('click', function() {
                            window.open(data.checkout_url, '_blank');
                        });
                        
                        this.parentNode.insertBefore(redirectBtn, resultElement);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultElement.textContent = 'Error: ' + error.message;
                    
                    this.disabled = false;
                    this.textContent = 'Create Test Checkout Session';
                });
        });
    </script>
</body>
</html>
