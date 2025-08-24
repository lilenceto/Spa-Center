<?php
session_start();
require_once "db.php"; 

$cat_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// вземаме името на категорията
$stmt = $mysqli->prepare("SELECT name FROM service_categories WHERE id=?");
$stmt->bind_param("i", $cat_id);
$stmt->execute();
$stmt->bind_result($cat_name);
if (!$stmt->fetch()) {
    die("Категорията не е намерена.");
}
$stmt->close();

// вземаме услугите в категорията
$stmt = $mysqli->prepare("SELECT id, name, description, duration, price FROM services WHERE category_id=? ORDER BY name");
$stmt->bind_param("i", $cat_id);
$stmt->execute();
$result = $stmt->get_result();

include "header.php";

// Define category-specific styling and content
$categoryConfig = [
    1 => [
        'name' => 'Масаж & Тяло',
        'icon' => 'fas fa-hands',
        'color' => '#d4af37',
        'gradient' => 'linear-gradient(135deg, #d4af37, #b8941f)',
        'description' => 'Experience ultimate relaxation with our premium massage and body treatment services',
        'bgPattern' => 'url("data:image/svg+xml,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\'><defs><pattern id=\'massage\' x=\'0\' y=\'0\' width=\'50\' height=\'50\' patternUnits=\'userSpaceOnUse\'><circle cx=\'25\' cy=\'25\' r=\'2\' fill=\'%23d4af37\' opacity=\'0.3\'/><path d=\'M10 20 Q25 10 40 20\' stroke=\'%23d4af37\' stroke-width=\'1\' fill=\'none\' opacity=\'0.2\'/></pattern></defs><rect width=\'100\' height=\'100\' fill=\'url(%23massage)\'/></svg>")'
    ],
    2 => [
        'name' => 'Фитнес',
        'icon' => 'fas fa-dumbbell',
        'color' => '#4caf50',
        'gradient' => 'linear-gradient(135deg, #4caf50, #2e7d32)',
        'description' => 'Energize your body and mind with our fitness and wellness programs',
        'bgPattern' => 'url("data:image/svg+xml,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\'><defs><pattern id=\'fitness\' x=\'0\' y=\'0\' width=\'30\' height=\'30\' patternUnits=\'userSpaceOnUse\'><rect x=\'10\' y=\'10\' width=\'10\' height=\'10\' fill=\'%234caf50\' opacity=\'0.3\'/><circle cx=\'25\' cy=\'25\' r=\'2\' fill=\'%234caf50\' opacity=\'0.2\'/></pattern></defs><rect width=\'100\' height=\'100\' fill=\'url(%23fitness)\'/></svg>")'
    ],
    3 => [
        'name' => 'Лице & Красота',
        'icon' => 'fas fa-spa',
        'color' => '#e91e63',
        'gradient' => 'linear-gradient(135deg, #e91e63, #c2185b)',
        'description' => 'Reveal your natural beauty with our advanced facial and beauty treatments',
        'bgPattern' => 'url("data:image/svg+xml,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\'><defs><pattern id=\'beauty\' x=\'0\' y=\'0\' width=\'40\' height=\'40\' patternUnits=\'userSpaceOnUse\'><circle cx=\'20\' cy=\'20\' r=\'1.5\' fill=\'%23e91e63\' opacity=\'0.3\'/><path d=\'M15 15 Q20 10 25 15\' stroke=\'%23e91e63\' stroke-width=\'0.8\' fill=\'none\' opacity=\'0.2\'/></pattern></defs><rect width=\'100\' height=\'100\' fill=\'url(%23beauty)\'/></svg>")'
    ],
    4 => [
        'name' => 'Аква & Басейн',
        'icon' => 'fas fa-swimming-pool',
        'color' => '#2196f3',
        'gradient' => 'linear-gradient(135deg, #2196f3, #1976d2)',
        'description' => 'Dive into relaxation with our aqua therapy and pool services',
        'bgPattern' => 'url("data:image/svg+xml,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\'><defs><pattern id=\'aqua\' x=\'0\' y=\'0\' width=\'60\' height=\'60\' patternUnits=\'userSpaceOnUse\'><path d=\'M10 30 Q30 20 50 30 Q70 40 90 30\' stroke=\'%232196f3\' stroke-width=\'1\' fill=\none\' opacity=\'0.3\'/><circle cx=\'30\' cy=\'50\' r=\'1.5\' fill=\'%232196f3\' opacity=\'0.2\'/></pattern></defs><rect width=\'100\' height=\'100\' fill=\'url(%23aqua)\'/></svg>")'
    ]
];

$config = $categoryConfig[$cat_id] ?? $categoryConfig[1];
?>
<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($cat_name) ?> - Lotus Temple</title>
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

    /* Category-Specific Background */
    .category-hero::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: <?= $config['bgPattern'] ?>;
      background-size: 100px 100px;
      opacity: 0.1;
      z-index: -1;
    }

    /* Hero Section */
    .category-hero {
      height: 70vh;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      position: relative;
      margin-top: 80px;
      background: rgba(15, 76, 58, 0.3);
      backdrop-filter: blur(10px);
      overflow: hidden;
    }

    .category-hero::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle at center, <?= str_replace(['linear-gradient(135deg, ', ', #'], ['rgba(', ', 0.1'], $config['gradient']) ?>, transparent 70%);
      z-index: -1;
    }

    .hero-content h1 {
      font-family: 'Playfair Display', serif;
      font-size: 4.5rem;
      font-weight: 700;
      margin-bottom: 1rem;
      color: <?= $config['color'] ?>;
      text-shadow: 3px 3px 6px rgba(0,0,0,0.5);
      animation: fadeInUp 1s ease-out;
    }

    .hero-content p {
      font-size: 1.4rem;
      margin-bottom: 2rem;
      color: #f8f9fa;
      font-weight: 300;
      animation: fadeInUp 1s ease-out 0.3s both;
      max-width: 700px;
      margin-left: auto;
      margin-right: auto;
      line-height: 1.6;
    }

    .category-icon {
      font-size: 5rem;
      color: <?= $config['color'] ?>;
      margin-bottom: 1rem;
      animation: fadeInUp 1s ease-out 0.1s both;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }

    .back-button {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 1.2rem 2.5rem;
      background: <?= $config['gradient'] ?>;
      color: #0f4c3a;
      text-decoration: none;
      border-radius: 50px;
      font-weight: 600;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      box-shadow: 0 8px 25px rgba(0,0,0,0.3);
      animation: fadeInUp 1s ease-out 0.6s both;
    }

    .back-button:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 35px rgba(0,0,0,0.4);
      filter: brightness(1.1);
    }

    /* Services Section */
    .services-section {
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
      color: <?= $config['color'] ?>;
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

    .services-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
      gap: 2.5rem;
    }

    .service-card {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 25px;
      overflow: hidden;
      transition: all 0.4s ease;
      position: relative;
      box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    }

    .service-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: <?= str_replace(['linear-gradient(135deg, ', ', #'], ['linear-gradient(135deg, ', ', 0.1'], $config['gradient']) ?>, rgba(15, 76, 58, 0.1);
      opacity: 0;
      transition: opacity 0.4s ease;
      z-index: 1;
    }

    .service-card:hover::before {
      opacity: 1;
    }

    .service-card:hover {
      transform: translateY(-15px) scale(1.02);
      border-color: <?= $config['color'] ?>;
      box-shadow: 0 25px 50px rgba(0,0,0,0.3);
    }

    .card-header {
      height: 200px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: <?= $config['gradient'] ?>;
      position: relative;
      overflow: hidden;
    }

    .card-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="pattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="%23ffffff" opacity="0.3"/></pattern></defs><rect width="100" height="100" fill="url(%23pattern)"/></svg>');
      opacity: 0.3;
    }

    .card-header .icon {
      background: rgba(255,255,255,0.2);
      padding: 35px;
      border-radius: 50%;
      font-size: 3.5rem;
      color: #0f4c3a;
      font-weight: bold;
      backdrop-filter: blur(10px);
      border: 2px solid rgba(255,255,255,0.3);
      z-index: 2;
      position: relative;
    }

    .card-body {
      padding: 2.5rem;
      position: relative;
      z-index: 2;
    }

    .card-body h3 {
      font-family: 'Playfair Display', serif;
      font-size: 1.6rem;
      font-weight: 600;
      color: <?= $config['color'] ?>;
      margin-bottom: 1rem;
      text-align: center;
    }

    .card-body p {
      color: #f8f9fa;
      font-size: 1rem;
      margin-bottom: 1.5rem;
      line-height: 1.6;
      text-align: center;
      opacity: 0.9;
    }

    .service-info {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 15px;
      padding: 1.2rem;
      margin-bottom: 1.5rem;
      text-align: center;
      backdrop-filter: blur(10px);
    }

    .service-info .duration,
    .service-info .price {
      display: inline-block;
      margin: 0 1.5rem;
      color: <?= $config['color'] ?>;
      font-weight: 600;
      font-size: 1.1rem;
    }

    .service-info i {
      margin-right: 0.5rem;
      color: <?= $config['color'] ?>;
    }

    .book-button {
      width: 100%;
      padding: 1.2rem;
      background: <?= $config['gradient'] ?>;
      color: #0f4c3a;
      border: none;
      border-radius: 15px;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }

    .book-button:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.4);
      filter: brightness(1.1);
    }

    /* Floating Elements */
    .floating-element {
      position: absolute;
      width: 100px;
      height: 100px;
      background: radial-gradient(circle, <?= str_replace(['linear-gradient(135deg, ', ', #'], ['rgba(', ', 0.1'], $config['gradient']) ?>, transparent);
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

    /* Category Stats */
    .category-stats {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 2rem;
      margin: 3rem 0;
      text-align: center;
      backdrop-filter: blur(10px);
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 2rem;
      margin-top: 1.5rem;
    }

    .stat-item {
      text-align: center;
    }

    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      color: <?= $config['color'] ?>;
      margin-bottom: 0.5rem;
    }

    .stat-label {
      color: #f8f9fa;
      font-size: 1rem;
      opacity: 0.8;
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
      .category-hero {
        height: 60vh;
        margin-top: 60px;
      }
      
      .hero-content h1 {
        font-size: 3rem;
      }
      
      .services-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
      }
      
      .card-header {
        height: 180px;
      }
      
      .card-header .icon {
        padding: 30px;
        font-size: 3rem;
      }
      
      .stats-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
      }
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      background: rgba(255, 255, 255, 0.05);
      border-radius: 25px;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .empty-state i {
      font-size: 4rem;
      color: <?= $config['color'] ?>;
      margin-bottom: 1rem;
      opacity: 0.7;
    }

    .empty-state h3 {
      font-family: 'Playfair Display', serif;
      font-size: 2rem;
      color: <?= $config['color'] ?>;
      margin-bottom: 1rem;
    }

    .empty-state p {
      color: #f8f9fa;
      font-size: 1.1rem;
      opacity: 0.8;
      margin-bottom: 2rem;
    }
  </style>
</head>
<body>
  <!-- Hero Section -->
  <section class="category-hero">
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    
    <div class="hero-content">
      <div class="category-icon">
        <i class="<?= $config['icon'] ?>"></i>
      </div>
      <h1><?= htmlspecialchars($cat_name) ?></h1>
      <p><?= $config['description'] ?></p>
      <a href="index.php" class="back-button">
        <i class="fas fa-arrow-left"></i> Back to Categories
      </a>
    </div>
  </section>

  <!-- Services Section -->
  <section class="services-section">
    <div class="container">
      <div class="section-title">
        <h2>Our <?= $config['name'] ?> Services</h2>
        <p>Experience the ultimate in luxury and wellness with our carefully curated selection</p>
      </div>
      
      <!-- Category Stats -->
      <div class="category-stats">
        <h3>Category Overview</h3>
        <div class="stats-grid">
          <div class="stat-item">
            <div class="stat-number"><?= $result->num_rows ?></div>
            <div class="stat-label">Available Services</div>
          </div>
          <div class="stat-item">
            <div class="stat-number">4.9</div>
            <div class="stat-label">Customer Rating</div>
          </div>
          <div class="stat-item">
            <div class="stat-number">15+</div>
            <div class="stat-label">Years Experience</div>
          </div>
        </div>
      </div>
      
      <?php if ($result->num_rows > 0): ?>
        <div class="services-grid">
          <?php while ($srv = $result->fetch_assoc()): ?>
            <div class="service-card">
              <div class="card-header">
                <span class="icon"><?= mb_substr($srv['name'], 0, 1) ?></span>
              </div>
              <div class="card-body">
                <h3><?= htmlspecialchars($srv['name']) ?></h3>
                <p><?= htmlspecialchars($srv['description']) ?></p>
                <div class="service-info">
                  <span class="duration">
                    <i class="fas fa-clock"></i> <?= (int)$srv['duration'] ?> min
                  </span>
                  <span class="price">
                    <i class="fas fa-coins"></i> <?= number_format($srv['price'], 2) ?> лв.
                  </span>
                </div>
                <a href="add_reservation.php?service_id=<?= $srv['id'] ?>" class="book-button">
                  <i class="fas fa-calendar-plus"></i> Book Now
                </a>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i class="<?= $config['icon'] ?>"></i>
          <h3>No Services Available</h3>
          <p>We're currently updating our services in this category. Please check back soon!</p>
          <a href="index.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Categories
          </a>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <script>
    // Add smooth scroll behavior
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

    // Add card entrance animations
    const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }
      });
    }, observerOptions);

    // Observe all service cards
    document.querySelectorAll('.service-card').forEach(card => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(30px)';
      card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
      observer.observe(card);
    });

    // Add floating elements animation
    const floatingElements = document.querySelectorAll('.floating-element');
    floatingElements.forEach((element, index) => {
      element.style.animationDelay = `${index * 2}s`;
    });
  </script>
</body>
</html>
<?php include "footer.php"; ?>
