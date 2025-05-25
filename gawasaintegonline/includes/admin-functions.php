<?php
// Admin-specific functions

// Function to check if a user is an admin
function isAdmin($conn, $userId) {
    $sql = "SELECT role FROM users WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['role'] === 'admin';
    }
    
    return false;
}

// Function to check if a user is an admin and redirect if not
function checkAdminAccess($conn) {
    // If user is not logged in or not an admin, redirect to the home page
    if (!isset($_SESSION['user_id']) || !isAdmin($conn, $_SESSION['user_id'])) {
        header("Location: ../index.php");
        exit();
    }
}

// Function to get all orders
function getAllOrders($conn) {
    $sql = "SELECT o.*, u.first_name, u.last_name, u.email FROM orders o
            LEFT JOIN users u ON o.user_id = u.user_id
            ORDER BY o.created_at DESC";
    $result = mysqli_query($conn, $sql);
    
    $orders = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Add username for display (combine first and last name)
        $row['username'] = $row['first_name'] . ' ' . $row['last_name'];
        
        // Map created_at to order_date for consistency
        if (isset($row['created_at'])) {
            $row['order_date'] = $row['created_at'];
        }
        
        $orders[] = $row;
    }
    
    return $orders;
}

// Function to get a single order by ID
function getOrderById($conn, $orderId) {
    $sql = "SELECT o.*, u.first_name, u.last_name, u.email FROM orders o
            LEFT JOIN users u ON o.user_id = u.user_id
            WHERE o.order_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $orderId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($order = mysqli_fetch_assoc($result)) {
        // Add username for display (combine first and last name)
        $order['username'] = $order['first_name'] . ' ' . $order['last_name'];
        
        // Map created_at to order_date for consistency
        if (isset($order['created_at'])) {
            $order['order_date'] = $order['created_at'];
        }
        
        // Get order items
        $sql = "SELECT oi.*, p.name as item_name, p.price 
                FROM order_items oi
                JOIN products p ON oi.product_id = p.product_id
                WHERE oi.order_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $orderId);
        mysqli_stmt_execute($stmt);
        $itemsResult = mysqli_stmt_get_result($stmt);
        
        $order['items'] = [];
        while ($item = mysqli_fetch_assoc($itemsResult)) {
            $order['items'][] = $item;
        }
        
        return $order;
    }
    
    return null;
}

// Function to update order status
function updateOrderStatus($conn, $orderId, $status) {
    $sql = "UPDATE orders SET status = ?, updated_at = NOW() WHERE order_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $status, $orderId);
    $result = mysqli_stmt_execute($stmt);
    
    if ($result) {
        // Add entry to order status history (if you have this table)
        // This is optional and can be implemented later if needed
        // addOrderStatusHistory($conn, $orderId, $status, 'Status updated by admin', $_SESSION['user_id']);
    }
    
    return $result;
}

// Function to update payment status
function updatePaymentStatus($conn, $orderId, $paymentStatus) {
    $sql = "UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE order_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $paymentStatus, $orderId);
    return mysqli_stmt_execute($stmt);
}

// Function to delete an order
function deleteOrder($conn, $orderId) {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Delete order items first (foreign key constraint)
        $sql = "DELETE FROM order_items WHERE order_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $orderId);
        $result1 = mysqli_stmt_execute($stmt);
        
        if (!$result1) {
            throw new Exception("Failed to delete order items: " . mysqli_error($conn));
        }
        
        // Then delete the order
        $sql = "DELETE FROM orders WHERE order_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $orderId);
        $result2 = mysqli_stmt_execute($stmt);
        
        if (!$result2) {
            throw new Exception("Failed to delete order: " . mysqli_error($conn));
        }
        
        // Commit transaction
        mysqli_commit($conn);
        return true;
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        error_log("Error deleting order #$orderId: " . $e->getMessage());
        return false;
    }
}
?>
