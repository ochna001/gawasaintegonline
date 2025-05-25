// This is a special debug script to help identify cart issues
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 menu-debug.js loaded - Testing add to cart functionality');
    
    // Try adding debug click handlers
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    console.log(`Found ${addToCartButtons.length} add-to-cart buttons`);
    
    addToCartButtons.forEach((button, i) => {
        console.log(`Button ${i} data attributes:`, {
            id: button.getAttribute('data-id'),
            name: button.getAttribute('data-name'),
            price: button.getAttribute('data-price'),
            image: button.getAttribute('data-image')
        });
        
        // Add a simple direct click handler
        button.addEventListener('click', function(e) {
            // Prevent other handlers from running
            e.stopPropagation();
            
            console.log(`🛒 Direct add to cart - Button ${i} clicked`);
            const productId = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const price = this.getAttribute('data-price');
            const image = this.getAttribute('data-image');
            
            // Get cart from localStorage
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            console.log('Current cart:', cart);
            
            // Add new item directly
            const numericPrice = parseFloat(price.replace(/[^0-9.]/g, ''));
            
            // Create new cart item
            const newItem = {
                product_id: productId,
                name: name,
                price: numericPrice,
                display_price: numericPrice.toFixed(2),
                quantity: 1,
                image: image
            };
            
            console.log('Adding new item:', newItem);
            
            // Add to cart
            cart.push(newItem);
            
            // Save to localStorage
            localStorage.setItem('cart', JSON.stringify(cart));
            console.log('Updated cart:', JSON.parse(localStorage.getItem('cart')));
            
            // Update cart count display
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
            if (localStorage.getItem('user_id')) {
                console.log('User is logged in, syncing with server...');
                // Create form data
                const formData = new FormData();
                formData.append('cart_items', JSON.stringify(cart));
                
                // Send to server
                fetch('sync_cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Server response:', data);
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
            
            // Return false to prevent other handlers
            return false;
        });
    });
});
