<?php
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // Redirect to login page with return URL
    header('Location: login.php?redirect=order-history.php');
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get all orders for the user
$orders = getUserOrders($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Flavors at Your Fingertips - Order History</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/orders.css">
    <link rel="stylesheet" href="css/cart-sync.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Additional styles for order history */
        .order-history-main {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 1rem;
        }
        
        .order-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background-color: #f8f8f8;
            border-bottom: 1px solid #eee;
        }
        
        .order-date {
            color: #666;
            font-size: 0.9rem;
        }
        
        .order-number {
            font-weight: bold;
            color: #ff6b35;
        }
        
        .order-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #ffebcc;
            color: #ff9900;
        }
        
        .status-processing {
            background-color: #e6f3ff;
            color: #0066cc;
        }
        
        .status-out_for_delivery {
            background-color: #e6fff2;
            color: #00cc66;
        }
        
        .status-delivered {
            background-color: #e6ffe6;
            color: #009933;
        }
        
        .status-cancelled {
            background-color: #ffe6e6;
            color: #cc0000;
        }
        
        .order-details {
            padding: 1rem;
        }
        
        .order-items {
            margin-bottom: 1rem;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-total {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        
        .order-actions {
            padding: 1rem;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            border-top: 1px solid #eee;
        }
        
        .order-btn {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-size: 0.9rem;
            cursor: pointer;
            border: none;
        }
        
        .view-details-btn {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .track-order-btn {
            background-color: #ff6b35;
            color: white;
        }
        
        .cancel-order-btn {
            background-color: #fff;
            color: #cc0000;
            border: 1px solid #cc0000;
        }
        
        .empty-orders {
            text-align: center;
            padding: 3rem 1rem;
        }
        
        .empty-orders i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
        }
        
        /* Modal for order details */
        .order-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .order-modal-content {
            background-color: #fff;
            width: 90%;
            max-width: 800px;
            border-radius: 8px;
            overflow: hidden;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background-color: #f8f8f8;
            border-bottom: 1px solid #eee;
        }
        
        .modal-close {
            cursor: pointer;
            font-size: 1.5rem;
        }
        
        .modal-body {
            padding: 1rem;
            overflow-y: auto;
        }
        
        .item-details {
            display: flex;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 1rem;
        }
        
        .item-info {
            flex: 1;
        }
        
        .tracking-info {
            margin-top: 1rem;
        }
        
        .status-timeline {
            margin-top: 1rem;
            position: relative;
        }
        
        .timeline-item {
            display: flex;
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .timeline-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            z-index: 1;
        }
        
        .timeline-icon.active {
            background-color: #ff6b35;
            color: white;
        }
        
        .timeline-line {
            position: absolute;
            left: 15px;
            top: 30px;
            bottom: -10px;
            width: 2px;
            background-color: #eee;
            z-index: 0;
        }
        
        .timeline-content {
            flex: 1;
        }
        
        .timeline-date {
            color: #666;
            font-size: 0.8rem;
        }
        
        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-status {
                margin-top: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="order-history-main">
        <h1>Order History</h1>
        
        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <i class="fas fa-receipt"></i>
                <h2>No Orders Yet</h2>
                <p>You haven't placed any orders yet.</p>
                <a href="menu.php" class="cta-button">Browse Menu</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-date"><?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?></div>
                            <div class="order-number">Order #<?php echo $order['order_number']; ?></div>
                        </div>
                        <div class="order-status status-<?php echo $order['status']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                        </div>
                    </div>
                    
                    <div class="order-details">
                        <?php 
                        // Get order details including items
                        $orderDetails = getOrderDetails($order['order_id']);
                        
                        // Calculate total items
                        $totalItems = 0;
                        foreach ($orderDetails['items'] as $item) {
                            $totalItems += $item['quantity'];
                        }
                        ?>
                        
                        <div class="order-summary">
                            <p><?php echo $totalItems; ?> item(s) • Total: ₱<?php echo number_format($order['total_amount'], 2); ?></p>
                        </div>
                    </div>
                    
                    <div class="order-actions">
                        <button class="order-btn view-details-btn" data-order-id="<?php echo $order['order_id']; ?>">
                            View Details
                        </button>
                        
                        <?php if ($order['status'] === 'pending' || $order['status'] === 'processing'): ?>
                            <button class="order-btn cancel-order-btn" data-order-id="<?php echo $order['order_id']; ?>">
                                Cancel Order
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($order['status'] === 'out_for_delivery'): ?>
                            <button class="order-btn track-order-btn" data-order-id="<?php echo $order['order_id']; ?>">
                                Track Order
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
    
    <!-- Order Details Modal -->
    <div class="order-modal" id="orderModal">
        <div class="order-modal-content">
            <div class="modal-header">
                <h2>Order Details</h2>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Order details will be loaded here via AJAX -->
                <div class="loading">
                    <div class="loader"></div>
                    <p>Loading order details...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Order Modal -->
    <div class="order-modal" id="cancelOrderModal">
        <div class="order-modal-content">
            <div class="modal-header">
                <h2>Cancel Order</h2>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this order?</p>
                <form id="cancelOrderForm">
                    <div class="form-group">
                        <label for="cancelReason">Reason for cancellation (optional)</label>
                        <textarea id="cancelReason" name="reason"></textarea>
                    </div>
                    <input type="hidden" id="cancelOrderId" name="order_id">
                    <div class="form-actions">
                        <button type="button" class="order-btn view-details-btn cancel-action">No, Keep Order</button>
                        <button type="submit" class="order-btn cancel-order-btn">Yes, Cancel Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script src="js/header.js?v=<?php echo time(); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const orderModal = document.getElementById('orderModal');
            const cancelOrderModal = document.getElementById('cancelOrderModal');
            const orderDetailsContent = document.getElementById('orderDetailsContent');
            const cancelOrderForm = document.getElementById('cancelOrderForm');
            const cancelOrderId = document.getElementById('cancelOrderId');
            
            // View Details button click
            document.querySelectorAll('.view-details-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const orderId = button.getAttribute('data-order-id');
                    orderDetailsContent.innerHTML = '<div class="loading"><div class="loader"></div><p>Loading order details...</p></div>';
                    orderModal.style.display = 'flex';
                    
                    // Fetch order details
                    fetch(`get-order-details.php?order_id=${orderId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Render order details
                                orderDetailsContent.innerHTML = renderOrderDetails(data.order);
                            } else {
                                orderDetailsContent.innerHTML = `<p class="error">${data.message}</p>`;
                            }
                        })
                        .catch(error => {
                            orderDetailsContent.innerHTML = `<p class="error">Error loading order details: ${error.message}</p>`;
                        });
                });
            });
            
            // Cancel Order button click
            document.querySelectorAll('.cancel-order-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const orderId = button.getAttribute('data-order-id');
                    cancelOrderId.value = orderId;
                    cancelOrderModal.style.display = 'flex';
                });
            });
            
            // Close modal when clicking the X
            document.querySelectorAll('.modal-close').forEach(closeBtn => {
                closeBtn.addEventListener('click', () => {
                    orderModal.style.display = 'none';
                    cancelOrderModal.style.display = 'none';
                });
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', (e) => {
                if (e.target === orderModal) {
                    orderModal.style.display = 'none';
                }
                if (e.target === cancelOrderModal) {
                    cancelOrderModal.style.display = 'none';
                }
            });
            
            // Cancel button in cancel modal
            document.querySelector('.cancel-action').addEventListener('click', () => {
                cancelOrderModal.style.display = 'none';
            });
            
            // Cancel order form submission
            cancelOrderForm.addEventListener('submit', (e) => {
                e.preventDefault();
                
                const orderId = cancelOrderId.value;
                const reason = document.getElementById('cancelReason').value;
                
                fetch('cancel-order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `order_id=${orderId}&reason=${encodeURIComponent(reason)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        showNotification('Order cancelled successfully');
                        
                        // Close modal
                        cancelOrderModal.style.display = 'none';
                        
                        // Reload page after a short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    showNotification(`Error: ${error.message}`, 'error');
                });
            });
            
            // Render order details function
            function renderOrderDetails(order) {
                let html = `
                    <div class="order-info">
                        <p><strong>Order Number:</strong> ${order.order_number}</p>
                        <p><strong>Date:</strong> ${new Date(order.order_date).toLocaleString()}</p>
                        <p><strong>Status:</strong> <span class="order-status status-${order.status}">${order.status.replace('_', ' ')}</span></p>
                        <p><strong>Payment Method:</strong> ${order.payment_method.replace('_', ' ')}</p>
                        <p><strong>Delivery Address:</strong> ${order.delivery_address}</p>
                        <p><strong>Contact Number:</strong> ${order.contact_number}</p>
                        ${order.special_instructions ? `<p><strong>Special Instructions:</strong> ${order.special_instructions}</p>` : ''}
                    </div>
                    
                    <h3>Order Items</h3>
                    <div class="order-items-list">
                `;
                
                // Add order items
                let subtotal = 0;
                order.items.forEach(item => {
                    const itemTotal = item.quantity * item.price;
                    subtotal += itemTotal;
                    
                    html += `
                        <div class="item-details">
                            <img src="${item.image_path}" alt="${item.name}" class="item-image">
                            <div class="item-info">
                                <h4>${item.name}</h4>
                                <p>Quantity: ${item.quantity}</p>
                                <p>Price: ₱${parseFloat(item.price).toFixed(2)}</p>
                                <p>Total: ₱${itemTotal.toFixed(2)}</p>
                            </div>
                        </div>
                    `;
                });
                
                // Add order total
                html += `
                    <div class="order-summary-details">
                        <div class="summary-item">
                            <span>Subtotal:</span>
                            <span>₱${subtotal.toFixed(2)}</span>
                        </div>
                        <div class="summary-item">
                            <span>Delivery Fee:</span>
                            <span>₱${(order.total_amount - subtotal).toFixed(2)}</span>
                        </div>
                        <div class="summary-item total">
                            <span>Total:</span>
                            <span>₱${parseFloat(order.total_amount).toFixed(2)}</span>
                        </div>
                    </div>
                `;
                
                // Add status history if available
                if (order.status_history && order.status_history.length > 0) {
                    html += `
                        <h3>Order Status History</h3>
                        <div class="status-timeline">
                    `;
                    
                    order.status_history.forEach((status, index) => {
                        const isActive = index === 0; // First status is the most recent
                        
                        html += `
                            <div class="timeline-item">
                                <div class="timeline-icon ${isActive ? 'active' : ''}">
                                    <i class="fas fa-check"></i>
                                </div>
                                ${index !== order.status_history.length - 1 ? '<div class="timeline-line"></div>' : ''}
                                <div class="timeline-content">
                                    <h4>${status.status.replace('_', ' ')}</h4>
                                    <div class="timeline-date">${new Date(status.status_date).toLocaleString()}</div>
                                    ${status.notes ? `<p>${status.notes}</p>` : ''}
                                </div>
                            </div>
                        `;
                    });
                    
                    html += '</div>';
                }
                
                return html;
            }
            
            // Show notification
            function showNotification(message, type = 'success') {
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                
                // Add icon based on notification type
                let icon = 'check-circle';
                if (type === 'error') icon = 'exclamation-circle';
                if (type === 'info') icon = 'info-circle';
                
                notification.innerHTML = `<i class="fas fa-${icon}" style="margin-right: 5px;"></i> ${message}`;
                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.remove();
                }, 3000);
            }
        });
    </script>
</body>
</html>
