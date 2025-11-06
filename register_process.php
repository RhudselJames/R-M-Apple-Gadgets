<?php
include 'db_connect.php';

$full_name = trim($_POST['full_name']);
$username  = trim($_POST['username']);
$email     = trim($_POST['email']);
$phone     = trim($_POST['phone']);
$password  = password_hash($_POST['password'], PASSWORD_DEFAULT);
$status    = 'active';

try {
    // Check if username or email already exists
    $check = $conn->prepare("SELECT id FROM customers WHERE username = :username OR email = :email");
    $check->execute(['username' => $username, 'email' => $email]);

    if ($check->rowCount() > 0) {
        echo "<script>alert('Username or email already exists.'); window.location='index.php';</script>";
        exit();
    }

    // Insert new user
    $stmt = $conn->prepare("
        INSERT INTO customers (username, email, password_hash, full_name, phone, status, created_at)
        VALUES (:username, :email, :password_hash, :full_name, :phone, :status, NOW())
    ");
    $stmt->execute([
        'username'      => $username,
        'email'         => $email,
        'password_hash' => $password,
        'full_name'     => $full_name,
        'phone'         => $phone,
        'status'        => $status
    ]);

    echo "<script>alert('Account created successfully! You can now log in.'); window.location='index.php';</script>";

} catch (PDOException $e) {
    echo "<script>alert('Error: " . $e->getMessage() . "'); window.location='index.php';</script>";
}
?>