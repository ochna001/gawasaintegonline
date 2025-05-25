<?php
/**
 * PayMongo API Handler
 * 
 * This class handles all interactions with the PayMongo API
 */

require_once __DIR__ . '/config.php';

class PayMongoAPI {
    /**
     * Create a checkout session
     * 
     * @param array $orderData Order details including items, amount, etc.
     * @return array Response with success status and checkout URL or error message
     */
    public static function createCheckoutSession($orderData) {
        try {
            // Format line items for PayMongo
            $lineItems = [];
            foreach ($orderData['items'] as $item) {
                $lineItems[] = [
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'amount' => round($item['price'] * 100), // Convert to cents
                    'currency' => 'PHP', // Required currency parameter
                    'description' => isset($item['description']) ? $item['description'] : ''
                ];
            }
            
            // Add delivery fee if present
            if (isset($orderData['delivery_fee']) && $orderData['delivery_fee'] > 0) {
                $lineItems[] = [
                    'name' => 'Delivery Fee',
                    'quantity' => 1,
                    'amount' => round($orderData['delivery_fee'] * 100),
                    'currency' => 'PHP', // Required currency parameter
                    'description' => 'Delivery fee for your order'
                ];
            }
            
            // Prepare checkout data
            $checkoutData = [
                'data' => [
                    'attributes' => [
                        'line_items' => $lineItems,
                        'payment_method_types' => ['gcash', 'card', 'dob'], // dob = direct online banking
                        'success_url' => PAYMONGO_WEBSITE_URL . '/checkout_success.php?session_id={CHECKOUT_SESSION_ID}&order_id=' . $orderData['order_id'],
                        'cancel_url' => PAYMONGO_WEBSITE_URL . '/checkout_cancel.php?session_id={CHECKOUT_SESSION_ID}&order_id=' . $orderData['order_id'],
                        'description' => 'Order #' . $orderData['order_id'],
                        'statement_descriptor' => PAYMONGO_STORE_NAME,
                        'reference_number' => $orderData['order_id']
                    ]
                ]
            ];
            
            // Convert to JSON
            $jsonData = json_encode($checkoutData);
            
            // Set up cURL request
            $ch = curl_init(PAYMONGO_API_URL . '/checkout_sessions');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':')
            ]);
            
            // Execute request
            $response = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Parse response
            $responseData = json_decode($response, true);
            
            // Log the request and response
            paymongo_log('Created checkout session for Order #' . $orderData['order_id'] . ' - Status: ' . $statusCode);
            
            if ($statusCode == 200 && isset($responseData['data']['attributes']['checkout_url'])) {
                return [
                    'success' => true,
                    'session_id' => $responseData['data']['id'],
                    'checkout_url' => $responseData['data']['attributes']['checkout_url']
                ];
            } else {
                $errorMessage = isset($responseData['errors'][0]['detail']) ? $responseData['errors'][0]['detail'] : 'Unknown error';
                paymongo_log('Failed to create checkout session: ' . $errorMessage, 'ERROR');
                return [
                    'success' => false,
                    'message' => 'Failed to create checkout session: ' . $errorMessage
                ];
            }
        } catch (Exception $e) {
            paymongo_log('Exception creating checkout session: ' . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'message' => 'System error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Retrieve a checkout session by ID
     * 
     * @param string $sessionId The PayMongo checkout session ID
     * @return array Response with session details or error message
     */
    public static function retrieveCheckoutSession($sessionId) {
        try {
            // Set up cURL request
            $ch = curl_init(PAYMONGO_API_URL . '/checkout_sessions/' . $sessionId);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':')
            ]);
            
            // Execute request
            $response = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Parse response
            $responseData = json_decode($response, true);
            
            if ($statusCode == 200 && isset($responseData['data'])) {
                paymongo_log('Retrieved checkout session: ' . $sessionId . ' - Status: ' . $responseData['data']['attributes']['payment_intent']['attributes']['status']);
                return [
                    'success' => true,
                    'session' => $responseData['data']
                ];
            } else {
                $errorMessage = isset($responseData['errors'][0]['detail']) ? $responseData['errors'][0]['detail'] : 'Unknown error';
                paymongo_log('Failed to retrieve checkout session: ' . $errorMessage, 'ERROR');
                return [
                    'success' => false,
                    'message' => 'Failed to retrieve checkout session: ' . $errorMessage
                ];
            }
        } catch (Exception $e) {
            paymongo_log('Exception retrieving checkout session: ' . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'message' => 'System error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get payment status from a checkout session
     * 
     * @param string $sessionId The PayMongo checkout session ID
     * @return array Response with payment status or error message
     */
    public static function getPaymentStatus($sessionId) {
        $result = self::retrieveCheckoutSession($sessionId);
        
        if (!$result['success']) {
            return $result;
        }
        
        $session = $result['session'];
        $paymentStatus = 'unpaid';
        
        // Check if payment intent exists and get its status
        if (isset($session['attributes']['payment_intent']) && 
            isset($session['attributes']['payment_intent']['attributes']['status'])) {
            $paymentStatus = $session['attributes']['payment_intent']['attributes']['status'];
        }
        
        return [
            'success' => true,
            'payment_status' => $paymentStatus,
            'payment_id' => isset($session['attributes']['payment_intent']['id']) ? $session['attributes']['payment_intent']['id'] : null,
            'payment_method' => isset($session['attributes']['payment_method_used']) ? $session['attributes']['payment_method_used'] : null,
            'session' => $session
        ];
    }
    
    /**
     * Process webhook events from PayMongo
     * 
     * @param string $payload Raw webhook payload
     * @param string $signature PayMongo signature header
     * @return array Response with event details or error message
     */
    public static function handleWebhook($payload, $signature) {
        try {
            // Parse the payload
            $eventData = json_decode($payload, true);
            
            if (!$eventData || !isset($eventData['data']['id'])) {
                paymongo_log('Invalid webhook payload', 'ERROR');
                return [
                    'success' => false,
                    'message' => 'Invalid webhook payload'
                ];
            }
            
            $eventType = $eventData['data']['attributes']['type'];
            paymongo_log('Received webhook: ' . $eventType);
            
            return [
                'success' => true,
                'event_type' => $eventType,
                'event_data' => $eventData
            ];
        } catch (Exception $e) {
            paymongo_log('Exception processing webhook: ' . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'message' => 'System error: ' . $e->getMessage()
            ];
        }
    }
}
?>
