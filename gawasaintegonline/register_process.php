<?php
// Include functions file
require_once 'includes/functions.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Create user data array from form submission
    $userData = [
        'first_name' => isset($_POST['firstName']) ? $_POST['firstName'] : '',
        'last_name' => isset($_POST['lastName']) ? $_POST['lastName'] : '',
        'email' => isset($_POST['email']) ? $_POST['email'] : '',
        'phone' => isset($_POST['phone']) ? $_POST['phone'] : '',
        'password' => isset($_POST['password']) ? $_POST['password'] : '',
        'confirm_password' => isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '',
        'address' => isset($_POST['address']) ? $_POST['address'] : ''
    ];
    
    // Register user
    $result = registerUser($userData);
    
    if ($result['success']) {
        // Set success message in session
        $_SESSION['message'] = $result['message'];
        $_SESSION['message_type'] = 'success';
        
        // Redirect to login page
        header('Location: login.php');
        exit;
    } else {
        // Set error message in session
        $_SESSION['message'] = $result['message'];
        $_SESSION['message_type'] = 'error';
        
        // Redirect back to registration page
        header('Location: register.php');
        exit;
    }
} else {
    // If not a POST request, redirect to registration page
    header('Location: register.php');
    exit;
}
?>
