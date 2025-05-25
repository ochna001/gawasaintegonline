document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search');
    const filterButtons = document.querySelectorAll('.filter-btn');
    const menuCategories = document.querySelectorAll('.menu-category');
    const dishCards = document.querySelectorAll('.dish-card');

    // Update cart count display
    function updateCartCount() {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const cartCountElements = document.querySelectorAll('.cart-count');
        cartCountElements.forEach(element => {
            element.textContent = cart.length;
        });
    }

    // Call updateCartCount on page load
    updateCartCount();

    // Filter by category
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            const category = button.dataset.category;
            
            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            // Show/hide categories
            menuCategories.forEach(categoryEl => {
                if (category === 'all' || categoryEl.dataset.category === category) {
                    categoryEl.style.display = 'block';
                } else {
                    categoryEl.style.display = 'none';
                }
            });
        });
    });

    // Search functionality
    searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        
        menuCategories.forEach(category => {
            const dishes = category.querySelectorAll('.dish-card');
            let hasVisibleDishes = false;

            dishes.forEach(dish => {
                const dishName = dish.querySelector('h3').textContent.toLowerCase();
                const dishDesc = dish.querySelector('p').textContent.toLowerCase();
                
                if (dishName.includes(searchTerm) || dishDesc.includes(searchTerm)) {
                    dish.style.display = 'block';
                    hasVisibleDishes = true;
                } else {
                    dish.style.display = 'none';
                }
            });

            // Show/hide category based on visible dishes
            category.style.display = hasVisibleDishes ? 'block' : 'none';
        });
    });

    // Note: Add to cart functionality is now handled directly in menu.php
    // to ensure correct product IDs from the database are used

    // Intersection Observer for category animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, {
        threshold: 0.1
    });

    menuCategories.forEach(category => {
        observer.observe(category);
    });

    // Add loading state simulation 
    const menuGrid = document.querySelector('.menu-grid');
    menuGrid.classList.add('loading');
    
    setTimeout(() => {
        menuGrid.classList.remove('loading');
        menuCategories.forEach(category => {
            category.classList.add('visible');
        });
        
        // Check if we need to highlight a menu item from index.html
        highlightItemFromIndex();
    }, 1000);
    
    // Function to highlight and scroll to item selected from index.html
    function highlightItemFromIndex() {
        const highlightData = localStorage.getItem('highlightMenuItem');
        
        if (highlightData) {
            const itemToHighlight = JSON.parse(highlightData);
            const timestamp = itemToHighlight.timestamp;
            const currentTime = Date.now();
            
            // Only process if the highlight request is recent (within last 30 seconds)
            if (currentTime - timestamp < 30000) {
                // Find matching menu item by name and price
                let foundItem = null;
                let foundCategory = null;
                
                // Loop through all dish cards to find a match
                menuCategories.forEach(category => {
                    const dishes = category.querySelectorAll('.dish-card');
                    
                    dishes.forEach(dish => {
                        const dishName = dish.querySelector('h3').textContent;
                        const priceText = dish.querySelector('.price').textContent;
                        const price = parseFloat(priceText.replace('₱', '').trim());
                        
                        // Check if both name and price match
                        if (dishName === itemToHighlight.name && Math.abs(price - itemToHighlight.price) < 0.01) {
                            foundItem = dish;
                            foundCategory = category;
                        }
                    });
                });
                
                if (foundItem && foundCategory) {
                    // Show the category if it's hidden
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    document.querySelector(`.filter-btn[data-category="${foundCategory.dataset.category}"]`).classList.add('active');
                    
                    menuCategories.forEach(cat => {
                        if (cat === foundCategory) {
                            cat.style.display = 'block';
                        } else {
                            cat.style.display = 'none';
                        }
                    });
                    
                    // Scroll to the item
                    foundItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // Highlight the item with animation
                    foundItem.classList.add('highlight-pulse');
                    
                    // Show a subtle notification guiding the user
                    window.showNotification(`Found ${itemToHighlight.name} in our menu! Click "Add to Cart" to add it.`, 'info');
                    
                    // Add a special indicator to show which button to click
                    const addButton = foundItem.querySelector('.add-to-cart');
                    if (addButton) {
                        addButton.classList.add('attention-button');
                        
                        // Remove the attention class after 5 seconds
                        setTimeout(() => {
                            addButton.classList.remove('attention-button');
                        }, 5000);
                    }
                } else {
                    window.showNotification(`Sorry, we couldn't find "${itemToHighlight.name}" in our menu.`, 'error');
                }
                
                // Clear the highlight data to prevent repeat highlights on page refresh
                localStorage.removeItem('highlightMenuItem');
            }
        }
    }
    
    // Note: Using the global window.showNotification function from main.js
    // This ensures consistent notification styling across the site
}); 