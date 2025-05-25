<?php
// Include database connection
require_once 'includes/config.php';

echo "<h1>Database Structure Check</h1>";

// Check if the role column exists in the users table
$query = "SHOW COLUMNS FROM users LIKE 'role'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✓ 'role' column exists in the users table</p>";
    
    // Get the column details
    $column = mysqli_fetch_assoc($result);
    echo "<pre>";
    print_r($column);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>✗ 'role' column does not exist in the users table</p>";
    
    // Add the role column to the users table
    $alterQuery = "ALTER TABLE users ADD COLUMN role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer'";
    
    if (mysqli_query($conn, $alterQuery)) {
        echo "<p style='color: green;'>✓ Successfully added 'role' column to users table</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to add 'role' column: " . mysqli_error($conn) . "</p>";
    }
}

// Show the structure of the users table
echo "<h2>Users Table Structure</h2>";
$structureQuery = "DESCRIBE users";
$structureResult = mysqli_query($conn, $structureQuery);

if ($structureResult) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($structureResult)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p style='color: red;'>Error getting table structure: " . mysqli_error($conn) . "</p>";
}
?>
