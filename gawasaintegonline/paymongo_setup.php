<?php
/**
 * PayMongo Setup Script
 * 
 * Run this script in your browser to set up the database for PayMongo integration
 */

// Set page header
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayMongo Integration Setup</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        body {
            padding: 2rem;
            background-color: #f8f9fa;
        }
        .setup-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .log-item {
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            border-radius: 4px;
        }
        .log-success {
            background-color: #d4edda;
            color: #155724;
        }
        .log-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .log-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <h1 class="mb-4">PayMongo Integration Setup</h1>
        
        <div class="alert alert-info mb-4">
            <h4 class="alert-heading">About This Script</h4>
            <p>This script will set up your database for PayMongo integration, adding necessary fields to store payment information.</p>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="mb-0">Database Setup Log</h3>
            </div>
            <div class="card-body">
                <div id="setup-log">
                    <?php
                    // Include database connection
                    require_once 'includes/functions.php';
                    
                    // Define the SQL queries
                    $queries = [
                        // Add PayMongo fields to orders table if they don't exist
                        "ALTER TABLE orders 
                         ADD COLUMN IF NOT EXISTS paymongo_session_id VARCHAR(255) NULL,
                         ADD COLUMN IF NOT EXISTS paymongo_payment_id VARCHAR(255) NULL,
                         ADD COLUMN IF NOT EXISTS paymongo_payment_method VARCHAR(50) NULL"
                    ];
                    
                    // Create logs directory if it doesn't exist
                    $logsDir = __DIR__ . '/logs';
                    if (!file_exists($logsDir)) {
                        mkdir($logsDir, 0755, true);
                        echo '<div class="log-item log-info">Created logs directory</div>';
                    }
                    
                    // Run each query and handle errors
                    echo '<div class="log-item log-info">Starting database update for PayMongo integration...</div>';
                    $success = true;
                    
                    foreach ($queries as $query) {
                        try {
                            // Use the global connection from functions.php
                            global $conn;
                            
                            // Check if we can modify the orders table
                            $checkQuery = "SHOW TABLES LIKE 'orders'";
                            $result = mysqli_query($conn, $checkQuery);
                            
                            if (mysqli_num_rows($result) == 0) {
                                echo '<div class="log-item log-error">Error: Orders table does not exist!</div>';
                                $success = false;
                                break;
                            }
                            
                            // Execute the query
                            $result = mysqli_query($conn, $query);
                            
                            if ($result) {
                                echo '<div class="log-item log-success">Success: ' . htmlspecialchars(substr($query, 0, 100)) . '...</div>';
                            } else {
                                echo '<div class="log-item log-error">Error: ' . htmlspecialchars(mysqli_error($conn)) . ' in query: ' . htmlspecialchars(substr($query, 0, 100)) . '...</div>';
                                $success = false;
                            }
                        } catch (Exception $e) {
                            echo '<div class="log-item log-error">Exception: ' . htmlspecialchars($e->getMessage()) . '</div>';
                            $success = false;
                        }
                    }
                    
                    // Try to create the index separately (it might fail if it already exists)
                    try {
                        $indexQuery = "CREATE INDEX idx_paymongo_session ON orders (paymongo_session_id)";
                        $result = mysqli_query($conn, $indexQuery);
                        
                        if ($result) {
                            echo '<div class="log-item log-success">Success: Created index on paymongo_session_id</div>';
                        } else {
                            // Only show error if it's not about the index already existing
                            $error = mysqli_error($conn);
                            if (strpos($error, 'Duplicate') === false) {
                                echo '<div class="log-item log-error">Error creating index: ' . htmlspecialchars($error) . '</div>';
                            } else {
                                echo '<div class="log-item log-info">Note: Index already exists on paymongo_session_id</div>';
                            }
                        }
                    } catch (Exception $e) {
                        echo '<div class="log-item log-error">Exception creating index: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                    
                    // Final status
                    if ($success) {
                        echo '<div class="alert alert-success mt-3">Database updated successfully for PayMongo integration!</div>';
                    } else {
                        echo '<div class="alert alert-danger mt-3">Database update completed with errors. Please check the log above.</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <h3 class="mb-3">Next Steps</h3>
        <div class="card mb-4">
            <div class="card-body">
                <ol>
                    <li>Create checkout success and cancel pages</li>
                    <li>Update the checkout page to include PayMongo payment options</li>
                    <li>Test the payment flow with PayMongo test credentials</li>
                </ol>
                
                <div class="mt-3">
                    <a href="index.html" class="btn btn-primary">Return to Homepage</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
