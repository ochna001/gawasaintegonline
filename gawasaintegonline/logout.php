<?php
// Include functions file
require_once 'includes/functions.php';

// Logout user
logoutUser();

// Output JavaScript to clear localStorage before redirecting
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logging out...</title>
</head>
<body>
    <script>
        // Clear user data from localStorage
        localStorage.removeItem('user_id');
        localStorage.removeItem('first_name');
        localStorage.removeItem('email');
        
        // Redirect to home page
        window.location.href = 'index.html';
    </script>
</body>
</html>
