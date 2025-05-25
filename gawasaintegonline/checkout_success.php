<?php
/**
 * PayMongo Checkout Success Page
 * 
 * This page handles successful payments from PayMongo
 */

// Include necessary files
require_once 'includes/functions.php';
require_once 'includes/paymongo/api.php';
require_once 'includes/paymongo/order_handler.php';

// Check if we need to force update the payment status
function forcePaymentUpdate($orderId) {
    global $conn;
    
    // Log that we're forcing payment update
    paymongo_log("Force payment update for order #$orderId");
    
    // Get the current order details
    $query = "SELECT * FROM orders WHERE order_id = $orderId";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 0) {
        paymongo_log("Order #$orderId not found", 'ERROR');
        return false;
    }
    
    $order = mysqli_fetch_assoc($result);
    
    // If already paid, no need to update
    if ($order['payment_status'] === 'paid') {
        paymongo_log("Order #$orderId is already marked as paid");
        return true;
    }
    
    // Force update the order status to paid
    $updateQuery = "UPDATE orders SET 
                    status = 'processing',
                    payment_status = 'paid'
                    WHERE order_id = $orderId";
                    
    $updateResult = mysqli_query($conn, $updateQuery);
    
    if ($updateResult) {
        paymongo_log("Successfully forced payment status for order #$orderId to paid");
        return true;
    } else {
        paymongo_log("Failed to update payment status: " . mysqli_error($conn), 'ERROR');
        return false;
    }
}

// LOCAL DEVELOPMENT ONLY: Auto-update payment status
// This code automatically checks and updates the payment status for local development
// In production, this would be handled by webhooks
if (isset($_GET['session_id'])) {
    $sessionId = $_GET['session_id'];
    
    // Log that we're checking payment status
    paymongo_log("Auto-checking payment status for session: $sessionId");
    
    // Update order status
    $updateResult = updateOrderFromPayMongo($sessionId);
    
    if ($updateResult['success']) {
        paymongo_log("Auto-update success: Order #{$updateResult['order_id']} updated to {$updateResult['payment_status']}");
    } else {
        paymongo_log("Auto-update failed: " . ($updateResult['message'] ?? 'Unknown error'), 'WARNING');
    }
}

// Instead of including admin-functions.php which causes conflicts,
// define a direct function to get an order by ID if it doesn't exist
if (!function_exists('getOrderById')) {
    function getOrderById($conn, $orderId) {
        $sql = "SELECT o.*, u.first_name, u.last_name, u.email FROM orders o
                LEFT JOIN users u ON o.user_id = u.user_id
                WHERE o.order_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $orderId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($order = mysqli_fetch_assoc($result)) {
            return $order;
        }
        
        return null;
    }
}

// Check if session ID and order ID are provided
if (!isset($_GET['session_id']) || !isset($_GET['order_id'])) {
    header('Location: index.html');
    exit;
}

$sessionId = $_GET['session_id'];
$orderId = (int)$_GET['order_id'];

// DEVELOPMENT ONLY: Force payment update for testing
// This will mark the order as paid without requiring a real payment
// Comment this out in production
forcePaymentUpdate($orderId);

// Get database connection
global $conn;

// Get order details first to check current status
$order = getOrderById($conn, $orderId);

// Update order status based on PayMongo session if not already paid
if (!$order || $order['payment_status'] !== 'paid') {
    $result = updateOrderFromPayMongo($sessionId);
} else {
    // Order is already marked as paid
    $result = [
        'success' => true,
        'payment_status' => 'paid',
        'order_status' => $order['status']
    ];
}

// Set message based on payment status
if ($order && $order['payment_status'] === 'paid') {
    $messageType = 'success';
    $message = 'Payment successful! Your order is now being processed.';
    
    // Clear the cart for successful payments
    clearCartForSuccess();
} else if ($result['success'] && isset($result['payment_status']) && $result['payment_status'] === 'paid') {
    $messageType = 'success';
    $message = 'Payment successful! Your order is now being processed.';
    
    // Clear the cart for successful payments
    clearCartForSuccess();
} else {
    $messageType = 'warning';
    $message = 'We have received your order, but there was an issue verifying the payment. Our team will review it.';
}

// Helper function to clear the cart after successful payment
function clearCartForSuccess() {
    global $conn;
    
    // Get user ID if logged in
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Clear cart in database if user is logged in
    if ($user_id) {
        // Call the clearCart function to clear items from the database
        clearCart($user_id);
    }
    
    // Clear cart in session if using session-based cart
    if (isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Clear cart in localStorage using JavaScript
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            // Clear cart in localStorage
            localStorage.removeItem('cart');
            console.log('Cart cleared after successful payment');
        });
    </script>";
}

// Refresh order details after potential update
if (!$order) {
    $order = getOrderById($conn, $orderId);
}

// Log order data for debugging
file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . ' - Order data: ' . json_encode($order) . PHP_EOL, FILE_APPEND);

// Add default values for fields that might be missing
if ($order) {
    // Map delivery_address to address for display
    if (isset($order['delivery_address']) && !isset($order['address'])) {
        $order['address'] = $order['delivery_address'];
    }
    
    // Map created_at to order_date if needed
    if (isset($order['created_at']) && !isset($order['order_date'])) {
        $order['order_date'] = $order['created_at'];
    }
    
    // Get user details if not present
    if (isset($order['user_id']) && $order['user_id']) {
        // Try to get user details if missing
        $userQuery = "SELECT first_name, last_name, email, phone FROM users WHERE user_id = ?";
        $userStmt = mysqli_prepare($conn, $userQuery);
        if ($userStmt) {
            mysqli_stmt_bind_param($userStmt, "i", $order['user_id']);
            mysqli_stmt_execute($userStmt);
            $userResult = mysqli_stmt_get_result($userStmt);
            if ($user = mysqli_fetch_assoc($userResult)) {
                // Only set if not already set
                if (!isset($order['first_name'])) $order['first_name'] = $user['first_name'];
                if (!isset($order['last_name'])) $order['last_name'] = $user['last_name'];
                if (!isset($order['email'])) $order['email'] = $user['email'];
                if (!isset($order['phone'])) $order['phone'] = $user['phone'];
            }
            mysqli_stmt_close($userStmt);
        }
    }
}

// Get order items directly instead of using getOrderDetails
$orderItems = [];
if ($order) {
    $itemQuery = "SELECT oi.*, p.name as product_name FROM order_items oi 
                  LEFT JOIN products p ON oi.product_id = p.product_id 
                  WHERE oi.order_id = ?";
    $itemStmt = mysqli_prepare($conn, $itemQuery);
    if ($itemStmt) {
        mysqli_stmt_bind_param($itemStmt, "i", $orderId);
        mysqli_stmt_execute($itemStmt);
        $itemResult = mysqli_stmt_get_result($itemStmt);
        while ($item = mysqli_fetch_assoc($itemResult)) {
            // Make sure item_name is set
            if (!isset($item['item_name']) && isset($item['product_name'])) {
                $item['item_name'] = $item['product_name'];
            } else if (!isset($item['item_name']) && isset($item['name'])) {
                $item['item_name'] = $item['name'];
            } else if (!isset($item['item_name'])) {
                $item['item_name'] = 'Unknown Item';
            }
            $orderItems[] = $item;
        }
        mysqli_stmt_close($itemStmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Local Flavors</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* Page layout */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            background-color: #f8f9fa;
        }
        
        main {
            flex: 1;
            padding: 40px 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .confirmation-container {
            text-align: center;
            padding: 40px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            margin: 0 auto;
            max-width: 600px;
            width: 100%;
        }
        
        /* Success message styling */
        .success-icon {
            margin-bottom: 30px;
        }
        
        .message-container {
            margin: 30px 0;
        }
        
        .message-container h1 {
            color: #333;
            margin-bottom: 15px;
            font-size: 28px;
        }
        
        .message-text {
            font-size: 18px;
            line-height: 1.6;
            color: #666;
            margin-bottom: 15px;
        }
        
        .order-number {
            font-weight: bold;
            color: #E65100;
            font-size: 16px;
            margin-bottom: 25px;
        }
        
        /* Button styling */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 16px;
        }
        
        .primary-btn {
            background-color: #E65100;
            color: white;
            border: 2px solid #E65100;
        }
        
        .primary-btn:hover {
            background-color: #D84315;
            border-color: #D84315;
        }
        
        .secondary-btn {
            background-color: white;
            color: #E65100;
            border: 2px solid #E65100;
        }
        
        .secondary-btn:hover {
            background-color: #FBE9E7;
        }
        
        .success-icon {
            text-align: center;
            margin-bottom: 20px;
        }
        .success-icon i {
            font-size: 80px;
            color: #28a745;
        }
        .message {
            text-align: center;
            margin-bottom: 30px;
        }
        .message h1 {
            margin-bottom: 10px;
            color: #333;
        }
        .message p {
            font-size: 18px;
            color: #666;
        }
        .order-details {
            margin-top: 30px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .order-details h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .order-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .order-info-box {
            flex: 1;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
            margin-right: 15px;
        }
        .order-info-box:last-child {
            margin-right: 0;
        }
        .order-info-box h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 16px;
            color: #555;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table th {
            text-align: left;
            padding: 12px 15px;
            background-color: #f5f5f5;
            border-bottom: 2px solid #ddd;
        }
        .items-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
        }
        .actions {
            margin-top: 30px;
            text-align: center;
        }
        .actions a {
            display: inline-block;
            padding: 12px 25px;
            margin: 0 10px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .actions .btn-primary {
            background-color: #E71D36;
            color: white;
        }
        .actions .btn-secondary {
            background-color: #fff;
            color: #E71D36;
            border: 1px solid #E71D36;
        }
        .actions a:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        .message-success {
            color: #28a745;
        }
        .message-warning {
            color: #ffc107;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main>
        <div class="confirmation-container">
            <div class="success-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#4CAF50" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
        
        <?php if ($order): ?>
        <div class="order-details">
            <h2>Order Details</h2>
            
            <div class="order-info">
                <div class="order-info-box">
                    <h3>Order Information</h3>
                    <p><strong>Order Date:</strong> <?php echo isset($order['created_at']) ? date('F j, Y', strtotime($order['created_at'])) : 'N/A'; ?></p>
                    <p><strong>Status:</strong> <?php echo ucfirst($order['status']); ?></p>
                    <p><strong>Payment Status:</strong> <span class="<?php echo ($order['payment_status'] === 'paid') ? 'message-success' : 'message-warning'; ?>"><?php echo ucfirst($order['payment_status'] ?? 'pending'); ?></span></p>
                    <p><strong>Payment Method:</strong> <?php 
                        $method = $order['payment_method'] ?? 'online_payment';
                        // Remove paymongo_ prefix if present
                        $method = str_replace('paymongo_', '', $method);
                        // Make it more readable
                        $method = str_replace('_', ' ', $method);
                        echo ucwords($method);
                    ?></p>
                </div>
                
                <div class="order-info-box">
                    <h3>Shipping Address</h3>
                    <p><?php echo isset($order['first_name']) ? $order['first_name'] . ' ' . $order['last_name'] : 'Guest Customer'; ?></p>
                    <p><?php echo isset($order['delivery_address']) ? $order['delivery_address'] : 'Address not available'; ?></p>
                    <p><?php echo isset($order['phone']) ? $order['phone'] : 'Phone not available'; ?></p>
                </div>
            </div>
            
            <h3>Items Ordered</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $subtotal = 0;
                    foreach ($orderItems as $item): 
                        $itemTotal = $item['price'] * $item['quantity'];
                        $subtotal += $itemTotal;
                    ?>
                    <tr>
                        <td><?php echo $item['item_name']; ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                        <td>₱<?php echo number_format($itemTotal, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right;"><strong>Subtotal:</strong></td>
                        <td>₱<?php echo number_format($subtotal, 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align: right;"><strong>Delivery Fee:</strong></td>
                        <td>₱50.00</td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                        <td>₱<?php echo number_format($subtotal + 50, 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="message-container">
            <h1>Thank You for Your Order!</h1>
            <p class="message-text <?php echo $messageType; ?>"><?php echo $message; ?></p>
            <p class="order-number">Order #<?php echo $orderId; ?></p>
        </div>
        
        <div class="action-buttons">
            <a href="menu.php" class="btn primary-btn">Continue Shopping</a>
            <?php if (isLoggedIn()): ?>
            <a href="profile.php#orders" class="btn secondary-btn">View Your Orders</a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Local Flavors. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="js/header.js"></script>
</body>
</html>
