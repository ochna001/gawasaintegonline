/**
 * PayMongo Checkout Integration
 * 
 * This script handles PayMongo payment methods in the checkout process
 */

document.addEventListener('DOMContentLoaded', () => {
    // Handle direct payment button click
    const directPaymentBtn = document.getElementById('directPaymentBtn');
    if (directPaymentBtn) {
        directPaymentBtn.addEventListener('click', function() {
            console.log('DEBUG: Direct payment button clicked');
            
            // Show loading state
            this.disabled = true;
            const originalText = this.textContent;
            this.textContent = 'Processing...';
            
            // Get form data
            const deliveryForm = document.getElementById('deliveryForm');
            if (!deliveryForm) {
                console.error('DEBUG: Form not found');
                this.disabled = false;
                this.textContent = 'Pay with GCash/Card';
                showNotification('Could not process payment. Please try again.', 'error');
                return;
            }
            
            const formData = new FormData(deliveryForm);
            const name = formData.get('name');
            const phone = formData.get('phone');
            const email = formData.get('email');
            const address = formData.get('address');
            const instructions = formData.get('instructions');
            
            // Get cart data
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            if (cart.length === 0) {
                console.error('DEBUG: Cart is empty');
                this.disabled = false;
                this.textContent = 'Pay with GCash/Card';
                showNotification('Your cart is empty', 'error');
                return;
            }
            
            // Calculate total
            const subtotalElement = document.querySelector('.subtotal');
            const deliveryFeeElement = document.querySelector('.delivery-fee');
            const totalElement = document.querySelector('.total-amount');
            
            const subtotal = parseFloat(subtotalElement.textContent.replace('₱', ''));
            const deliveryFee = parseFloat(deliveryFeeElement.textContent.replace('₱', ''));
            const total = parseFloat(totalElement.textContent.replace('₱', ''));
            
            // Create payload for API
            const payload = {
                customer: {
                    name: name,
                    email: email,
                    phone: phone
                },
                billing: {
                    address: address,
                    notes: instructions || ''
                },
                items: cart.map(item => ({
                    product_id: item.product_id,
                    name: item.name,
                    quantity: parseInt(item.quantity),
                    price: parseFloat(item.price.replace('₱', '')),
                    currency: 'PHP'
                })),
                total_amount: total,
                payment_method_type: 'gcash', // Default to gcash for direct payment
                first_name: name.split(' ')[0],
                last_name: name.split(' ').slice(1).join(' ') || name.split(' ')[0],
                email: email,
                phone: phone,
                address: address,
                instructions: instructions || ''
            };
            
            // Debug logging only - no alerts
            console.log('DEBUG: Sending direct payment request with payload:', payload);
            
            // Add debug logging to network request
            console.log('DEBUG: Request URL:', 'paymongo_checkout.php');
            console.log('DEBUG: Request method:', 'POST');
            console.log('DEBUG: Request payload:', JSON.stringify(payload));
            
            // Send request to create PayMongo checkout session
            fetch('paymongo_checkout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                console.log('DEBUG: Direct payment response received', response);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                console.log('DEBUG: Direct payment data:', data);
                if (data.success && data.checkout_url) {
                    // Redirect to PayMongo checkout
                    window.location.href = data.checkout_url;
                } else {
                    throw new Error(data.message || 'Failed to create checkout session');
                }
            })
            .catch(error => {
                console.error('DEBUG: Direct payment error:', error);
                this.disabled = false;
                this.textContent = originalText; // Restore original button text
                showNotification('Payment error: ' + error.message, 'error');
            });
        });
    }
    
    // Get payment description element
    const paymentDescription = document.getElementById('paymentDescription');
    
    // Get all payment method radio buttons
    const paymentOptions = document.querySelectorAll('input[name="payment"]');
    
    // Track if selected payment method is PayMongo
    let isPayMongoSelected = false;
    
    // Function to check if a payment method is PayMongo
    function isPayMongoMethod(method) {
        // PayMongo methods include gcash and card
        const paymongoMethods = ['gcash', 'card', 'paymongo_gcash', 'paymongo_card'];
        return paymongoMethods.includes(method);
    }
    
    // Function to update UI based on selected payment method
    function updatePaymentUI() {
        const selectedPayment = document.querySelector('input[name="payment"]:checked').value;
        isPayMongoSelected = isPayMongoMethod(selectedPayment);
        
        // Show/hide payment description for PayMongo
        if (isPayMongoSelected) {
            paymentDescription.style.display = 'block';
        } else {
            paymentDescription.style.display = 'none';
        }
    }
    
    // Listen for payment method changes
    paymentOptions.forEach(option => {
        option.addEventListener('change', updatePaymentUI);
    });
    
    // Initialize payment UI
    updatePaymentUI();
    
    // Override the checkout form submission for PayMongo payments
    const deliveryForm = document.getElementById('deliveryForm');
    if (deliveryForm) {
        // Use addEventListener instead of onsubmit property to ensure our handler runs
        deliveryForm.addEventListener('submit', function(event) {
            console.log('DEBUG: Form submit event captured');
            
            console.log('DEBUG: Form submitted - starting checkout process');
            
            // Get selected payment method
            const paymentMethod = document.querySelector('input[name="payment"]:checked').value;
            console.log('DEBUG: Selected payment method:', paymentMethod);
            
            // If not PayMongo, let the original handler process it
            if (!isPayMongoMethod(paymentMethod)) {
                console.log('DEBUG: Non-PayMongo method selected, using standard checkout');
                return true;
            }
            
            // CRITICAL: Prevent default form submission for PayMongo methods
            event.preventDefault();
            
            console.log('DEBUG: PayMongo method detected, handling with custom flow');
            
            // For PayMongo payments, we need to handle it differently
            event.preventDefault();
            
            // Get form data
            const formData = new FormData(deliveryForm);
            const name = formData.get('name');
            const phone = formData.get('phone');
            const email = formData.get('email');
            const address = formData.get('address');
            const instructions = formData.get('instructions');
            const promoCode = formData.get('promo');
            
            console.log('DEBUG: Form data collected:', {
                name, phone, email, address, 
                instructions: instructions || '(none)',
                promo: promoCode || '(none)'
            });
            
            // Get cart data
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            console.log('DEBUG: Cart data retrieved:', cart);
            
            // Calculate amounts
            const subtotalElement = document.querySelector('.subtotal');
            const totalElement = document.querySelector('.total-amount');
            const discountElement = document.querySelector('.discount-amount');
            
            const subtotal = parseFloat(subtotalElement.textContent.replace('₱', ''));
            const total = parseFloat(totalElement.textContent.replace('₱', ''));
            const discount = discountElement ? parseFloat(discountElement.textContent.replace('-₱', '')) : 0;
            
            // Check if cart is empty
            if (cart.length === 0) {
                console.log('DEBUG: Cart is empty, aborting checkout');
                showNotification('Your cart is empty', 'error');
                return false;
            }
            
            console.log('DEBUG: Cart has items, continuing checkout');
            
            // No longer showing payment modal immediately
            // Instead, will redirect directly to PayMongo after creating the checkout session
            // Update payment details in case we need them later
            const selectedPaymentMethod = document.getElementById('selectedPaymentMethod');
            const paymentAmount = document.getElementById('paymentAmount');
            
            if (selectedPaymentMethod) {
                selectedPaymentMethod.textContent = paymentMethod.replace('paymongo_', '').toUpperCase();
            }
            
            if (paymentAmount) {
                paymentAmount.textContent = totalElement.textContent;
            }
            
            // DEBUG: Start of PayMongo checkout process
            console.log('DEBUG: Starting PayMongo checkout process for ' + paymentMethod);
            
            // Show a more informative notification
            window.showNotification('Creating secure checkout session...', 'info');
            
            // IMPORTANT: Remove ALL loading-related CSS and classes
            document.body.classList.remove('loading');
            document.documentElement.style.overflow = 'auto';
            document.body.style.cursor = 'default';
            
            // Create order data
            const orderData = {
                first_name: name.split(' ')[0] || name,
                last_name: name.split(' ').slice(1).join(' ') || name,
                email: email,
                phone: phone,
                address: address,
                instructions: instructions,
                payment_method: paymentMethod,
                items: cart,
                subtotal: subtotal,
                total_amount: total,
                promo_code: promoCode || null,
                discount_amount: discount || 0
            };
            
            // DEBUG: About to call backend API
            console.log('DEBUG: Sending order data to paymongo_checkout.php:', orderData);
            
            // Call the backend
            console.log('DEBUG: Preparing checkout data for PayMongo');
            
            // Prepare the request payload
            const payloadData = {
                customer: {
                    name: name,
                    email: email,
                    phone: phone
                },
                billing: {
                    address: address,
                    notes: instructions
                },
                items: cart.map(item => ({
                    name: item.name,
                    quantity: parseInt(item.quantity),
                    amount: parseFloat(item.price.replace('₱', '')),
                    currency: 'PHP'
                })),
                total_amount: total,
                payment_method_type: paymentMethod.startsWith('paymongo_') ? paymentMethod.replace('paymongo_', '') : paymentMethod
            };
            
            console.log('DEBUG: PayMongo checkout payload:', payloadData);
            console.log('DEBUG: Sending request to paymongo_checkout.php');
            
            // Start the checkout process
            fetch('paymongo_checkout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payloadData)
            })
            .then(response => {
                console.log('DEBUG: Got response from server:', response);
                if (!response.ok) {
                    console.error('DEBUG: Response not OK. Status:', response.status);
                    throw new Error('Server error: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('DEBUG: Parsed response data:', data);
                
                if (data.success) {
                    // Clear the cart after successful order creation
                    localStorage.removeItem('cart');
                    
                    // Show success notification before redirect
                    window.showNotification('Redirecting to secure payment...', 'success');
                    
                    // Remove ALL loading states and indicators
                    document.body.classList.remove('loading');
                    document.documentElement.style.overflow = 'auto';
                    document.body.style.cursor = 'default';
                    document.querySelectorAll('.loading-indicator, .loader').forEach(el => el.remove());
                    
                    // DEBUG: Attempting redirect
                    console.log('DEBUG: Redirecting to PayMongo URL:', data.checkout_url);
                    
                    // Store the checkout URL for the button
                    console.log('DEBUG: Setting checkout URL on button:', data.checkout_url);
                    
                    // Show payment modal with the Go to PayMongo button
                    const paymentModal = document.getElementById('paymentModal');
                    const goToPaymongoBtn = document.getElementById('goToPaymongoBtn');
                    
                    if (goToPaymongoBtn) {
                        // Set the URL on the button
                        goToPaymongoBtn.setAttribute('href', data.checkout_url);
                        console.log('DEBUG: Set checkout URL on goToPaymongoBtn');
                        
                        // Show the modal
                        if (paymentModal) {
                            console.log('DEBUG: Found payment modal, displaying it');
                            paymentModal.style.display = 'flex';
                        } else {
                            console.error('DEBUG: Payment modal element not found!');
                        }
                        
                        // Handle cancel button
                        const cancelPaymentBtn = document.getElementById('cancelPaymentBtn');
                        if (cancelPaymentBtn) {
                            cancelPaymentBtn.addEventListener('click', function() {
                                paymentModal.style.display = 'none';
                                window.showNotification('Payment cancelled. Your order has been saved.', 'info');
                            });
                        }
                    } else {
                        console.error('DEBUG: Could not find the Go to PayMongo button, falling back to redirect');
                        // Fallback to direct redirect if button not found
                        console.log('DEBUG: Redirecting directly to:', data.checkout_url);
                        window.location.href = data.checkout_url;
                    }
                } else {
                    console.error('DEBUG: Server returned success=false:', data);
                    throw new Error(data.message || 'Failed to create checkout session');
                }
            })
            .catch(error => {
                console.error('DEBUG: Error during PayMongo checkout:', error);
                
                // Log stack trace for better debugging
                console.error('DEBUG: Stack trace:', new Error().stack);
                
                // Hide payment modal
                if (paymentModal) {
                    console.log('DEBUG: Hiding payment modal');
                    paymentModal.style.display = 'none';
                }
                
                // Show error notification
                window.showNotification(error.message || 'An error occurred during checkout. Please try again.', 'error');
                
                // Remove ALL loading states and indicators
                console.log('DEBUG: Removing loading states');
                document.body.classList.remove('loading');
                document.documentElement.style.overflow = 'auto';
                document.body.style.cursor = 'default';
                document.querySelectorAll('.loading-indicator, .loader').forEach(el => el.remove());
                
                // Reset any payment button states
                console.log('DEBUG: Re-enabling payment buttons');
                const paymentButtons = document.querySelectorAll('input[name="payment_method"]');
                paymentButtons.forEach(btn => {
                    btn.disabled = false;
                });
                
                // Check PHP logs or add more debugging
                console.log('DEBUG: Consider checking PHP logs for backend errors');
                console.log('DEBUG: Checkout process failed. Please check your PHP error logs, specifically for paymongo_checkout.php');
            });
            
            return false;
        });
    }
    
    // Helper function to show notifications
    // Renamed the original implementation to allow for a wrapper
    function showNotificationInternal(message, type = 'success') {
        // Fallback notification implementation
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // Wrapper function that decides whether to use global or internal notification
    function showNotification(message, type = 'success') {
        if (typeof window.showNotification === 'function' && window.showNotification !== showNotification) {
            // If a global window.showNotification exists and it's not this current function, call it.
            window.showNotification(message, type);
        } else {
            // Otherwise, use the internal implementation.
            showNotificationInternal(message, type);
        }
    } // End of showNotification function

}); // End of document.addEventListener('DOMContentLoaded', ...)
