// Specific functionality for index.html's Today's Specials section
document.addEventListener('DOMContentLoaded', function() {
    console.log('index.js loaded - handling Today\'s Specials redirect functionality');
    
    // Get cart from localStorage or initialize empty array (only for cart count display)
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Update cart count in the header
    function updateCartCount() {
        const cartCount = document.querySelector('.cart-count');
        if (cartCount) {
            cartCount.textContent = cart.length;
        }
    }

    // Initialize cart count
    updateCartCount();

    // Redirect to menu.php and highlight the corresponding item
    function redirectToMenuWithItem(dishCard) {
        // Extract product details from the dish card
        const name = dishCard.querySelector('h3').textContent;
        const priceText = dishCard.querySelector('.price').textContent;
        
        // Extract numeric price from the text (remove currency symbol)
        const price = parseFloat(priceText.replace('₱', '').trim());
        
        // Show notification using the global function
        window.showNotification(`Finding ${name} in our menu...`, 'info');
        
        // Store the selected item in localStorage so menu.php can find it
        const selectedItem = {
            name: name,
            price: price,
            timestamp: Date.now() // To ensure freshness of the selection
        };
        
        localStorage.setItem('highlightMenuItem', JSON.stringify(selectedItem));
        
        // Redirect to menu page
        window.location.href = 'menu.php';
    }
    
    // Create a consistent product ID from the product name (kept for reference)
    function generateProductId(name) {
        // Convert name to lowercase, remove special chars, replace spaces with dashes
        return name.toLowerCase()
            .replace(/[^\w\s]/gi, '')
            .replace(/\s+/g, '-');
    }
    
    // Note: Using the global showNotification function from main.js now

    // Add click event listeners to all "Find in Menu" buttons
    const findInMenuButtons = document.querySelectorAll('.featured-dishes .find-in-menu');
    
    findInMenuButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const dishCard = e.target.closest('.dish-card');
            if (dishCard) {
                redirectToMenuWithItem(dishCard);
            }
        });
    });
    
    console.log(`Found ${findInMenuButtons.length} "Find in Menu" buttons in Today's Specials`);
});
