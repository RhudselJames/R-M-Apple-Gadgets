<?php
session_start();
require_once __DIR__ . '/../config/db_connect.php';
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

$product_id = $data['product_id'] ?? null;
$quantity = $data['quantity'] ?? 1;
$color = $data['color'] ?? null;
$storage = $data['storage'] ?? null;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Product ID required']);
    exit;
}

try {
    // Check if product exists and is in stock
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    if ($product['stock_quantity'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // Check if item already exists in cart
    $stmt = $conn->prepare("
        SELECT * FROM cart 
        WHERE user_id = ? AND product_id = ? AND color = ? AND storage = ?
    ");
    $stmt->execute([$user_id, $product_id, $color, $storage]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update quantity
        $new_quantity = $existing['quantity'] + $quantity;
        
        if ($new_quantity > $product['stock_quantity']) {
            echo json_encode(['success' => false, 'message' => 'Cannot add more than available stock']);
            exit;
        }

        $stmt = $conn->prepare("
            UPDATE cart 
            SET quantity = ?, price = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$new_quantity, $product['price'], $existing['id']]);
    } else {
        // Insert new cart item
        $stmt = $conn->prepare("
            INSERT INTO cart (user_id, product_id, quantity, color, storage, price, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$user_id, $product_id, $quantity, $color, $storage, $product['price']]);

    // Get cart count
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    echo json_encode([
        'success' => true, 
        'message' => 'Product added to cart successfully!',
        'cart_count' => $cart_count
    ]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>