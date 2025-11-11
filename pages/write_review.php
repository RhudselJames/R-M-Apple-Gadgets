<?php
session_start();
require_once __DIR__ . '/../backend/config/db_connect.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

$order_id = $_GET['order_id'] ?? null;
$product_id = $_GET['product_id'] ?? null;

if (!$order_id || !$product_id) {
    header('Location: customerdash.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Verify this is the user's delivered order
$stmt = $conn->prepare("
    SELECT o.*, oi.product_id, p.name, p.image_url
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.order_id = ? AND o.user_id = ? AND o.status = 'Delivered' AND oi.product_id = ?
");
$stmt->execute([$order_id, $user_id, $product_id]);
$order_product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order_product) {
    $_SESSION['error'] = "You can only review products from delivered orders.";
    header('Location: customerdash.php');
    exit;
}

// Check if already reviewed
$stmt = $conn->prepare("SELECT review_id FROM reviews WHERE user_id = ? AND product_id = ?");
$stmt->execute([$user_id, $product_id]);
$existing_review = $stmt->fetch();

if ($existing_review) {
    $_SESSION['error'] = "You have already reviewed this product.";
    header('Location: order_details.php?id=' . $order_id);
    exit;
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating']);
    $review_title = trim($_POST['review_title']);
    $review_text = trim($_POST['review_text']);
    
    if ($rating < 1 || $rating > 5) {
        $error = "Please select a rating between 1 and 5 stars.";
    } elseif (empty($review_text)) {
        $error = "Please write your review.";
    } else {
        try {
            $stmt = $conn->prepare("
                INSERT INTO reviews (product_id, user_id, rating, review_title, review_text, verified_purchase, created_at)
                VALUES (?, ?, ?, ?, ?, TRUE, NOW())
            ");
            $stmt->execute([$product_id, $user_id, $rating, $review_title, $review_text]);
            
            $_SESSION['success'] = "Thank you for your review!";
            header('Location: order_details.php?id=' . $order_id);
            exit;
        } catch (PDOException $e) {
            $error = "Error submitting review. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Write Review - R&M Apple Gadgets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f5f5f7;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .navbar-custom {
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(20px);
            padding: 12px 40px;
        }

        .review-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .review-card {
            background: white;
            border-radius: 18px;
            padding: 40px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .product-preview {
            display: flex;
            gap: 20px;
            padding: 20px;
            background: #f5f5f7;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .product-preview img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: 8px;
        }

        .product-preview-details h4 {
            font-size: 1.2em;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .rating-input {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }

        .star-input {
            font-size: 2.5em;
            color: #ddd;
            cursor: pointer;
            transition: all 0.2s;
        }

        .star-input:hover,
        .star-input.active {
            color: #ffb800;
            transform: scale(1.1);
        }

        .form-control, .form-label {
            border-radius: 10px;
        }

        .form-control:focus {
            border-color: #0071e3;
            box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.1);
        }

        .btn-submit {
            background: #0071e3;
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            width: 100%;
            transition: 0.3s;
        }

        .btn-submit:hover {
            background: #0077ed;
        }

        .section-title {
            font-size: 2em;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .back-link {
            color: #0071e3;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .char-counter {
            font-size: 0.85em;
            color: #6e6e73;
            text-align: right;
            margin-top: 5px;
        }
    </style>
</head>
<body>

<!-- Navigation -->
<header class="navbar navbar-expand-lg navbar-dark navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="../index.php">
            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.53 4.09l-.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z'/%3E%3C/svg%3E" width="24" height="24" class="me-2">
            <span class="text-white fw-bold">R&M Apple Gadgets</span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-white"><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?></span>
        </div>
    </div>
</header>

<div class="review-container">
    <a href="order_details.php?id=<?= $order_id ?>" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Order
    </a>

    <div class="review-card">
        <h1 class="section-title">Write a Review</h1>
        <p class="text-muted mb-4">Share your experience with this product</p>

        <!-- Product Preview -->
        <div class="product-preview">
            <img src="../<?= htmlspecialchars($order_product['image_url']) ?>" alt="<?= htmlspecialchars($order_product['name']) ?>">
            <div class="product-preview-details">
                <h4><?= htmlspecialchars($order_product['name']) ?></h4>
                <p class="text-muted mb-0">Order #<?= htmlspecialchars($order_id) ?></p>
                <span class="badge bg-success">Verified Purchase</span>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <!-- Review Form -->
        <form method="POST">
            <!-- Rating -->
            <div class="mb-4">
                <label class="form-label"><strong>Your Rating *</strong></label>
                <div class="rating-input" id="rating-input">
                    <span class="star-input" data-rating="1">★</span>
                    <span class="star-input" data-rating="2">★</span>
                    <span class="star-input" data-rating="3">★</span>
                    <span class="star-input" data-rating="4">★</span>
                    <span class="star-input" data-rating="5">★</span>
                </div>
                <input type="hidden" name="rating" id="rating-value" required>
                <small class="text-muted" id="rating-text">Click to rate</small>
            </div>

            <!-- Review Title -->
            <div class="mb-3">
                <label for="review_title" class="form-label"><strong>Review Title</strong> (Optional)</label>
                <input type="text" 
                       class="form-control" 
                       id="review_title" 
                       name="review_title" 
                       maxlength="255"
                       placeholder="Sum up your experience in one line">
            </div>

            <!-- Review Text -->
            <div class="mb-3">
                <label for="review_text" class="form-label"><strong>Your Review *</strong></label>
                <textarea class="form-control" 
                          id="review_text" 
                          name="review_text" 
                          rows="6" 
                          maxlength="1000"
                          placeholder="Tell us what you think about this product. What did you like or dislike? How did it perform?" 
                          required></textarea>
                <div class="char-counter">
                    <span id="char-count">0</span>/1000 characters
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i> Submit Review
            </button>
        </form>
    </div>
</div>

<script>
// Rating functionality
let selectedRating = 0;
const stars = document.querySelectorAll('.star-input');
const ratingValue = document.getElementById('rating-value');
const ratingText = document.getElementById('rating-text');

const ratingMessages = {
    1: 'Poor - Not satisfied',
    2: 'Fair - Below expectations',
    3: 'Good - Meets expectations',
    4: 'Very Good - Exceeded expectations',
    5: 'Excellent - Outstanding!'
};

stars.forEach(star => {
    star.addEventListener('click', function() {
        selectedRating = parseInt(this.dataset.rating);
        ratingValue.value = selectedRating;
        updateStars();
        ratingText.textContent = ratingMessages[selectedRating];
        ratingText.style.color = '#0071e3';
    });

    star.addEventListener('mouseenter', function() {
        const rating = parseInt(this.dataset.rating);
        highlightStars(rating);
    });
});

document.getElementById('rating-input').addEventListener('mouseleave', function() {
    updateStars();
});

function updateStars() {
    stars.forEach((star, index) => {
        if (index < selectedRating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
}

function highlightStars(rating) {
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
}

// Character counter
const reviewText = document.getElementById('review_text');
const charCount = document.getElementById('char-count');

reviewText.addEventListener('input', function() {
    charCount.textContent = this.value.length;
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>