<?php
// Start session
session_start();

// Include database connection
require_once 'includes/config.php';

echo "<h1>Admin Role Debug</h1>";

// Check session variables
echo "<h2>Session Variables</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check if user is in database with admin role
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    echo "<h2>Database Check</h2>";
    
    // Query the database
    $query = "SELECT * FROM users WHERE user_id = $user_id";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        echo "<p>User found in database:</p>";
        echo "<pre>";
        // Hide password for security
        $user['password'] = '[HIDDEN]';
        print_r($user);
        echo "</pre>";
        
        // Update role if needed
        if (!isset($user['role']) || $user['role'] !== 'admin') {
            echo "<h3>Setting Admin Role</h3>";
            $updateQuery = "UPDATE users SET role = 'admin' WHERE user_id = $user_id";
            
            if (mysqli_query($conn, $updateQuery)) {
                echo "<p style='color: green;'>User role updated to admin in database!</p>";
                
                // Also update session
                $_SESSION['user_role'] = 'admin';
                echo "<p style='color: green;'>Session updated with admin role!</p>";
                
                echo "<p>Please refresh the page to see the admin dashboard button.</p>";
            } else {
                echo "<p style='color: red;'>Error updating user role: " . mysqli_error($conn) . "</p>";
            }
        } else {
            echo "<p>User already has admin role in database.</p>";
            
            // Make sure session matches
            if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
                $_SESSION['user_role'] = 'admin';
                echo "<p style='color: green;'>Session updated with admin role!</p>";
            }
        }
    } else {
        echo "<p>User not found in database or query error.</p>";
    }
} else {
    echo "<p>No user is logged in.</p>";
}

// Check localStorage with JavaScript
echo "<h2>LocalStorage Check</h2>";
echo "<div id='localStorageContent'></div>";
?>

<script>
    // Display localStorage content
    const localStorageDiv = document.getElementById('localStorageContent');
    const userRole = localStorage.getItem('user_role');
    
    localStorageDiv.innerHTML = `
        <p><strong>user_role in localStorage:</strong> ${userRole || 'not set'}</p>
        <p><strong>Is Admin in localStorage?</strong> ${userRole === 'admin' ? 'Yes' : 'No'}</p>
    `;
    
    // Fix localStorage if needed
    if (userRole !== 'admin') {
        localStorage.setItem('user_role', 'admin');
        localStorageDiv.innerHTML += `<p style="color: green;">LocalStorage updated with admin role!</p>`;
    }
</script>

<div style="margin-top: 20px; padding: 15px; background-color: #f5f5f5; border-radius: 5px;">
    <h3>Next Steps</h3>
    <p>After confirming your admin role is set correctly:</p>
    <ol>
        <li>Go back to <a href="index.html">Home Page</a></li>
        <li>You should now see the admin dashboard button in the header</li>
        <li>If not, try logging out and logging back in</li>
    </ol>
</div>
