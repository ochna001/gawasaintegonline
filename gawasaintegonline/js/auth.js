// Authentication state management
const auth = {
    isLoggedIn: false,
    currentUser: null,

    init() {
        // Check if user is logged in from localStorage
        const userData = localStorage.getItem('currentUser') || sessionStorage.getItem('currentUser');
        if (userData) {
            this.isLoggedIn = true;
            this.currentUser = JSON.parse(userData);
            document.body.classList.add('logged-in');
            this.updateUserDisplay();
        }

        // Add event listeners for login/register forms
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');

        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        if (registerForm) {
            registerForm.addEventListener('submit', this.handleRegister.bind(this));
        }

        // Password toggle logic for all password fields with Bootstrap
        document.querySelectorAll('.input-group').forEach(function(wrapper) {
            const input = wrapper.querySelector('input[type="password"]');
            const toggle = wrapper.querySelector('.password-toggle');
            if (!input || !toggle) return;

            // Toggle password visibility
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                if (input.type === 'password') {
                    input.type = 'text';
                    toggle.querySelector('i').classList.remove('bi-eye');
                    toggle.querySelector('i').classList.add('bi-eye-slash');
                } else {
                    input.type = 'password';
                    toggle.querySelector('i').classList.add('bi-eye');
                    toggle.querySelector('i').classList.remove('bi-eye-slash');
                }
            });

            // Ensure correct icon when clicking outside
            input.addEventListener('blur', function() {
                if (input.type === 'text') {
                    input.type = 'password';
                    toggle.querySelector('i').classList.add('bi-eye');
                    toggle.querySelector('i').classList.remove('bi-eye-slash');
                }
            });
        });

        // Add logout functionality
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', this.handleLogout.bind(this));
        }

        // Add cart click handling
        const cartIcon = document.querySelector('.cart-icon');
        if (cartIcon) {
            cartIcon.addEventListener('click', (e) => {
                // Allow cart access without login requirement
            });
        }
    },

    updateUserDisplay() {
        const accountLink = document.querySelector('.account-link');
        const welcomeMessage = document.querySelector('.welcome-message');
        const body = document.body;
        
        if (this.currentUser) {
            accountLink.textContent = this.currentUser.firstName;
            welcomeMessage.textContent = `Welcome back, ${this.currentUser.firstName}!`;
            body.classList.add('logged-in');
        } else {
            accountLink.textContent = 'Account';
            welcomeMessage.textContent = '';
            body.classList.remove('logged-in');
        }
    },

    handleLogin(event) {
        event.preventDefault();
        const email = document.getElementById('loginEmail').value;
        const password = document.getElementById('loginPassword').value;
        const rememberMe = document.querySelector('input[name="remember"]').checked;

        // Validate email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showNotification('Please enter a valid email address', 'error');
            return;
        }

        // Validate password
        if (password.length < 6) {
            showNotification('Password must be at least 6 characters long', 'error');
            return;
        }

        // Show loading state
        const loginBtn = document.querySelector('#loginForm .auth-btn');
        const originalBtnText = loginBtn.textContent;
        loginBtn.disabled = true;
        loginBtn.textContent = 'Signing in...';

        // Simulate API call delay
        setTimeout(() => {
            // In a real application, this would be an API call
            // For demo purposes, we'll use localStorage
            const users = JSON.parse(localStorage.getItem('users') || '[]');
            const user = users.find(u => u.email === email && u.password === password);

            if (user) {
                this.isLoggedIn = true;
                this.currentUser = { ...user, password: undefined };
                
                // Store user data in localStorage
                if (rememberMe) {
                    localStorage.setItem('currentUser', JSON.stringify(this.currentUser));
                } else {
                    sessionStorage.setItem('currentUser', JSON.stringify(this.currentUser));
                }
                
                document.body.classList.add('logged-in');
                this.updateUserDisplay();
                
                // Show success message
                showNotification(`Welcome back, ${this.currentUser.firstName}!`, 'success');
                
                // Always redirect to home page
                window.location.replace('index.html');
            } else {
                // Show error message
                showNotification('Invalid email or password', 'error');
                
                // Reset button state
                loginBtn.disabled = false;
                loginBtn.textContent = originalBtnText;
            }
        }, 1000);
    },

    handleRegister(event) {
        event.preventDefault();
        const firstName = document.getElementById('firstName').value;
        const lastName = document.getElementById('lastName').value;
        const email = document.getElementById('registerEmail').value;
        const password = document.getElementById('registerPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const phone = document.getElementById('phone').value;

        if (password !== confirmPassword) {
            alert('Passwords do not match');
            return;
        }

        // In a real application, this would be an API call
        const users = JSON.parse(localStorage.getItem('users') || '[]');
        
        if (users.some(u => u.email === email)) {
            alert('Email already registered');
            return;
        }

        const newUser = {
            id: Date.now(),
            firstName,
            lastName,
            email,
            password,
            phone,
            joinDate: new Date().toISOString()
        };

        users.push(newUser);
        localStorage.setItem('users', JSON.stringify(users));

        // Auto login after registration
        this.isLoggedIn = true;
        this.currentUser = { ...newUser, password: undefined };
        localStorage.setItem('currentUser', JSON.stringify(this.currentUser));
        document.body.classList.add('logged-in');
        this.updateUserDisplay();
        
        // Redirect to home page
        window.location.href = 'index.html';
    },

    handleLogout() {
        this.isLoggedIn = false;
        this.currentUser = null;
        localStorage.removeItem('currentUser');
        document.body.classList.remove('logged-in');
        
        // Reset account link text
        const accountLinks = document.querySelectorAll('.account-link');
        accountLinks.forEach(link => {
            link.textContent = 'Account';
        });
        
        window.location.href = 'index.html';
    }
};

// Initialize auth when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    auth.init();
});

// Cart functionality that requires authentication
const cart = {
    init() {
        // Initialize cart functionality regardless of login status
        this.loadCart();
        this.updateCartCount();
    },

    loadCart() {
        // Load cart from localStorage
        const cartData = localStorage.getItem(`cart_${auth.currentUser.id}`) || '[]';
        this.items = JSON.parse(cartData);
    },

    saveCart() {
        if (auth.currentUser) {
            localStorage.setItem(`cart_${auth.currentUser.id}`, JSON.stringify(this.items));
        }
    },

    addToCart(item) {
        const existingItem = this.items.find(i => i.id === item.id);
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            this.items.push({ ...item, quantity: 1 });
        }
        this.saveCart();
        this.updateCartCount();
    },

    updateCartCount() {
        const count = this.items.reduce((total, item) => total + item.quantity, 0);
        document.querySelectorAll('.cart-count').forEach(el => {
            el.textContent = count;
        });
    }
};

// Initialize cart when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    cart.init();
});

// Add notification function
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);

    // Add styles if not already present
    if (!document.getElementById('notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 25px;
                border-radius: 5px;
                color: white;
                font-weight: 500;
                z-index: 1000;
                animation: slideIn 0.3s ease-out;
            }
            .notification.success {
                background-color: var(--success-color);
            }
            .notification.error {
                background-color: var(--error-color);
            }
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    }

    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Simple page navigation
document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        alert('Login form found!');
        loginForm.addEventListener('submit', (e) => {
            alert('Form submitted!');
            e.preventDefault();
            try {
                window.location.replace('index.html');
            } catch (error) {
                alert('Error during navigation: ' + error.message);
            }
        });
    } else {
        alert('Login form not found!');
    }
}); 