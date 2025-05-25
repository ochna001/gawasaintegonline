<?php
// Set page title
$pageTitle = "Database Connection Test";

// Include the database configuration file
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Flavors - Database Connection Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .test-container {
            max-width: 800px;
            margin: 100px auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            background-color: white;
        }
        .test-item {
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1 class="text-center mb-4">Database Connection Test</h1>
        
        <?php
        // Display connection status
        displayConnectionStatus();
        
        // Check if tables exist
        if ($connection_status) {
            echo '<h3 class="mt-4">Database Structure Tests:</h3>';
            
            $tables = ['users', 'categories', 'products', 'orders', 'order_items', 'cart', 'cart_items', 'reviews'];
            $tablesExist = true;
            
            echo '<div class="test-item ' . ($tablesExist ? 'info' : 'warning') . '">';
            echo '<h4>Checking Required Tables:</h4>';
            echo '<ul>';
            
            foreach ($tables as $table) {
                $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
                $tableExists = mysqli_num_rows($tableCheck) > 0;
                
                if (!$tableExists) {
                    $tablesExist = false;
                }
                
                echo '<li>' . $table . ': ' . ($tableExists ? '✅ Exists' : '❌ Missing') . '</li>';
            }
            
            echo '</ul>';
            
            if (!$tablesExist) {
                echo '<div class="mt-3">';
                echo '<p><strong>Some tables are missing. Import the database schema to create them:</strong></p>';
                echo '<ol>';
                echo '<li>Open phpMyAdmin at <a href="http://localhost/phpmyadmin/" target="_blank">http://localhost/phpmyadmin/</a></li>';
                echo '<li>Select the "local_flavors" database</li>';
                echo '<li>Click on the "SQL" tab</li>';
                echo '<li>Import the SQL schema provided in the implementation guide</li>';
                echo '</ol>';
                echo '</div>';
            }
            
            echo '</div>';
            
            // Test form submission URL
            echo '<h3 class="mt-4">Form Configuration Tests:</h3>';
            
            // Check register.html form
            $registerFile = file_exists('register.html') ? 'register.html' : (file_exists('register.php') ? 'register.php' : null);
            $loginFile = file_exists('login.html') ? 'login.html' : (file_exists('login.php') ? 'login.php' : null);
            
            if ($registerFile) {
                $registerContent = file_get_contents($registerFile);
                $registerFormOk = strpos($registerContent, 'action="register_process.php"') !== false;
                
                echo '<div class="test-item ' . ($registerFormOk ? 'success' : 'warning') . '">';
                echo '<h4>Registration Form:</h4>';
                echo $registerFormOk 
                    ? '✅ Registration form is correctly configured to submit to register_process.php' 
                    : '❌ Registration form action is not set to "register_process.php". Please update your ' . $registerFile . ' file.';
                echo '</div>';
            } else {
                echo '<div class="test-item warning">';
                echo '<h4>Registration Form:</h4>';
                echo '❌ Could not find register.html or register.php file.';
                echo '</div>';
            }
            
            if ($loginFile) {
                $loginContent = file_get_contents($loginFile);
                $loginFormOk = strpos($loginContent, 'action="login_process.php"') !== false;
                
                echo '<div class="test-item ' . ($loginFormOk ? 'success' : 'warning') . '">';
                echo '<h4>Login Form:</h4>';
                echo $loginFormOk 
                    ? '✅ Login form is correctly configured to submit to login_process.php' 
                    : '❌ Login form action is not set to "login_process.php". Please update your ' . $loginFile . ' file.';
                echo '</div>';
            } else {
                echo '<div class="test-item warning">';
                echo '<h4>Login Form:</h4>';
                echo '❌ Could not find login.html or login.php file.';
                echo '</div>';
            }
        }
        ?>
        
        <div class="text-center mt-4">
            <a href="index.html" class="btn btn-primary">Back to Home</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
