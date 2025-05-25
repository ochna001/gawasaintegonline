document.addEventListener('DOMContentLoaded', function() {
    console.log('Header.js is running...');
    
    // Check if user is logged in by looking for user data in localStorage
    const isLoggedIn = localStorage.getItem('user_id') !== null;
    const firstName = localStorage.getItem('first_name') || '';
    const email = localStorage.getItem('email') || '';
    const userRole = localStorage.getItem('user_role') || '';
    const isAdmin = userRole === 'admin';
    
    // Add or remove the logged-in class on body based on login status
    if (isLoggedIn) {
        document.body.classList.add('logged-in');
        
        // Add admin class if user is admin
        if (isAdmin) {
            document.body.classList.add('admin-user');
        }
    } else {
        document.body.classList.remove('logged-in');
        document.body.classList.remove('admin-user');
    }
    
    console.log('Login status:', isLoggedIn);
    console.log('User ID:', localStorage.getItem('user_id'));
    console.log('First Name:', firstName);
    console.log('User Role:', userRole);
    console.log('Is Admin:', isAdmin);
    
    // Get the navigation links container
    const navLinks = document.querySelector('.nav-links');
    
    // Get auth links container - use ID if available, otherwise fallback to class
    let authLinks = document.getElementById('authLinks') || document.querySelector('.auth-links');
    if (!authLinks) {
        authLinks = document.createElement('div');
        authLinks.className = 'auth-links';
        authLinks.id = 'authLinks';
        navLinks.appendChild(authLinks);
    }
    
    // Add CSS for the admin button (do this once regardless of condition)
    const addAdminStyles = function() {
        // Check if styles already exist
        if (!document.getElementById('adminButtonStyles')) {
            const style = document.createElement('style');
            style.id = 'adminButtonStyles';
            style.textContent = `
                .admin-link {
                    background-color: #FF6B35;
                    color: white !important;
                    padding: 8px 15px;
                    border-radius: 4px;
                    margin-right: 10px;
                    font-weight: 500;
                    display: inline-flex;
                    align-items: center;
                    transition: background-color 0.3s;
                    text-decoration: none;
                }
                
                .admin-link:hover {
                    background-color: #e25a2b;
                    text-decoration: none;
                }
                
                .admin-link i {
                    margin-right: 5px;
                }
            `;
            document.head.appendChild(style);
        }
    };
    
    // Clear existing auth links
    while (authLinks.firstChild) {
        authLinks.removeChild(authLinks.firstChild);
    }
    
    // Now add the appropriate links based on login status
    
    // Find the account link - use ID if available, otherwise fallback to class
    const accountLink = document.getElementById('accountLink') || document.querySelector('.account-link');
    
    // Check if admin button already exists to avoid duplicates
    const existingAdminBtn = document.querySelector('.admin-link');
    if (existingAdminBtn) {
        existingAdminBtn.remove();
    }
    
    // Add the admin dashboard button if user is admin (OUTSIDE auth-links container)
    if (isLoggedIn && isAdmin) {
        console.log('User is admin - adding dashboard button');
        
        // Add admin styles
        addAdminStyles();
        
        // Create dashboard button
        const dashboardBtn = document.createElement('a');
        dashboardBtn.href = '/gawasainteg/admin/index.php';
        dashboardBtn.className = 'admin-link';
        dashboardBtn.innerHTML = '<i class="fas fa-tachometer-alt"></i> Admin Dashboard';
        
        // Insert admin button AFTER auth-links (not inside it)
        // This ensures it won't be affected by operations on auth-links
        if (authLinks.parentNode) {
            authLinks.parentNode.insertBefore(dashboardBtn, authLinks);
        } else {
            navLinks.appendChild(dashboardBtn);
        }
    }
    
    // Find existing login and register links (to hide/remove them if needed)
    const existingLoginLink = document.querySelector('.login-link');
    const existingRegisterLink = document.querySelector('.register-link');
    const existingLogoutLink = document.querySelector('.logout-link');
    
    // Determine if we need to modify the DOM
    if (isLoggedIn) {
        console.log('User is logged in:', firstName);
        
        // Update account link with user's name if logged in
        if (accountLink) {
            accountLink.textContent = firstName;
            accountLink.href = 'profile.php';
            accountLink.title = 'View your profile';
        }
        
        // Hide any existing login/register links in the DOM
        if (existingLoginLink && existingLoginLink.parentNode) {
            existingLoginLink.style.display = 'none';
        }
        
        if (existingRegisterLink && existingRegisterLink.parentNode) {
            existingRegisterLink.style.display = 'none';
        }
        
        // Add logout link if it doesn't exist
        if (!existingLogoutLink) {
            const logoutLink = document.createElement('a');
            logoutLink.href = 'logout.php';
            logoutLink.className = 'logout-link';
            logoutLink.id = 'logoutLink';
            logoutLink.textContent = 'Logout';
            authLinks.appendChild(logoutLink);
        } else {
            existingLogoutLink.style.display = 'inline-block';
        }
        
        // Optionally add user greeting somewhere on the page
        const userGreeting = document.getElementById('userGreeting');
        if (userGreeting) {
            userGreeting.textContent = `Welcome back, ${firstName}!`;
            userGreeting.style.display = 'block';
        }
    } else {
        console.log('User is not logged in');
        
        // Reset account link if not logged in
        if (accountLink) {
            accountLink.textContent = 'Account';
            accountLink.href = 'account.html';
            accountLink.title = 'View your account';
        }
        
        // Hide logout link if it exists
        if (existingLogoutLink && existingLogoutLink.parentNode) {
            existingLogoutLink.style.display = 'none';
        }
        
        // Show existing login/register links if they exist, or create new ones
        if (existingLoginLink && existingLoginLink.parentNode) {
            existingLoginLink.style.display = 'inline-block';
        } else {
            const loginLink = document.createElement('a');
            loginLink.href = 'login.php';
            loginLink.className = 'login-link';
            loginLink.id = 'loginLink';
            loginLink.textContent = 'Login';
            authLinks.appendChild(loginLink);
        }
        
        if (existingRegisterLink && existingRegisterLink.parentNode) {
            existingRegisterLink.style.display = 'inline-block';
        } else {
            const registerLink = document.createElement('a');
            registerLink.href = 'register.php';
            registerLink.className = 'register-link';
            registerLink.id = 'registerLink';
            registerLink.textContent = 'Register';
            authLinks.appendChild(registerLink);
        }
        
        // Hide user greeting if present
        const userGreeting = document.getElementById('userGreeting');
        if (userGreeting) {
            userGreeting.style.display = 'none';
        }
    }
    
    console.log('Header.js completed running');
});
