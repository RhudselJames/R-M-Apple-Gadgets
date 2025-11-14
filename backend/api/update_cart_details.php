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
$color = $data['color'] ?? null;
$storage = $data['storage'] ?? null;

// This is the check that is failing.
// The new JS sends "N/A" for storage on some MacBooks, which is fine.
if (!$cart_id || !$color || !$storage) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters. JS may be sending null.']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];

    // 1. Get current cart item info
    $stmt = $conn->prepare("SELECT p.id, p.name, p.category FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?");
    $stmt->execute([$cart_id, $user_id]);
    $cart_item_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cart_item_info) {
        echo json_encode(['success' => false, 'message' => 'Cart item not found']);
        exit;
    }

    $base_name = preg_replace('/\s*-\s*(128GB|256GB|512GB|1TB|64GB).*$/i', '', $cart_item_info['name']);
    $base_name = preg_replace('/\s*\(.*\).*$/i', '', $base_name);
    $category = $cart_item_info['category'];
    $new_price = null;

    // 2. Try to find a specific variant product that matches the new selection
    $search_pattern = $base_name . '%';
    $variant_stmt = $conn->prepare("
        SELECT id as new_product_id, price as new_price
        FROM products 
        WHERE status = 'active'
        AND category = ? AND name LIKE ? AND color = ? AND storage = ?
        AND LOCATE(',', color) = 0 AND LOCATE(',', storage) = 0
        LIMIT 1
    ");
    $variant_stmt->execute([$category, $search_pattern, $color, $storage]);
    $new_variant = $variant_stmt->fetch(PDO::FETCH_ASSOC);

    if ($new_variant) {
        // --- LOGIC A: Found a variant (iPhone) ---
        $new_price = $new_variant['new_price'];
        $update_stmt = $conn->prepare("
            UPDATE cart SET product_id = ?, color = ?, storage = ?, price = ?, updated_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        $update_stmt->execute([$new_variant['new_product_id'], $color, $storage, $new_price, $cart_id, $user_id]);

    } else {
        // --- LOGIC B: No variant found (iPad/MacBook) ---
        // Find the master product to get its option lists and base price
        $master_product_stmt = $conn->prepare("SELECT price, storage as storage_list FROM products WHERE category = ? AND name = ? LIMIT 1");
        $master_product_stmt->execute([$category, $base_name]);
        $master_product = $master_product_stmt->fetch(PDO::FETCH_ASSOC);

        if ($master_product) {
            $base_price = $master_product['price'];
            $storages = explode(',', $master_product['storage_list']);
            
            // Calculate price based on storage step
            $base_storage_val = 128; // Default
            if (!empty($storages)) {
                preg_match('/(\d+)(GB|TB)/i', trim($storages[0]), $matches);
                if ($matches) {
                    $base_storage_val = $matches[1] * (strtoupper($matches[2] ?? 'GB') == 'TB' ? 1024 : 1);
                }
            }
            preg_match('/(\d+)(GB|TB)/i', $storage, $matches);
            $storage_val = isset($matches[1]) ? $matches[1] * (strtoupper(isset($matches[2]) ? $matches[2] : 'GB') == 'TB' ? 1024 : 1) : $base_storage_val;
            
            // Ensure storage_val is numeric before calculation
            if (is_numeric($storage_val) && is_numeric($base_storage_val)) {
                $stepDifference = ($storage_val - $base_storage_val) / 128;
                $new_price = $base_price + (max(0, $stepDifference) * 5000);
            } else {
                // Fallback if storage is "N/A" for a MacBook
                $new_price = $base_price;
            }
        } else {
            // Fallback if master product isn't found (shouldn't happen)
            $new_price = 0;
        }
        
        // Update the cart with the new options and the CALCULATED price
        $update_stmt = $conn->prepare("
            UPDATE cart SET color = ?, storage = ?, price = ?, updated_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        $update_stmt->execute([$color, $storage, $new_price, $cart_id, $user_id]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Cart updated successfully!',
        'new_price_per_unit' => $new_price
    ]);

} catch (PDOException $e) {
    error_log("Database Error in update_cart_details: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.']);
}
?>