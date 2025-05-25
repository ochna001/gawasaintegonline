<?php
require_once 'includes/functions.php';

// Start our detailed debug log
$debug_log = fopen('cart_debug.log', 'a');
fwrite($debug_log, "\n\n========= CART SYNC REQUEST: " . date('Y-m-d H:i:s') . " =========\n");

// Set header to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    $error_message = 'User not logged in';
    fwrite($debug_log, "ERROR: {$error_message}\n");
    fclose($debug_log);
    echo json_encode(['success' => false, 'message' => $error_message]);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];
fwrite($debug_log, "User ID: {$user_id}\n");

// Log all headers for debugging
fwrite($debug_log, "Request Headers:\n");
foreach (getallheaders() as $name => $value) {
    fwrite($debug_log, "$name: $value\n");
}

// Get cart data from POST request
$raw_data = file_get_contents('php://input');
fwrite($debug_log, "Raw input data length: " . strlen($raw_data) . " bytes\n");
fwrite($debug_log, "Raw input data: " . $raw_data . "\n");

// Log POST data
fwrite($debug_log, "POST data:\n" . print_r($_POST, true) . "\n");

// Parse the data from request
if (!empty($raw_data)) {
    // Extract cart_items parameter from raw input
    fwrite($debug_log, "Attempting to extract cart_items from raw input\n");
    parse_str($raw_data, $post_data);
    fwrite($debug_log, "Extracted data: " . print_r($post_data, true) . "\n");
    
    if (isset($post_data['cart_items'])) {
        fwrite($debug_log, "Attempting to parse cart_items JSON\n");
        $cart_items = json_decode($post_data['cart_items'], true);
        fwrite($debug_log, "JSON decode result: " . (json_last_error() === JSON_ERROR_NONE ? 'Success' : 'Failed - ' . json_last_error_msg()) . "\n");
    } else {
        fwrite($debug_log, "No cart_items found in raw input\n");
        $cart_items = [];
    }
} 
// Then try to get from POST form data
elseif (isset($_POST['cart_items'])) {
    fwrite($debug_log, "Attempting to parse cart_items from POST data\n");
    $cart_items = json_decode($_POST['cart_items'], true);
    fwrite($debug_log, "POST cart_items decode result: " . (json_last_error() === JSON_ERROR_NONE ? 'Success' : 'Failed - ' . json_last_error_msg()) . "\n");
} else {
    fwrite($debug_log, "No cart_items found in POST data\n");
    $cart_items = [];
}

fwrite($debug_log, "Parsed cart items: " . print_r($cart_items, true) . "\n");

// Validate cart data
if (!is_array($cart_items)) {
    $error_message = 'Invalid cart data format';
    fwrite($debug_log, "ERROR: {$error_message} - cart_items is not an array\n");
    fclose($debug_log);
    echo json_encode(['success' => false, 'message' => $error_message]);
    exit;
}

fwrite($debug_log, "Cart items count: " . count($cart_items) . "\n");

if (empty($cart_items)) {
    $message = 'Cart is empty';
    fwrite($debug_log, "INFO: {$message}\n");
    fwrite($debug_log, "Returning success=true for empty cart\n");
    fclose($debug_log);
    // For empty cart, we'll return success since there's nothing to sync
    echo json_encode(['success' => true, 'message' => $message, 'cart_items' => []]);
    exit;
}

// Process cart items to ensure proper data types
fwrite($debug_log, "Processing cart items to ensure proper data types...\n");

foreach ($cart_items as $key => &$item) {
    fwrite($debug_log, "Processing item {$key}: " . print_r($item, true) . "\n");
    
    // Check if item has product_id
    if (!isset($item['product_id'])) {
        fwrite($debug_log, "ERROR: Item {$key} missing product_id, skipping\n");
        unset($cart_items[$key]);
        continue;
    }
    
    // Ensure product_id is an integer
    $original_product_id = $item['product_id'];
    $item['product_id'] = intval($item['product_id']);
    fwrite($debug_log, "Converted product_id from {$original_product_id} to {$item['product_id']}\n");
    
    // Ensure quantity is an integer and at least 1
    $original_quantity = isset($item['quantity']) ? $item['quantity'] : 1;
    $item['quantity'] = max(1, intval($item['quantity'] ?? 1));
    fwrite($debug_log, "Processed quantity from {$original_quantity} to {$item['quantity']}\n");
    
    // Ensure price is a float (handle both number and string formats)
    if (isset($item['price'])) {
        $original_price = $item['price'];
        // If price is a string (might include currency symbol), clean it
        if (is_string($item['price'])) {
            // Remove any non-numeric characters except decimal point
            $item['price'] = preg_replace('/[^0-9.]/', '', $item['price']);
            fwrite($debug_log, "Cleaned price string from {$original_price} to {$item['price']}\n");
        }
        $item['price'] = floatval($item['price']);
        fwrite($debug_log, "Final price: {$item['price']}\n");
    } else {
        // If price doesn't exist, set to 0 (or fetch from database)
        fwrite($debug_log, "Price not set, defaulting to 0\n");
        $item['price'] = 0;
    }
}

// Remove empty items
$cart_items = array_filter($cart_items);
$cart_items = array_values($cart_items); // Re-index array

fwrite($debug_log, "Final processed cart items: " . print_r($cart_items, true) . "\n");

// Check if we still have items after validation
if (empty($cart_items)) {
    $message = 'No valid items in cart after processing';
    fwrite($debug_log, "WARNING: {$message}\n");
    fclose($debug_log);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// Sync cart with database
fwrite($debug_log, "Calling syncCart function with user_id: {$user_id}\n");
$result = syncCart($user_id, $cart_items);

fwrite($debug_log, "syncCart result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n");

if ($result) {
    // Return updated cart items from database
    $updated_items = getCartItems($user_id);
    fwrite($debug_log, "getCartItems returned: " . print_r($updated_items, true) . "\n");
    fwrite($debug_log, "Sync completed successfully\n");
    fclose($debug_log);
    echo json_encode(['success' => true, 'cart_items' => $updated_items]);
} else {
    fwrite($debug_log, "Failed to sync cart with database\n");
    fclose($debug_log);
    echo json_encode(['success' => false, 'message' => 'Failed to sync cart with database']);
}
?>
