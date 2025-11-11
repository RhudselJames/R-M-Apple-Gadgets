<?php
session_start();
require_once __DIR__ . '/../config/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$cart_id = $data['cart_id'] ?? null;
$quantity = $data['quantity'] ?? 0;

if (!$cart_id || $quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];

    // Get cart item with product info
    $stmt = $conn->prepare("
        SELECT c.*, p.stock_quantity, p.price 
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.id = ? AND c.user_id = ?
    ");
    $stmt->execute([$cart_id, $user_id]);
    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cart_item) {
        echo json_encode(['success' => false, 'message' => 'Cart item not found']);
        exit;
    }

    // Check stock availability
    if ($quantity > $cart_item['stock_quantity']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Only ' . $cart_item['stock_quantity'] . ' items available'
        ]);
        exit;
    }

    // Update quantity
    $stmt = $conn->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$quantity, $cart_id]);

    // Calculate new subtotal
    $subtotal = $cart_item['price'] * $quantity;

    echo json_encode([
        'success' => true,
        'message' => 'Cart updated',
        'subtotal' => $subtotal
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>