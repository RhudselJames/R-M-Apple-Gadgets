<?php

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    // Not logged in, redirect to login page
    header("Location: index.php?error=not_logged_in");
    exit();
}

// Check if user has admin role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // Not an admin, redirect to customer homepage
    header("Location: index.php?error=access_denied");
    exit();
}

// Optional: Update last login time
try {
    $host = 'localhost';
    $dbname = 'rm_apple_gadgets';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
} catch(PDOException $e) {
    
}
?>