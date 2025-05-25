<?php
// Include database connection
require_once '../includes/config.php';

// Function to get table structure
function getTableStructure($conn, $tableName) {
    $structure = [];
    $query = "DESCRIBE $tableName";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $structure[] = $row;
        }
    }
    
    return $structure;
}

// Function to check if table exists
function tableExists($conn, $tableName) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return mysqli_num_rows($result) > 0;
}

// List all tables in the database
echo "<h1>Database Tables</h1>";
$tablesResult = mysqli_query($conn, "SHOW TABLES");
echo "<ul>";
while ($table = mysqli_fetch_row($tablesResult)) {
    echo "<li>{$table[0]}</li>";
}
echo "</ul>";

// Check specific tables needed for admin dashboard
$requiredTables = ['users', 'orders', 'order_items'];
echo "<h1>Required Tables Check</h1>";
echo "<ul>";
foreach ($requiredTables as $table) {
    if (tableExists($conn, $table)) {
        echo "<li>✅ Table '$table' exists</li>";
        
        // Show structure for this table
        echo "<h3>Structure of '$table':</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        $structure = getTableStructure($conn, $table);
        foreach ($structure as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<li>❌ Table '$table' does not exist</li>";
    }
}
echo "</ul>";
?>
