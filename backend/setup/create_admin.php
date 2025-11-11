<?php
/**
 * Run this file ONCE to create or update an admin account in your existing database
 * Access it via: http://localhost/your-project/create_admin.php
 * After creating the admin, DELETE this file for security
 */

// Database configuration
$host = 'localhost';
$dbname = 'r&m-apple_gadgets'; // Your existing database
$username = 'root';
$password = ''; // Change if different

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Admin credentials - CHANGE THESE!
$admin_username = "admin";
$admin_password = "Admin@123"; // Change this to a secure password

// Check if admin already exists
$stmt = $pdo->prepare("SELECT admin_id FROM admin WHERE username = :username");
$stmt->bindParam(':username', $admin_username);
$stmt->execute();

$existing_admin = $stmt->fetch();

// Hash the password
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

if ($existing_admin) {
    // Update existing admin password
    $stmt = $pdo->prepare("UPDATE admin SET password = :password, password_hash = :password_hash WHERE username = :username");
    $stmt->bindParam(':password', $admin_password); // plain password (optional)
    $stmt->bindParam(':password_hash', $hashed_password);
    $stmt->bindParam(':username', $admin_username);
    
    if ($stmt->execute()) {
        echo "<h2>✅ Admin Account Updated Successfully!</h2>";
        echo "<p><strong>Username:</strong> " . htmlspecialchars($admin_username) . "</p>";
        echo "<p><strong>New Password:</strong> " . htmlspecialchars($admin_password) . "</p>";
    } else {
        echo "Failed to update admin account.";
    }
} else {
    // Insert new admin account
    $stmt = $pdo->prepare("INSERT INTO admin (username, password, password_hash, created_at) 
                          VALUES (:username, :password, :password_hash, NOW())");
    
    $stmt->bindParam(':username', $admin_username);
    $stmt->bindParam(':password', $admin_password); // plain password (optional)
    $stmt->bindParam(':password_hash', $hashed_password);
    
    if ($stmt->execute()) {
        echo "<h2>✅ Admin Account Created Successfully!</h2>";
        echo "<p><strong>Username:</strong> " . htmlspecialchars($admin_username) . "</p>";
        echo "<p><strong>Password:</strong> " . htmlspecialchars($admin_password) . "</p>";
    } else {
        echo "Failed to create admin account.";
    }
}

echo "<hr>";
echo "<p style='color: red;'><strong>IMPORTANT:</strong> Please DELETE this file (create_admin.php) immediately for security reasons!</p>";
echo "<p><a href='index.php'>Go to Login Page</a></p>";
?>