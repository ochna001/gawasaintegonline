// Cart functionality
let cart = JSON.parse(localStorage.getItem('cart')) || [];

function updateCartCount() {
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        cartCount.textContent = cart.length;
    }
}

function addToCart(dish) {
    // Check if we're on index.html page - if so, don't add to cart
    const onIndexPage = window.location.pathname.endsWith('index.html') || 
                       window.location.pathname === '/' || 
                       window.location.pathname.endsWith('/');
    
    if (onIndexPage) {
        console.log('Prevented adding to cart from index.html');
        return; // Don't add to cart if on index page
    }
    
    console.log('🚨 addToCart function called from main.js');
    console.log('🚨 Call stack:', new Error().stack);
    console.log('🚨 Dish:', dish);
    
    // Add debug info to see if this is a duplicate add
    const existingCart = JSON.parse(localStorage.getItem('cart')) || [];
    console.log('🚨 Cart before adding:', existingCart);
    
    cart.push(dish);
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    window.showNotification('Item added to cart!');
}

// Global notification function for use across the site
window.showNotification = function(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    
    // Add icon based on notification type
    let icon = 'check-circle';
    if (type === 'error') icon = 'exclamation-circle';
    if (type === 'info') icon = 'info-circle';
    
    notification.innerHTML = `<i class="fas fa-${icon}" style="margin-right: 5px;"></i> ${message}`;
    document.body.appendChild(notification);

    // Style the notification
    Object.assign(notification.style, {
        position: 'fixed',
        bottom: '20px',
        right: '20px',
        background: type === 'success' ? '#4CAF50' : type === 'error' ? '#F44336' : '#2196F3',
        color: 'white',
        padding: '12px 20px',
        borderRadius: '4px',
        boxShadow: '0 2px 10px rgba(0,0,0,0.2)',
        zIndex: '1000',
        display: 'flex',
        alignItems: 'center',
        fontWeight: '500',
        opacity: '0',
        transform: 'translateY(20px)',
        transition: 'opacity 0.3s, transform 0.3s'
    });
    
    // Animate in
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateY(0)';
    }, 10);

    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(20px)';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Mobile Menu Functionality
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');
    const body = document.body;

    mobileMenuBtn.addEventListener('click', function() {
        navLinks.classList.toggle('active');
        mobileMenuBtn.classList.toggle('active');
        body.classList.toggle('menu-open');
    });

    // Close menu when clicking on a link
    navLinks.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            navLinks.classList.remove('active');
            mobileMenuBtn.classList.remove('active');
            body.classList.remove('menu-open');
        });
    });

    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        if (!navLinks.contains(event.target) && !mobileMenuBtn.contains(event.target) && navLinks.classList.contains('active')) {
            navLinks.classList.remove('active');
            mobileMenuBtn.classList.remove('active');
            body.classList.remove('menu-open');
        }
    });

    // Initialize cart count
    updateCartCount();

    // Initialize cart count only (Add to cart functionality is handled in menu.php)
    // NOTE: The code below was causing duplicate items to be added to cart
    // It's commented out to prevent double-adding items
    /*
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            const dishCard = e.target.closest('.dish-card');
            const dish = {
                name: dishCard.querySelector('h3').textContent,
                price: dishCard.querySelector('.price').textContent,
                image: dishCard.querySelector('.dish-image').src
            };
            addToCart(dish);
        });
    });
    */
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth'
            });
        }
    });
});

// Form validation
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required]');
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('error');
        } else {
            input.classList.remove('error');
        }
    });

    return isValid;
}

// The error class for form validation
const style = document.createElement('style');
style.textContent = `
    .error {
        border-color: var(--error-color) !important;
    }
`;
document.head.appendChild(style); 