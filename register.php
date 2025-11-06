
<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'r&m-apple_gadgets'; // Your existing database
$username = 'root'; // Change if different
$password = ''; // Change if different

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $reg_username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $reg_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    $errors = [];
    
    // Check if passwords match
    if ($reg_password !== $confirm_password) {
        $errors[] = "Passwords do not match!";
    }
    
    // Validate password strength
    if (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[@$!%*?&]).{8,}$/', $reg_password)) {
        $errors[] = "Password must contain at least 8 characters, 1 uppercase, 1 lowercase, 1 number, and 1 special character";
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !strpos($email, '@')) {
        $errors[] = "Invalid email format";
    }
    
    // Validate phone number
    if (!preg_match('/^[0-9]{10,11}$/', $phone)) {
        $errors[] = "Phone number must be 10-11 digits";
    }
    
    // Check if username already exists in users table
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->bindParam(':username', $reg_username);
    $stmt->execute();
    if ($stmt->fetch()) {
        $errors[] = "Username already taken!";
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    if ($stmt->fetch()) {
        $errors[] = "Email already registered!";
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($reg_password, PASSWORD_DEFAULT);
        
        // First, check what columns exist in users table
        $stmt = $pdo->prepare("SHOW COLUMNS FROM users");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Build INSERT query based on available columns
        $insert_columns = ['username', 'password'];
        $insert_values = [':username', ':password'];
        $params = [
            ':username' => $reg_username,
            ':password' => $hashed_password
        ];
        
        if (in_array('full_name', $columns) || in_array('name', $columns)) {
            $col_name = in_array('full_name', $columns) ? 'full_name' : 'name';
            $insert_columns[] = $col_name;
            $insert_values[] = ':full_name';
            $params[':full_name'] = $full_name;
        }
        
        if (in_array('email', $columns)) {
            $insert_columns[] = 'email';
            $insert_values[] = ':email';
            $params[':email'] = $email;
        }
        
        if (in_array('phone', $columns)) {
            $insert_columns[] = 'phone';
            $insert_values[] = ':phone';
            $params[':phone'] = $phone;
        }
        
        if (in_array('created_at', $columns)) {
            $insert_columns[] = 'created_at';
            $insert_values[] = 'NOW()';
        }
        
        $sql = "INSERT INTO users (" . implode(', ', $insert_columns) . ") 
                VALUES (" . implode(', ', $insert_values) . ")";
        
        $stmt = $pdo->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        if ($stmt->execute()) {
            // Registration successful
            $_SESSION['success'] = "Registration successful! Please login.";
            header("Location: index.php?registration=success");
            exit();
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
    
    // If there are errors, store them in session and redirect back
    if (!empty($errors)) {
        $_SESSION['registration_errors'] = $errors;
        header("Location: index.php?error=registration_failed");
        exit();
    }
    
} else {
    // If accessed directly without POST
    header("Location: index.php");
    exit();
}
?>