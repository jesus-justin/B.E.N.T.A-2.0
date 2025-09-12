<?php
// Database configuration
$host = "localhost";   // XAMPP usually runs MySQL on localhost
$user = "root";        // default XAMPP MySQL username
$pass = "";            // default XAMPP MySQL password (blank)
$db   = "benta_db";    // database name we created earlier

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: set character encoding
$conn->set_charset("utf8mb4");
?>
