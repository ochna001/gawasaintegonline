<?php
/**
 * PayMongo Checkout Cancel Page
 * 
 * This page handles when users cancel or abandon the PayMongo checkout
 */

// Include necessary files
require_once 'includes/functions.php';

// Check if order ID is provided
if (!isset($_GET['order_id'])) {
    header('Location: index.html');
    exit;
}

$orderId = (int)$_GET['order_id'];

// Get order details for display
$order = getOrderById($orderId);

// Update order status to cancelled
if ($order) {
    global $conn;
    $query = "UPDATE orders SET status = 'cancelled', payment_status = 'cancelled' WHERE order_id = " . $orderId;
    mysqli_query($conn, $query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled - Local Flavors</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .order-cancel {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .cancel-icon {
            text-align: center;
            margin-bottom: 20px;
        }
        .cancel-icon i {
            font-size: 80px;
            color: #dc3545;
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
            margin-bottom: 5px;
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
        .help-options {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .help-options h3 {
            text-align: center;
            margin-bottom: 20px;
        }
        .help-options-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .help-option {
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
            text-align: center;
        }
        .help-option i {
            font-size: 30px;
            margin-bottom: 10px;
            color: #555;
        }
        .help-option h4 {
            margin-bottom: 10px;
            color: #333;
        }
        .help-option p {
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Include Header -->
    <?php include 'includes/header.php'; ?>
    
    <div class="order-cancel">
        <div class="cancel-icon">
            <i class="fas fa-times-circle"></i>
        </div>
        
        <div class="message">
            <h1>Payment Cancelled</h1>
            <p>Your payment for Order #<?php echo $orderId; ?> was cancelled.</p>
            <p>Don't worry, you can still complete your purchase at any time.</p>
        </div>
        
        <div class="actions">
            <a href="checkout.html?order_id=<?php echo $orderId; ?>" class="btn-primary">Try Again</a>
            <a href="index.html" class="btn-secondary">Continue Shopping</a>
        </div>
        
        <div class="help-options">
            <h3>Need Help?</h3>
            <div class="help-options-grid">
                <div class="help-option">
                    <i class="fas fa-credit-card"></i>
                    <h4>Payment Issues?</h4>
                    <p>We support multiple payment methods including GCash, credit card, and online banking.</p>
                </div>
                <div class="help-option">
                    <i class="fas fa-question-circle"></i>
                    <h4>Have Questions?</h4>
                    <p>Check our FAQ or contact our customer support team for assistance.</p>
                </div>
                <div class="help-option">
                    <i class="fas fa-shopping-cart"></i>
                    <h4>Your Cart is Saved</h4>
                    <p>Your items are still in your cart, ready when you decide to complete your purchase.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Include Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <script src="js/header.js"></script>
</body>
</html>
