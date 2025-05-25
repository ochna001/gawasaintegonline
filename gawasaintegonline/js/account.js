/**
 * Account page functionality
 * Handles tab switching and other account page interactions
 */
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching functionality
    const accountNav = document.querySelector('.account-nav');
    
    if (accountNav) {
        const navLinks = accountNav.querySelectorAll('a:not(#logoutBtn)');
        const sections = document.querySelectorAll('.account-section');
        
        // Add click event listeners to each nav link
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Get the target section ID from the href attribute
                const targetId = this.getAttribute('href').substring(1);
                
                // Remove active class from all links and sections
                navLinks.forEach(link => link.classList.remove('active'));
                sections.forEach(section => section.classList.remove('active'));
                
                // Add active class to the clicked link and corresponding section
                this.classList.add('active');
                document.getElementById(targetId).classList.add('active');
                
                // Fix map display if switching to profile tab
                if (targetId === 'profile' && window.map) {
                    setTimeout(() => window.map.invalidateSize(), 100);
                }
            });
        });
    }
    
    // Handle mobile view for account sidebar toggle
    const mobileSidebarToggle = document.querySelector('.mobile-sidebar-toggle');
    const accountSidebar = document.querySelector('.account-sidebar');
    
    if (mobileSidebarToggle && accountSidebar) {
        mobileSidebarToggle.addEventListener('click', function() {
            accountSidebar.classList.toggle('active');
            this.classList.toggle('active');
        });
    }
    
    // Profile form submission handling
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            // Client-side validation can be added here if needed
        });
    }
    
    // Settings form submission handling
    const settingsForm = document.getElementById('settingsForm');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match');
            }
        });
    }
    
    // Initialize any tooltips or popovers
    const tooltips = document.querySelectorAll('[data-tooltip]');
    if (tooltips.length > 0) {
        tooltips.forEach(tooltip => {
            tooltip.addEventListener('mouseenter', function() {
                const tooltipText = this.getAttribute('data-tooltip');
                const tooltipEl = document.createElement('div');
                tooltipEl.className = 'tooltip';
                tooltipEl.textContent = tooltipText;
                document.body.appendChild(tooltipEl);
                
                const rect = this.getBoundingClientRect();
                tooltipEl.style.top = rect.bottom + 10 + 'px';
                tooltipEl.style.left = rect.left + (rect.width / 2) - (tooltipEl.offsetWidth / 2) + 'px';
                tooltipEl.style.opacity = 1;
            });
            
            tooltip.addEventListener('mouseleave', function() {
                const tooltipEl = document.querySelector('.tooltip');
                if (tooltipEl) {
                    tooltipEl.remove();
                }
            });
        });
    }
    
    // Add console logging for debugging
    console.log('Account.js loaded successfully');
    console.log('Nav links:', navLinks);
    console.log('Sections:', sections);
});
