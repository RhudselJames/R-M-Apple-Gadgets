<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Support - R&M Apple Gadgets</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: #f5f5f7;
      color: #1d1d1f;
    }

    .navbar-custom {
      background: rgba(0,0,0,0.8);
      backdrop-filter: blur(20px);
      padding: 12px 40px;
    }

    .support-hero {
      background: linear-gradient(135deg,  #262928ff 0%, #000000ff 100%);
      color: white;
      text-align: center;
      padding: 100px 20px 80px;
      margin-bottom: 50px;
    }

    .support-hero h1 {
      font-size: 3.5em;
      font-weight: 700;
      margin-bottom: 20px;
    }

    .support-hero p {
      font-size: 1.3em;
      opacity: 0.95;
      max-width: 600px;
      margin: 0 auto;
    }

    .container-custom {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px 80px;
    }

    .section-title {
      font-size: 2.2em;
      font-weight: 700;
      text-align: center;
      margin-bottom: 50px;
      color: #1d1d1f;
    }

    .contact-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 30px;
      margin-bottom: 60px;
    }

    .contact-card {
      background: white;
      padding: 40px;
      border-radius: 18px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      text-align: center;
      transition: all 0.3s ease;
    }

    .contact-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }

    .contact-icon {
      width: 80px;
      height: 80px;
      border: 3px solid #000000ff;
      background: linear-gradient(135deg, #3c3c3dff 0%, #616161ff 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 25px;
      font-size: 2em;
      color: white;
    }

    .contact-card h3 {
      font-size: 1.5em;
      font-weight: 600;
      margin-bottom: 15px;
      color: #1d1d1f;
    }

    .contact-card p {
      color: #6e6e73;
      font-size: 1em;
      margin-bottom: 10px;
      line-height: 1.6;
    }

    .contact-card a {
      color: #0071e3;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s;
    }

    .contact-card a:hover {
      color: #0077ed;
      text-decoration: underline;
    }

    .team-section {
      background: white;
      padding: 60px 40px;
      border-radius: 18px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      margin-bottom: 60px;
    }

    .team-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 40px;
      margin-top: 40px;
    }

    .team-member {
      text-align: center;
    }

    .team-avatar {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      margin: 0 auto 20px;
      overflow: hidden; 
      border: 3px solid #000000ff;
      background: linear-gradient(135deg, #3c3c3dff 0%, #616161ff 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 700;
      font-size: 1.8em;
      text-transform: uppercase;
  }

    
    .team-avatar img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      transform: scale(1.05);
      transform-origin: center;
      display: block;
  }

    
    .team-avatar::before {
      content: attr(data-initials);
  }
  .team-avatar1 {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    margin: 0 auto 20px;
    overflow: hidden; 
    border: 3px solid #000000ff;
    background: linear-gradient(135deg, #3c3c3dff 0%, #616161ff 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.8em;
    text-transform: uppercase;
  }

    
  .team-avatar1 img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    transform: scale(1.2);
    transform-origin: center;
    display: block;
  }

    
  .team-avatar1::before {
    content: attr(data-initials);
  }

  .team-member h4 {
    font-size: 1.3em;
    font-weight: 600;
    margin-bottom: 8px;
    color: #1d1d1f;
  }

  .team-member p {
    color: #6e6e73;
    font-size: 0.95em;
    margin-bottom: 5px;
  }

  .team-member a {
    color: #0071e3;
    text-decoration: none;
    font-size: 0.9em;
  }

  .disclaimer-section {
    background: #fff3cd;
    border: 2px solid #ffc107;
    border-radius: 18px;
    padding: 40px;
    margin-bottom: 60px;
  }

  .disclaimer-section h3 {
    color: #856404;
    font-size: 1.5em;
    font-weight: 700;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .disclaimer-section p {
    color: #856404;
    font-size: 1em;
    line-height: 1.8;
    margin-bottom: 15px;
  }

  .disclaimer-section ul {
    color: #856404;
    margin-left: 20px;
    line-height: 1.8;
  }

  .faq-section {
    background: white;
    padding: 60px 40px;
    border-radius: 18px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  }

  .faq-item {
    border-bottom: 1px solid #e5e5e7;
    padding: 25px 0;
  }

  .faq-item:last-child {
    border-bottom: none;
  }

  .faq-question {
    font-size: 1.2em;
    font-weight: 600;
    color: #1d1d1f;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .faq-answer {
    color: #6e6e73;
    font-size: 1em;
    line-height: 1.7;
  }

  @media (max-width: 768px) {
    .support-hero h1 {
      font-size: 2.5em;
    }

    .section-title {
      font-size: 1.8em;
    }

    .contact-grid,
    .team-grid {
      grid-template-columns: 1fr;
    }
  }
  </style>
</head>
<body>

<!-- Navigation Bar -->
<header class="navbar navbar-expand-lg navbar-dark navbar-custom">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="../index.php">
      <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.53 4.09l-.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z'/%3E%3C/svg%3E" width="24" height="24" class="me-2">
      <span class="text-white fw-bold">R&M Apple Gadgets</span>
    </a>
    
    <nav class="d-none d-lg-block">
      <ul class="navbar-nav d-flex flex-row gap-3">
        <li class="nav-item"><a class="nav-link text-white" href="../index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="iphone.php">iPhone</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="ipad.php">iPad</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="macbook.php">MacBook</a></li>
        <li class="nav-item"><a class="nav-link text-white active" href="support.php">Support</a></li>
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
            <li><a class="dropdown-item text-danger" href="../backend/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
          </ul>
        </div>
      <?php else: ?>
        <a href="../index.php" class="btn btn-outline-light btn-sm">Login</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<!-- Hero Section -->
<section class="support-hero">
  <h1>How can we help you?</h1>
  <p>We're here to answer your questions and provide support for all your Apple gadget needs.</p>
</section>

<div class="container-custom">
  <!-- Contact Information -->
  <h2 class="section-title">Get in Touch</h2>
  <div class="contact-grid">
    <div class="contact-card">
      <div class="contact-icon">
        <i class="fas fa-envelope"></i>
      </div>
      <h3>Email Us</h3>
      <p>Send us an email and we'll respond within 24 hours.</p>
      <a href="mailto:support@rmapplegadgets.com">support@rmapplegadgets.com</a>
    </div>

    <div class="contact-card">
      <div class="contact-icon">
        <i class="fas fa-phone"></i>
      </div>
      <h3>Call Us</h3>
      <p>Available Monday to Saturday<br>9:00 AM - 6:00 PM</p>
      <a href="tel:+639123456789">+63 912 345 6789</a>
    </div>

    <div class="contact-card">
      <div class="contact-icon">
        <i class="fas fa-map-marker-alt"></i>
      </div>
      <h3>Visit Us</h3>
      <p>Mapua Malayan Colleges of Mindanao<br>General Douglas, MacArthur Highway, Matina<br>Philippines 8000</p>
      <a href="https://maps.google.com" target="_blank">Get Directions</a>
    </div>
  </div>

  <!-- Team Section -->
  <div class="team-section">
    <h2 class="section-title" style="margin-bottom: 20px;">Our Team</h2>
    <p style="text-align: center; color: #6e6e73; margin-bottom: 30px;">
      Meet the people behind R&M Apple Gadgets
    </p>
    
    <div class="team-grid">
      <div class="team-member">
        <div class="team-avatar">
            <img src="../assets/images/RJUY.png">
        </div>
        <h4>Rhudsel James M. Uy</h4>
        <p>Co-Founder & CEO</p>
        <p><a href="mailto:rjUy@mcm.edu.com">rjUy@mcm.edu.com</a></p>
      </div>

      <div class="team-member">
        <div class="team-avatar1">
            <img src="../assets/images/MarManaay.png">
        </div>
        <h4>Mar Christian M. Mana-ay</h4>
        <p>CTO</p>
        <p><a href="mailto:mcManaay@mcm.edu.com">mcManaay@mcm.edu.com</a></p>
      </div>

      <div class="team-member">
        <div class="team-avatar">CS</div>
        <h4>Customer Support</h4>
        <p>Support Team</p>
        <p><a href="mailto:support@rmapplegadgets.com">support@rmapplegadgets.com</a></p>
      </div>
    </div>
  </div>

  <!-- Disclaimer Section -->
  <div class="disclaimer-section">
    <h3>
      <i class="fas fa-exclamation-triangle"></i>
      Educational Purpose Disclaimer
    </h3>
    <p>
      <strong>Important Notice:</strong> This website is created solely for educational and academic purposes as part of a school project. 
    </p>
    <ul>
      <li><strong>Product Images:</strong> All product images used on this website are sourced from Apple Inc.'s official website and Facebook Marketplace listings. We do not claim ownership of these images.</li>
      <li><strong>Non-Commercial Use:</strong> This website is not intended for commercial use and no actual transactions or sales are conducted through this platform.</li>
      <li><strong>Academic Project:</strong> This is a student project designed to demonstrate web development, e-commerce concepts, and database management skills.</li>
      <li><strong>No Affiliation:</strong> R&M Apple Gadgets is not affiliated with, endorsed by, or connected to Apple Inc. or any other company mentioned on this website.</li>
      <li><strong>Copyright:</strong> All trademarks, logos, and brand names are the property of their respective owners. All company, product and service names used in this website are for identification purposes only.</li>
    </ul>
    <p style="margin-top: 20px;">
      <strong>Fair Use Statement:</strong> The use of copyrighted material on this educational website falls under fair use for educational purposes as defined by copyright law. If you are a copyright holder and believe your work has been used inappropriately, please contact us immediately.
    </p>
  </div>

  <!-- FAQ Section -->
  <div class="faq-section">
    <h2 class="section-title" style="margin-bottom: 30px;">Frequently Asked Questions</h2>

    <div class="faq-item">
      <div class="faq-question">
        <i class="fas fa-question-circle" style="color: #0071e3;"></i>
        What payment methods do you accept?
      </div>
      <div class="faq-answer">
        We accept Cash on Delivery (COD), bank transfers, and major credit/debit cards. All payments are secure and processed through trusted payment gateways.
      </div>
    </div>

    <div class="faq-item">
      <div class="faq-question">
        <i class="fas fa-question-circle" style="color: #0071e3;"></i>
        Do you offer warranty on refurbished products?
      </div>
      <div class="faq-answer">
        Yes! All our refurbished products come with a 90-day warranty covering manufacturing defects. Extended warranty options are also available.
      </div>
    </div>

    <div class="faq-item">
      <div class="faq-question">
        <i class="fas fa-question-circle" style="color: #0071e3;"></i>
        What is your return policy?
      </div>
      <div class="faq-answer">
        We offer a 7-day return policy for unopened brand new products and a 3-day return policy for refurbished items. Products must be in original condition with all accessories included.
      </div>
    </div>

    <div class="faq-item">
      <div class="faq-question">
        <i class="fas fa-question-circle" style="color: #0071e3;"></i>
        How long does shipping take?
      </div>
      <div class="faq-answer">
        For Metro Manila, delivery typically takes 1-3 business days. For provincial areas, expect 3-7 business days. We also offer same-day delivery for selected areas within Metro Manila.
      </div>
    </div>

    <div class="faq-item">
      <div class="faq-question">
        <i class="fas fa-question-circle" style="color: #0071e3;"></i>
        Are your products authentic Apple products?
      </div>
      <div class="faq-answer">
        Yes, all our products are 100% authentic Apple products. We source from authorized distributors and provide certificates of authenticity upon request.
      </div>
    </div>

    <div class="faq-item">
      <div class="faq-question">
        <i class="fas fa-question-circle" style="color: #0071e3;"></i>
        Can I trade-in my old device?
      </div>
      <div class="faq-answer">
        Yes! We accept trade-ins for iPhones, iPads, and MacBooks. Contact our support team for a quote based on your device's condition and model.
      </div>
    </div>
  </div>
</div>

<!-- Footer -->
<footer style="background: #1d1d1f; color: #f5f5f7; padding: 40px 20px; margin-top: 80px;">
  <div class="container-custom">
    <div style="text-align: center;">
      <p style="margin-bottom: 10px;">&copy; 2025 R&M Apple Gadgets. Educational Project Only.</p>
      <p style="font-size: 0.9em; color: #86868b;">
        This is a student project for educational purposes. Not affiliated with Apple Inc.
      </p>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>