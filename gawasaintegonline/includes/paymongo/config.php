<?php
/**
 * PayMongo Configuration
 * 
 * This file contains the API keys and configuration for PayMongo integration.
 * IMPORTANT: Keep this file secure and never expose the secret key.
 */

// API Keys
define('PAYMONGO_PUBLIC_KEY', 'pk_test_zPYnBVAJeMsB4S2tieMK6sV5');
define('PAYMONGO_SECRET_KEY', 'sk_test_orK6MTNaBig29mb3WoQh2TQU');

// API Configuration
define('PAYMONGO_API_URL', 'https://api.paymongo.com/v1');
define('PAYMONGO_MODE', 'test'); // Change to 'live' for production

// Application Settings
define('PAYMONGO_WEBSITE_URL', 'http://localhost/gawasainteg');

// Store name for payment descriptor
define('PAYMONGO_STORE_NAME', 'LOCAL FLAVORS');

// Log file for debugging
define('PAYMONGO_LOG_FILE', __DIR__ . '/../../logs/paymongo.log');

// Function to safely log PayMongo events (create this directory)
function paymongo_log($message, $type = 'INFO') {
    $dir = dirname(PAYMONGO_LOG_FILE);
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] [$type] $message" . PHP_EOL;
    file_put_contents(PAYMONGO_LOG_FILE, $log_message, FILE_APPEND);
}
?>
