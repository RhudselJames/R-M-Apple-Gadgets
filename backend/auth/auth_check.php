<?php

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    // Not logged in, redirect to login page
    // UPDATED: Path to index.php from backend/auth/
    header("Location: ../../index.php?error=not_logged_in");
    exit();
}

// Check if user has admin role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // Not an admin, redirect to customer homepage
    // UPDATED: Path to index.php from backend/auth/
    header("Location: ../../index.php?error=access_denied");
    exit();
}

// Optional: Update last login time
try {
    // UPDATED: Path to db_connect.php from backend/auth/
    require_once __DIR__ . '/../config/db_connect.php';
    
    $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
} catch(PDOException $e) {
    // Silently fail - not critical
}
?>