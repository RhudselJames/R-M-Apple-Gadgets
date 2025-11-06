
<?php
session_start();
include 'db_connect.php';
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle both GET (from URL) and POST (from AJAX) requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $cart_id = $data['cart_id'] ?? null;
} else {
    $cart_id = $_GET['id'] ?? null;
}

// Check if cart ID is provided
if (!$cart_id || empty($cart_id)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header("Location: customerdash.php");
        exit();
    }
    echo json_encode(['success' => false, 'message' => 'Cart ID required']);
    exit();
}

try {
    // Verify that the cart item belongs to the logged-in user before deleting
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        // Get updated cart count
        $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cart_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            header("Location: customerdash.php?success=item_removed");
            exit();
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Item removed from cart',
            'cart_count' => $cart_count
        ]);
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            header("Location: customerdash.php?error=item_not_found");
            exit();
        }
        
        echo json_encode(['success' => false, 'message' => 'Item not found']);
    }
} catch (PDOException $e) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header("Location: customerdash.php?error=database_error");
        exit();
    }
    
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>