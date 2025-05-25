<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'config.php';

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Sanitize input data
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    if ($conn) {
        $data = mysqli_real_escape_string($conn, $data);
    }
    return $data;
}

/**
 * Register a new user
 * @param array $userData
 * @return array
 */
function registerUser($userData) {
    global $conn;
    
    // Validate required fields
    $requiredFields = ['first_name', 'last_name', 'email', 'password', 'confirm_password'];
    foreach ($requiredFields as $field) {
        if (empty($userData[$field])) {
            return ['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'];
        }
    }
    
    // Validate email
    if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }
    
    // Validate password match
    if ($userData['password'] !== $userData['confirm_password']) {
        return ['success' => false, 'message' => 'Passwords do not match'];
    }
    
    // Check if email already exists
    $email = sanitizeInput($userData['email']);
    $checkEmail = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $checkEmail);
    
    if (mysqli_num_rows($result) > 0) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    // Hash password
    $password = password_hash($userData['password'], PASSWORD_DEFAULT);
    
    // Prepare user data for insertion
    $firstName = sanitizeInput($userData['first_name']);
    $lastName = sanitizeInput($userData['last_name']);
    $phone = !empty($userData['phone']) ? sanitizeInput($userData['phone']) : '';
    $address = !empty($userData['address']) ? sanitizeInput($userData['address']) : '';
    
    // Insert user into database
    $query = "INSERT INTO users (first_name, last_name, email, phone, password, address) 
              VALUES ('$firstName', '$lastName', '$email', '$phone', '$password', '$address')";
    
    if (mysqli_query($conn, $query)) {
        return ['success' => true, 'message' => 'Registration successful'];
    } else {
        return ['success' => false, 'message' => 'Registration failed: ' . mysqli_error($conn)];
    }
}

/**
 * Login user
 * @param string $email
 * @param string $password
 * @param bool $remember
 * @return array
 */
function loginUser($email, $password, $remember = false) {
    global $conn;
    
    // Sanitize input
    $email = sanitizeInput($email);
    
    // Get user from database
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Store user data in session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'] ?? 'customer'; // Store user role
            
            // Set remember me cookie if requested
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                
                setcookie('remember_token', $token, $expiry, '/');
                setcookie('remember_user', $user['user_id'], $expiry, '/');
                
                // Store token in database (you would need a remember_tokens table)
                // This is just a placeholder - implement full token storage for production
                // storeRememberToken($user['user_id'], $token, $expiry);
            }
            
            return ['success' => true, 'message' => 'Login successful'];
        } else {
            return ['success' => false, 'message' => 'Invalid password'];
        }
    } else {
        return ['success' => false, 'message' => 'Email not found'];
    }
}

/**
 * Get user profile
 * @param int $userId
 * @return array|null
 */
function getUserProfile($userId) {
    global $conn;
    
    $userId = (int)$userId;
    $query = "SELECT user_id, first_name, last_name, email, phone, address, latitude, longitude, created_at 
              FROM users WHERE user_id = $userId";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

/**
 * Update user profile
 * @param int $userId
 * @param array $userData
 * @return array
 */
function updateUserProfile($userId, $userData) {
    global $conn;
    
    $userId = (int)$userId;
    
    // Sanitize inputs
    $firstName = sanitizeInput($userData['first_name']);
    $lastName = sanitizeInput($userData['last_name']);
    $phone = sanitizeInput($userData['phone']);
    $address = sanitizeInput($userData['address']);
    
    // Get latitude and longitude if provided
    $latitude = isset($userData['latitude']) ? floatval($userData['latitude']) : null;
    $longitude = isset($userData['longitude']) ? floatval($userData['longitude']) : null;
    
    // Build update query
    $query = "UPDATE users SET 
              first_name = '$firstName',
              last_name = '$lastName',
              phone = '$phone',
              address = '$address'";
    
    // Add coordinates to the query if they're provided
    if ($latitude !== null && $longitude !== null) {
        $query .= ",
              latitude = $latitude,
              longitude = $longitude";
    }
    
    $query .= " WHERE user_id = $userId";
    
    if (mysqli_query($conn, $query)) {
        // Update session data
        $_SESSION['first_name'] = $firstName;
        $_SESSION['last_name'] = $lastName;
        
        return ['success' => true, 'message' => 'Profile updated successfully'];
    } else {
        return ['success' => false, 'message' => 'Update failed: ' . mysqli_error($conn)];
    }
}

/**
 * Change user password
 * @param int $userId
 * @param string $currentPassword
 * @param string $newPassword
 * @return array
 */
function changePassword($userId, $currentPassword, $newPassword) {
    global $conn;
    
    $userId = (int)$userId;
    
    // Get current user password
    $query = "SELECT password FROM users WHERE user_id = $userId";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Verify current password
        if (password_verify($currentPassword, $user['password'])) {
            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $updateQuery = "UPDATE users SET password = '$hashedPassword' WHERE user_id = $userId";
            
            if (mysqli_query($conn, $updateQuery)) {
                return ['success' => true, 'message' => 'Password changed successfully'];
            } else {
                return ['success' => false, 'message' => 'Password change failed: ' . mysqli_error($conn)];
            }
        } else {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
    }
    
    return ['success' => false, 'message' => 'User not found'];
}

/**
 * Logout user
 */
function logoutUser() {
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
    
    // Delete remember me cookies if they exist
    if (isset($_COOKIE['remember_token']) || isset($_COOKIE['remember_user'])) {
        setcookie('remember_token', '', time() - 3600, '/');
        setcookie('remember_user', '', time() - 3600, '/');
    }
}

/**
 * Get or create a cart for the user
 * @param int $user_id
 * @return int cart_id
 */
function getUserCart($user_id) {
    global $conn;
    
    // Check if user already has a cart
    $stmt = $conn->prepare("SELECT cart_id FROM carts WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Return existing cart ID
        $row = $result->fetch_assoc();
        return $row['cart_id'];
    } else {
        // Create new cart
        $stmt = $conn->prepare("INSERT INTO carts (user_id) VALUES (?)");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $conn->insert_id;
    }
}

/**
 * Get cart items for a user
 * @param int $user_id
 * @return array
 */
function getCartItems($user_id) {
    global $conn;
    
    $cart_id = getUserCart($user_id);
    
    $stmt = $conn->prepare("
        SELECT ci.*, p.name, p.image_path 
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.product_id
        WHERE ci.cart_id = ?
    ");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Add item to cart
 * @param int $user_id
 * @param int $product_id
 * @param int $quantity
 * @param float $price
 * @return bool
 */
function addToCart($user_id, $product_id, $quantity, $price) {
    global $conn;
    
    $cart_id = getUserCart($user_id);
    
    // Check if product already exists in cart
    $stmt = $conn->prepare("SELECT item_id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $cart_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update quantity if product already in cart
        $row = $result->fetch_assoc();
        $new_quantity = $row['quantity'] + $quantity;
        
        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE item_id = ?");
        $stmt->bind_param("ii", $new_quantity, $row['item_id']);
        return $stmt->execute();
    } else {
        // Add new product to cart
        $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $cart_id, $product_id, $quantity, $price);
        return $stmt->execute();
    }
}

/**
 * Update cart item quantity
 * @param int $user_id
 * @param int $item_id
 * @param int $quantity
 * @return bool
 */
function updateCartItem($user_id, $item_id, $quantity) {
    global $conn;
    
    $cart_id = getUserCart($user_id);
    
    $stmt = $conn->prepare("UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE item_id = ? AND cart_id = ?");
    $stmt->bind_param("iii", $quantity, $item_id, $cart_id);
    return $stmt->execute();
}

/**
 * Remove item from cart
 * @param int $user_id
 * @param int $item_id
 * @return bool
 */
function removeFromCart($user_id, $item_id) {
    global $conn;
    
    $cart_id = getUserCart($user_id);
    
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE item_id = ? AND cart_id = ?");
    $stmt->bind_param("ii", $item_id, $cart_id);
    return $stmt->execute();
}

/**
 * Sync localStorage cart with database
 * @param int $user_id
 * @param array $cart_items
 * @return bool
 */
function syncCart($user_id, $cart_items) {
    global $conn;
    
    // Start debug log
    $debug_log = fopen("cart_sync_debug.log", "a");
    fwrite($debug_log, "\n\n========= CART SYNC START: " . date('Y-m-d H:i:s') . " =========\n");
    fwrite($debug_log, "User ID: $user_id\n");
    fwrite($debug_log, "Cart Items: " . print_r($cart_items, true) . "\n");
    
    // Get or create cart
    $cart_id = getUserCart($user_id);
    fwrite($debug_log, "Cart ID: $cart_id\n");
    
    // Check database connection
    if ($conn->connect_error) {
        fwrite($debug_log, "DATABASE ERROR: Connection failed: " . $conn->connect_error . "\n");
        fwrite($debug_log, "========= CART SYNC FAILED =========\n");
        fclose($debug_log);
        return false;
    }
    
    fwrite($debug_log, "Database connection successful\n");
    
    // Begin transaction for data consistency
    $conn->begin_transaction();
    fwrite($debug_log, "Transaction started\n");
    
    try {
        // Clear existing cart items (optional - could merge instead)
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
        if (!$stmt) {
            throw new Exception("Prepare statement for delete failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $cart_id);
        $result = $stmt->execute();
        
        if (!$result) {
            throw new Exception("Delete execution failed: " . $stmt->error);
        }
        
        fwrite($debug_log, "Cleared existing cart items for cart_id: $cart_id\n");
        
        // Add new items from localStorage
        fwrite($debug_log, "Adding new items to cart...\n");
        
        foreach ($cart_items as $index => $item) {
            // Validate data
            $product_id = isset($item['product_id']) ? intval($item['product_id']) : 0;
            $quantity = isset($item['quantity']) ? intval($item['quantity']) : 1;
            $price = isset($item['price']) ? floatval($item['price']) : 0;
            
            fwrite($debug_log, "Item $index: product_id=$product_id, quantity=$quantity, price=$price\n");
            
            if ($product_id <= 0) {
                fwrite($debug_log, "WARNING: Invalid product_id for item $index\n");
                continue;
            }
            
            $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Prepare statement for insert failed: " . $conn->error);
            }
            
            $stmt->bind_param("iiid", $cart_id, $product_id, $quantity, $price);
            $result = $stmt->execute();
            
            if (!$result) {
                throw new Exception("Insert execution failed for item $index: " . $stmt->error);
            }
            
            fwrite($debug_log, "Successfully added item $index to cart\n");
        }
        
        // Commit the transaction
        $conn->commit();
        fwrite($debug_log, "Transaction committed successfully\n");
        
        // Verify the items were added
        $verify_stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart_items WHERE cart_id = ?");
        $verify_stmt->bind_param("i", $cart_id);
        $verify_stmt->execute();
        $result = $verify_stmt->get_result();
        $row = $result->fetch_assoc();
        
        fwrite($debug_log, "Verification: Found {$row['count']} items in cart_id: $cart_id\n");
        fwrite($debug_log, "========= CART SYNC COMPLETED SUCCESSFULLY =========\n");
        fclose($debug_log);
        
        return true;
    } catch (Exception $e) {
        // Rollback in case of error
        $conn->rollback();
        
        fwrite($debug_log, "ERROR: " . $e->getMessage() . "\n");
        fwrite($debug_log, "Stack trace: " . $e->getTraceAsString() . "\n");
        fwrite($debug_log, "Transaction rolled back\n");
        fwrite($debug_log, "========= CART SYNC FAILED =========\n");
        fclose($debug_log);
        
        return false;
    }
}

/**
 * Generate unique order number
 * @return string
 */
function generateOrderNumber() {
    $prefix = 'LF';
    $timestamp = date('ymdHi'); // YearMonthDayHourMinute
    $random = mt_rand(1000, 9999); // Random 4-digit number
    return $prefix . $timestamp . $random;
}

/**
 * Get order number for a specific order ID
 * @param int $order_id
 * @return string|null
 */
function getOrderNumber($order_id) {
    // Since we don't have an order_number column in the database,
    // we'll use a prefix with the order_id to create a formatted order number
    $prefix = 'LF';
    $formatted_id = str_pad($order_id, 6, '0', STR_PAD_LEFT);
    return $prefix . $formatted_id;
}

/**
 * Create a new order
 * @param int $user_id
 * @param float $total_amount
 * @param string $payment_method
 * @param string $delivery_address
 * @param string $contact_number
 * @param string $special_instructions
 * @return int|false
 */
function createOrder($user_id, $total_amount, $payment_method, $delivery_address, $contact_number, $special_instructions = '') {
    global $conn;
    
    // Debug log the payment method
    error_log("Payment method received: " . $payment_method);
    
    // Map to one of the exact ENUM values defined in the database
    // The database has: enum('credit_card', 'pay_at_delivery', 'online_payment', 'bank_transfer')
    if ($payment_method === 'cash_on_delivery') {
        $payment_method = 'pay_at_delivery';
    } else if ($payment_method === 'credit_card') {
        $payment_method = 'credit_card';
    } else if ($payment_method === 'gcash') {
        $payment_method = 'online_payment';
    } else {
        $payment_method = 'pay_at_delivery'; // Default fallback
    }
    
    error_log("Mapped payment method to ENUM value: " . $payment_method);
    
    // Get current status and payment status
    $status = 'pending';
    $payment_status = ($payment_method === 'pay_at_delivery') ? 'pending' : 'paid';
    
    // Create new order based on actual database structure
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, payment_method, payment_status, delivery_address) VALUES (?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    // Debug the SQL and parameters
    error_log("SQL: INSERT INTO orders (user_id, total_amount, status, payment_method, payment_status, delivery_address) VALUES ({$user_id}, {$total_amount}, {$status}, {$payment_method}, {$payment_status}, {$delivery_address})");
    
    $stmt->bind_param("idssss", $user_id, $total_amount, $status, $payment_method, $payment_status, $delivery_address);
    
    if ($stmt->execute()) {
        $order_id = $conn->insert_id;
        
        // Add initial status to history
        addOrderStatusHistory($order_id, 'pending', 'Order placed' . ($special_instructions ? " - Note: {$special_instructions}" : ''), $user_id);
        
        return $order_id;
    } else {
        error_log("Execute failed: " . $stmt->error);
        return false;
    }
}

/**
 * Add item to order
 * @param int $order_id
 * @param int $product_id
 * @param int $quantity
 * @param float $price
 * @param string $item_name Item name to use for matching with database products
 * @return bool
 */
function addOrderItem($order_id, $product_id, $quantity, $price, $item_name = '') {
    global $conn;
    
    // First try using the provided product_id
    if ($product_id > 0) {
        // Check if this product_id exists
        $check_query = "SELECT COUNT(*) FROM products WHERE product_id = ?";
        $check_stmt = $conn->prepare($check_query);
        
        if (!$check_stmt) {
            error_log("Prepare check failed: " . $conn->error);
            return false;
        }
        
        $check_stmt->bind_param("i", $product_id);
        $check_stmt->execute();
        $check_stmt->bind_result($count);
        $check_stmt->fetch();
        $check_stmt->close();
        
        // If product exists, use it
        if ($count > 0) {
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            
            if (!$stmt) {
                error_log("Prepare failed: " . $conn->error);
                return false;
            }
            
            $stmt->bind_param("iiid", $order_id, $product_id, $quantity, $price);
            return $stmt->execute();
        }
    }
    
    // If we reach here, either product_id was 0 or the product wasn't found
    // Try to match the item with an existing product based on name and price
    if (!empty($item_name)) {
        // Look for a matching product by name and price
        $find_query = "SELECT product_id FROM products WHERE LOWER(name) = LOWER(?) AND price = ? LIMIT 1";
        $find_stmt = $conn->prepare($find_query);
        
        if (!$find_stmt) {
            error_log("Prepare find query failed: " . $conn->error);
            return false;
        }
        
        $find_stmt->bind_param("sd", $item_name, $price);
        $find_stmt->execute();
        $find_stmt->bind_result($matching_product_id);
        $found = $find_stmt->fetch();
        $find_stmt->close();
        
        if ($found && $matching_product_id > 0) {
            // We found a matching product, use its ID
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            
            if (!$stmt) {
                error_log("Prepare failed after finding match: " . $conn->error);
                return false;
            }
            
            $stmt->bind_param("iiid", $order_id, $matching_product_id, $quantity, $price);
            $result = $stmt->execute();
            
            if ($result) {
                error_log("Successfully matched item '{$item_name}' with product_id {$matching_product_id}");
                return true;
            } else {
                error_log("Failed to insert matched item: " . $stmt->error);
                return false;
            }
        }
    }
    
    // If we still don't have a match, try inserting a generic menu item
    $generic_query = "SELECT product_id FROM products WHERE name = 'Menu Item' LIMIT 1";
    $generic_result = $conn->query($generic_query);
    
    if ($generic_result && $generic_result->num_rows > 0) {
        $generic_item = $generic_result->fetch_assoc();
        $generic_id = $generic_item['product_id'];
        
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, item_name) VALUES (?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            error_log("Prepare failed for generic item: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("iidds", $order_id, $generic_id, $quantity, $price, $item_name);
        return $stmt->execute();
    }
    
    // Last resort: check if the order_items table has an item_name column, add it if not
    $check_column = "SHOW COLUMNS FROM order_items LIKE 'item_name'";
    $column_result = $conn->query($check_column);
    
    if ($column_result && $column_result->num_rows == 0) {
        // Add item_name column to order_items table
        $alter_query = "ALTER TABLE order_items ADD COLUMN item_name VARCHAR(255) NULL";
        if (!$conn->query($alter_query)) {
            error_log("Failed to add item_name column: " . $conn->error);
            return false;
        }
    }
    
    // Use NULL for product_id since we couldn't find a match
    // This works if the foreign key constraint allows NULL values
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, item_name) VALUES (?, NULL, ?, ?, ?)");
    
    if (!$stmt) {
        error_log("Prepare failed for NULL product_id: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("iids", $order_id, $quantity, $price, $item_name);
    $result = $stmt->execute();
    
    if (!$result) {
        error_log("Failed to insert with NULL product_id: " . $stmt->error . ". Checking constraint...");
        
        // If this failed, the NULL might not be allowed for the foreign key
        // Try to create a fallback product for these cases
        if (!createFallbackProduct()) {
            error_log("Could not create fallback product");
            return false;
        }
        
        // Try again with the fallback product
        $fallback_id = getFallbackProductId();
        if (!$fallback_id) {
            error_log("Could not get fallback product ID");
            return false;
        }
        
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, item_name) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            error_log("Prepare failed for fallback product: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("iiids", $order_id, $fallback_id, $quantity, $price, $item_name);
        return $stmt->execute();
    }
    
    return $result;
}

/**
 * Create a fallback product to use when no matching product is found
 * @return bool Success/failure
 */
function createFallbackProduct() {
    global $conn;
    
    // Check if fallback product already exists
    $check_query = "SELECT product_id FROM products WHERE name = 'Custom Menu Item' LIMIT 1";
    $result = $conn->query($check_query);
    
    if ($result && $result->num_rows > 0) {
        // Fallback product already exists
        return true;
    }
    
    // Create the fallback product
    $insert_query = "INSERT INTO products (name, description, price, category_id, image_url) 
                      VALUES ('Custom Menu Item', 'Custom item added from cart', 0.00, 1, 'assets/default-item.jpg')";
    
    return $conn->query($insert_query);
}

/**
 * Get the ID of the fallback product
 * @return int|bool Product ID or false on failure
 */
function getFallbackProductId() {
    global $conn;
    
    $query = "SELECT product_id FROM products WHERE name = 'Custom Menu Item' LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['product_id'];
    }
    
    return false;
}

/**
 * Add order status history - NOOP function since table doesn't exist
 * This function is kept for compatibility but doesn't do anything since
 * the order_status_history table doesn't exist.
 * 
 * @param int $order_id
 * @param string $status
 * @param string $notes
 * @param int $updated_by
 * @return bool
 */
function addOrderStatusHistory($order_id, $status, $notes = '', $updated_by = null) {
    // Just return true since we don't have this table
    // This prevents errors when this function is called
    error_log("Note: addOrderStatusHistory was called but the table doesn't exist in this database.");
    return true;
}

/**
 * Delete order and all associated items
 * @param int $order_id
 * @return bool
 */
function deleteOrder($order_id) {
    global $conn;
    
    // Start transaction to ensure all deletes succeed or fail together
    $conn->begin_transaction();
    
    try {
        // First delete the order items (due to foreign key constraints)
        $items_stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        
        if (!$items_stmt) {
            throw new Exception("Prepare failed for items delete: " . $conn->error);
        }
        
        $items_stmt->bind_param("i", $order_id);
        $items_stmt->execute();
        
        // Then delete the order
        $order_stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
        
        if (!$order_stmt) {
            throw new Exception("Prepare failed for order delete: " . $conn->error);
        }
        
        $order_stmt->bind_param("i", $order_id);
        $order_stmt->execute();
        
        // If we got here, commit the transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Something went wrong, rollback the transaction
        $conn->rollback();
        error_log("Error deleting order: " . $e->getMessage());
        return false;
    }
}

/**
 * Clear user's cart after successful order
 * @param int $user_id
 * @return bool
 */
function clearCart($user_id) {
    global $conn;
    
    $cart_id = getUserCart($user_id);
    
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("i", $cart_id);
    
    return $stmt->execute();
}

/**
 * Get all orders for a user
 * @param int $user_id
 * @return array
 */
function getUserOrders($user_id) {
    global $conn;
    
    // Order by created_at instead of order_date which doesn't exist in your schema
    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    
    // Add order_number and order_date to each order
    foreach ($orders as &$order) {
        $order['order_number'] = getOrderNumber($order['order_id']);
        if (isset($order['created_at'])) {
            $order['order_date'] = $order['created_at'];
        }
    }
    
    return $orders;
}

/**
 * Get order details including items
 * @param int $order_id
 * @param int $user_id (optional) - to verify order belongs to user
 * @return array|null
 */
function getOrderDetails($order_id, $user_id = null) {
    global $conn;
    
    $query = "SELECT * FROM orders WHERE order_id = ?";
    $params = [$order_id];
    $types = "i";
    
    if ($user_id !== null) {
        $query .= " AND user_id = ?";
        $params[] = $user_id;
        $types .= "i";
    }
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    $order = $result->fetch_assoc();
    
    // Add a formatted order number based on order_id
    $order['order_number'] = getOrderNumber($order_id);
    
    // Format order dates if they exist
    if (isset($order['created_at'])) {
        $order['order_date'] = $order['created_at'];
    }
    
    // Map contact_number if it doesn't exist
    if (!isset($order['contact_number']) && isset($order['user_id'])) {
        // Get user phone from users table
        $user_stmt = $conn->prepare("SELECT phone FROM users WHERE user_id = ?");
        $user_stmt->bind_param("i", $order['user_id']);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        if ($user_result->num_rows > 0) {
            $user = $user_result->fetch_assoc();
            $order['contact_number'] = $user['phone'];
        }
    }
    
    // Get order items
    $stmt = $conn->prepare("
        SELECT oi.*, p.name, p.name as item_name, p.image_path 
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
    ");
    
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $order['items'] = $result->fetch_all(MYSQLI_ASSOC);
    
    // Since order_status_history table doesn't exist, create a simple status history
    $order['status_history'] = [
        [
            'status' => $order['status'],
            'notes' => 'Order placed',
            'status_date' => $order['created_at'] ?? date('Y-m-d H:i:s'),
            'first_name' => 'System',
            'last_name' => ''
        ]
    ];
    
    return $order;
}

/**
 * Get all orders (admin function)
 * @param string $status (optional) - filter by status
 * @return array
 */
function getAllOrders($status = null) {
    global $conn;
    
    $query = "SELECT o.*, u.first_name, u.last_name, u.email 
              FROM orders o 
              JOIN users u ON o.user_id = u.user_id";
    
    if ($status !== null) {
        $query .= " WHERE o.status = ?";
    }
    
    $query .= " ORDER BY o.order_date DESC";
    
    $stmt = $conn->prepare($query);
    
    if ($status !== null) {
        $stmt->bind_param("s", $status);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Update order status
 * @param int $order_id
 * @param string $status
 * @param string $notes
 * @param int $updated_by
 * @return bool
 */
function updateOrderStatus($order_id, $status, $notes = '', $updated_by = null) {
    return addOrderStatusHistory($order_id, $status, $notes, $updated_by);
}

/**
 * Cancel order
 * @param int $order_id
 * @param int $user_id
 * @param string $reason
 * @return bool
 */
function cancelOrder($order_id, $user_id, $reason = '') {
    // Check if order belongs to user
    $order = getOrderDetails($order_id, $user_id);
    
    if (!$order) {
        return false;
    }
    
    // Only allow cancellation if order is pending or processing
    if ($order['status'] !== 'pending' && $order['status'] !== 'processing') {
        return false;
    }
    
    // Add cancellation to history
    return updateOrderStatus($order_id, 'cancelled', $reason, $user_id);
}

/**
 * Check if user is admin
 * @return bool
 */
function isAdmin() {
    // First check session for user_role
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        return true;
    }
    
    // If role not in session but user_id is, check the database
    if (isset($_SESSION['user_id'])) {
        global $conn;
        
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
        
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $user = $result->fetch_assoc();
    return $user['role'] === 'admin';
}}

/**
 * Delete user account
 * @param int $userId
 * @return array
 */
function deleteUserAccount($userId) {
    global $conn;
    
    $userId = (int)$userId;
    
    // Check if user exists
    $query = "SELECT user_id FROM users WHERE user_id = $userId";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
                // Delete cart items first
                $cartQuery = "DELETE ci FROM cart_items ci 
                             JOIN carts c ON ci.cart_id = c.cart_id 
                             WHERE c.user_id = $userId";
                mysqli_query($conn, $cartQuery);
                
                // Delete cart
                $deleteCartQuery = "DELETE FROM carts WHERE user_id = $userId";
                mysqli_query($conn, $deleteCartQuery);
                
                // Update orders to anonymize them instead of deleting
                $updateOrdersQuery = "UPDATE orders SET user_id = NULL, 
                                     delivery_address = 'Account deleted', 
                                     notes = CONCAT(IFNULL(notes, ''), ' - User account deleted') 
                                     WHERE user_id = $userId";
                mysqli_query($conn, $updateOrdersQuery);
                
                // Finally delete the user
                $deleteUserQuery = "DELETE FROM users WHERE user_id = $userId";
                $deleteResult = mysqli_query($conn, $deleteUserQuery);
                
                if ($deleteResult) {
                    mysqli_commit($conn);
                    // Clear session data
                    $_SESSION = [];
                    session_destroy();
                    return ['success' => true, 'message' => 'Your account has been permanently deleted.'];
                } else {
                    throw new Exception(mysqli_error($conn));
                }
            } catch (Exception $e) {
                mysqli_rollback($conn);
                return ['success' => false, 'message' => 'Error deleting account: ' . $e->getMessage()];
            }
    }
    
    return ['success' => false, 'message' => 'Account not found.'];
}
?>
