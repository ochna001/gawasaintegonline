<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/admin-functions.php');

// Check admin access
checkAdminAccess($conn);

// Handle order deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $orderId = $_GET['delete'];
    if (deleteOrder($conn, $orderId)) {
        $successMessage = "Order #$orderId has been deleted successfully.";
    } else {
        $errorMessage = "Failed to delete order #$orderId.";
    }
}

// Get all orders
$orders = getAllOrders($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Gawasainteg</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <div class="admin-logo">
                <h1>Local Flavors</h1>
                <p>Admin Dashboard</p>
            </div>
            <ul class="admin-menu">
                <li class="active"><a href="index.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="../index.html"><i class="fas fa-home"></i> Back to Site</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h2>Orders Management</h2>
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
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orders) > 0): ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['username'] ?? 'Guest'); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status status-<?php echo strtolower($order['status']); ?>">
                                            <?php echo $order['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status status-<?php echo strtolower($order['payment_status'] ?? 'pending'); ?>">
                                            <?php echo $order['payment_status'] ?? 'pending'; ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="edit-order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="index.php?delete=<?php echo $order['order_id']; ?>" class="btn btn-delete" 
                                           onclick="return confirm('Are you sure you want to delete this order?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="no-orders">No orders found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // Simple script to handle active menu item
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const menuItems = document.querySelectorAll('.admin-menu li');
            
            menuItems.forEach(item => {
                const link = item.querySelector('a');
                if (currentPath.includes(link.getAttribute('href'))) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
