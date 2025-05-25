<?php
/**
 * PayMongo Webhook Handler
 * 
 * This file handles incoming webhooks from PayMongo for payment status updates
 */

// Include necessary files
require_once 'includes/functions.php';
require_once 'includes/paymongo/api.php';
require_once 'includes/paymongo/order_handler.php';

// Log the webhook request
paymongo_log('Received webhook request');

// Get the JSON payload
$payload = file_get_contents('php://input');
$signature = isset($_SERVER['HTTP_PAYMONGO_SIGNATURE']) ? $_SERVER['HTTP_PAYMONGO_SIGNATURE'] : '';

// Handle the webhook
try {
    // Parse the webhook payload
    $result = PayMongoAPI::handleWebhook($payload, $signature);
    
    if (!$result['success']) {
        http_response_code(400);
        echo json_encode(['error' => $result['message']]);
        exit;
    }
    
    $eventData = $result['event_data'];
    $eventType = $result['event_type'];
    
    paymongo_log("Processing webhook event: $eventType");
    
    // Handle different event types
    switch ($eventType) {
        case 'payment.paid':
            handlePaymentPaid($eventData);
            break;
            
        case 'payment.failed':
            handlePaymentFailed($eventData);
            break;
            
        case 'checkout_session.completed':
            handleCheckoutCompleted($eventData);
            break;
            
        default:
            // Log unhandled event type
            paymongo_log("Unhandled event type: $eventType", 'WARNING');
    }
    
    // Return success response
    http_response_code(200);
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    paymongo_log('Error processing webhook: ' . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

/**
 * Handle payment.paid event
 * 
 * @param array $eventData Event data from PayMongo
 */
function handlePaymentPaid($eventData) {
    global $conn;
    
    // Get the payment ID and session ID
    $paymentId = $eventData['data']['id'] ?? null;
    
    if (!$paymentId) {
        paymongo_log('Payment ID not found in event data', 'ERROR');
        return;
    }
    
    // Find the order with this payment ID
    $query = "SELECT order_id FROM orders WHERE paymongo_payment_id = '" . 
             mysqli_real_escape_string($conn, $paymentId) . "'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 0) {
        // If payment ID is not found, try to find by session ID if available
        $checkoutSessionId = $eventData['data']['attributes']['data']['attributes']['payment_intent_id'] ?? null;
        
        if ($checkoutSessionId) {
            $query = "SELECT order_id FROM orders WHERE paymongo_session_id = '" . 
                     mysqli_real_escape_string($conn, $checkoutSessionId) . "'";
            $result = mysqli_query($conn, $query);
        }
        
        if (mysqli_num_rows($result) == 0) {
            paymongo_log("No order found for payment ID: $paymentId", 'WARNING');
            return;
        }
    }
    
    $order = mysqli_fetch_assoc($result);
    $orderId = $order['order_id'];
    
    // Update order status to paid
    $updateQuery = "UPDATE orders SET 
                    status = 'processing',
                    payment_status = 'paid',
                    paymongo_payment_id = '" . mysqli_real_escape_string($conn, $paymentId) . "'
                    WHERE order_id = " . (int)$orderId;
    
    $updateResult = mysqli_query($conn, $updateQuery);
    
    if ($updateResult) {
        paymongo_log("Updated order #$orderId payment status to paid");
    } else {
        paymongo_log("Failed to update order #$orderId: " . mysqli_error($conn), 'ERROR');
    }
}

/**
 * Handle payment.failed event
 * 
 * @param array $eventData Event data from PayMongo
 */
function handlePaymentFailed($eventData) {
    global $conn;
    
    // Get the payment ID
    $paymentId = $eventData['data']['id'] ?? null;
    
    if (!$paymentId) {
        paymongo_log('Payment ID not found in event data', 'ERROR');
        return;
    }
    
    // Find the order with this payment ID
    $query = "SELECT order_id FROM orders WHERE paymongo_payment_id = '" . 
             mysqli_real_escape_string($conn, $paymentId) . "'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 0) {
        // If payment ID is not found, try to find by session ID if available
        $checkoutSessionId = $eventData['data']['attributes']['data']['attributes']['payment_intent_id'] ?? null;
        
        if ($checkoutSessionId) {
            $query = "SELECT order_id FROM orders WHERE paymongo_session_id = '" . 
                     mysqli_real_escape_string($conn, $checkoutSessionId) . "'";
            $result = mysqli_query($conn, $query);
        }
        
        if (mysqli_num_rows($result) == 0) {
            paymongo_log("No order found for payment ID: $paymentId", 'WARNING');
            return;
        }
    }
    
    $order = mysqli_fetch_assoc($result);
    $orderId = $order['order_id'];
    
    // Update order status to failed
    $updateQuery = "UPDATE orders SET 
                    payment_status = 'failed',
                    paymongo_payment_id = '" . mysqli_real_escape_string($conn, $paymentId) . "'
                    WHERE order_id = " . (int)$orderId;
    
    $updateResult = mysqli_query($conn, $updateQuery);
    
    if ($updateResult) {
        paymongo_log("Updated order #$orderId payment status to failed");
    } else {
        paymongo_log("Failed to update order #$orderId: " . mysqli_error($conn), 'ERROR');
    }
}

/**
 * Handle checkout_session.completed event
 * 
 * @param array $eventData Event data from PayMongo
 */
function handleCheckoutCompleted($eventData) {
    // Get the checkout session ID
    $sessionId = $eventData['data']['id'] ?? null;
    
    if (!$sessionId) {
        paymongo_log('Session ID not found in event data', 'ERROR');
        return;
    }
    
    // Update order status based on checkout session
    $result = updateOrderFromPayMongo($sessionId);
    
    if ($result['success']) {
        paymongo_log("Updated order #{$result['order_id']} from checkout session: $sessionId");
    } else {
        paymongo_log("Failed to update order from checkout session: $sessionId - {$result['message']}", 'ERROR');
    }
}
?>
