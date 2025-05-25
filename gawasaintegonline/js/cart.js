/**
 * Format a price with Philippine Peso symbol (₱)
 * @param {number|string} price - The price to format
 * @return {string} Formatted price with Peso symbol
 */
function formatPriceWithPeso(price) {
    // If price is already a string with peso symbol, return it
    if (typeof price === 'string' && price.includes('₱')) {
        return price;
    }
    
    // Convert to number if it's a string
    let numericPrice;
    if (typeof price === 'string') {
        // Remove any non-numeric characters except decimal point
        numericPrice = parseFloat(price.replace(/[^0-9.]/g, ''));
    } else {
        numericPrice = parseFloat(price);
    }
    
    // Handle NaN
    if (isNaN(numericPrice)) {
        return '₱0.00';
    }
    
    // Format with 2 decimal places and add Peso symbol
    return `₱${numericPrice.toFixed(2)}`;
}

document.addEventListener('DOMContentLoaded', () => {
    // Load cart from localStorage
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Check if user is logged in
    const isLoggedIn = localStorage.getItem('user_id') !== null;
    
    // Sync cart with server if logged in
    if (isLoggedIn) {
        const lastSynced = localStorage.getItem('cartLastSynced');
        const needsSync = localStorage.getItem('cartSynced') === 'false';
        
        // Sync cart if needed or if it's been more than 5 minutes since last sync
        if (needsSync || !lastSynced || (new Date() - new Date(lastSynced)) > 5 * 60 * 1000) {
            syncCartWithServer();
        } else {
            // Try to get cart from server anyway
            fetchCartFromServer();
        }
    }
    
    // Always update cart count on any page
    updateCartCount();
    
    // Check if we're on the cart page
    const cartItemsContainer = document.querySelector('.cart-items');
    const emptyCartMessage = document.querySelector('.empty-cart');
    const cartContainer = document.querySelector('.cart-container');
    const subtotalElement = document.querySelector('.subtotal');
    const totalAmountElement = document.querySelector('.total-amount');
    const continueShoppingBtn = document.querySelector('.continue-shopping');
    const checkoutBtn = document.querySelector('.checkout-btn');

    // Update cart display
    function updateCartDisplay() {
        cartItemsContainer.innerHTML = '';
        
        if (cart.length === 0) {
            cartContainer.style.display = 'none';
            emptyCartMessage.style.display = 'block';
            return;
        }

        cartContainer.style.display = 'block';
        emptyCartMessage.style.display = 'none';

        cart.forEach((item, index) => {
            const cartItem = document.createElement('div');
            cartItem.className = 'cart-item';
            // Format price with Peso symbol
            const formattedPrice = formatPriceWithPeso(item.price);
            
            cartItem.innerHTML = `
                <img src="${item.image}" alt="${item.name}" class="cart-item-image">
                <div class="cart-item-details">
                    <h3 class="cart-item-name">${item.name}</h3>
                    <p class="cart-item-price">${formattedPrice}</p>
                    <div class="cart-item-quantity">
                        <button class="quantity-btn decrease" data-index="${index}">-</button>
                        <input type="number" class="quantity-input" value="${item.quantity || 1}" min="1">
                        <button class="quantity-btn increase" data-index="${index}">+</button>
                        <span class="remove-item" data-index="${index}">
                            <i class="fas fa-trash"></i>
                        </span>
                    </div>
                </div>
            `;
            cartItemsContainer.appendChild(cartItem);
        });

        updateCartTotal();
    }

    // Update cart total
    function updateCartTotal() {
        let subtotal = 0;
        const quantityInputs = document.querySelectorAll('.quantity-input');
        
        // Debug cart items
        console.log('Updating cart total with items:', JSON.parse(JSON.stringify(cart)));
        
        quantityInputs.forEach((input, index) => {
            if (index >= cart.length) {
                console.warn(`Input index ${index} exceeds cart length ${cart.length}`);
                return;
            }
            
            // Handle price formatting - remove currency symbol if present
            let price;
            if (typeof cart[index].price === 'string') {
                price = parseFloat(cart[index].price.replace(/[^0-9.]/g, ''));
            } else {
                price = parseFloat(cart[index].price);
            }
            
            // If price is NaN, try using display_price if available
            if (isNaN(price) && cart[index].display_price) {
                price = parseFloat(cart[index].display_price.replace(/[^0-9.]/g, ''));
            }
            
            // Make sure we have a valid price
            if (isNaN(price)) {
                console.warn(`Invalid price for item ${index}:`, cart[index]);
                price = 0;
            }
            
            // Get quantity from input element
            const quantity = parseInt(input.value) || 1;
            
            // Update the quantity in the cart object
            cart[index].quantity = quantity;
            
            // Calculate item subtotal
            const itemSubtotal = price * quantity;
            console.log(`Item ${index}: ${cart[index].name} - Price: ${price} x Quantity: ${quantity} = ${itemSubtotal}`);
            
            subtotal += itemSubtotal;
        });

        const deliveryFee = 50;
        const total = subtotal + deliveryFee;

        console.log(`Subtotal: ${subtotal}, Delivery Fee: ${deliveryFee}, Total: ${total}`);

        // Update cart in localStorage with corrected data
        localStorage.setItem('cart', JSON.stringify(cart));
        
        // Format with Peso symbol
        subtotalElement.textContent = formatPriceWithPeso(subtotal);
        totalAmountElement.textContent = formatPriceWithPeso(total);
    }

    // Event listeners for quantity buttons
    cartItemsContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('decrease')) {
            const index = e.target.dataset.index;
            const input = e.target.nextElementSibling;
            if (parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
                
                // Update quantity in cart array
                cart[index].quantity = parseInt(input.value);
                // Save updated cart to localStorage
                localStorage.setItem('cart', JSON.stringify(cart));
                
                updateCartTotal();
            }
        } else if (e.target.classList.contains('increase')) {
            const index = e.target.dataset.index;
            const input = e.target.previousElementSibling;
            input.value = parseInt(input.value) + 1;
            
            // Update quantity in cart array
            cart[index].quantity = parseInt(input.value);
            // Save updated cart to localStorage
            localStorage.setItem('cart', JSON.stringify(cart));
            
            updateCartTotal();
        } else if (e.target.closest('.remove-item')) {
            const index = e.target.closest('.remove-item').dataset.index;
            const item = cart[index];
            
            // Remove from localStorage
            cart.splice(index, 1);
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartDisplay();
            updateCartCount();
            
            // If logged in, also remove from server
            const isLoggedIn = localStorage.getItem('user_id') !== null;
            if (isLoggedIn && item && item.item_id) {
                // If the item has an item_id, it came from the server
                fetch('remove_from_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `item_id=${item.item_id}`,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Item removed from server cart');
                    } else {
                        console.error('Failed to remove item from server cart');
                    }
                })
                .catch(error => {
                    console.error('Error removing item from cart:', error);
                });
            } else if (isLoggedIn) {
                // If we don't have an item_id, sync the whole cart
                syncCartWithServer();
            }
        }
    });

    // Event listener for quantity input changes
    cartItemsContainer.addEventListener('change', (e) => {
        if (e.target.classList.contains('quantity-input')) {
            const index = e.target.closest('.cart-item-quantity').querySelector('.decrease').dataset.index;
            const newQuantity = parseInt(e.target.value);
            
            // Ensure quantity is at least 1
            if (newQuantity < 1) {
                e.target.value = 1;
                cart[index].quantity = 1;
            } else {
                // Update quantity in cart array
                cart[index].quantity = newQuantity;
            }
            
            // Save updated cart to localStorage
            localStorage.setItem('cart', JSON.stringify(cart));
            
            updateCartTotal();
        }
    });

    // Continue shopping button
    continueShoppingBtn.addEventListener('click', () => {
        window.location.href = 'menu.php';
    });

    // Checkout button
    checkoutBtn.addEventListener('click', () => {
        if (cart.length === 0) {
            showNotification('Your cart is empty!', 'error');
            return;
        }
        
        // Check if user is logged in
        const isLoggedIn = localStorage.getItem('user_id') !== null;
        
        if (isLoggedIn) {
            // Show loading indicator
            const loadingIndicator = document.createElement('div');
            loadingIndicator.className = 'loading-indicator';
            loadingIndicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing cart...';
            document.body.appendChild(loadingIndicator);
            
            // Sync cart with server before checkout
            syncCartWithServer()
                .then(success => {
                    // Remove loading indicator
                    loadingIndicator.remove();
                    
                    if (success) {
                        window.location.href = 'checkout.php';
                    } else {
                        showNotification('Failed to sync cart with server. Please try again.', 'error');
                    }
                })
                .catch(error => {
                    // Remove loading indicator
                    loadingIndicator.remove();
                    showNotification('An error occurred. Please try again.', 'error');
                    console.error('Error during checkout:', error);
                });
        } else {
            // Redirect to login with return URL
            localStorage.setItem('redirect_after_login', 'checkout.php');
            showNotification('Please log in to proceed with checkout', 'info');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 1500);
        }
    });

    // Only initialize cart display if we're on the cart page
    if (cartItemsContainer && emptyCartMessage && cartContainer) {
        updateCartDisplay();
    }

    // Update cart count in header
    function updateCartCount() {
        // Get all cart count elements to update them all
        const cartCountElements = document.querySelectorAll('.cart-count');
        
        // If cart count elements exist, update them
        if (cartCountElements.length > 0) {
            cartCountElements.forEach(element => {
                element.textContent = cart.length;
            });
        }
        
        // Also update cart count in localStorage for cross-page consistency
        localStorage.setItem('cartCount', cart.length);
        
        // Mark cart as needing sync if user is logged in
        if (localStorage.getItem('user_id') !== null) {
            localStorage.setItem('cartSynced', 'false');
        }
    }
    
    // Sync cart with server
    function syncCartWithServer() {
        const isLoggedIn = localStorage.getItem('user_id') !== null;
        
        console.log('syncCartWithServer called, isLoggedIn:', isLoggedIn);
        console.log('Current cart:', cart);
        
        if (!isLoggedIn) {
            console.log('User not logged in, skipping sync');
            return Promise.resolve(false);
        }
        
        // Check if cart has valid items
        if (!cart || !Array.isArray(cart) || cart.length === 0) {
            console.log('Cart is empty or invalid, skipping sync');
            return Promise.resolve(true); // Return true since there's nothing to sync
        }
        
        // Fix items that are missing product_id
        const validCart = cart.map(item => {
            // Create a copy of the item
            const newItem = {...item};
            
            // If the item doesn't have a product_id but has a name, try to fix it
            if (!newItem.product_id && newItem.name) {
                console.log('Found item missing product_id:', newItem.name);
                
                // Look for another item with the same name but with a product_id
                const similarItem = cart.find(otherItem => 
                    otherItem.product_id && otherItem.name === newItem.name
                );
                
                if (similarItem) {
                    console.log('Found matching item with product_id, copying id:', similarItem.product_id);
                    newItem.product_id = similarItem.product_id;
                }
            }
            
            return newItem;
        }).filter(item => !!item.product_id); // Only keep items with product_id
        
        if (validCart.length === 0) {
            console.log('No valid items in cart after filtering');
            return Promise.resolve(false);
        }
        
        console.log('Sending to server:', validCart);
        
        return fetch('sync_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `cart_items=${encodeURIComponent(JSON.stringify(validCart))}`,
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Sync response data:', data);
            if (data.success) {
                localStorage.setItem('cartSynced', 'true');
                localStorage.setItem('cartLastSynced', new Date().toISOString());
                console.log('Cart synced with server successfully');
                return true;
            }
            console.error('Server returned error:', data.message);
            return false;
        })
        .catch(error => {
            console.error('Error syncing cart:', error);
            return false;
        });
    }
    
    // Fetch cart from server
    function fetchCartFromServer() {
        const isLoggedIn = localStorage.getItem('user_id') !== null;
        
        if (!isLoggedIn) return Promise.resolve(false);
        
        return fetch('get_cart.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.cart_items) {
                // Update cart with items from server
                const serverCart = data.cart_items.map(item => ({
                    product_id: item.product_id,
                    name: item.name,
                    price: `₱${parseFloat(item.price).toFixed(2)}`,
                    quantity: parseInt(item.quantity),
                    image: item.image_path
                }));
                
                // Only update if there are items on the server
                if (serverCart.length > 0) {
                    cart = serverCart;
                    localStorage.setItem('cart', JSON.stringify(cart));
                    updateCartCount();
                    
                    // If we're on the cart page, update the display
                    if (cartItemsContainer && emptyCartMessage && cartContainer) {
                        updateCartDisplay();
                    }
                    
                    localStorage.setItem('cartSynced', 'true');
                    localStorage.setItem('cartLastSynced', new Date().toISOString());
                    console.log('Cart fetched from server successfully');
                }
                return true;
            }
            return false;
        })
        .catch(error => {
            console.error('Error fetching cart:', error);
            return false;
        });
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