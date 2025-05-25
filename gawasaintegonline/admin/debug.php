<?php
// Basic debug file to check database and admin function status
session_start();

// Include database connection
require_once('../includes/config.php');

// Output database connection status
echo "<h1>Database Connection Check</h1>";
echo "Connection status: " . ($conn ? "Connected successfully" : "Connection failed") . "<br>";

// Check if admin-functions.php exists
echo "<h1>Admin Functions File Check</h1>";
$adminFunctionsPath = '../includes/admin-functions.php';
echo "Admin functions file: " . (file_exists($adminFunctionsPath) ? "Exists" : "Does not exist") . "<br>";

// Check user session
echo "<h1>User Session Check</h1>";
echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";

// Check users table structure
echo "<h1>Users Table Structure</h1>";
if ($conn) {
    $result = mysqli_query($conn, "DESCRIBE users");
    if ($result) {
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Error describing users table: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "Cannot check users table: No database connection<br>";
}

// Check orders table structure
echo "<h1>Orders Table Structure</h1>";
if ($conn) {
    $result = mysqli_query($conn, "DESCRIBE orders");
    if ($result) {
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Error describing orders table: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "Cannot check orders table: No database connection<br>";
}

// Try to include admin-functions.php and see if it works
echo "<h1>Admin Functions Test</h1>";
try {
    require_once($adminFunctionsPath);
    echo "Admin functions included successfully<br>";
    
    // Test if isAdmin function exists
    if (function_exists('isAdmin')) {
        echo "isAdmin function exists<br>";
    } else {
        echo "isAdmin function does not exist<br>";
    }
} catch (Exception $e) {
    echo "Error including admin functions: " . $e->getMessage() . "<br>";
}
?>
