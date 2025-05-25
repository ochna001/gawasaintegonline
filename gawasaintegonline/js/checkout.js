document.addEventListener('DOMContentLoaded', () => {
    console.log('Checkout page loaded');
    // Debug flag
    const DEBUG = true;
    const deliveryForm = document.getElementById('deliveryForm');
    const orderItemsContainer = document.querySelector('.order-items');
    const subtotalElement = document.querySelector('.subtotal');
    const totalAmountElement = document.querySelector('.total-amount');
    const deliveryFeeElement = document.querySelector('.delivery-fee');
    const promoDiscountElement = document.querySelector('.promo-discount');
    const discountAmountElement = document.querySelector('.discount-amount');
    const applyPromoBtn = document.querySelector('.apply-promo');
    const placeOrderBtn = document.querySelector('.place-order-btn');
    const paymentModal = document.getElementById('paymentModal');
    const selectedPaymentMethod = document.getElementById('selectedPaymentMethod');
    const paymentAmount = document.getElementById('paymentAmount');

    // Ensure modal is hidden by default
    paymentModal.style.display = 'none';

    // Load cart from localStorage
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    let promoCode = '';
    let discount = 0;

    // Update order summary
    function updateOrderSummary() {
        if (DEBUG) console.log('Updating order summary with cart:', cart);
        
        orderItemsContainer.innerHTML = '';
        let subtotal = 0;

        cart.forEach(item => {
            // Extract numeric price
            let itemPrice;
            if (typeof item.price === 'string') {
                itemPrice = parseFloat(item.price.replace(/[^0-9.]/g, ''));
            } else {
                itemPrice = parseFloat(item.price);
            }
            
            // Calculate item total (price * quantity)
            const quantity = item.quantity || 1;
            const itemTotal = itemPrice * quantity;
            
            // Format price for display
            const displayPrice = `₱${itemPrice.toFixed(2)}`;
            const displayTotal = `₱${itemTotal.toFixed(2)}`;
            
            // Create order item element
            const orderItem = document.createElement('div');
            orderItem.className = 'order-item';
            orderItem.innerHTML = `
                <div class="order-item-details">
                    <img src="${item.image}" alt="${item.name}" class="order-item-image">
                    <div class="order-item-info">
                        <span class="order-item-name">${item.name}</span>
                        <span class="order-item-quantity">Qty: ${quantity}</span>
                    </div>
                </div>
                <span class="order-item-price">${displayTotal}</span>
            `;
            orderItemsContainer.appendChild(orderItem);

            // Add to subtotal
            subtotal += itemTotal;
        });

        const deliveryFee = parseFloat(deliveryFeeElement.textContent.replace('₱', ''));
        const total = subtotal + deliveryFee - discount;

        subtotalElement.textContent = `₱${subtotal.toFixed(2)}`;
        totalAmountElement.textContent = `₱${total.toFixed(2)}`;
    }

    // Apply promo code
    applyPromoBtn.addEventListener('click', () => {
        const promoInput = document.getElementById('promo');
        promoCode = promoInput.value.trim().toUpperCase();

        // Simulate promo code validation
        if (promoCode === 'WELCOME10') {
            discount = parseFloat(subtotalElement.textContent.replace('₱', '')) * 0.1;
            promoDiscountElement.style.display = 'flex';
            discountAmountElement.textContent = `-₱${discount.toFixed(2)}`;
            updateOrderSummary();
            showNotification('Promo code applied successfully!', 'success');
        } else if (promoCode) {
            showNotification('Invalid promo code', 'error');
        }
    });

    // Set cart items in hidden field
    function setCartItemsField() {
        const cartItemsField = document.getElementById('cartItemsField');
        if (cartItemsField) {
            cartItemsField.value = JSON.stringify(cart);
            if (DEBUG) console.log('Cart data set in hidden field:', cartItemsField.value);
        } else {
            console.error('Cart items field not found');
        }
    }

    // Form validation
    deliveryForm.addEventListener('submit', e => {
        e.preventDefault();
        
        // First, set the cart items in the hidden field
        setCartItemsField();

        // Validate form fields
        const name = document.getElementById('name').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const email = document.getElementById('email').value.trim();
        const address = document.getElementById('address').value.trim();
        const paymentMethod = document.querySelector('input[name="payment"]:checked').value;

        if (!name || !phone || !email || !address) {
            showNotification('Please fill in all required fields', 'error');
            return;
        }

        // Validate phone number format
        const phoneRegex = /^[0-9]{10,11}$/;
        if (!phoneRegex.test(phone)) {
            showNotification('Please enter a valid phone number', 'error');
            return;
        }

        // Validate email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showNotification('Please enter a valid email address', 'error');
            return;
        }

        // For COD, skip payment modal and submit form directly
        // Debug which payment method is selected
        console.log('DEBUG: Payment method selected:', paymentMethod);
        
        // Show appropriate notification based on payment method
        if (paymentMethod.startsWith('paymongo_')) {
            window.showNotification('Preparing PayMongo checkout...', 'info');
        } else {
            window.showNotification('Processing your order...', 'info');
        }
        
        // Don't add any loading classes for ANY payment method
        // This prevents the page from getting stuck in a loading state
        document.body.classList.remove('loading');
        document.documentElement.style.overflow = 'auto';
        document.body.style.cursor = 'default';
        
        if (paymentMethod === 'cod') {
            // For COD, submit form directly
            deliveryForm.submit();
            return; // Exit the function early
        }
        
        // For traditional payment methods (not PayMongo)
        // PayMongo payments are handled by paymongo-checkout.js
        if (!paymentMethod.startsWith('paymongo_')) {
            // Update payment details in case we need them
            selectedPaymentMethod.textContent = paymentMethod.charAt(0).toUpperCase() + paymentMethod.slice(1);
            paymentAmount.textContent = totalAmountElement.textContent;
        }


        // Process payment based on selected method
        const processPayment = () => {
            return new Promise((resolve, reject) => {
                // For Cash on Delivery, no need to process payment
                if (paymentMethod === 'cod') {
                    // Immediately resolve the promise
                    resolve();
                    return;
                }
                
                // For other payment methods (e.g., GCash), simulate payment processing
                setTimeout(() => {
                    // Simulate payment processing
                    const success = Math.random() > 0.1; // 90% success rate for demo
                    if (success) {
                        resolve();
                    } else {
                        reject(new Error('Payment failed. Please try again.'));
                    }
                }, 7000);
            });
        };

        // Handle payment processing
        processPayment()
            .then(() => {
                // Store order details in session storage for confirmation page
                const orderDetails = {
                    name,
                    phone,
                    email,
                    address,
                    paymentMethod,
                    items: cart,
                    total: totalAmountElement.textContent,
                    date: new Date().toISOString()
                };
                sessionStorage.setItem('orderDetails', JSON.stringify(orderDetails));
                
                // Submit the form to save order in database
                showNotification('Processing order...', 'info');
                
                // Important: Submit the form to the server to process the order
                deliveryForm.submit();
                
                // Note: Form submission will redirect after processing
                // No need to manually redirect here
            })
            .catch(error => {
                // Show error message
                showNotification(error.message, 'error');
                
                // Reset loading state
                document.body.classList.remove('loading');
                paymentModal.style.display = 'none';
                placeOrderBtn.disabled = false;
            });
    });

    // Update delivery fee based on delivery method
    const deliveryOptions = document.querySelectorAll('input[name="delivery"]');
    deliveryOptions.forEach(option => {
        option.addEventListener('change', () => {
            if (option.value === 'pickup') {
                deliveryFeeElement.textContent = '₱0.00';
            } else {
                deliveryFeeElement.textContent = '₱50.00';
            }
            updateOrderSummary();
        });
    });

    // Show notification
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // Initialize order summary
    updateOrderSummary();
}); 