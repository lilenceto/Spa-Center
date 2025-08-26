<?php
session_start();
require_once "db.php";
include "header.php";

// Get all services with categories for price list
$servicesQuery = "
    SELECT s.id, s.name, s.description, s.price, s.duration, c.name AS category
    FROM services s
    JOIN service_categories c ON c.id = s.category_id
    ORDER BY c.name, s.name
";
$servicesResult = $mysqli->query($servicesQuery);

$services = [];
while ($row = $servicesResult->fetch_assoc()) {
    $services[$row['category']][] = $row;
}
?>

<!-- Main Content -->
<div class="page-container">
    <div class="price-hero">
        <div class="floating-prices-element"></div>
        <div class="floating-prices-element"></div>
        <div class="floating-prices-element"></div>
        
        <div class="hero-content">
            <h1 class="hero-title">
                <i class="fas fa-list-alt"></i>
                Complete Price List
            </h1>
            <p class="hero-subtitle">Transparent pricing for all our premium wellness services</p>
        </div>
    </div>

    <div class="price-list-container">
        <?php foreach ($services as $category => $items): ?>
            <div class="price-category">
                <div class="category-header">
                    <h2><i class="fas fa-star"></i> <?= htmlspecialchars($category) ?></h2>
                    <p class="category-description">Premium <?= strtolower(htmlspecialchars($category)) ?> services for your wellness journey</p>
                </div>
                
                <div class="price-grid">
                    <?php foreach ($items as $srv): ?>
                        <div class="service-card">
                            <div class="service-image">
                                <?php 
                                    $categoryId = array_search($category, array_keys($services)) + 1;
                                    $imagePath = "images/category_{$categoryId}.jpg";
                                ?>
                                <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($srv['name']) ?>" class="service-img">
                                <div class="service-overlay">
                                    <div class="service-icon">
                                        <i class="fas fa-spa"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="service-header">
                                <h3 class="service-name"><?= htmlspecialchars($srv['name']) ?></h3>
                                <div class="service-price">
                                    <span class="price-amount">€<?= number_format($srv['price'], 2) ?></span>
                                    <span class="price-period">per session</span>
                                </div>
                            </div>
                            
                            <div class="service-details">
                                <p class="service-description"><?= htmlspecialchars($srv['description']) ?></p>
                                <div class="service-meta">
                                    <span class="duration">
                                        <i class="fas fa-clock"></i>
                                        <?= (int)$srv['duration'] ?> min
                                    </span>
                                    <span class="price-per-minute">
                                        €<?= number_format($srv['price'] / $srv['duration'], 2) ?>/min
                                    </span>
                                </div>
                            </div>
                            
                            <div class="service-action">
                                <?php if (!empty($_SESSION['user_id'])): ?>
                                    <a href="add_reservation.php?service_id=<?= $srv['id'] ?>" class="book-now-btn">
                                        <i class="fas fa-calendar-plus"></i> Book Now
                                    </a>
                                <?php else: ?>
                                    <a href="login.php?next=add_reservation.php?service_id=<?= $srv['id'] ?>" class="login-to-book-btn">
                                        <i class="fas fa-sign-in-alt"></i> Login to Book
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
/* Price List Specific Styles */

/* Smooth scrolling for better experience */
html {
    scroll-behavior: smooth;
}

/* Prices Background for the entire page */
body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
        url('images/prices.jpg'),
        linear-gradient(135deg, rgba(15, 76, 58, 0.85) 0%, rgba(26, 95, 74, 0.8) 50%, rgba(45, 122, 95, 0.75) 100%);
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
    z-index: -1;
    filter: blur(2px) brightness(0.7) saturate(1.2);
    transform: scale(1.1);
}

/* Additional prices overlay for depth */
body::after {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 20% 80%, rgba(212, 175, 55, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(45, 122, 95, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 40% 40%, rgba(26, 95, 74, 0.1) 0%, transparent 50%);
    z-index: -1;
    pointer-events: none;
}

.price-hero {
    background: rgba(15, 76, 58, 0.9);
    padding: 4rem 2rem;
    text-align: center;
    margin-bottom: 3rem;
    border-radius: 20px;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(15px);
    border: 1px solid rgba(212, 175, 55, 0.3);
}

.price-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at center, rgba(212, 175, 55, 0.15) 0%, transparent 60%),
        radial-gradient(circle at 30% 70%, rgba(45, 122, 95, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 70% 30%, rgba(26, 95, 74, 0.1) 0%, transparent 50%);
    z-index: 1;
}

/* Floating prices elements */
.price-hero::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="prices-pattern" x="0" y="0" width="200" height="200" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="%23d4af37" opacity="0.2"/><circle cx="175" cy="75" r="1.5" fill="%23ffffff" opacity="0.15"/><circle cx="75" cy="175" r="0.8" fill="%23d4af37" opacity="0.18"/><path d="M10 50 Q30 30 50 50 Q70 70 90 50" stroke="%23d4af37" stroke-width="0.3" fill="none" opacity="0.15"/><path d="M150 20 Q170 40 150 60" stroke="%23ffffff" stroke-width="0.2" fill="none" opacity="0.12"/></pattern></defs><rect width="100" height="100" fill="url(%23prices-pattern)"/></svg>');
    background-size: 200px 200px;
    opacity: 0.6;
    z-index: 1;
    animation: pricesFloat 20s ease-in-out infinite;
}

@keyframes pricesFloat {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-10px) rotate(1deg); }
}

.hero-content {
    position: relative;
    z-index: 3;
    background: rgba(15, 76, 58, 0.1);
    padding: 3rem;
    border-radius: 20px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(212, 175, 55, 0.2);
    box-shadow: 
        0 20px 40px rgba(0, 0, 0, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.hero-title {
    font-family: 'Playfair Display', serif;
    font-size: 3.5rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 1.5rem;
    text-shadow: 
        3px 3px 6px rgba(0,0,0,0.7),
        0 0 20px rgba(212, 175, 55, 0.3),
        0 0 40px rgba(212, 175, 55, 0.1);
    position: relative;
}

.hero-title::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 120px;
    height: 3px;
    background: linear-gradient(90deg, transparent, #d4af37, transparent);
    border-radius: 2px;
}

.hero-subtitle {
    font-size: 1.4rem;
    color: #ffffff;
    font-weight: 400;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.6);
    max-width: 700px;
    margin: 0 auto;
    line-height: 1.6;
}

.price-list-container {
    max-width: 1200px;
    margin: 0 auto;
    position: relative;
    z-index: 2;
}

.price-category {
    margin-bottom: 4rem;
    background: rgba(15, 76, 58, 0.9);
    border-radius: 20px;
    padding: 2.5rem;
    border: 1px solid rgba(212, 175, 55, 0.3);
    backdrop-filter: blur(15px);
    box-shadow: 
        0 20px 40px rgba(0, 0, 0, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
    position: relative;
}

.price-category::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 20% 50%, rgba(212, 175, 55, 0.05) 0%, transparent 50%),
        radial-gradient(circle at 80% 50%, rgba(45, 122, 95, 0.05) 0%, transparent 50%);
    border-radius: 20px;
    pointer-events: none;
}

.category-header {
    text-align: center;
    margin-bottom: 3rem;
    padding-bottom: 2rem;
    border-bottom: 2px solid rgba(212, 175, 55, 0.3);
    position: relative;
    z-index: 2;
}

.category-header h2 {
    font-family: 'Playfair Display', serif;
    font-size: 2.8rem;
    color: #d4af37;
    margin-bottom: 1rem;
    text-shadow: 
        3px 3px 6px rgba(0,0,0,0.5),
        0 0 20px rgba(212, 175, 55, 0.2);
    position: relative;
}

.category-header h2::after {
    content: '';
    position: absolute;
    bottom: -15px;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 2px;
    background: linear-gradient(90deg, transparent, #d4af37, transparent);
    border-radius: 1px;
}

.category-description {
    color: #ffffff;
    font-size: 1.2rem;
    opacity: 0.9;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    font-weight: 400;
}

.price-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
}

.service-card {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 0;
    border: 1px solid rgba(212, 175, 55, 0.2);
    transition: all 0.4s ease;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(10px);
}

.service-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, #d4af37, #b8941f);
    transform: scaleX(0);
    transition: transform 0.4s ease;
    z-index: 2;
}

.service-card:hover::before {
    transform: scaleX(1);
}

.service-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 
        0 25px 50px rgba(0,0,0,0.3),
        0 0 0 1px rgba(212, 175, 55, 0.4);
    border-color: #d4af37;
}

/* Service Image Styling */
.service-image {
    position: relative;
    width: 100%;
    height: 200px;
    overflow: hidden;
    border-radius: 20px 20px 0 0;
}

.service-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.service-card:hover .service-img {
    transform: scale(1.1);
}

.service-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        135deg,
        rgba(15, 76, 58, 0.3) 0%,
        rgba(26, 95, 74, 0.2) 50%,
        rgba(212, 175, 55, 0.1) 100%
    );
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.4s ease;
}

.service-card:hover .service-overlay {
    opacity: 1;
}

.service-icon {
    width: 60px;
    height: 60px;
    background: rgba(212, 175, 55, 0.9);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: #0f4c3a;
    transform: scale(0);
    transition: transform 0.4s ease;
}

.service-card:hover .service-icon {
    transform: scale(1);
}

.service-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    padding: 2rem 2rem 1.5rem;
    border-bottom: 1px solid rgba(212, 175, 55, 0.2);
    position: relative;
    z-index: 2;
}

.service-name {
    font-family: 'Playfair Display', serif;
    font-size: 1.6rem;
    color: #d4af37;
    font-weight: 600;
    margin: 0;
    flex: 1;
    margin-right: 1rem;
    text-shadow: 
        2px 2px 4px rgba(0,0,0,0.5),
        0 0 15px rgba(212, 175, 55, 0.2);
    line-height: 1.3;
}

.service-price {
    text-align: right;
    min-width: 100px;
}

.price-amount {
    display: block;
    font-size: 1.8rem;
    font-weight: 700;
    color: #d4af37;
    line-height: 1;
}

.price-period {
    display: block;
    font-size: 0.8rem;
    color: #f8f9fa;
    opacity: 0.7;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.service-details {
    margin-bottom: 2rem;
    padding: 0 2rem;
}

.service-description {
    color: #ffffff;
    opacity: 0.95;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    font-size: 1rem;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    font-weight: 400;
}

.service-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.9rem;
}

.duration {
    color: #d4af37;
    font-weight: 600;
}

.duration i {
    margin-right: 0.5rem;
}

.price-per-minute {
    color: #f8f9fa;
    opacity: 0.7;
    font-style: italic;
}

.service-action {
    text-align: center;
    padding: 0 2rem 2rem;
}

.book-now-btn {
    display: inline-block;
    padding: 1rem 2.5rem;
    background: linear-gradient(135deg, #d4af37, #b8941f);
    color: #0f4c3a;
    text-decoration: none;
    border-radius: 30px;
    font-weight: 700;
    font-size: 1.1rem;
    transition: all 0.4s ease;
    border: 2px solid rgba(255, 255, 255, 0.2);
    cursor: pointer;
    box-shadow: 
        0 8px 25px rgba(212, 175, 55, 0.4),
        0 0 0 0 rgba(212, 175, 55, 0.7);
    position: relative;
    overflow: hidden;
}

.book-now-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #ffffff, rgba(255, 255, 255, 0.8));
    border-radius: 30px;
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: -1;
}

.book-now-btn:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 
        0 15px 35px rgba(212, 175, 55, 0.5),
        0 0 0 8px rgba(212, 175, 55, 0.3);
    border-color: rgba(255, 255, 255, 0.4);
}

.book-now-btn:hover::before {
    opacity: 0.1;
}

.login-to-book-btn {
    display: inline-block;
    padding: 1rem 2.5rem;
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
    text-decoration: none;
    border-radius: 30px;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.4s ease;
    border: 2px solid rgba(212, 175, 55, 0.3);
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
}

.login-to-book-btn:hover {
    background: rgba(212, 175, 55, 0.2);
    border-color: #d4af37;
    transform: translateY(-3px) scale(1.05);
    box-shadow: 
        0 15px 35px rgba(212, 175, 55, 0.3),
        0 0 0 4px rgba(212, 175, 55, 0.2);
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .price-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .service-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .service-price {
        text-align: left;
        min-width: auto;
    }
    
    .service-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}

/* Floating prices elements */
.floating-prices-element {
    position: absolute;
    width: 80px;
    height: 80px;
    background: radial-gradient(circle, rgba(212, 175, 55, 0.1), transparent);
    border-radius: 50%;
    animation: pricesElementFloat 8s ease-in-out infinite;
    pointer-events: none;
    z-index: 1;
}

.floating-prices-element:nth-child(1) {
    top: 15%;
    left: 8%;
    animation-delay: 0s;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.08), transparent);
}

.floating-prices-element:nth-child(2) {
    top: 65%;
    right: 12%;
    animation-delay: 3s;
    background: radial-gradient(circle, rgba(212, 175, 55, 0.06), transparent);
}

.floating-prices-element:nth-child(3) {
    bottom: 25%;
    left: 15%;
    animation-delay: 6s;
    background: radial-gradient(circle, rgba(45, 122, 95, 0.08), transparent);
}

@keyframes pricesElementFloat {
    0%, 100% { 
        transform: translateY(0px) rotate(0deg) scale(1); 
        opacity: 0.6;
    }
    25% { 
        transform: translateY(-15px) rotate(2deg) scale(1.1); 
        opacity: 0.8;
    }
    50% { 
        transform: translateY(-8px) rotate(-1deg) scale(0.9); 
        opacity: 0.7;
    }
    75% { 
        transform: translateY(-12px) rotate(1deg) scale(1.05); 
        opacity: 0.9;
    }
}

/* Animation for service cards */
.service-card {
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Stagger animation for multiple cards */
.service-card:nth-child(1) { animation-delay: 0.1s; }
.service-card:nth-child(2) { animation-delay: 0.2s; }
.service-card:nth-child(3) { animation-delay: 0.3s; }
.service-card:nth-child(4) { animation-delay: 0.4s; }
.service-card:nth-child(5) { animation-delay: 0.5s; }
.service-card:nth-child(6) { animation-delay: 0.6s; }

/* Enhanced hover effects for service cards */
.service-card:hover .service-meta {
    transform: translateY(-2px);
}

.service-meta {
    transition: transform 0.3s ease;
}

/* Smooth transitions for all elements */
* {
    transition: all 0.3s ease;
}

/* Enhanced price display */
.price-amount {
    text-shadow: 0 0 10px rgba(212, 175, 55, 0.3);
}

/* Enhanced category headers */
.category-header:hover h2 {
    transform: scale(1.02);
}

.category-header h2 {
    transition: transform 0.3s ease;
}
</style>

<?php include "footer.php"; ?>
