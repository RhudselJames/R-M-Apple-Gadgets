<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

$user_id = $_SESSION['user_id'];
$address = $data['address'] ?? '';
$payment_method = $data['payment_method'] ?? 'Cash on Delivery';
$notes = $data['notes'] ?? '';

// Validate required fields
if (empty($address)) {
    echo json_encode(['success' => false, 'message' => 'Address is required']);
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Fetch cart items with product details
    $stmt = $conn->prepare("
        SELECT 
            c.id as cart_id,
            c.product_id,
            c.quantity,
            c.color,
            c.storage,
            p.name,
            p.price,
            p.stock_quantity
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if cart is empty
    if (empty($cart_items)) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        exit;
    }

    // Calculate total and verify stock
    $total_amount = 0;
    foreach ($cart_items as $item) {
        // Check stock availability
        if ($item['quantity'] > $item['stock_quantity']) {
            $conn->rollBack();
            echo json_encode([
                'success' => false, 
                'message' => $item['name'] . ' is out of stock. Only ' . $item['stock_quantity'] . ' available.'
            ]);
            exit;
        }
        
        $total_amount += floatval($item['price']) * intval($item['quantity']);
    }

    // Add shipping fee
    $shipping = $total_amount > 50000 ? 0 : 200;
    $total_amount += $shipping;

    // Create order
    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, address, payment_method, total_amount, status, order_date) 
        VALUES (?, ?, ?, ?, 'Pending', NOW())
    ");
    $stmt->execute([$user_id, $address, $payment_method, $total_amount]);
    
    $order_id = $conn->lastInsertId();

    // Create order items with color and storage, and update stock
    $stmt_order_item = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price, color, storage) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt_update_stock = $conn->prepare("
        UPDATE products 
        SET stock_quantity = stock_quantity - ? 
        WHERE id = ?
    ");

    foreach ($cart_items as $item) {
        // Insert order item with color and storage
        $stmt_order_item->execute([
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $item['price'],
            $item['color'],
            $item['storage']
        ]);

        // Update product stock
        $stmt_update_stock->execute([
            $item['quantity'],
            $item['product_id']
        ]);
    }

    // Clear user's cart
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully!',
        'order_id' => $order_id
    ]);

} catch (PDOException $e) {
    // Rollback on error
    $conn->rollBack();
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process order: ' . $e->getMessage()
    ]);
}
?>