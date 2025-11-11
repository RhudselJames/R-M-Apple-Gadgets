<?php
// Database connection settings
$servername = "localhost";
$username = "root";      // default WAMP username
$password = "";          // default WAMP password (empty)
$database = "r&m-apple_gadgets"; // change to your actual DB name

try {
    // Create PDO connection
    $conn = new PDO("mysql:host=$servername;dbname=$database;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Database connected successfully!"; // uncomment to test
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>