<?php
session_start();
require_once "db.php";
include "header.php";

// вземаме всички категории
$res = $mysqli->query("SELECT id, name FROM service_categories ORDER BY id");
?>
<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lotus Temple - Luxurious Wellness Experience</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #0f4c3a 0%, #1a5f4a 50%, #2d7a5f 100%);
      color: #f8f9fa;
      overflow-x: hidden;
      position: relative;
    }

    /* Tropical Background */
    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: 
        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="tropical" x="0" y="0" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="2" fill="%23ffffff" opacity="0.1"/><circle cx="80" cy="40" r="1.5" fill="%23ffffff" opacity="0.08"/><circle cx="40" cy="80" r="1" fill="%23ffffff" opacity="0.06"/><path d="M10 30 Q30 10 50 30 Q70 50 90 30" stroke="%23ffffff" stroke-width="0.5" fill="none" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23tropical)"/></svg>'),
        linear-gradient(135deg, #0f4c3a 0%, #1a5f4a 50%, #2d7a5f 100%);
      background-size: 200px 200px, 100% 100%;
      z-index: -1;
      filter: blur(0.5px);
    }

    /* Navigation */
    .navbar {
      background: rgba(15, 76, 58, 0.95);
      backdrop-filter: blur(20px);
      border-bottom: 2px solid #d4af37;
      position: fixed;
      top: 0;
      width: 100%;
      z-index: 1000;
      padding: 1rem 0;
    }

    .nav-container {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 2rem;
    }

    .logo {
      font-family: 'Playfair Display', serif;
      font-size: 2rem;
      font-weight: 700;
      color: #d4af37;
      text-decoration: none;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }

    .nav-links {
      display: flex;
      gap: 2rem;
      list-style: none;
    }

    .nav-links a {
      color: #f8f9fa;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
      position: relative;
      padding: 0.5rem 1rem;
    }

    .nav-links a::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      width: 0;
      height: 2px;
      background: #d4af37;
      transition: all 0.3s ease;
      transform: translateX(-50%);
    }

    .nav-links a:hover::after {
      width: 100%;
    }

    .nav-links a:hover {
      color: #d4af37;
    }

    /* Hero Section */
    .hero {
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      position: relative;
      margin-top: 80px;
    }

    .hero::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle at center, rgba(212, 175, 55, 0.1) 0%, transparent 70%);
      z-index: -1;
    }

    .hero-content h1 {
      font-family: 'Playfair Display', serif;
      font-size: 4rem;
      font-weight: 700;
      margin-bottom: 1rem;
      color: #f8f9fa;
      text-shadow: 3px 3px 6px rgba(0,0,0,0.5);
      animation: fadeInUp 1s ease-out;
    }

    .hero-content p {
      font-size: 1.3rem;
      margin-bottom: 2rem;
      color: #d4af37;
      font-weight: 300;
      animation: fadeInUp 1s ease-out 0.3s both;
    }

    .cta-button {
      display: inline-block;
      padding: 1rem 2.5rem;
      background: linear-gradient(135deg, #d4af37, #b8941f);
      color: #0f4c3a;
      text-decoration: none;
      border-radius: 50px;
      font-weight: 600;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      box-shadow: 0 8px 25px rgba(212, 175, 55, 0.3);
      animation: fadeInUp 1s ease-out 0.6s both;
    }

    .cta-button:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 35px rgba(212, 175, 55, 0.4);
      background: linear-gradient(135deg, #b8941f, #d4af37);
    }

    /* Categories Section */
    .categories-section {
      padding: 5rem 0;
      background: rgba(15, 76, 58, 0.8);
      backdrop-filter: blur(10px);
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 2rem;
    }

    .section-title {
      text-align: center;
      margin-bottom: 4rem;
    }

    .section-title h2 {
      font-family: 'Playfair Display', serif;
      font-size: 3rem;
      font-weight: 600;
      color: #d4af37;
      margin-bottom: 1rem;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }

    .section-title p {
      font-size: 1.1rem;
      color: #f8f9fa;
      max-width: 600px;
      margin: 0 auto;
      line-height: 1.6;
    }

    .categories {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2rem;
    }

    .category-card {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(212, 175, 55, 0.3);
      border-radius: 20px;
      overflow: hidden;
      transition: all 0.4s ease;
      position: relative;
      text-decoration: none;
      color: inherit;
    }

    .category-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, rgba(212, 175, 55, 0.1), rgba(15, 76, 58, 0.1));
      opacity: 0;
      transition: opacity 0.4s ease;
      z-index: 1;
    }

    .category-card:hover::before {
      opacity: 1;
    }

    .category-card:hover {
      transform: translateY(-10px) scale(1.02);
      border-color: #d4af37;
      box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }

    .category-card img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      transition: transform 0.4s ease;
    }

    .category-card:hover img {
      transform: scale(1.1);
    }

    .category-content {
      padding: 1.5rem;
      position: relative;
      z-index: 2;
    }

    .category-content h3 {
      font-family: 'Playfair Display', serif;
      font-size: 1.5rem;
      font-weight: 600;
      color: #d4af37;
      margin-bottom: 0.5rem;
      text-align: center;
    }

    .category-content p {
      color: #f8f9fa;
      text-align: center;
      font-size: 0.9rem;
      opacity: 0.9;
    }

    /* Floating Elements */
    .floating-element {
      position: absolute;
      width: 100px;
      height: 100px;
      background: radial-gradient(circle, rgba(212, 175, 55, 0.1), transparent);
      border-radius: 50%;
      animation: float 6s ease-in-out infinite;
    }

    .floating-element:nth-child(1) {
      top: 20%;
      left: 10%;
      animation-delay: 0s;
    }

    .floating-element:nth-child(2) {
      top: 60%;
      right: 15%;
      animation-delay: 2s;
    }

    .floating-element:nth-child(3) {
      bottom: 20%;
      left: 20%;
      animation-delay: 4s;
    }

    /* Animations */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes float {
      0%, 100% {
        transform: translateY(0px);
      }
      50% {
        transform: translateY(-20px);
      }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .hero-content h1 {
        font-size: 2.5rem;
      }
      
      .nav-links {
        display: none;
      }
      
      .categories {
        grid-template-columns: 1fr;
      }
    }

    /* Scroll Indicator */
    .scroll-indicator {
      position: absolute;
      bottom: 2rem;
      left: 50%;
      transform: translateX(-50%);
      animation: bounce 2s infinite;
    }

    .scroll-indicator i {
      color: #d4af37;
      font-size: 1.5rem;
    }

    @keyframes bounce {
      0%, 20%, 50%, 80%, 100% {
        transform: translateX(-50%) translateY(0);
      }
      40% {
        transform: translateX(-50%) translateY(-10px);
      }
      60% {
        transform: translateX(-50%) translateY(-5px);
      }
    }
  </style>
</head>
<body>
  <!-- Navigation -->
  <nav class="navbar">
    <div class="nav-container">
      <a href="#" class="logo">
        <i class="fas fa-spa"></i> Lotus Temple
      </a>
      <ul class="nav-links">
        <li><a href="#home">Home</a></li>
        <li><a href="#services">Services</a></li>
        <li><a href="price_list.php">Price List</a></li>
        <?php if(isset($_SESSION['user_id'])): ?>
          <li><a href="reservations.php">My Reservations</a></li>
          <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
          <li><a href="login.php">Login</a></li>
          <li><a href="register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero" id="home">
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    
    <div class="hero-content">
      <h1>Welcome to Lotus Temple - Spa & Wellness</h1>
      <p>Experience ultimate relaxation and rejuvenation in our serene wellness sanctuary</p>
      <a href="#services" class="cta-button">
        <i class="fas fa-arrow-down"></i> Explore Services
      </a>
    </div>
    
    <div class="scroll-indicator">
      <i class="fas fa-chevron-down"></i>
    </div>
  </section>

  <!-- Categories Section -->
  <section class="categories-section" id="services">
    <div class="container">
      <div class="section-title">
        <h2>Our Wellness Categories</h2>
        <p>Discover our carefully curated selection of premium spa treatments designed to restore your mind, body, and soul</p>
      </div>
      
      <div class="categories">
        <?php $categoryNames = [1 => 'Massage', 2 => 'Fitness']; ?>
        <?php while($row = $res->fetch_assoc()): ?>
          <?php $displayName = $categoryNames[$row['id']] ?? $row['name']; ?>
          <a class="category-card" href="category.php?id=<?= $row['id'] ?>">
            <img src="images/category_<?= $row['id'] ?>.jpg" alt="<?= htmlspecialchars($displayName) ?>">
            <div class="category-content">
              <h3><?= htmlspecialchars($displayName) ?></h3>
              <p>Experience the ultimate in luxury and wellness</p>
            </div>
          </a>
        <?php endwhile; ?>
      </div>
    </div>
  </section>

  <script>
    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    });

    // Navbar background change on scroll
    window.addEventListener('scroll', () => {
      const navbar = document.querySelector('.navbar');
      if (window.scrollY > 100) {
        navbar.style.background = 'rgba(15, 76, 58, 0.98)';
      } else {
        navbar.style.background = 'rgba(15, 76, 58, 0.95)';
      }
    });
  </script>
</body>
</html>
<?php include "footer.php"; ?>
