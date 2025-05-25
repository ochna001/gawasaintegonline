<?php
// Database connection parameters
$db_host = 'crossover.proxy.rlwy.net';
$db_port = '40666';
$db_user = 'root';
$db_pass = 'LZRbOJMMfmkGRNYTKwHyyfcicEBggrzt'; // Replace with your actual password
$db_name = 'railway';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>