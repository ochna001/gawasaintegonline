<?php
// Include functions file
require_once 'includes/functions.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get login credentials
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) ? true : false;
    
    // Login user
    $result = loginUser($email, $password, $remember);
    
    if ($result['success']) {
        // Get user role for use in JavaScript
        $userRole = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';
        
        // Start preparing the JavaScript for the page
        echo "<html><head>
            <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css'>
            <style>
                .admin-toast {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    background-color: #FF6B35;
                    color: white;
                    padding: 15px 25px;
                    border-radius: 4px;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                    z-index: 9999;
                    font-family: Arial, sans-serif;
                    font-weight: 500;
                    display: none;
                }
            </style>
            <script>
                // Store user data in localStorage
                localStorage.setItem('user_id', '{$_SESSION['user_id']}');
                localStorage.setItem('first_name', '{$_SESSION['first_name']}');
                localStorage.setItem('email', '{$_SESSION['email']}');
                localStorage.setItem('user_role', '$userRole');
                
                // Store admin status explicitly for HTML pages
                if ('$userRole' === 'admin') {
                    localStorage.setItem('isAdmin', '1');
                } else {
                    localStorage.setItem('isAdmin', '0');
                }
                
                // Set cart as needing synchronization
                localStorage.setItem('cartSynced', 'false');
                
                // Wait for document to be ready
                document.addEventListener('DOMContentLoaded', function() {
                    // Show admin toast if user is admin
                    if ('$userRole' === 'admin') {
                        const toast = document.getElementById('adminToast');
                        toast.style.display = 'block';
                        
                        // Hide toast after 5 seconds
                        setTimeout(function() {
                            toast.style.display = 'none';
                        }, 5000);
                    }
                    
                    // Redirect after toast is shown
                    setTimeout(function() {
";
        
        // Check if there's a redirect URL stored
        if (isset($_SESSION['redirect_url'])) {
            $redirectUrl = $_SESSION['redirect_url'];
            unset($_SESSION['redirect_url']);
            echo "window.location.href = '$redirectUrl';";
        } else {
            // Redirect to home page
            echo "window.location.href = 'index.html';";
        }
        
        echo "
                    }, 1000); // Short delay to allow toast to be seen
                });
            </script>
        </head>
        <body>
            <div id='adminToast' class='admin-toast'>
                <i class='fas fa-crown' style='margin-right: 8px;'></i> Welcome Admin!
            </div>
        </body></html>";
        
        exit;
    } else {
        // Set error message in session
        $_SESSION['message'] = $result['message'];
        $_SESSION['message_type'] = 'error';
        
        // Redirect back to login page
        header('Location: login.php');
        exit;
    }
} else {
    // Redirect to login page if accessed directly
    header('Location: login.php');
    exit;
}
?>
