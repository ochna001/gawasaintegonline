<?php
/**
 * PayMongo Order Handler
 * 
 * This file handles the integration between the PayMongo API and the website's order system
 */

require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/api.php';

/**
 * Process an order with PayMongo payment
 * 
 * @param int $orderId The order ID
 * @param string $paymentMethod The payment method (gcash, bank, card)
 * @return array Response with success status and checkout URL or error message
 */
function processPayMongoOrder($orderId, $paymentMethod) {
    global $conn;
    
    // Get order details - use inline query since functions might conflict
    $sql = "SELECT o.*, u.first_name, u.last_name, u.email FROM orders o
            LEFT JOIN users u ON o.user_id = u.user_id
            WHERE o.order_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $orderId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($result);
    if (!$order) {
        return [
            'success' => false,
            'message' => 'Order not found'
        ];
    }
    
    // Get order items
    $orderItems = getOrderDetails($orderId);
    if (!$orderItems) {
        return [
            'success' => false,
            'message' => 'Order details not found'
        ];
    }
    
    // Format data for PayMongo
    $items = [];
    foreach ($orderItems as $item) {
        $items[] = [
            'name' => $item['item_name'],
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'description' => 'Order #' . $orderId
        ];
    }
    
    // Prepare order data for PayMongo
    $orderData = [
        'order_id' => $orderId,
        'items' => $items,
        'delivery_fee' => 50, // Default delivery fee
        'customer' => [
            'name' => $order['first_name'] . ' ' . $order['last_name'],
            'email' => $order['email'],
            'phone' => isset($order['phone']) ? $order['phone'] : ''
        ]
    ];
    
    // Create checkout session
    $result = PayMongoAPI::createCheckoutSession($orderData);
    
    if ($result['success']) {
        // Update order with PayMongo session ID
        $query = "UPDATE orders SET 
                    payment_method = 'paymongo_" . mysqli_real_escape_string($conn, $paymentMethod) . "', 
                    paymongo_session_id = '" . mysqli_real_escape_string($conn, $result['session_id']) . "',
                    payment_status = 'pending'
                  WHERE order_id = " . (int)$orderId;
        
        mysqli_query($conn, $query);
        
        paymongo_log("Created PayMongo checkout for Order #$orderId - Session ID: " . $result['session_id']);
    } else {
        paymongo_log("Failed to create PayMongo checkout for Order #$orderId: " . $result['message'], 'ERROR');
    }
    
    return $result;
}

/**
 * Update order status based on PayMongo payment status
 * 
 * @param string $sessionId The PayMongo checkout session ID
 * @return array Response with success status and updated order info
 */
function updateOrderFromPayMongo($sessionId) {
    global $conn;
    
    // Log the function call
    paymongo_log("Updating order from PayMongo session: $sessionId");
    
    // First, check if we already have an order with this session ID
    $query = "SELECT order_id, status, payment_status FROM orders WHERE paymongo_session_id = '" . 
              mysqli_real_escape_string($conn, $sessionId) . "'";
    $res = mysqli_query($conn, $query);
    
    // If we don't have a matching order, look by order_id instead (from URL parameter)
    if (mysqli_num_rows($res) == 0) {
        paymongo_log("No order found with session ID: $sessionId - will check if order_id is provided in URL");
        
        // Check if order_id is in the URL parameters
        if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
            $orderId = (int)$_GET['order_id'];
            
            // If we have an order ID from URL, update that order with the session ID
            $updateSessionQuery = "UPDATE orders SET paymongo_session_id = '" . 
                                 mysqli_real_escape_string($conn, $sessionId) . "' WHERE order_id = $orderId";
            $updateResult = mysqli_query($conn, $updateSessionQuery);
            
            if ($updateResult) {
                paymongo_log("Updated order #$orderId with session ID $sessionId from URL parameter");
                
                // Re-fetch the order to ensure we have the latest data
                $query = "SELECT order_id, status, payment_status FROM orders WHERE order_id = $orderId";
                $res = mysqli_query($conn, $query);
            } else {
                paymongo_log("Failed to update order #$orderId with session ID: " . mysqli_error($conn), 'ERROR');
            }
        } else {
            paymongo_log("No order_id parameter found in URL, cannot update order status", 'WARNING');
            return [
                'success' => false,
                'message' => 'No order found matching this session ID and no order_id provided'
            ];
        }
    }
    
    // If we still don't have an order, return an error
    if (mysqli_num_rows($res) == 0) {
        paymongo_log("No order found after all checks, cannot update payment status", 'ERROR');
        return [
            'success' => false,
            'message' => 'No order found with this session ID'
        ];
    }
    
    // Get the order data
    $order = mysqli_fetch_assoc($res);
    $orderId = $order['order_id'];
    $currentStatus = $order['status'];
    $currentPaymentStatus = $order['payment_status'];
    
    paymongo_log("Found order #$orderId with current status: $currentStatus, payment status: $currentPaymentStatus");
    
    // If order is already marked as paid, don't update it again
    if ($currentPaymentStatus === 'paid') {
        paymongo_log("Order #$orderId is already marked as paid, no update needed");
        return [
            'success' => true,
            'order_id' => $orderId,
            'payment_status' => 'paid',
            'order_status' => $currentStatus,
            'message' => 'Order is already marked as paid'
        ];
    }
    
    // Get payment status from PayMongo
    paymongo_log("Retrieving payment status from PayMongo for session $sessionId");
    $result = PayMongoAPI::getPaymentStatus($sessionId);
    
    // Log the full result for debugging
    paymongo_log("PayMongo API response: " . json_encode($result));
    
    if (!$result['success']) {
        paymongo_log("Failed to get payment status from PayMongo: " . ($result['message'] ?? 'Unknown error'), 'ERROR');
        return $result;
    }
    
    // Update order based on payment status
    $paymentStatus = $result['payment_status'];
    paymongo_log("PayMongo payment status: $paymentStatus");
    
    // Force payment status to 'paid' for testing if needed
    // Uncomment this line to always mark payments as successful for testing
    // $paymentStatus = 'paid';
    
    $orderStatus = 'pending'; // Default
    $paymentStatusDb = 'pending';
    
    // Map PayMongo status to our system status
    switch ($paymentStatus) {
        case 'paid':
        case 'succeeded':
        case 'success':
            $orderStatus = 'processing';
            $paymentStatusDb = 'paid';
            break;
        case 'unpaid':
        case 'awaiting_payment_method':
        case 'awaiting_next_action':
            $orderStatus = 'pending';
            $paymentStatusDb = 'pending';
            break;
        case 'failed':
        case 'cancelled':
            $orderStatus = 'cancelled';
            $paymentStatusDb = 'failed';
            break;
        default:
            $orderStatus = 'pending';
            $paymentStatusDb = 'pending';
    }
    
    // Prepare payment ID and method values
    $paymentId = $result['payment_id'] ?? null;
    $paymentMethod = $result['payment_method'] ?? null;
    
    // Log what we're going to update
    paymongo_log("Updating order #$orderId to status: $orderStatus, payment status: $paymentStatusDb");
    
    // Update order using prepared statement for better security
    $updateQuery = "UPDATE orders SET 
                    status = ?, 
                    payment_status = ?, 
                    paymongo_payment_id = ?, 
                    paymongo_payment_method = ? 
                    WHERE order_id = ?";
                    
    $stmt = mysqli_prepare($conn, $updateQuery);
    
    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt, 
            "ssssi", 
            $orderStatus, 
            $paymentStatusDb, 
            $paymentId, 
            $paymentMethod, 
            $orderId
        );
        
        $updateResult = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        if ($updateResult) {
            paymongo_log("Successfully updated order #$orderId payment status to $paymentStatusDb");
            return [
                'success' => true,
                'order_id' => $orderId,
                'payment_status' => $paymentStatusDb,
                'order_status' => $orderStatus
            ];
        } else {
            paymongo_log("Failed to update order #$orderId: " . mysqli_error($conn), 'ERROR');
            return [
                'success' => false,
                'message' => 'Failed to update order status: ' . mysqli_error($conn)
            ];
        }
    } else {
        paymongo_log("Failed to prepare statement for order #$orderId: " . mysqli_error($conn), 'ERROR');
        return [
            'success' => false,
            'message' => 'Failed to prepare database statement: ' . mysqli_error($conn)
        ];
    }
}
?>
