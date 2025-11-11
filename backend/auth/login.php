<?php
session_start();
// UPDATED: Path to db_connect.php from backend/auth/
include __DIR__ . '/../config/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_username = trim($_POST['username']);
    $input_password = trim($_POST['password']);
    
    // Validate inputs are not empty
    if (empty($input_username) || empty($input_password)) {
        // UPDATED: Redirect path to index.php from backend/auth/
        header("Location: ../../index.php?error=empty_fields");
        exit();
    }
    
    // Check ADMIN first
    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $input_username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        // Admin found - check password
        $password_match = false;
        
        if (password_verify($input_password, $admin['password'])) {
            $password_match = true;
        } elseif ($input_password === $admin['password']) {
            $password_match = true;
        }
        
        if ($password_match) {
            // Admin login SUCCESS
            $_SESSION['user_id'] = $admin['admin_id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['full_name'] = $admin['username'];
            $_SESSION['user_role'] = 'admin';
            // UPDATED: Redirect path to admindash.php from backend/auth/
            header("Location: ../../pages/admindash.php");
            exit();
        } else {
            // Wrong admin password
            header("Location: ../../index.php?error=invalid_credentials");
            exit();
        }
    }
    
    // Check USERS (customers)
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $input_username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // User found - check password
        $password_match = false;
        
        if (password_verify($input_password, $user['password'])) {
            $password_match = true;
        } elseif ($input_password === $user['password']) {
            $password_match = true;
        }
        
        if ($password_match) {
            // Customer login SUCCESS
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_role'] = 'customer';
            // UPDATED: Redirect path to index.php from backend/auth/
            header("Location: ../../index.php");
            exit();
        } else {
            // Wrong customer password
            header("Location: ../../index.php?error=invalid_credentials");
            exit();
        }
    }
    
    // Neither admin nor user found
    header("Location: ../../index.php?error=invalid_credentials");
    exit();
    
} else {
    // Direct access without POST
    header("Location: ../../index.php");
    exit();
}
?>