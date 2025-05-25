<?php
// Script to debug session and database operations

// Turn on error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log script start
file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Debug script started\n", FILE_APPEND);

// Test 1: Session Start
echo "<h3>Test 1: Session Start</h3>";
try {
    if (headers_sent($file, $line)) {
        echo "Headers already sent in $file on line $line<br>";
    } else {
        echo "Headers not sent yet<br>";
        session_start();
        echo "Session started successfully<br>";
        echo "Session ID: " . session_id() . "<br>";
    }
} catch (Exception $e) {
    echo "Session error: " . $e->getMessage() . "<br>";
}

// Test 2: Database Connection
echo "<h3>Test 2: Database Connection</h3>";
try {
    require_once 'includes/config.php';
    if (!isset($conn) || !$conn) {
        echo "Database connection failed<br>";
    } else {
        echo "Database connection successful<br>";
        echo "Connection info: " . mysqli_get_host_info($conn) . "<br>";
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

// Test 3: Create Test Order
echo "<h3>Test 3: Create Test Order</h3>";
try {
    if (isset($conn) && $conn) {
        // Get column names from orders table
        $columnsQuery = "SHOW COLUMNS FROM orders";
        $columnsResult = mysqli_query($conn, $columnsQuery);
        
        if (!$columnsResult) {
            echo "Error getting columns: " . mysqli_error($conn) . "<br>";
        } else {
            echo "Table columns:<br>";
            $columns = [];
            while ($row = mysqli_fetch_assoc($columnsResult)) {
                $columns[] = $row['Field'];
                echo "- " . $row['Field'] . " (" . $row['Type'] . ")";
                if ($row['Null'] == 'NO') echo " [Required]";
                if ($row['Default'] !== null) echo " [Default: " . $row['Default'] . "]";
                echo "<br>";
            }
            
            // Try to create a minimal order
            $orderFields = [];
            $orderValues = [];
            
            // Only use fields that exist in the table
            if (in_array('total_amount', $columns)) {
                $orderFields[] = 'total_amount';
                $orderValues[] = 100;
            }
            
            if (in_array('status', $columns)) {
                $orderFields[] = 'status';
                $orderValues[] = 'pending';
            }
            
            if (in_array('payment_method', $columns)) {
                $orderFields[] = 'payment_method';
                $orderValues[] = 'paymongo_gcash';
            }
            
            if (in_array('payment_status', $columns)) {
                $orderFields[] = 'payment_status';
                $orderValues[] = 'pending';
            }
            
            if (in_array('delivery_address', $columns)) {
                $orderFields[] = 'delivery_address';
                $orderValues[] = 'Test Address';
            }
            
            // Build query
            $fieldsStr = implode(', ', $orderFields);
            $valuesPlaceholders = implode(', ', array_fill(0, count($orderFields), '?'));
            
            $query = "INSERT INTO orders ($fieldsStr) VALUES ($valuesPlaceholders)";
            echo "SQL Query: $query<br>";
            
            // Prepare statement
            $stmt = mysqli_prepare($conn, $query);
            if (!$stmt) {
                echo "Error preparing statement: " . mysqli_error($conn) . "<br>";
            } else {
                // Build types string
                $types = '';
                foreach ($orderValues as $value) {
                    if (is_int($value)) $types .= 'i';
                    elseif (is_float($value)) $types .= 'd';
                    else $types .= 's';
                }
                
                // Bind parameters
                $bindParams = array($stmt, $types);
                foreach ($orderValues as $key => $value) {
                    $bindParams[] = &$orderValues[$key];
                }
                call_user_func_array('mysqli_stmt_bind_param', $bindParams);
                
                // Execute
                $result = mysqli_stmt_execute($stmt);
                if (!$result) {
                    echo "Error executing statement: " . mysqli_stmt_error($stmt) . "<br>";
                } else {
                    $orderId = mysqli_insert_id($conn);
                    echo "Order created successfully (ID: $orderId)<br>";
                    
                    // Clean up - delete test order
                    mysqli_query($conn, "DELETE FROM orders WHERE order_id = $orderId");
                    echo "Test order deleted<br>";
                }
                
                mysqli_stmt_close($stmt);
            }
        }
    }
} catch (Exception $e) {
    echo "Test order error: " . $e->getMessage() . "<br>";
}

// Test 4: curl_init and HTTPS capabilities
echo "<h3>Test 4: cURL and HTTPS</h3>";
try {
    if (!function_exists('curl_init')) {
        echo "cURL is not available<br>";
    } else {
        echo "cURL is available<br>";
        
        $ch = curl_init('https://api.paymongo.com/v1');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        
        if ($error) {
            echo "cURL error: $error<br>";
        } else {
            echo "cURL request successful<br>";
            echo "HTTP Status: " . $info['http_code'] . "<br>";
        }
        
        curl_close($ch);
    }
} catch (Exception $e) {
    echo "cURL error: " . $e->getMessage() . "<br>";
}

// Log script end
file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Debug script completed\n", FILE_APPEND);
?>
