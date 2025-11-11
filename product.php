<?php
session_start();
include __DIR__ . '/../backend/config/db_connect.php'; // make sure this file connects using PDO or mysqli

if (!isset($_GET['id'])) {
    // Redirect if no product ID is provided
    header("Location: index.php");
    exit();
}

$product_id = intval($_GET['id']); // prevent SQL injection

// Fetch product details
$query = $pdo->prepare("SELECT * FROM products WHERE id = :id AND status = 'active' LIMIT 1");
$query->bindParam(':id', $product_id, PDO::PARAM_INT);
$query->execute();
$product = $query->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "<h2 style='text-align:center; margin-top:50px;'>⚠️ Product not found.</h2>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> | R&M Apple Gadgets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f9f9f9;
            font-family: 'Poppins', sans-serif;
        }
        .product-container {
            max-width: 1000px;
            margin: 50px auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .product-img {
            width: 100%;
            border-radius: 10px;
        }
        .product-name {
            font-size: 2rem;
            font-weight: 600;
        }
        .price {
            color: #28a745;
            font-size: 1.5rem;
            font-weight: 600;
        }
        .original-price {
            text-decoration: line-through;
            color: gray;
            font-size: 1rem;
            margin-left: 8px;
        }
        .variant-label {
            font-weight: 500;
        }
        .btn-buy {
            background-color: #007bff;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
        }
        .btn-buy:hover {
            background-color: #0056b3;
        }
        .btn-back {
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
        }
        .btn-back:hover {
            background-color: #5a6268;
            color: white;
        }
    </style>
</head>
<body>

<div class="container product-container">
    <div class="row">
        <div class="col-md-5 text-center">
            <img src="../<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img">
        </div>
        <div class="col-md-7">
            <h1 class="product-name"><?= htmlspecialchars($product['name']) ?></h1>
            <p><span class="price">₱<?= number_format($product['price'], 2) ?></span>
               <?php if (!empty($product['original_price'])): ?>
                   <span class="original-price">₱<?= number_format($product['original_price'], 2) ?></span>
               <?php endif; ?>
            </p>

            <p><strong>Category:</strong> <?= htmlspecialchars($product['category']) ?></p>
            <p><strong>Condition:</strong> <?= htmlspecialchars($product['condition_type']) ?></p>
            <p><strong>Color:</strong> <?= htmlspecialchars($product['color']) ?></p>
            <p><strong>Storage:</strong> <?= htmlspecialchars($product['storage']) ?></p>
            <p><strong>Model Year:</strong> <?= htmlspecialchars($product['model_year']) ?></p>
            <p><strong>In Stock:</strong> <?= htmlspecialchars($product['stock_quantity']) ?></p>
            <p class="mt-3"><?= nl2br(htmlspecialchars($product['description'])) ?></p>

            <div class="mt-4">
                <button class="btn-buy me-2">Buy Now</button>
                <a href="../index.php" class="btn-back">← Back to Products</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>