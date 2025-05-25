// Debug counter to track how many times the script is loaded
let addToCartScriptLoaded = window.addToCartScriptLoaded || 0;
window.addToCartScriptLoaded = addToCartScriptLoaded + 1;
console.log('🔍 add-to-cart.js loaded ' + window.addToCartScriptLoaded + ' times');

// Debug function to track event listeners
function debugEventListeners(selector) {
    const elements = document.querySelectorAll(selector);
    console.log(`🔍 Found ${elements.length} elements matching selector: ${selector}`);
    
    // Add a custom attribute to track how many times we've attached a listener
    elements.forEach((el, i) => {
        el.setAttribute('data-debug-index', i);
        el.addEventListener('click', function(e) {
            console.log(`🔍 Click detected on ${selector} with index ${this.getAttribute('data-debug-index')}`);
        }, {capture: true}); // Use capture to ensure this logs before other handlers
    });
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 DOM loaded in add-to-cart.js');
    
    // Debug existing event handlers
    debugEventListeners('.add-to-cart');
    
    // List all script tags on the page
    const scripts = document.querySelectorAll('script');
    console.log('🔍 Scripts loaded on page:');
    scripts.forEach((script, index) => {
        console.log(`  Script ${index + 1}: ${script.src || 'inline script'}`);
    });
    
    // Add to cart functionality for menu.php
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    console.log(`🔍 Setting up click handlers on ${addToCartButtons.length} 'Add to Cart' buttons`);
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            console.log('🔍 add-to-cart.js click handler fired');
            console.log('🔄 Event target:', event.target);
            console.log('🔄 Current target:', event.currentTarget);
            
            // Debug data attributes
            const productId = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const price = this.getAttribute('data-price');
            const image = this.getAttribute('data-image');
            
            console.log('🔄 Product Data:', {
                productId,
                name,
                price,
                image,
                buttonElement: this,
                allAttributes: Array.from(this.attributes).map(attr => `${attr.name}=${attr.value}`)
            });
            
            // Get cart from localStorage
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            
            // Check if product already in cart
            const existingItemIndex = cart.findIndex(item => item.product_id == productId);
            
            if (existingItemIndex > -1) {
                // Update quantity if product already in cart
                cart[existingItemIndex].quantity += 1;
            } else {
                // Add new item to cart
                // Store price as a pure number for database compatibility
                // Remove any currency symbols first
                const numericPrice = parseFloat(price.replace(/[^0-9.]/g, ''));
                
                cart.push({
                    product_id: productId,
                    name: name,
                    price: numericPrice, // Store as number, not string
                    display_price: numericPrice.toFixed(2), // For display purposes
                    quantity: 1,
                    image: image
                });
            }
            
            // Save updated cart to localStorage
            localStorage.setItem('cart', JSON.stringify(cart));
            
            // Update cart count
            const cartCountElements = document.querySelectorAll('.cart-count');
            cartCountElements.forEach(element => {
                element.textContent = cart.length;
            });
            
            // Show notification
            const notification = document.createElement('div');
            notification.className = 'notification success';
            notification.innerHTML = `<i class="fas fa-check-circle" style="margin-right: 5px;"></i> Item added to cart!`;
            document.body.appendChild(notification);
            
            // Remove notification after 3 seconds
            setTimeout(() => {
                notification.remove();
            }, 3000);
            
            // If user is logged in, sync with server
            const isLoggedIn = localStorage.getItem('user_id') !== null;
            if (isLoggedIn) {
                // Create a form data object to send
                const formData = new FormData();
                formData.append('cart_items', JSON.stringify(cart));
                
                console.log('🔄 Attempting to sync cart with server...');
                console.log('🔄 User ID:', localStorage.getItem('user_id'));
                console.log('🔄 Cart items being sent:', cart);
                
                // Create a debug element to show sync status
                const debugElement = document.createElement('div');
                debugElement.style.position = 'fixed';
                debugElement.style.bottom = '10px';
                debugElement.style.right = '10px';
                debugElement.style.backgroundColor = 'rgba(0,0,0,0.7)';
                debugElement.style.color = 'white';
                debugElement.style.padding = '10px';
                debugElement.style.borderRadius = '5px';
                debugElement.style.zIndex = '9999';
                debugElement.style.maxWidth = '300px';
                debugElement.innerHTML = 'Syncing cart with server...';
                document.body.appendChild(debugElement);
                
                // Send the updated cart to the server
                fetch('sync_cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('🔄 Server response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('🔄 Server response data:', data);
                    if (data.success) {
                        localStorage.setItem('cartSynced', 'true');
                        localStorage.setItem('cartLastSynced', new Date().toISOString());
                        console.log('✅ Cart synced with server after adding item');
                        console.log('✅ Updated items in database:', data.cart_items);
                        debugElement.style.backgroundColor = 'rgba(0,128,0,0.7)';
                        debugElement.innerHTML = `
                            <strong>✅ Cart synced with database!</strong><br>
                            ${data.cart_items.length} items in cart<br>
                            DB Cart ID: ${data.cart_items.length > 0 ? data.cart_items[0].cart_id : 'N/A'}<br>
                            Last item: ${data.cart_items.length > 0 ? data.cart_items[data.cart_items.length-1].name : 'None'}
                        `;
                    } else {
                        console.error('❌ Failed to sync cart with server:', data.message);
                        debugElement.style.backgroundColor = 'rgba(255,0,0,0.7)';
                        debugElement.innerHTML = `❌ Sync failed: ${data.message}`;
                    }
                    
                    // Remove debug element after 5 seconds
                    setTimeout(() => debugElement.remove(), 5000);
                })
                .catch(error => {
                    console.error('❌ Error syncing cart with server:', error);
                    debugElement.style.backgroundColor = 'rgba(255,0,0,0.7)';
                    debugElement.innerHTML = `❌ Sync error: ${error.message}`;
                    setTimeout(() => debugElement.remove(), 5000);
                });
            }
        });
    });
});
