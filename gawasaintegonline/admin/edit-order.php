<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/admin-functions.php');

// Check admin access
checkAdminAccess($conn);

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$orderId = $_GET['id'];
$order = getOrderById($conn, $orderId);

// If order not found, redirect to dashboard
if (!$order) {
    header("Location: index.php");
    exit();
}

// Handle form submission for updating order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['status'];
    
    if (updateOrderStatus($conn, $orderId, $newStatus)) {
        $successMessage = "Order status updated successfully!";
        // Refresh order data
        $order = getOrderById($conn, $orderId);
    } else {
        $errorMessage = "Failed to update order status.";
    }
}

// Handle form submission for updating payment status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $newPaymentStatus = $_POST['payment_status'];
    
    if (updatePaymentStatus($conn, $orderId, $newPaymentStatus)) {
        $successMessage = "Payment status updated successfully!";
        // Refresh order data
        $order = getOrderById($conn, $orderId);
    } else {
        $errorMessage = "Failed to update payment status.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Order #<?php echo $orderId; ?> - Gawasainteg Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/paymongo-admin.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <div class="admin-logo">
                <h1>Gawasainteg</h1>
                <p>Admin Dashboard</p>
            </div>
            <ul class="admin-menu">
                <li class="active"><a href="index.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="../index.php"><i class="fas fa-home"></i> Back to Site</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h2>Edit Order #<?php echo $orderId; ?></h2>
                <a href="index.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Back to Orders</a>
            </div>
            
            <?php if (isset($successMessage)): ?>
            <div class="alert alert-success">
                <?php echo $successMessage; ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger">
                <?php echo $errorMessage; ?>
            </div>
            <?php endif; ?>
            
            <div class="admin-card">
                <div class="order-details">
                    <div class="order-header">
                        <h3>Order Information</h3>
                    </div>
                    
                    <div class="order-info">
                        <div class="info-item">
                            <strong>Order ID:</strong> #<?php echo $order['order_id']; ?>
                        </div>
                        <div class="info-item">
                            <strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?>
                        </div>
                        <div class="info-item">
                            <strong>Customer:</strong> <?php echo htmlspecialchars($order['username'] ?? 'Guest'); ?>
                        </div>
                        <div class="info-item">
                            <strong>Email:</strong> <?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?>
                        </div>
                        <div class="info-item">
                            <strong>Total Amount:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?>
                        </div>
                        <div class="info-item">
                            <strong>Status:</strong> 
                            <span class="status status-<?php echo strtolower($order['status']); ?>">
                                <?php echo $order['status']; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <strong>Payment Status:</strong> 
                            <span class="status payment-status-<?php echo strtolower($order['payment_status'] ?? 'pending'); ?>">
                                <?php echo $order['payment_status'] ?? 'Pending'; ?>
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <strong>Payment Method:</strong> 
                            <?php 
                            $paymentMethod = $order['payment_method'] ?? 'cod';
                            $formattedPaymentMethod = '';
                            
                            if (strpos($paymentMethod, 'paymongo_') === 0) {
                                $paymentType = str_replace('paymongo_', '', $paymentMethod);
                                switch($paymentType) {
                                    case 'gcash':
                                        $formattedPaymentMethod = 'GCash via PayMongo';
                                        break;
                                    case 'card':
                                        $formattedPaymentMethod = 'Credit/Debit Card via PayMongo';
                                        break;
                                    case 'dob':
                                        $formattedPaymentMethod = 'Online Banking via PayMongo';
                                        break;
                                    default:
                                        $formattedPaymentMethod = ucfirst($paymentType) . ' via PayMongo';
                                }
                            } else {
                                $formattedPaymentMethod = $paymentMethod === 'cod' ? 'Cash on Delivery' : ucfirst($paymentMethod);
                            }
                            
                            echo htmlspecialchars($formattedPaymentMethod); 
                            ?>
                        </div>
                    </div>
                    
                    <div class="order-address">
                        <h4>Delivery Information</h4>
                        <p>
                            <strong>Address:</strong> <?php echo htmlspecialchars($order['address'] ?? 'N/A'); ?><br>
                            <strong>Phone:</strong> <?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?>
                        </p>
                    </div>
                    
                    <?php if (strpos($order['payment_method'] ?? '', 'paymongo_') === 0): ?>
                    <div class="paymongo-info">
                        <h4>PayMongo Payment Details</h4>
                        <p>
                            <strong>Session ID:</strong> 
                            <?php echo !empty($order['paymongo_session_id']) ? 
                                    htmlspecialchars($order['paymongo_session_id']) : 
                                    '<span class="text-muted">Not available</span>'; ?>
                        </p>
                        <p>
                            <strong>Payment ID:</strong> 
                            <?php echo !empty($order['paymongo_payment_id']) ? 
                                    htmlspecialchars($order['paymongo_payment_id']) : 
                                    '<span class="text-muted">Not available</span>'; ?>
                        </p>
                        <p>
                            <strong>Payment Method Used:</strong> 
                            <?php echo !empty($order['paymongo_payment_method']) ? 
                                    htmlspecialchars(ucfirst($order['paymongo_payment_method'])) : 
                                    '<span class="text-muted">Not available</span>'; ?>
                        </p>
                        
                        <?php if (!empty($order['paymongo_session_id'])): ?>
                        <div class="paymongo-actions">
                            <a href="https://dashboard.paymongo.com/checkout-sessions/<?php echo htmlspecialchars($order['paymongo_session_id']); ?>" 
                               class="btn btn-sm" target="_blank">
                                <i class="fas fa-external-link-alt"></i> View in PayMongo Dashboard
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="order-update">
                        <h4>Update Order Status</h4>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="status">Status:</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="Pending" <?php echo ($order['status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Processing" <?php echo ($order['status'] === 'Processing') ? 'selected' : ''; ?>>Processing</option>
                                    <option value="Completed" <?php echo ($order['status'] === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                    <option value="Cancelled" <?php echo ($order['status'] === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                        </form>
                    </div>
                    
                    <div class="order-update">
                        <h4>Update Payment Status</h4>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="payment_status">Payment Status:</label>
                                <select name="payment_status" id="payment_status" class="form-control">
                                    <option value="Pending" <?php echo (($order['payment_status'] ?? '') === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Paid" <?php echo (($order['payment_status'] ?? '') === 'Paid') ? 'selected' : ''; ?>>Paid</option>
                                    <option value="Failed" <?php echo (($order['payment_status'] ?? '') === 'Failed') ? 'selected' : ''; ?>>Failed</option>
                                    <option value="Refunded" <?php echo (($order['payment_status'] ?? '') === 'Refunded') ? 'selected' : ''; ?>>Refunded</option>
                                </select>
                            </div>
                            <button type="submit" name="update_payment" class="btn btn-primary">Update Payment Status</button>
                        </form>
                    </div>
                </div>
                
                <div class="order-items">
                    <h3>Order Items</h3>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['items'] as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-right"><strong>Total:</strong></td>
                                <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="order-actions">
                    <a href="index.php?delete=<?php echo $order['order_id']; ?>" class="btn btn-delete" 
                       onclick="return confirm('Are you sure you want to delete this order? This cannot be undone.');">
                        <i class="fas fa-trash"></i> Delete Order
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
