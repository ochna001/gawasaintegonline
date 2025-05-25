<?php
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // Redirect to login page with return URL
    header('Location: login.php?redirect=checkout.php');
    exit;
}

// Get user profile information
$user_id = $_SESSION['user_id'];
$user = getUserProfile($user_id);

// Process form submission
$order_success = false;
$order_id = null;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $name = sanitizeInput($_POST['name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $instructions = sanitizeInput($_POST['instructions'] ?? '');
    $delivery_method = sanitizeInput($_POST['delivery'] ?? 'delivery');
    $payment_method = sanitizeInput($_POST['payment'] ?? 'cod');
    
    // Convert payment method values to database enum values
    $payment_method_map = [
        'cod' => 'cash_on_delivery',
        'gcash' => 'gcash',
        'card' => 'credit_card'
    ];
    $db_payment_method = $payment_method_map[$payment_method] ?? 'cash_on_delivery';
    
    // Get cart items from session/localStorage
    $cart_items = [];
    if (isset($_POST['cart_items'])) {
        $cart_items = json_decode($_POST['cart_items'], true);
    }
    
    // Validate required fields
    if (empty($name) || empty($phone) || empty($email) || empty($address)) {
        $error_message = 'Please fill in all required fields';
    } elseif (empty($cart_items)) {
        $error_message = 'Your cart is empty';
    } else {
        // Calculate order total
        $subtotal = 0;
        foreach ($cart_items as $item) {
            $price = floatval(preg_replace('/[^0-9.]/', '', $item['price']));
            $quantity = intval($item['quantity']);
            $subtotal += $price * $quantity;
        }
        
        $delivery_fee = ($delivery_method === 'delivery') ? 50 : 0;
        $total_amount = $subtotal + $delivery_fee;
        
        // Create the order
        $order_id = createOrder(
            $user_id,
            $total_amount,
            $db_payment_method,
            $address,
            $phone,
            $instructions
        );
        
        if ($order_id) {
            // Add order items
            $items_added = true;
            foreach ($cart_items as $item) {
                $product_id = intval($item['product_id']);
                $quantity = intval($item['quantity']);
                $price = floatval(preg_replace('/[^0-9.]/', '', $item['price']));
                $item_name = $item['name'] ?? 'Unknown Item';
                
                // Pass the item name as well, which will be used if product_id doesn't exist
                $item_added = addOrderItem($order_id, $product_id, $quantity, $price, $item_name);
                if (!$item_added) {
                    $items_added = false;
                    error_log("Failed to add order item: product_id={$product_id}, name={$item_name}, quantity={$quantity}, price={$price}");
                }
            }
            
            if ($items_added) {
                // Clear the cart
                clearCart($user_id);
                
                // Store order info in session for confirmation page
                $_SESSION['last_order_id'] = $order_id;
                $_SESSION['last_order_number'] = getOrderNumber($order_id);
                
                // Log success for debugging
                error_log("Order {$order_id} created successfully");
                
                // Redirect to confirmation page
                header("Location: confirmation.html");
                exit;
            } else {
                $error_message = 'Failed to add order items';
                error_log("Failed to add all items to order {$order_id}");
                deleteOrder($order_id);
                $order_id = null;
            }
        } else {
            $error_message = 'Error creating order';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Flavors at Your Fingertips - Checkout</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/checkout.css">
    <link rel="stylesheet" href="css/cart-sync.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* PayMongo Payment Modal Styles */
        .payment-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .payment-modal-content {
            background-color: white;
            max-width: 500px;
            width: 90%;
            margin: 0 auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            text-align: center;
        }
        
        .payment-header {
            margin-bottom: 20px;
        }
        
        .payment-header h2 {
            color: #E65100;
            margin-bottom: 10px;
        }
        
        .success-icon {
            margin: 20px 0;
        }
        
        .payment-status h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .status-message {
            margin-bottom: 20px;
            color: #666;
            line-height: 1.5;
        }
        
        .payment-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }
        
        .primary-btn {
            background-color: #E65100;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .primary-btn:hover {
            background-color: #D84315;
        }
        
        .secondary-btn {
            background-color: white;
            color: #E65100;
            padding: 12px 24px;
            border-radius: 6px;
            border: 1px solid #E65100;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .secondary-btn:hover {
            background-color: #FBE9E7;
        }
        
        .payment-buttons {
            display: flex;
            gap: 10px;
        }
        
        .direct-payment-btn {
            background-color: #4CAF50;
            color: white;
            padding: 12px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .direct-payment-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="checkout-main">
        <?php if ($order_success): ?>
            <div class="order-success">
                <i class="fas fa-check-circle"></i>
                <h2>Order Placed Successfully!</h2>
                <p>Your order #<?php echo $order_id; ?> has been placed successfully.</p>
                <p>You will receive a confirmation email shortly.</p>
                <div class="success-actions">
                    <a href="order-history.php" class="btn">View Order History</a>
                    <a href="menu.php" class="btn">Continue Shopping</a>
                </div>
            </div>
        <?php else: ?>
            <div class="checkout-container">
                <h1>Checkout</h1>
                
                <?php if (!empty($error_message)): ?>
                    <div class="error-message">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="checkout-grid">
                    <div class="checkout-form">
                        <h2>Delivery Information</h2>
                        <form id="deliveryForm" method="post" action="checkout.php">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="<?php echo $user['first_name'] . ' ' . $user['last_name']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo $user['phone'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Delivery Address</label>
                                <textarea id="address" name="address" required><?php echo $user['address'] ?? ''; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="instructions">Delivery Instructions (Optional)</label>
                                <textarea id="instructions" name="instructions"></textarea>
                            </div>

                            <h2>Delivery Method</h2>
                            <div class="delivery-options">
                                <label class="delivery-option">
                                    <input type="radio" name="delivery" value="delivery" checked>
                                    <span>Delivery</span>
                                </label>
                                <label class="delivery-option">
                                    <input type="radio" name="delivery" value="pickup">
                                    <span>Pickup</span>
                                </label>
                            </div>

                            <h2>Payment Method</h2>
                            <div class="payment-options">
                                <label class="payment-option">
                                    <input type="radio" name="payment" value="cod" checked>
                                    <span>Cash on Delivery</span>
                                </label>
                                <label class="payment-option">
                                    <input type="radio" name="payment" value="gcash">
                                    <span>GCash</span>
                                </label>
                                <label class="payment-option">
                                    <input type="radio" name="payment" value="card">
                                    <span>Credit/Debit Card</span>
                                </label>
                            </div>

                            <div class="form-group promo-code">
                                <label for="promo">Promo Code (Optional)</label>
                                <div class="promo-input">
                                    <input type="text" id="promo" name="promo">
                                    <button type="button" class="apply-promo">Apply</button>
                                </div>
                            </div>
                            
                            <!-- Hidden field for cart items -->
                            <input type="hidden" name="cart_items" id="cartItemsField">
                        </form>
                    </div>

                    <div class="order-summary">
                        <h2>Order Summary</h2>
                        <div class="order-items">
                            <!-- Order items will be dynamically added here by JavaScript -->
                        </div>
                        
                        <div class="summary-details">
                            <div class="summary-item">
                                <span>Subtotal</span>
                                <span class="subtotal">₱0.00</span>
                            </div>
                            <div class="summary-item">
                                <span>Delivery Fee</span>
                                <span class="delivery-fee">₱50.00</span>
                            </div>
                            <div class="summary-item promo-discount" style="display: none;">
                                <span>Promo Discount</span>
                                <span class="discount-amount">-₱0.00</span>
                            </div>
                            <div class="summary-item total">
                                <span>Total</span>
                                <span class="total-amount">₱0.00</span>
                            </div>
                        </div>

                        <div class="payment-buttons">
                            <button type="submit" form="deliveryForm" class="place-order-btn">Place Order</button>
                            <button type="button" id="directPaymentBtn" class="direct-payment-btn">Pay with GCash/Card</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Payment Transaction Modal -->
    <div class="payment-modal" id="paymentModal">
        <div class="payment-modal-content">
            <div class="payment-header">
                <h2>Payment Confirmation</h2>
                <div class="payment-method-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
            </div>
            <div class="payment-status">
                <div class="success-icon">
                    <i class="fas fa-check-circle" style="font-size: 50px; color: #4CAF50;"></i>
                </div>
                <h3>Order Created Successfully!</h3>
                <p class="status-message">Your order has been created. Click the button below to proceed to the payment page.</p>
                <p><strong>Payment Method:</strong> <span id="selectedPaymentMethod">PayMongo</span></p>
                <p><strong>Amount:</strong> <span id="paymentAmount">₱0.00</span></p>
                <div class="payment-actions">
                    <a href="#" id="goToPaymongoBtn" class="btn primary-btn">Go to Payment Page</a>
                    <button type="button" id="cancelPaymentBtn" class="btn secondary-btn">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script src="js/cart.js?v=<?php echo time(); ?>"></script>
    <script src="js/header.js?v=<?php echo time(); ?>"></script>
    <script src="js/checkout.js?v=<?php echo time(); ?>"></script>
    <script src="js/paymongo-checkout.js?v=<?php echo time(); ?>"></script>
    <script src="js/payment-toggle.js?v=<?php echo time(); ?>"></script>
    
    <!-- Debug script for Pay with GCash/Card button -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DEBUG INIT: Checkout page loaded, checking for direct payment button');
            
            const directPaymentBtn = document.getElementById('directPaymentBtn');
            if (directPaymentBtn) {
                console.log('DEBUG INIT: Direct payment button found:', directPaymentBtn);
                
                // Add a redundant click handler for debugging
                directPaymentBtn.addEventListener('click', function() {
                    console.log('DEBUG CLICK: Direct payment button clicked (from debug handler)');
                });
            } else {
                console.error('DEBUG INIT: Direct payment button NOT FOUND');
            }
        });
    </script>
</body>
</html>
