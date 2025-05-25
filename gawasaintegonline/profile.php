<?php
// Include functions file
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // Store current page as redirect URL
    $_SESSION['redirect_url'] = $_SERVER['PHP_SELF'];
    // Redirect to login page
    header('Location: login.php');
    exit;
}

// Get user profile data
$userProfile = getUserProfile($_SESSION['user_id']);

// Process account deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_account'])) {
    $result = deleteUserAccount($_SESSION['user_id']);
    
    if ($result['success']) {
        // Account deletion was successful, user is already logged out
        // Just redirect to homepage with a message
        session_start();
        $_SESSION['message'] = $result['message'];
        $_SESSION['message_type'] = 'success';
        header('Location: index.html');
        exit;
    } else {
        $message = $result['message'];
        $messageType = 'error';
    }
}

// Process profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $userData = [
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'phone' => $_POST['phone'],
        'address' => $_POST['address']
    ];
    
    // Include latitude and longitude if provided
    if (isset($_POST['latitude']) && isset($_POST['longitude'])) {
        $userData['latitude'] = $_POST['latitude'];
        $userData['longitude'] = $_POST['longitude'];
    }
    
    $result = updateUserProfile($_SESSION['user_id'], $userData);
    
    if ($result['success']) {
        $message = $result['message'];
        $messageType = 'success';
        // Refresh user profile data
        $userProfile = getUserProfile($_SESSION['user_id']);
    } else {
        $message = $result['message'];
        $messageType = 'error';
    }
}

// Process password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if ($newPassword !== $confirmPassword) {
        $passwordMessage = 'New passwords do not match';
        $passwordMessageType = 'error';
    } else {
        $result = changePassword($_SESSION['user_id'], $currentPassword, $newPassword);
        $passwordMessage = $result['message'];
        $passwordMessageType = $result['success'] ? 'success' : 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Flavors at Your Fingertips - Account</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/account.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Leaflet CSS and JS for maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
    <!-- Leaflet Geocoder -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <style>
        #map-container {
            margin-top: 15px;
            position: relative;
            height: 300px;
            width: 100%;
            border-radius: 5px;
            overflow: hidden;
        }
        #map {
            height: 100%;
            width: 100%;
        }
        .map-instructions {
            background-color: rgba(255, 255, 255, 0.8);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
            font-size: 14px;
            color: #555;
        }
        .location-btn {
            background-color: #FF6B35;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 8px;
            display: flex;
            align-items: center;
            font-size: 14px;
        }
        .location-btn i {
            margin-right: 5px;
        }
        
        /* Order history styles */
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .orders-table th {
            background-color: #f8f8f8;
            text-align: left;
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
            font-weight: 600;
            color: #333;
        }
        
        .orders-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            color: #555;
        }
        
        .order-row {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .order-row:hover {
            background-color: #f5f5f5;
        }
        
        .order-row.active {
            background-color: #f0f7ff;
        }
        
        .order-number {
            font-weight: 600;
            color: #FF6B35;
        }
        
        /* Status badges */
        .status-badge, .payment-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }
        
        .status-badge.pending {
            background-color: #FFF3CD;
            color: #856404;
        }
        
        .status-badge.processing {
            background-color: #CCE5FF;
            color: #004085;
        }
        
        .status-badge.out-for-delivery {
            background-color: #D1ECF1;
            color: #0C5460;
        }
        
        .status-badge.delivered {
            background-color: #D4EDDA;
            color: #155724;
        }
        
        .status-badge.cancelled {
            background-color: #F8D7DA;
            color: #721C24;
        }
        
        /* Payment badges */
        .payment-badge.pending {
            background-color: #FFF3CD;
            color: #856404;
        }
        
        .payment-badge.paid {
            background-color: #D4EDDA;
            color: #155724;
        }
        
        .payment-badge.failed {
            background-color: #F8D7DA;
            color: #721C24;
        }
        
        .payment-badge.refunded {
            background-color: #D1ECF1;
            color: #0C5460;
        }
        
        /* Order details */
        .order-details {
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        
        .order-items {
            margin-bottom: 20px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .items-table th {
            background-color: #eee;
            padding: 8px 10px;
            text-align: left;
            font-size: 13px;
        }
        
        .items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        
        .order-summary {
            margin-top: 15px;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .summary-row.total {
            font-weight: 700;
            font-size: 16px;
            color: #333;
            border-top: 1px solid #ddd;
            padding-top: 5px;
            margin-top: 5px;
        }
        
        .delivery-info {
            margin-top: 20px;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        
        .delivery-info h4 {
            margin-bottom: 10px;
        }
        
        .delivery-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .empty-orders {
            text-align: center;
            padding: 40px 0;
            color: #888;
        }
        
        .empty-orders i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
        }
        
        .empty-orders p {
            margin-bottom: 20px;
            font-size: 16px;
        }
        
        .cta-button {
            display: inline-block;
            background-color: #FF6B35;
            color: white;
            padding: 8px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.2s;
        }
        
        .cta-button:hover {
            background-color: #e55a25;
        }
        
        /* Responsive styles for orders section */
        @media (max-width: 992px) {
            .orders-table th, .orders-table td {
                padding: 10px 12px;
                font-size: 14px;
            }
            
            .status-badge, .payment-badge {
                padding: 3px 6px;
                font-size: 11px;
            }
        }
        
        @media (max-width: 768px) {
            .orders-table {
                width: 100%;
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .order-details-row td {
                padding: 10px;
            }
            
            .items-table {
                font-size: 13px;
            }
            
            .items-table th, .items-table td {
                padding: 6px 8px;
            }
        }
        
        @media (max-width: 576px) {
            /* Card-style layout for very small screens */
            .orders-table thead {
                display: none;
            }
            
            .orders-table, .orders-table tbody, .orders-table tr, .orders-table td {
                display: block;
                width: 100%;
                text-align: left;
            }
            
            .orders-table tr {
                margin-bottom: 15px;
                border: 1px solid #eee;
                border-radius: 5px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                position: relative;
                padding-bottom: 10px;
            }
            
            .orders-table td {
                border: none;
                padding: 8px 15px;
                padding-left: 40%;
                position: relative;
                text-align: right;
            }
            
            .orders-table td:before {
                content: attr(data-label);
                position: absolute;
                left: 15px;
                width: 40%;
                padding-right: 10px;
                font-weight: 600;
                text-align: left;
                color: #555;
            }
            
            /* Make order detail rows full width */
            .order-details-row {
                display: block !important;
                width: 100%;
            }
            
            .order-details {
                padding: 10px;
            }
            
            .summary-row {
                font-size: 13px;
            }
            
            .summary-row.total {
                font-size: 15px;
            }
            
            .delivery-info p {
                font-size: 13px;
            }
        }
        
        /* Account tabs styling */
        .account-section {
            display: none;
        }
        
        .account-section.active {
            display: block;
        }
        
        .account-nav a {
            display: block;
            padding: 12px 20px;
            color: #555;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
        }
        
        .account-nav a:hover {
            background-color: #f5f5f5;
            color: #FF6B35;
        }
        
        .account-nav a.active {
            border-left-color: #FF6B35;
            background-color: #fff5f1;
            color: #FF6B35;
            font-weight: 600;
        }
        
        /* Delete account button styling */
        .delete-account-btn {
            display: block;
            width: 100%;
            text-align: left;
            padding: 12px 20px;
            background: none;
            border: none;
            border-left: 3px solid transparent;
            margin-top: 20px;
            color: #dc3545;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .delete-account-btn:hover {
            background-color: #fff5f5;
            border-left-color: #dc3545;
        }
        
        /* Modal styles for confirmation */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-container {
            background-color: white;
            width: 90%;
            max-width: 500px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .modal-header {
            background-color: #f8f8f8;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #333;
            font-size: 18px;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #999;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .modal-btn {
            padding: 8px 15px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .modal-btn.cancel {
            background-color: #f8f8f8;
            color: #333;
        }
        
        .modal-btn.danger {
            background-color: #dc3545;
            color: white;
        }
        
        .modal-btn.danger:disabled {
            background-color: #e99;
            cursor: not-allowed;
        }
        
        .confirmation-input {
            margin-top: 15px;
        }
        
        .confirmation-input input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 5px;
        }
        
        .confirmation-checkbox {
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .warning-text {
            color: #dc3545;
            font-weight: 600;
            margin: 15px 0;
        }
        
        .deletion-progress {
            margin-top: 15px;
            padding: 10px;
            background-color: #fff5f5;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .step-indicator {
            display: flex;
            margin-top: 15px;
            justify-content: center;
            gap: 5px;
        }
        
        .step {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #ddd;
        }
        
        .step.active {
            background-color: #FF6B35;
        }
    </style>
</head>
<body class="logged-in">
    <?php include_once 'includes/header.php'; ?>

    <main class="account-main">
        <div class="account-container">
            <?php if (isset($message)): ?>
            <div class="alert <?php echo $messageType === 'success' ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <div class="account-sidebar">
                <div class="user-profile">
                    <div class="profile-image">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h2 id="userName"><?php echo $userProfile['first_name'] . ' ' . $userProfile['last_name']; ?></h2>
                    <p id="userEmail"><?php echo $userProfile['email']; ?></p>
                </div>
                <nav class="account-nav">
                    <a href="#profile" class="active">Profile</a>
                    <a href="#orders">Orders</a>
                    <a href="logout.php" id="logoutBtn">Logout</a>
                    <button type="button" id="deleteAccountBtn" class="delete-account-btn">Delete Account</button>
                </nav>
            </div>

            <div class="account-content">
                <!-- Profile Section -->
                <section id="profile" class="account-section active">
                    <h2>Profile Information</h2>
                    <form id="profileForm" class="account-form" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo $userProfile['first_name']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo $userProfile['last_name']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo $userProfile['email']; ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo $userProfile['phone']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="address">Delivery Address</label>
                            <textarea id="address" name="address" rows="3"><?php echo $userProfile['address']; ?></textarea>
                            <p class="map-instructions"><i class="fas fa-info-circle"></i> Use the map below to select your exact location. You can search for an address or click on the map to set your delivery location.</p>
                            <div id="map-container">
                                <div id="map"></div>
                            </div>
                            <button type="button" class="location-btn" id="current-location-btn">
                                <i class="fas fa-map-marker-alt"></i> Use My Current Location
                            </button>
                            <input type="hidden" id="latitude" name="latitude" value="<?php echo $userProfile['latitude'] ?? ''; ?>">
                            <input type="hidden" id="longitude" name="longitude" value="<?php echo $userProfile['longitude'] ?? ''; ?>">
                        </div>
                        <button type="submit" name="update_profile" class="save-btn">Save Changes</button>
                    </form>
                </section>

                <!-- Orders Section -->
                <section id="orders" class="account-section">
                    <h2>Order History</h2>
                    <div class="orders-list">
                        <?php
                        // Get user orders
                        $orders = getUserOrders($_SESSION['user_id']);
                        
                        if (!empty($orders)) {
                            // Display orders in a table
                            echo '<table class="orders-table">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                    </tr>
                                </thead>
                                <tbody>';
                                
                            foreach ($orders as $order) {
                                // Get order details including items
                                $orderDetails = getOrderDetails($order['order_id']);
                                
                                // Count total items
                                $itemCount = 0;
                                if (isset($orderDetails['items'])) {
                                    $itemCount = count($orderDetails['items']);
                                }
                                
                                // Format date
                                $orderDate = date('M d, Y', strtotime($order['created_at']));
                                
                                // Map status to user-friendly text and colors
                                $statusMap = [
                                    'pending' => '<span class="status-badge pending">Pending</span>',
                                    'processing' => '<span class="status-badge processing">Processing</span>',
                                    'out_for_delivery' => '<span class="status-badge out-for-delivery">Out for Delivery</span>',
                                    'delivered' => '<span class="status-badge delivered">Delivered</span>',
                                    'cancelled' => '<span class="status-badge cancelled">Cancelled</span>'
                                ];
                                
                                // Map payment status to user-friendly text and colors
                                $paymentStatusMap = [
                                    'pending' => '<span class="payment-badge pending">Pending</span>',
                                    'paid' => '<span class="payment-badge paid">Paid</span>',
                                    'failed' => '<span class="payment-badge failed">Failed</span>',
                                    'refunded' => '<span class="payment-badge refunded">Refunded</span>'
                                ];
                                
                                $status = isset($statusMap[$order['status']]) ? $statusMap[$order['status']] : $order['status'];
                                $paymentStatus = isset($paymentStatusMap[$order['payment_status']]) ? $paymentStatusMap[$order['payment_status']] : $order['payment_status'];
                                
                                echo '<tr class="order-row" data-order-id="' . $order['order_id'] . '">
                                    <td class="order-number" data-label="Order #">' . $order['order_id'] . '</td>
                                    <td data-label="Date">' . $orderDate . '</td>
                                    <td data-label="Items">' . $itemCount . ' item(s)</td>
                                    <td data-label="Total">₱' . number_format($order['total_amount'], 2) . '</td>
                                    <td data-label="Status">' . $status . '</td>
                                    <td data-label="Payment">' . $paymentStatus . '</td>
                                </tr>';
                                
                                // Order details section (hidden by default, shown on click)
                                echo '<tr class="order-details-row" id="order-details-' . $order['order_id'] . '" style="display: none;">
                                    <td colspan="6">
                                        <div class="order-details">
                                            <h4>Order Details</h4>
                                            <div class="order-items">';
                                            
                                if (isset($orderDetails['items']) && !empty($orderDetails['items'])) {
                                    echo '<table class="items-table">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Quantity</th>
                                                <th>Price</th>
                                                <th>Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>';
                                        
                                    foreach ($orderDetails['items'] as $item) {
                                        $subtotal = $item['quantity'] * $item['price'];
                                        $itemName = isset($item['item_name']) ? $item['item_name'] : 
                                                   (isset($item['name']) ? $item['name'] : 'Item #' . $item['product_id']);
                                        echo '<tr>
                                            <td>' . $itemName . '</td>
                                            <td>' . $item['quantity'] . '</td>
                                            <td>₱' . number_format($item['price'], 2) . '</td>
                                            <td>₱' . number_format($subtotal, 2) . '</td>
                                        </tr>';
                                    }
                                    
                                    echo '</tbody></table>';
                                } else {
                                    echo '<p>No items found for this order.</p>';
                                }
                                
                                echo '</div>
                                            <div class="order-summary">
                                                <div class="summary-row">
                                                    <span>Subtotal:</span>
                                                    <span>₱' . number_format($order['total_amount'] - 50, 2) . '</span>
                                                </div>
                                                <div class="summary-row">
                                                    <span>Delivery Fee:</span>
                                                    <span>₱50.00</span>
                                                </div>
                                                <div class="summary-row total">
                                                    <span>Total:</span>
                                                    <span>₱' . number_format($order['total_amount'], 2) . '</span>
                                                </div>
                                            </div>
                                            <div class="delivery-info">
                                                <h4>Delivery Information</h4>
                                                <p><strong>Address:</strong> ' . $order['delivery_address'] . '</p>
                                                <p><strong>Payment Method:</strong> ' . ucfirst(str_replace('_', ' ', $order['payment_method'])) . '</p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>';
                            }
                            
                            echo '</tbody></table>
                            
                            <script>
                            document.querySelectorAll(".order-row").forEach(row => {
                                row.addEventListener("click", function() {
                                    const orderId = this.getAttribute("data-order-id");
                                    const detailsRow = document.getElementById("order-details-" + orderId);
                                    
                                    if (detailsRow.style.display === "none" || detailsRow.style.display === "") {
                                        detailsRow.style.display = "table-row";
                                        this.classList.add("active");
                                    } else {
                                        detailsRow.style.display = "none";
                                        this.classList.remove("active");
                                    }
                                });
                            });
                            </script>';
                        } else {
                            // No orders found
                            echo '<div class="empty-orders">
                                <i class="fas fa-shopping-bag"></i>
                                <p>No orders yet</p>
                                <a href="menu.php" class="cta-button">Start Ordering</a>
                            </div>';
                        }
                        ?>
                    </div>
                </section>

                <!-- Addresses Section -->
                <section id="addresses" class="account-section">
                    <h2>Saved Addresses</h2>
                    <div class="addresses-list">
                        <!-- Addresses will be dynamically added here -->
                        <div class="empty-addresses">
                            <i class="fas fa-map-marker-alt"></i>
                            <p>No saved addresses</p>
                            <button class="add-address-btn">Add New Address</button>
                        </div>
                    </div>
                </section>

                <!-- Settings Section -->
                <section id="settings" class="account-section">
                    <h2>Account Settings</h2>
                    <?php if (isset($passwordMessage)): ?>
                    <div class="alert <?php echo $passwordMessageType === 'success' ? 'success' : 'error'; ?>">
                        <?php echo $passwordMessage; ?>
                    </div>
                    <?php endif; ?>
                    <form id="settingsForm" class="account-form" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="form-group">
                            <label for="notifications">Email Notifications</label>
                            <div class="toggle-switch">
                                <input type="checkbox" id="notifications" name="notifications" checked>
                                <span class="toggle-slider"></span>
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="save-btn">Save Changes</button>
                    </form>
                </section>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p>Email: info@localflavors.com</p>
                <p>Phone: (02) 123-4567</p>
            </div>
            <div class="footer-section">
                <h3>Follow Us</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Hours</h3>
                <p>Monday - Sunday</p>
                <p>8:00 AM - 9:00 PM</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 Local Flavors. All rights reserved.</p>
        </div>
    </footer>

    <!-- Delete Account Confirmation Modals -->
    <div class="modal-overlay" id="deleteAccountModal">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Delete Account</h3>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="deleteStep1" class="delete-step">
                    <p>Are you sure you want to delete your account?</p>
                    <p>This action cannot be undone and all your personal information will be permanently removed.</p>
                    <div class="warning-text">
                        <i class="fas fa-exclamation-triangle"></i> Warning: Your order history will be anonymized but not deleted.
                    </div>
                    <div class="confirmation-checkbox">
                        <input type="checkbox" id="confirmFirstStep">
                        <label for="confirmFirstStep">Yes, I want to delete my account</label>
                    </div>
                </div>
                
                <div id="deleteStep2" class="delete-step" style="display: none;">
                    <p>Please review what will happen when you delete your account:</p>
                    <ul>
                        <li>Your personal information will be permanently deleted</li>
                        <li>Your cart items will be removed</li>
                        <li>Your order history will be preserved but anonymized</li>
                        <li>You will be immediately logged out</li>
                        <li>This action CANNOT be reversed</li>
                    </ul>
                    <div class="confirmation-checkbox">
                        <input type="checkbox" id="confirmSecondStep">
                        <label for="confirmSecondStep">I understand the consequences</label>
                    </div>
                </div>
                
                <div class="step-indicator">
                    <div class="step step1 active"></div>
                    <div class="step step2"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" id="cancelDeleteBtn">Cancel</button>
                <button type="button" class="modal-btn danger" id="nextStepBtn">Continue</button>
            </div>
        </div>
    </div>
    
    <!-- Processing Deletion Modal -->
    <div class="modal-overlay" id="processingDeletionModal">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Deleting Account</h3>
            </div>
            <div class="modal-body">
                <div class="deletion-progress">
                    <span>Deleting your account...</span>
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
                <p>Please do not close this window.</p>
            </div>
        </div>
    </div>
    
    <script src="js/main.js"></script>
    <script src="js/cart.js"></script>
    <script src="js/header.js"></script>
    <script src="js/account.js"></script>
    <script src="js/map.js"></script>
    
    <script>
        // Delete account functionality
        document.addEventListener('DOMContentLoaded', function() {
            const deleteAccountBtn = document.getElementById('deleteAccountBtn');
            const deleteAccountModal = document.getElementById('deleteAccountModal');
            const processingModal = document.getElementById('processingDeletionModal');
            const modalClose = document.querySelector('.modal-close');
            const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
            const nextStepBtn = document.getElementById('nextStepBtn');
            
            // Step elements
            const step1 = document.getElementById('deleteStep1');
            const step2 = document.getElementById('deleteStep2');
            
            // Step indicators
            const stepIndicators = document.querySelectorAll('.step');
            
            // Checkboxes
            const confirmFirstStep = document.getElementById('confirmFirstStep');
            const confirmSecondStep = document.getElementById('confirmSecondStep');
            
            let currentStep = 1;
            
            // Show the modal when delete account button is clicked
            deleteAccountBtn.addEventListener('click', function() {
                deleteAccountModal.style.display = 'flex';
                resetDeleteProcess();
            });
            
            // Close the modal
            modalClose.addEventListener('click', function() {
                deleteAccountModal.style.display = 'none';
            });
            
            cancelDeleteBtn.addEventListener('click', function() {
                deleteAccountModal.style.display = 'none';
            });
            
            // Handle continue button click
            nextStepBtn.addEventListener('click', function() {
                if (currentStep === 1) {
                    if (confirmFirstStep.checked) {
                        showStep(2);
                    } else {
                        alert('Please confirm that you want to delete your account');
                    }
                } else if (currentStep === 2) {
                    if (confirmSecondStep.checked) {
                        // Final step - submit the deletion request
                        submitAccountDeletion();
                    } else {
                        alert('Please confirm that you understand the consequences');
                    }
                }
            });
            
            // Update button text based on step
            function updateButtonText() {
                if (currentStep === 2) {
                    nextStepBtn.textContent = 'Delete My Account';
                    nextStepBtn.classList.add('final-delete');
                } else {
                    nextStepBtn.textContent = 'Continue';
                    nextStepBtn.classList.remove('final-delete');
                }
            }
            
            // Show a specific step
            function showStep(step) {
                currentStep = step;
                
                // Hide all steps
                step1.style.display = 'none';
                step2.style.display = 'none';
                
                // Show the current step
                if (step === 1) step1.style.display = 'block';
                if (step === 2) step2.style.display = 'block';
                
                // Update step indicators
                stepIndicators.forEach((indicator, index) => {
                    if (index < step) {
                        indicator.classList.add('active');
                    } else {
                        indicator.classList.remove('active');
                    }
                });
                
                updateButtonText();
            }
            
            // Reset the delete process
            function resetDeleteProcess() {
                confirmFirstStep.checked = false;
                confirmSecondStep.checked = false;
                showStep(1);
            }
            
            // Submit the account deletion request
            function submitAccountDeletion() {
                // Show processing modal
                deleteAccountModal.style.display = 'none';
                processingModal.style.display = 'flex';
                
                // Create form data
                const formData = new FormData();
                formData.append('delete_account', 'true');
                
                // Send the request
                fetch('<?php echo $_SERVER["PHP_SELF"]; ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(html => {
                    // Clear localStorage data before redirecting
                    localStorage.removeItem('user');
                    localStorage.removeItem('isLoggedIn');
                    localStorage.removeItem('remember_token');
                    localStorage.removeItem('user_id');
                    localStorage.removeItem('user_name');
                    localStorage.removeItem('user_email');
                    localStorage.removeItem('user_role');
                    localStorage.removeItem('cart');
                    
                    // Redirect to homepage after successful deletion
                    window.location.href = 'index.html';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again later.');
                    processingModal.style.display = 'none';
                });
            }
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === deleteAccountModal) {
                    deleteAccountModal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
