document.addEventListener('DOMContentLoaded', () => {
    // Get elements
    const orderNumber = document.getElementById('orderNumber');
    const orderDate = document.getElementById('orderDate');
    const estimatedDelivery = document.getElementById('estimatedDelivery');
    const deliveryAddress = document.getElementById('deliveryAddress');
    const contactNumber = document.getElementById('contactNumber');
    const orderItems = document.getElementById('orderItems');
    const subtotalElement = document.getElementById('subtotal');
    const totalElement = document.getElementById('total');
    const statusSteps = document.querySelectorAll('.status-step');

    // Get order details from session storage
    let orderDetails = JSON.parse(sessionStorage.getItem('orderDetails')) || null;
    
    // Fetch real order details from server
    fetch('get-last-order.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Use real order data from database
                const serverOrder = data.order;
                
                // Update order number with real order number from database
                orderNumber.textContent = serverOrder.order_number;
                
                // Update order date with real date from database
                // Use created_at instead of order_date to match your DB structure
                const orderDateTime = new Date(serverOrder.created_at || serverOrder.order_date || new Date());
                orderDate.textContent = orderDateTime.toLocaleString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                // Set estimated delivery time (45 minutes from order time)
                const estimatedTime = new Date(orderDateTime.getTime() + 45 * 60000);
                estimatedDelivery.textContent = estimatedTime.toLocaleString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                // Set delivery information
                deliveryAddress.textContent = serverOrder.delivery_address;
                contactNumber.textContent = serverOrder.contact_number;
                
                // Display order items
                displayOrderItems(serverOrder.items);
                
                // Update order total
                if (totalElement) {
                    totalElement.textContent = `₱${parseFloat(serverOrder.total_amount).toFixed(2)}`;
                }
                
                // Clear cart since order is now in the database
                localStorage.removeItem('cart');
                
                return;
            }
            
            // Fall back to localStorage data if server request fails
            if (!orderDetails) {
                orderDetails = {
                    address: '123 Sample St, Manila',
                    phone: '09123456789'
                };
            }
            
            // Use local data for order number if not already set
            if (!orderNumber.textContent || orderNumber.textContent === '12345') {
                const randomNum = Math.floor(Math.random() * 90000) + 10000;
                orderNumber.textContent = `#${randomNum}`;
            }
            
            // Set current date as fallback
            const now = new Date();
            orderDate.textContent = now.toLocaleString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            // Set estimated delivery time (45 minutes from now)
            const estimatedTime = new Date(now.getTime() + 45 * 60000);
            estimatedDelivery.textContent = estimatedTime.toLocaleString('en-US', {
                hour: '2-digit',
                minute: '2-digit'
            });

            // Set delivery information
            deliveryAddress.textContent = orderDetails.address;
            contactNumber.textContent = orderDetails.phone;
            
            // Fall back to localStorage for items
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            displayOrderItems(cart);
        })
        .catch(error => {
            console.error('Error fetching order:', error);
            // Handle the error gracefully with fallback data
            if (!orderDetails) {
                orderDetails = {
                    address: '123 Sample St, Manila',
                    phone: '09123456789'
                };
            }
            
            // Set current date as fallback
            const now = new Date();
            orderDate.textContent = now.toLocaleString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            // Set estimated delivery time (45 minutes from now)
            const estimatedTime = new Date(now.getTime() + 45 * 60000);
            estimatedDelivery.textContent = estimatedTime.toLocaleString('en-US', {
                hour: '2-digit',
                minute: '2-digit'
            });
        });

    // Function to display order items
    function displayOrderItems(items) {
        if (!orderItems) return;
        
        // Clear existing items
        orderItems.innerHTML = '';
        
        let subtotal = 0;
        items.forEach(item => {
            const itemElement = document.createElement('div');
            itemElement.className = 'summary-item';
            
            // Get price, name, and image with fallbacks
            const price = item.price || '₱0.00';
            const priceValue = parseFloat(price);
            const quantity = item.quantity || 1;
            const name = item.name || `Product ${item.product_id || ''}`;
            
            // Image handling - use image_path from products table if available
            let image = 'assets/placeholder.jpg';
            if (item.image_path) {
                image = item.image_path;
            } else if (item.image) {
                image = item.image;
            }
            
            itemElement.innerHTML = `
                <div class="summary-item-details">
                    <img src="${image}" alt="${name}" class="summary-item-image">
                    <span class="summary-item-name">${name} (x${quantity})</span>
                </div>
                <span class="summary-item-price">₱${(priceValue * quantity).toFixed(2)}</span>
            `;
            
            orderItems.appendChild(itemElement);
            
            // Add item price to subtotal
            subtotal += priceValue * quantity;
        });

        // Update subtotal
        if (subtotalElement) {
            subtotalElement.textContent = `₱${subtotal.toFixed(2)}`;
        }
        
        // Update total
        const deliveryFee = 50;
        if (totalElement) {
            totalElement.textContent = `₱${(subtotal + deliveryFee).toFixed(2)}`;
        }
    }

    // Simulate order status updates
    let currentStep = 0;
    const updateStatus = () => {
        if (currentStep < statusSteps.length) {
            statusSteps[currentStep].classList.add('active');
            currentStep++;
            
            // Schedule next update
            if (currentStep < statusSteps.length) {
                setTimeout(updateStatus, getRandomDelay());
            }
        }
    };

    // Get random delay between 5-15 seconds for status updates
    function getRandomDelay() {
        return Math.floor(Math.random() * 10000) + 5000;
    }

    // Start status updates after 2 seconds
    setTimeout(updateStatus, 2000);

    // Clear cart from localStorage
    localStorage.removeItem('cart');
    
    // Update cart count in header
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        cartCount.textContent = '0';
    }

    // Print order function
    window.printOrder = function() {
        window.print();
    };
});