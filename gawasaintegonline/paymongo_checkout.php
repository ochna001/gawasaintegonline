<?php
/**
 * PayMongo Checkout Session Creator - WORKING VERSION
 * 
 * This endpoint calls the PayMongo API without database operations
 */

// Prevent ANY output except the final JSON
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'paymongo_error.log');

// Set content type BEFORE any output
header('Content-Type: application/json');

// Include only the PayMongo API file
require_once 'includes/paymongo/config.php';
require_once 'includes/paymongo/api.php';

// Log request for debugging
file_put_contents('paymongo_debug.log', date('Y-m-d H:i:s') . ' - Step 1: PayMongo API test' . PHP_EOL, FILE_APPEND);

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
file_put_contents('paymongo_debug.log', date('Y-m-d H:i:s') . ' - Received data: ' . json_encode($data) . PHP_EOL, FILE_APPEND);

try {
    // First, check if headers have been sent (important for session_start)
    if (headers_sent()) {
        file_put_contents('paymongo_debug.log', date('Y-m-d H:i:s') . ' - Headers already sent, skipping session_start' . PHP_EOL, FILE_APPEND);
    } else {
        // Start session to get user_id if logged in
        session_start();
    }
    
    // Include database connection
    require_once 'includes/config.php';
    
    // Check if user is logged in
    $userId = null;
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }
    
    // Calculate total amount from items
    $totalAmount = 0;
    foreach ($data['items'] as $item) {
        $totalAmount += $item['price'] * $item['quantity'];
    }
    
    // Add delivery fee
    $deliveryFee = 50;
    $totalAmount += $deliveryFee;
    
    // Store order in database if connection exists
    $orderId = null;
    if (isset($conn) && $conn) {
        // Map PayMongo payment method to valid enum value
        // Ensure payment_method is one of: 'credit_card', 'pay_at_delivery', 'online_payment'
        $paymentMethod = 'online_payment'; // Default for all PayMongo methods
        if ($data['payment_method_type'] == 'card') {
            $paymentMethod = 'credit_card';
        }
        
        // Prepare order data with minimal required fields
        // Only use fields that exist and are required
        $orderData = [
            'total_amount' => $totalAmount,
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
            'delivery_address' => $data['address']
        ];
        
        // Add user_id if available
        if ($userId) {
            $orderData['user_id'] = $userId;
        }
        
        // Insert order
        $fields = implode(', ', array_keys($orderData));
        $placeholders = implode(', ', array_fill(0, count($orderData), '?'));
        
        $query = "INSERT INTO orders ($fields) VALUES ($placeholders)";
        file_put_contents('paymongo_debug.log', date('Y-m-d H:i:s') . ' - SQL Query: ' . $query . PHP_EOL, FILE_APPEND);
        
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            // Bind parameters
            $types = '';
            $bindValues = [];
            
            foreach ($orderData as $key => $value) {
                if (is_int($value)) $types .= 'i';
                elseif (is_float($value)) $types .= 'd';
                else $types .= 's';
                $bindValues[] = $value;
            }
            
            mysqli_stmt_bind_param($stmt, $types, ...$bindValues);
            
            // Execute query
            $result = mysqli_stmt_execute($stmt);
            if ($result) {
                $orderId = mysqli_insert_id($conn);
                file_put_contents('paymongo_debug.log', date('Y-m-d H:i:s') . ' - Order created with ID: ' . $orderId . PHP_EOL, FILE_APPEND);
                
                // Add order items
                foreach ($data['items'] as $item) {
                    $itemData = [
                        'order_id' => $orderId,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'item_name' => $item['name']
                    ];
                    
                    $itemFields = implode(', ', array_keys($itemData));
                    $itemPlaceholders = implode(', ', array_fill(0, count($itemData), '?'));
                    
                    $itemQuery = "INSERT INTO order_items ($itemFields) VALUES ($itemPlaceholders)";
                    $itemStmt = mysqli_prepare($conn, $itemQuery);
                    
                    if ($itemStmt) {
                        $itemTypes = '';
                        $itemBindValues = [];
                        
                        foreach ($itemData as $key => $value) {
                            if (is_int($value)) $itemTypes .= 'i';
                            elseif (is_float($value)) $itemTypes .= 'd';
                            else $itemTypes .= 's';
                            $itemBindValues[] = $value;
                        }
                        
                        mysqli_stmt_bind_param($itemStmt, $itemTypes, ...$itemBindValues);
                        mysqli_stmt_execute($itemStmt);
                        mysqli_stmt_close($itemStmt);
                    }
                }
            } else {
                file_put_contents('paymongo_debug.log', date('Y-m-d H:i:s') . ' - Failed to create order: ' . mysqli_stmt_error($stmt) . PHP_EOL, FILE_APPEND);
            }
            
            mysqli_stmt_close($stmt);
        } else {
            file_put_contents('paymongo_debug.log', date('Y-m-d H:i:s') . ' - Failed to prepare statement: ' . mysqli_error($conn) . PHP_EOL, FILE_APPEND);
        }
    }
    
    // If database insertion failed, use a timestamp as temporary order ID
    if (!$orderId) {
        $orderId = time();
        file_put_contents('paymongo_debug.log', date('Y-m-d H:i:s') . ' - Using temporary order ID: ' . $orderId . PHP_EOL, FILE_APPEND);
    }
    
    // Format line items for PayMongo API (including required currency)
    $lineItems = [];
    foreach ($data['items'] as $item) {
        $lineItems[] = [
            'name' => $item['name'],
            'quantity' => $item['quantity'],
            'amount' => round($item['price'] * 100), // Convert to centavos as required
            'currency' => 'PHP', // Required currency parameter
            'description' => 'Order #' . $orderId
        ];
    }
    
    // Add delivery fee
    $lineItems[] = [
        'name' => 'Delivery Fee',
        'quantity' => 1,
        'amount' => 5000, // ₱50.00 in centavos
        'currency' => 'PHP',
        'description' => 'Standard delivery fee'
    ];
    
    // Create direct PayMongo checkout session
    $checkoutData = [
        'data' => [
            'attributes' => [
                'line_items' => $lineItems,
                'payment_method_types' => ['gcash', 'card'],
                'success_url' => 'http://localhost/gawasainteg/checkout_success.php?session_id={CHECKOUT_SESSION_ID}&order_id=' . $orderId,
                'cancel_url' => 'http://localhost/gawasainteg/checkout.php?canceled=true',
                'description' => 'Order #' . $orderId,
                'statement_descriptor' => 'Gawasa Integ',
                'reference_number' => (string)$orderId
            ]
        ]
    ];
    
    // Convert to JSON
    $jsonData = json_encode($checkoutData);
    file_put_contents('paymongo_debug.log', date('Y-m-d H:i:s') . ' - PayMongo request: ' . $jsonData . PHP_EOL, FILE_APPEND);
    
    // Make API call to PayMongo
    $ch = curl_init('https://api.paymongo.com/v1/checkout_sessions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':')
    ]);
    
    // Execute request
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log the response
    file_put_contents('paymongo_debug.log', date('Y-m-d H:i:s') . ' - PayMongo response code: ' . $statusCode . PHP_EOL, FILE_APPEND);
    file_put_contents('paymongo_debug.log', date('Y-m-d H:i:s') . ' - PayMongo response: ' . $response . PHP_EOL, FILE_APPEND);
    
    // Parse response
    $responseData = json_decode($response, true);
    
    if ($statusCode == 200 && isset($responseData['data']['attributes']['checkout_url'])) {
        // Get the PayMongo session ID from the response
        $paymongoSessionId = $responseData['data']['id'] ?? null;
        
        // Update the order with the PayMongo session ID if we have a database connection and real order ID
        if (isset($conn) && $conn && $orderId && is_numeric($orderId) && $paymongoSessionId) {
            // Update the order with the session ID
            $updateQuery = "UPDATE orders SET paymongo_session_id = ? WHERE order_id = ?";
            $updateStmt = mysqli_prepare($conn, $updateQuery);
            
            if ($updateStmt) {
                mysqli_stmt_bind_param($updateStmt, "si", $paymongoSessionId, $orderId);
                mysqli_stmt_execute($updateStmt);
                mysqli_stmt_close($updateStmt);
                
                file_put_contents('paymongo_debug.log', date('Y-m-d H:i:s') . ' - Updated order ' . $orderId . ' with PayMongo session ID: ' . $paymongoSessionId . PHP_EOL, FILE_APPEND);
            }
        }
        
        // Return success with checkout URL
        echo json_encode([
            'success' => true,
            'order_id' => $orderId,
            'checkout_url' => $responseData['data']['attributes']['checkout_url']
        ]);
    } else {
        // Return error with details
        $errorMessage = isset($responseData['errors'][0]['detail']) ? $responseData['errors'][0]['detail'] : 'Unknown PayMongo error';
        file_put_contents('paymongo_debug.log', date('Y-m-d H:i:s') . ' - PayMongo error: ' . $errorMessage . PHP_EOL, FILE_APPEND);
        
        echo json_encode([
            'success' => false,
            'message' => 'PayMongo error: ' . $errorMessage,
            'fallback_url' => 'http://localhost/gawasainteg/checkout.php?error=paymongo'
        ]);
    }
} catch (Exception $e) {
    // Log and return error
    file_put_contents('paymongo_debug.log', date('Y-m-d H:i:s') . ' - Exception: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    
    echo json_encode([
        'success' => false,
        'message' => 'System error: ' . $e->getMessage()
    ]);
}
?>