<?php
session_start();
require_once 'db_connect.php';
$category = $_GET['category'] ?? 'iPhone';
$categoryPage = $_GET['categoryPage'] ?? 'iphone.php';


$product_id = $_GET['id'] ?? null;
if (!$product_id) {
    header('Location: iphone.php');
    exit;
}

// Fetch product
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: iphone.php');
    exit;
}

// Fetch reviews with user info
$reviews_stmt = $conn->prepare("
    SELECT r.*, u.full_name, u.username 
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
");
$reviews_stmt->execute([$product_id]);
$reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate average rating
$rating_stmt = $conn->prepare("
    SELECT 
        AVG(rating) as avg_rating,
        COUNT(*) as total_reviews
    FROM reviews 
    WHERE product_id = ?
");
$rating_stmt->execute([$product_id]);
$rating_data = $rating_stmt->fetch(PDO::FETCH_ASSOC);
$avg_rating = round($rating_data['avg_rating'] ?? 0, 1);
$total_reviews = $rating_data['total_reviews'] ?? 0;

// Handle null values for colors and storage
$colors = !empty($product['color']) ? explode(',', $product['color']) : ['Black'];
$storages = !empty($product['storage']) ? explode(',', $product['storage']) : ['256GB'];

// Product specifications
$productSpecs = [
    1 => ['display' => '6.9-inch Super Retina XDR', 'resolution' => '2868 x 1320 at 460 ppi', 'dynamic_island' => true, 'camera_main' => '48MP (f/1.78)', 'camera_ultra' => '48MP Ultra Wide', 'camera_tele' => '12MP 5x Telephoto', 'video' => '4K Dolby Vision 60fps', 'battery' => 'Up to 33 hours video', 'chip' => 'A19 Pro', 'water_resistance' => 'IP68', 'connectivity' => '5G, Wi-Fi 6E, BT 5.3'],

    // iPhone 17 Pro
    2 => ['display' => '6.3-inch Super Retina XDR', 'resolution' => '2796 x 1290 at 460 ppi', 'dynamic_island' => true, 'camera_main' => '48MP (f/1.78)', 'camera_ultra' => '48MP Ultra Wide', 'camera_tele' => '12MP 5x Telephoto', 'video' => '4K Dolby Vision 60fps', 'battery' => 'Up to 28 hours video', 'chip' => 'A19 Pro', 'water_resistance' => 'IP68', 'connectivity' => '5G, Wi-Fi 7, BT 5.4'],

    3 => ['display' => '6.6-inch Super Retina XDR', 'resolution' => '2796 x 1290 at 460 ppi', 'dynamic_island' => true, 'camera_main' => '48MP (f/1.78)', 'camera_ultra' => '12MP Ultra Wide', 'camera_tele' => null, 'video' => '4K Dolby Vision 60fps', 'battery' => 'Up to 27 hours video', 'chip' => 'A18', 'water_resistance' => 'IP68', 'connectivity' => '5G, Wi-Fi 6E, BT 5.3'],

    // iPhone 17
    4 => ['display' => '6.1-inch Super Retina XDR', 'resolution' => '2556 x 1179 at 460 ppi', 'dynamic_island' => true, 'camera_main' => '48MP (f/1.6)', 'camera_ultra' => '12MP Ultra Wide', 'camera_tele' => null, 'video' => '4K Dolby Vision 60fps', 'battery' => 'Up to 22 hours video', 'chip' => 'A19', 'water_resistance' => 'IP68', 'connectivity' => '5G, Wi-Fi 7, BT 5.4'],

    5 => ['display' => '6.9-inch Super Retina XDR', 'resolution' => '2868 x 1320 at 460 ppi', 'dynamic_island' => true, 'camera_main' => '48MP (f/1.78)', 'camera_ultra' => '48MP Ultra Wide', 'camera_tele' => '12MP 5x Telephoto', 'video' => '4K Dolby Vision 60fps', 'battery' => 'Up to 33 hours video', 'chip' => 'A18 Pro', 'water_resistance' => 'IP68', 'connectivity' => '5G, Wi-Fi 6E, BT 5.3'],

    // iPhone 16 Plus
    6 => ['display' => '6.7-inch Super Retina XDR', 'resolution' => '2796 x 1290 at 460 ppi', 'dynamic_island' => true, 'camera_main' => '48MP (f/1.6)', 'camera_ultra' => '12MP Ultra Wide', 'camera_tele' => null, 'video' => '4K Dolby Vision 60fps', 'battery' => 'Up to 26 hours video', 'chip' => 'A18', 'water_resistance' => 'IP68', 'connectivity' => '5G, Wi-Fi 6E, BT 5.3'],

    // iPhone 16e
    7 => ['display' => '6.1-inch Super Retina XDR', 'resolution' => '2556 x 1179 at 460 ppi', 'dynamic_island' => true, 'camera_main' => '48MP (f/1.6)', 'camera_ultra' => '12MP Ultra Wide', 'camera_tele' => null, 'video' => '4K Dolby Vision 60fps', 'battery' => 'Up to 20 hours video', 'chip' => 'A17', 'water_resistance' => 'IP68', 'connectivity' => '5G, Wi-Fi 6E, BT 5.3'],

    8 => ['display' => '6.1-inch Super Retina XDR', 'resolution' => '2556 x 1179 at 460 ppi', 'dynamic_island' => true, 'camera_main' => '48MP (f/1.6)', 'camera_ultra' => '12MP Ultra Wide', 'camera_tele' => null, 'video' => '4K Dolby Vision 60fps', 'battery' => 'Up to 22 hours video', 'chip' => 'A18', 'water_resistance' => 'IP68', 'connectivity' => '5G, Wi-Fi 6E, BT 5.3'],

    // iPhone 15 Pro
    9 => ['display' => '6.1-inch Super Retina XDR', 'resolution' => '2556 x 1179 at 460 ppi', 'dynamic_island' => true, 'camera_main' => '48MP (f/1.78)', 'camera_ultra' => '12MP Ultra Wide', 'camera_tele' => '12MP 3x Telephoto', 'video' => '4K Dolby Vision 60fps', 'battery' => 'Up to 23 hours video', 'chip' => 'A17 Pro', 'water_resistance' => 'IP68', 'connectivity' => '5G, Wi-Fi 6E, BT 5.3'],

    10 => ['display' => '6.1-inch Super Retina XDR', 'resolution' => '2556 x 1179 at 460 ppi', 'dynamic_island' => true, 'camera_main' => '48MP (f/1.6)', 'camera_ultra' => '12MP Ultra Wide', 'camera_tele' => null, 'video' => '4K Dolby Vision 60fps', 'battery' => 'Up to 20 hours video', 'chip' => 'A16 Bionic', 'water_resistance' => 'IP68', 'connectivity' => '5G, Wi-Fi 6, BT 5.3'],

    11 => ['display' => '6.1-inch Super Retina XDR', 'resolution' => '2532 x 1170 at 460 ppi', 'dynamic_island' => false, 'camera_main' => '12MP (f/1.5)', 'camera_ultra' => '12MP Ultra Wide', 'camera_tele' => null, 'video' => '4K Dolby Vision 60fps', 'battery' => 'Up to 20 hours video', 'chip' => 'A15 Bionic', 'water_resistance' => 'IP68', 'connectivity' => '5G, Wi-Fi 6, BT 5.3'],

    12 => ['display' => '6.1-inch Super Retina XDR', 'resolution' => '2532 x 1170 at 460 ppi', 'dynamic_island' => false, 'camera_main' => '12MP (f/1.6)', 'camera_ultra' => '12MP Ultra Wide', 'camera_tele' => null, 'video' => '4K Dolby Vision 60fps', 'battery' => 'Up to 19 hours video', 'chip' => 'A15 Bionic', 'water_resistance' => 'IP68', 'connectivity' => '5G, Wi-Fi 6, BT 5.0'],

    // iPhone 16 Pro Max
    13 => ['display' => '6.9-inch Super Retina XDR', 'resolution' => '2868 x 1320 at 460 ppi', 'dynamic_island' => true, 'camera_main' => '48MP (f/1.78)', 'camera_ultra' => '48MP Ultra Wide', 'camera_tele' => '12MP 5x Telephoto', 'video' => '4K Dolby Vision 60fps', 'battery' => 'Up to 33 hours video', 'chip' => 'A18 Pro', 'water_resistance' => 'IP68', 'connectivity' => '5G, Wi-Fi 6E, BT 5.3'],

    // iPhone 16 Pro
    14 => ['display' => '6.3-inch Super Retina XDR', 'resolution' => '2796 x 1290 at 460 ppi', 'dynamic_island' => true, 'camera_main' => '48MP (f/1.78)', 'camera_ultra' => '48MP Ultra Wide', 'camera_tele' => '12MP 5x Telephoto', 'video' => '4K Dolby Vision 60fps', 'battery' => 'Up to 28 hours video', 'chip' => 'A18 Pro', 'water_resistance' => 'IP68', 'connectivity' => '5G, Wi-Fi 6E, BT 5.3'],

    // iPhone 15 Pro Max
    15 => ['display' => '6.7-inch Super Retina XDR', 'resolution' => '2796 x 1290 at 460 ppi', 'dynamic_island' => true, 'camera_main' => '48MP (f/1.78)', 'camera_ultra' => '12MP Ultra Wide', 'camera_tele' => '12MP 5x Telephoto', 'video' => '4K Dolby Vision 60fps', 'battery' => 'Up to 29 hours video', 'chip' => 'A17 Pro', 'water_resistance' => 'IP68', 'connectivity' => '5G, Wi-Fi 6E, BT 5.3'],

    // iPhone 14 Pro Max
    16 => ['display' => '6.7-inch Super Retina XDR', 'resolution' => '2796 x 1290 at 460 ppi', 'dynamic_island' => true, 'camera_main' => '48MP (f/1.78)', 'camera_ultra' => '12MP Ultra Wide', 'camera_tele' => '12MP 3x Telephoto', 'video' => '4K Dolby Vision 60fps', 'battery' => 'Up to 29 hours video', 'chip' => 'A16 Bionic', 'water_resistance' => 'IP68', 'connectivity' => '5G, Wi-Fi 6, BT 5.3'],

    // iPhone 14 Pro Max 
    17 => ['display' => '6.7-inch Super Retina XDR', 'resolution' => '2796 x 1290 at 460 ppi', 'dynamic_island' => true, 'camera_main' => '48MP (f/1.78)', 'camera_ultra' => '12MP Ultra Wide', 'camera_tele' => '12MP 3x Telephoto', 'video' => '4K Dolby Vision 60fps', 'battery' => 'Up to 29 hours video', 'chip' => 'A16 Bionic', 'water_resistance' => 'IP68', 'connectivity' => '5G, Wi-Fi 6, BT 5.3'],

    18 => ['display' => '11-inch Liquid Retina', 'resolution' => '2388 x 1668 at 264 ppi', 'promotion' => true, 'camera_main' => '12MP Wide', 'camera_ultra' => '10MP Ultra Wide', 'front_camera' => '12MP TrueDepth', 'video' => '4K at 24/30/60 fps, ProRes', 'battery' => 'Up to 10 hours', 'chip' => 'M2', 'apple_pencil' => 'Apple Pencil (2nd gen)', 'connectivity' => 'Wi-Fi 6E, 5G (cellular models)', 'ports' => 'Thunderbolt / USB 4'],

    19 => ['display' => '13-inch Liquid Retina XDR', 'resolution' => '2732 x 2048 at 264 ppi', 'promotion' => true, 'camera_main' => '12MP Wide', 'camera_ultra' => '10MP Ultra Wide', 'front_camera' => '12MP TrueDepth', 'video' => '4K at 24/30/60 fps, ProRes', 'battery' => 'Up to 10 hours', 'chip' => 'M2', 'apple_pencil' => 'Apple Pencil (2nd gen)', 'connectivity' => 'Wi-Fi 6E, 5G (cellular models)', 'ports' => 'Thunderbolt / USB 4'],

    // iPad Air 11-inch (latest gen)
    20 => ['display' => '11-inch Liquid Retina', 'resolution' => '2360 x 1640 at 264 ppi', 'promotion' => false, 'camera_main' => '12MP Wide', 'front_camera' => '12MP Landscape Ultra Wide', 'video' => '4K at 24/30/60 fps', 'battery' => 'Up to 10 hours', 'chip' => 'M2', 'apple_pencil' => 'Apple Pencil Pro / 2nd gen', 'connectivity' => 'Wi-Fi 6E, 5G (cellular models)', 'ports' => 'USB-C'],

    // iPad Air 13-inch (latest gen)
    21 => ['display' => '13-inch Liquid Retina', 'resolution' => '2732 x 2048 at 264 ppi', 'promotion' => false, 'camera_main' => '12MP Wide', 'front_camera' => '12MP Landscape Ultra Wide', 'video' => '4K at 24/30/60 fps', 'battery' => 'Up to 10 hours', 'chip' => 'M2', 'apple_pencil' => 'Apple Pencil Pro / 2nd gen', 'connectivity' => 'Wi-Fi 6E, 5G (cellular models)', 'ports' => 'USB-C'],

    // iPad Air 11-inch (5th Gen)
    22 => ['display' => '10.9-inch Liquid Retina', 'resolution' => '2360 x 1640 at 264 ppi', 'promotion' => false, 'camera_main' => '12MP Wide', 'front_camera' => '12MP Ultra Wide (Center Stage)', 'video' => '4K at 24/30/60 fps', 'battery' => 'Up to 10 hours', 'chip' => 'M1', 'apple_pencil' => 'Apple Pencil (2nd gen)', 'connectivity' => 'Wi-Fi 6, 5G (cellular models)', 'ports' => 'USB-C'],

    // iPad (10th gen)
    23 => ['display' => '10.9-inch Liquid Retina', 'resolution' => '2360 x 1640 at 264 ppi', 'promotion' => false, 'camera_main' => '12MP Wide', 'front_camera' => '12MP Landscape Ultra Wide', 'video' => '4K at 24/30/60 fps', 'battery' => 'Up to 10 hours', 'chip' => 'A14 Bionic', 'apple_pencil' => 'Apple Pencil (USB-C)', 'connectivity' => 'Wi-Fi 6, 5G (cellular models)', 'ports' => 'USB-C'],

    // iPad Pro 11-inch (4th Gen)
    24 => ['display' => '11-inch Liquid Retina', 'resolution' => '2388 x 1668 at 264 ppi', 'promotion' => true, 'camera_main' => '12MP Wide', 'camera_ultra' => '10MP Ultra Wide', 'front_camera' => '12MP TrueDepth', 'video' => '4K at 24/30/60 fps, ProRes', 'battery' => 'Up to 10 hours', 'chip' => 'M2', 'apple_pencil' => 'Apple Pencil (2nd gen)', 'connectivity' => 'Wi-Fi 6E, 5G (cellular models)', 'ports' => 'Thunderbolt / USB 4'],

    // iPad Pro 11-inch (5th Gen)
    25 => ['display' => '11-inch Ultra Retina XDR (Tandem OLED)', 'resolution' => '2420 x 1668 at 264 ppi', 'promotion' => true, 'camera_main' => '12MP Wide', 'front_camera' => '12MP Landscape Ultra Wide', 'video' => '4K at 24/30/60 fps, ProRes', 'battery' => 'Up to 10 hours', 'chip' => 'M4', 'apple_pencil' => 'Apple Pencil Pro / 2nd gen', 'connectivity' => 'Wi-Fi 6E, 5G (cellular models)', 'ports' => 'Thunderbolt / USB 4'],
    
];

$macbookSpecs = [
    35 => [
        'CPU' => '10-Core CPU',
        'GPU' => '10-Core GPU',
        'Neural Engine' => '16-Core Neural Engine',
        'Display' => '14-inch Liquid Retina XDR Display',
        'Power Adapter' => '70W USB-C Power Adapter'
    ],
    36 => [
        'CPU' => '10-Core CPU',
        'GPU' => '10-Core GPU',
        'Neural Engine' => '16-Core Neural Engine',
        'Display' => '14-inch Liquid Retina XDR Display',
        'Power Adapter' => '70W USB-C Power Adapter'
    ],
    37 => [
        'CPU' => '12-Core CPU',
        'GPU' => '16-Core GPU',
        'Neural Engine' => '16-Core Neural Engine',
        'Display' => '14-inch Liquid Retina XDR Display',
        'Power Adapter' => '70W USB-C Power Adapter'
    ],
    38 => [
        'CPU' => '14-Core CPU',
        'GPU' => '32-Core GPU',
        'Neural Engine' => '16-Core Neural Engine',
        'Display' => '14-inch Liquid Retina XDR Display',
        'Power Adapter' => '70W USB-C Power Adapter'
    ],
    39 => [
        'CPU' => '14-Core CPU',
        'GPU' => '20-Core GPU',
        'Neural Engine' => '16-Core Neural Engine',
        'Display' => '16-inch Liquid Retina XDR Display',
        'Power Adapter' => '140W USB-C Power Adapter'
    ],
    40 => [
        'CPU' => '16-Core CPU',
        'GPU' => '40-Core GPU',
        'Neural Engine' => '16-Core Neural Engine',
        'Display' => '16-inch Liquid Retina XDR Display',
        'Power Adapter' => '140W USB-C Power Adapter'
    ],
    41 => [
        'CPU' => '10-Core CPU',
        'GPU' => '8-Core GPU',
        'Neural Engine' => '16-Core Neural Engine',
        'Display' => '13.6-inch Liquid Retina Display with True Tone',
        'Power Adapter' => '30W USB-C Power Adapter'
    ],
    42 => [
        'CPU' => '10-Core CPU',
        'GPU' => '10-Core GPU',
        'Neural Engine' => '16-Core Neural Engine',
        'Display' => '13.6-inch Liquid Retina Display with True Tone',
        'Power Adapter' => '35W Dual USB-C Port Compact Power Adapter'
    ],
    43 => [
        'CPU' => '10-Core CPU',
        'GPU' => '10-Core GPU',
        'Neural Engine' => '16-Core Neural Engine',
        'Display' => '13.6-inch Liquid Retina Display with True Tone',
        'Power Adapter' => '35W Dual USB-C Port Compact Power Adapter'
    ],
    44 => [
        'CPU' => '10-Core CPU',
        'GPU' => '10-Core GPU',
        'Neural Engine' => '16-Core Neural Engine',
        'Display' => '13.6-inch Liquid Retina Display with True Tone',
        'Power Adapter' => '35W Dual USB-C Port Compact Power Adapter'
    ],
    45 => [
        'CPU' => '10-Core CPU',
        'GPU' => '10-Core GPU',
        'Neural Engine' => '16-Core Neural Engine',
        'Display' => '13.6-inch Liquid Retina Display with True Tone',
        'Power Adapter' => '35W Dual USB-C Port Compact Power Adapter'
    ],
    46 => [
        'CPU' => '10-Core CPU',
        'GPU' => '10-Core GPU',
        'Neural Engine' => '16-Core Neural Engine',
        'Display' => '13.6-inch Liquid Retina Display with True Tone',
        'Power Adapter' => '35W Dual USB-C Port Compact Power Adapter'
    ],
];

$specs = $productSpecs[$product_id] ?? null;

function getColorHex($color) {
    $colorMap = [
        'deep blue'=> '#1a2e45', 'cosmic orange' => '#ff8c30', 'silver' => '#f5f5f7',
        'desert titanium' => '#d6c6b3', 'natural titanium' => '#c8c0b3',
        'white titanium' => '#f5f5f0', 'black titanium' => '#1d1d1f',
        'blue titanium' => '#4b5b78', 'blue' => '#99c7f2', 'pink' => '#ffd6e8',
        'teal' => '#bfe7e0', 'yellow' => '#fff4b1', 'midnight' => '#1d1d1f',
        'starlight' => '#f5e4ca', 'green' => '#c9dcd4', 'purple' => '#dcc5da',
        'red' => '#f54542', 'white' => '#f5f5f0', 'black' => '#1d1d1f',
        'sky blue' => '#8ec6f9', 'light gold' => '#e8d9b8', 'cloud white' => '#f5f5f5',
        'space black' => '#1d1d1f'
    ];
    return $colorMap[strtolower(trim($color))] ?? '#ddd';
}

function renderStars($rating) {
    $output = '';
    for ($i = 1; $i <= 5; $i++) {
        $output .= $i <= $rating ? '★' : '☆';
    }
    return $output;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name'] ?? 'Product') ?> - R&M Apple Gadgets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #fff; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .navbar-custom { background: rgba(0,0,0,0.8); backdrop-filter: blur(20px); padding: 12px 40px; }
        .product-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .product-image-large { width: 100%; max-height: 600px; border-radius: 18px; background: #f5f5f7; padding: 40px; object-fit: contain; }
        .price-section { background: #f9f9f9; padding: 25px; border-radius: 12px; margin: 20px 0; }
        .price { font-size: 2.2em; font-weight: 700; }
        .color-option { width: 42px; height: 42px; border-radius: 50%; border: 2px solid #ccc; cursor: pointer; transition: 0.25s; }
        .color-option.selected { border: 3px solid #0071e3; transform: scale(1.1); }
        .storage-option { padding: 12px 20px; border: 2px solid #ddd; border-radius: 10px; cursor: pointer; transition: 0.25s; background: #fff; font-weight: 500; }
        .storage-option.selected { background: #0071e3; color: #fff; border-color: #0071e3; }
        .add-to-cart-btn { width: 100%; padding: 15px; background: #0071e3; color: #fff; border: none; border-radius: 10px; font-size: 1.1em; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .add-to-cart-btn:hover { background: #0077ed; transform: translateY(-2px); }
        .specs-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin: 30px 0; }
        .spec-card { background: #f9f9f9; padding: 20px; border-radius: 12px; }
        .spec-card h4 { font-size: 1.1em; font-weight: 600; color: #0071e3; margin-bottom: 10px; }
        .review-card { background: #f9f9f9; padding: 20px; border-radius: 12px; margin-bottom: 15px; }
        .review-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .review-stars { color: #ffb800; font-size: 1.2em; }
        .section-title { font-size: 1.8em; font-weight: 700; margin: 50px 0 25px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0; }
        .rating-display { color: #ffb800; font-size: 1.3em; }
        .breadcrumb { padding: 20px 0; font-size: 0.9em; }
        .breadcrumb a { color: #0071e3; text-decoration: none; }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<header class="navbar navbar-expand-lg navbar-dark navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.53 4.09l-.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z'/%3E%3C/svg%3E" width="24" height="24" class="me-2">
            <span class="text-white fw-bold">R&M Apple Gadgets</span>
        </a>
        
        <nav class="d-none d-lg-block">
            <ul class="navbar-nav d-flex flex-row gap-3">
                <li class="nav-item"><a class="nav-link text-white" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link text-white active" href="iphone.php">iPhone</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="ipad.php">iPad</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="macbook.php">MacBook</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="support.php">Support</a></li>
            </ul>
        </nav>
        
        <div class="d-flex align-items-center gap-3">
            <a href="cart.php" class="text-white text-decoration-none">
                <i class="fas fa-shopping-cart"></i>
            </a>
            <?php if (isset($_SESSION['username'])): ?>
                <div class="dropdown">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="customerdash.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="index.php" class="btn btn-outline-light btn-sm">Login</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="container product-container">
    <nav class="breadcrumb mb-4">
        <a href="index.php">Home</a> / 
        <a href="<?= htmlspecialchars($categoryPage) ?>"><?= htmlspecialchars($category) ?></a> / 
        <span><?= htmlspecialchars($product['name'] ?? 'Product') ?></span>
    </nav>

    <div class="row g-5">
        <div class="col-md-6">
            <img src="<?= htmlspecialchars($product['image_url'] ?? 'images/placeholder.png') ?>" 
                 alt="<?= htmlspecialchars($product['name'] ?? 'Product') ?>" 
                 class="product-image-large"
                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22600%22 height=%22600%22%3E%3Crect fill=%22%23f5f5f7%22 width=%22600%22 height=%22600%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 fill=%22%2386868b%22 font-size=%22120%22%3E📱%3C/text%3E%3C/svg%3E'">
        </div>

        <div class="col-md-6">
            <?php if ($product['condition_type'] === 'new'): ?>
                <span class="badge bg-dark">NEW</span>
            <?php elseif ($product['condition_type'] === 'refurbished'): ?>
                <span class="badge bg-success">REFURBISHED</span>
            <?php else: ?>
                <span class="badge bg-warning">PRE-OWNED</span>
            <?php endif; ?>
            
            <h1 class="mt-2 fw-bold"><?= htmlspecialchars($product['name'] ?? 'Product') ?></h1>
            
            <?php if (!empty($product['description'])): ?>
                <p class="text-muted"><?= htmlspecialchars($product['description']) ?></p>
            <?php else: ?>
                <p class="text-muted">Premium quality iPhone in excellent condition.</p>
            <?php endif; ?>

            <?php if ($total_reviews > 0): ?>
            <div class="rating-display mb-3">
                <?= renderStars($avg_rating) ?> 
                <span class="text-muted ms-2"><?= $avg_rating ?> (<?= $total_reviews ?> reviews)</span>
            </div>
            <?php endif; ?>

            <div class="price-section">
                <div class="price">₱<span id="current-price"><?= number_format($product['price']) ?></span></div>
                <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
                    <div class="text-muted text-decoration-line-through">₱<?= number_format($product['original_price']) ?></div>
                    <div class="text-success fw-bold">Save ₱<?= number_format($product['original_price'] - $product['price']) ?></div>
                <?php endif; ?>
            </div>

            <?php if ($product['stock_quantity'] < 10 && $product['stock_quantity'] > 0): ?>
            <div class="alert alert-warning">⚡ Only <?= $product['stock_quantity'] ?> left in stock!</div>
            <?php elseif ($product['stock_quantity'] == 0): ?>
            <div class="alert alert-danger">❌ Out of stock</div>
            <?php endif; ?>

            <?php if (count($colors) > 0 && !empty($colors[0])): ?>
            <div class="mt-4">
                <h5>Choose Color</h5>
                <div class="d-flex gap-2 my-3">
                    <?php foreach ($colors as $index => $color): ?>
                    <div class="color-option <?= $index === 0 ? 'selected' : '' ?>" 
                         style="background: <?= getColorHex($color) ?>" 
                         data-color="<?= htmlspecialchars(trim($color)) ?>"
                         title="<?= htmlspecialchars(trim($color)) ?>"></div>
                    <?php endforeach; ?>
                </div>
                <p class="text-muted small">Selected: <span id="color-display"><?= htmlspecialchars(trim($colors[0])) ?></span></p>
            </div>
            <?php endif; ?>

            <?php
        
            $macbookIDs = [35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49];
            ?>

            <?php if (!in_array($product['id'], $macbookIDs)): ?>
                <?php if (count($storages) > 0 && !empty($storages[0])): ?>
                    <div class="mt-4">
                        <h5>Choose Storage</h5>
                        <div class="d-flex gap-2 my-3 flex-wrap">
                            <?php foreach ($storages as $index => $storage): ?>
                            <div class="storage-option <?= $index === 0 ? 'selected' : '' ?>" 
                                data-storage="<?= htmlspecialchars(trim($storage)) ?>" 
                                data-index="<?= $index ?>">
                                <?= htmlspecialchars(trim($storage)) ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($product['stock_quantity'] > 0): ?>
                <button class="add-to-cart-btn mt-4" onclick="addToCart()">Add to Cart</button>
            <?php else: ?>
                <button class="add-to-cart-btn mt-4" disabled style="background: #ccc; cursor: not-allowed;">Out of Stock</button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($specs): ?>
    <h2 class="section-title">Technical Specifications</h2>
    <div class="specs-grid">
        <div class="spec-card">
            <h4>Display</h4>
            <p><?= $specs['display'] ?></p>
            <p><?= $specs['resolution'] ?></p>
            <?php if (isset($specs['dynamic_island'])): ?>
                <p><?= $specs['dynamic_island'] ? 'Dynamic Island ✓' : 'Notch Display' ?></p>
            <?php elseif (isset($specs['promotion'])): ?>
                <p><?= $specs['promotion'] ? 'ProMotion Technology ✓' : '' ?></p>
            <?php endif; ?>
        </div>
        
        <div class="spec-card">
            <h4>Camera System</h4>
            <?php if (isset($specs['camera_main'])): ?>
                <p>Main: <?= $specs['camera_main'] ?></p>
            <?php endif; ?>
            <?php if (isset($specs['camera_ultra'])): ?>
                <p>Ultra Wide: <?= $specs['camera_ultra'] ?></p>
            <?php endif; ?>
            <?php if (isset($specs['camera_tele'])): ?>
                <p>Telephoto: <?= $specs['camera_tele'] ?></p>
            <?php endif; ?>
            <?php if (isset($specs['front_camera'])): ?>
                <p>Front: <?= $specs['front_camera'] ?></p>
            <?php endif; ?>
            <p><?= $specs['video'] ?></p>
        </div>
        
        <div class="spec-card">
            <h4>Performance</h4>
            <p>Chip: <?= $specs['chip'] ?></p>
            <p>Battery: <?= $specs['battery'] ?></p>
            <?php if (isset($specs['apple_pencil'])): ?>
                <p>Supports: <?= $specs['apple_pencil'] ?></p>
            <?php endif; ?>
        </div>
        
        <div class="spec-card">
            <h4>Connectivity</h4>
            <p><?= $specs['connectivity'] ?></p>
            <?php if (isset($specs['water_resistance'])): ?>
                <p><?= $specs['water_resistance'] ?></p>
            <?php endif; ?>
            <?php if (isset($specs['ports'])): ?>
                <p>Ports: <?= $specs['ports'] ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($macbookSpecs[$product['id']]) || !empty($product['chip']) || !empty($product['screen_size']) || !empty($product['unified_memory'])): ?>
    <h2 class="section-title">Specifications</h2>
    <div class="specs-grid">

        <!-- Database-based specs -->
        <?php if (!empty($product['chip'])): ?>
            <div class="spec-card">
                <h4>Chip</h4>
                <p><?= htmlspecialchars($product['chip']) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($product['unified_memory'])): ?>
            <div class="spec-card">
                <h4>Unified Memory</h4>
                <p><?= htmlspecialchars($product['unified_memory']) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($product['screen_size'])): ?>
            <div class="spec-card">
                <h4>Screen Size</h4>
                <p><?= htmlspecialchars($product['screen_size']) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($product['storage'])): ?>
            <div class="spec-card">
                <h4>Storage</h4>
                <p><?= htmlspecialchars($product['storage']) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($product['colors'])): ?>
            <div class="spec-card">
                <h4>Colors</h4>
                <p><?= htmlspecialchars($product['colors']) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($product['model_year'])): ?>
            <div class="spec-card">
                <h4>Model Year</h4>
                <p><?= htmlspecialchars($product['model_year']) ?></p>
            </div>
        <?php endif; ?>

        <!-- Static MacBook specs -->
        <?php if (isset($macbookSpecs[$product['id']])): ?>
            <?php foreach ($macbookSpecs[$product['id']] as $key => $value): ?>
                <div class="spec-card">
                    <h4><?= htmlspecialchars($key) ?></h4>
                    <p><?= htmlspecialchars($value) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
    <?php endif; ?>

    <h2 class="section-title">Customer Reviews (<?= $total_reviews ?>)</h2>
    
    <?php if (count($reviews) > 0): ?>
        <?php foreach ($reviews as $review): ?>
        <div class="review-card">
            <div class="review-header">
                <div>
                    <strong><?= htmlspecialchars($review['full_name'] ?? 'Anonymous') ?></strong>
                    <?php if (!empty($review['verified_purchase'])): ?>
                    <span class="badge bg-success ms-2">Verified Purchase</span>
                    <?php endif; ?>
                </div>
                <small class="text-muted"><?= date('M d, Y', strtotime($review['created_at'])) ?></small>
            </div>
            <div class="review-stars"><?= renderStars($review['rating']) ?></div>
            <?php if (!empty($review['review_title'])): ?>
            <h6 class="mt-2"><?= htmlspecialchars($review['review_title']) ?></h6>
            <?php endif; ?>
            <?php if (!empty($review['review_text'])): ?>
            <p class="mb-2"><?= htmlspecialchars($review['review_text']) ?></p>
            <?php endif; ?>
            <small class="text-muted">👍 <?= $review['helpful_count'] ?? 0 ?> people found this helpful</small>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-muted">No reviews yet. Be the first to review this product!</p>
    <?php endif; ?>
</div>

<script>
const basePrice = <?= (int)$product['price']; ?>;
const productId = <?= $product['id'] ?>;
const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;

let selectedColor = '<?= trim($colors[0]) ?>';
let selectedStorage = '<?= trim($storages[0]) ?>';

document.querySelectorAll('.color-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.color-option').forEach(o => o.classList.remove('selected'));
        this.classList.add('selected');
        selectedColor = this.dataset.color;
        document.getElementById('color-display').textContent = selectedColor;
    });
});

document.querySelectorAll('.storage-option').forEach(option => {
    option.addEventListener('click', function() {
        // Remove highlight from other buttons
        document.querySelectorAll('.storage-option').forEach(o => o.classList.remove('selected'));
        this.classList.add('selected');

        selectedStorage = this.dataset.storage;

        // Extract numeric value (handles GB and TB)
        const baseStorage = 128; // your base model size
        const storageText = selectedStorage.toUpperCase();
        let storageValue = 0;

        if (storageText.includes('TB')) {
            // Convert TB to GB for calculation
            storageValue = parseFloat(storageText) * 1024;
        } else if (storageText.includes('GB')) {
            storageValue = parseInt(storageText);
        }

        // Calculate step difference
        const stepDifference = (storageValue - baseStorage) / 128; 
        // Each +128GB = ₱5,000
        const newPrice = basePrice + (stepDifference * 5000);

        document.getElementById('current-price').textContent = newPrice.toLocaleString();
    });
});

async function addToCart() {
    if (!isLoggedIn) {
        alert('Please login first');
        window.location.href = 'index.php';
        return;
    }

    try {
        const response = await fetch('add_to_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                product_id: productId,
                quantity: 1,
                color: selectedColor,
                storage: selectedStorage
            })
        });

        const result = await response.json();
        alert(result.message);
        if (result.success) {
            setTimeout(() => location.reload(), 1000);
        }
    } catch (error) {
        alert('Failed to add to cart');
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>