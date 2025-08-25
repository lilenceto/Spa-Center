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
.price-hero {
    background: linear-gradient(135deg, rgba(15, 76, 58, 0.9) 0%, rgba(26, 95, 74, 0.9) 100%);
    padding: 4rem 2rem;
    text-align: center;
    margin-bottom: 3rem;
    border-radius: 20px;
    position: relative;
    overflow: hidden;
}

.price-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="hero-pattern" x="0" y="0" width="50" height="50" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="%23d4af37" opacity="0.1"/><path d="M10 20 Q25 10 40 20" stroke="%23d4af37" stroke-width="0.3" fill="none" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23hero-pattern)"/></svg>');
    background-size: 50px 50px;
    opacity: 0.3;
    z-index: 1;
}

.hero-content {
    position: relative;
    z-index: 2;
}

.hero-title {
    font-family: 'Playfair Display', serif;
    font-size: 3rem;
    font-weight: 600;
    color: #d4af37;
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.hero-subtitle {
    font-size: 1.2rem;
    color: #f8f9fa;
    opacity: 0.9;
}

.price-list-container {
    max-width: 1200px;
    margin: 0 auto;
}

.price-category {
    margin-bottom: 4rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 20px;
    padding: 2rem;
    border: 1px solid rgba(212, 175, 55, 0.2);
    backdrop-filter: blur(10px);
}

.category-header {
    text-align: center;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid rgba(212, 175, 55, 0.3);
}

.category-header h2 {
    font-family: 'Playfair Display', serif;
    font-size: 2.5rem;
    color: #d4af37;
    margin-bottom: 0.5rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.category-description {
    color: #f8f9fa;
    font-size: 1.1rem;
    opacity: 0.8;
}

.price-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
}

.service-card {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    padding: 1.5rem;
    border: 1px solid rgba(212, 175, 55, 0.2);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
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
    transition: transform 0.3s ease;
}

.service-card:hover::before {
    transform: scaleX(1);
}

.service-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.2);
    border-color: #d4af37;
}

.service-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(212, 175, 55, 0.2);
}

.service-name {
    font-size: 1.3rem;
    color: #f8f9fa;
    font-weight: 600;
    margin: 0;
    flex: 1;
    margin-right: 1rem;
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
    margin-bottom: 1.5rem;
}

.service-description {
    color: #f8f9fa;
    opacity: 0.9;
    line-height: 1.6;
    margin-bottom: 1rem;
    font-size: 0.95rem;
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
}

.book-now-btn {
    display: inline-block;
    padding: 0.8rem 2rem;
    background: linear-gradient(135deg, #d4af37, #b8941f);
    color: #0f4c3a;
    text-decoration: none;
    border-radius: 25px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
}

.book-now-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
}

.login-to-book-btn {
    display: inline-block;
    padding: 0.8rem 2rem;
    background: rgba(255, 255, 255, 0.1);
    color: #f8f9fa;
    text-decoration: none;
    border-radius: 25px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    border: 1px solid rgba(212, 175, 55, 0.3);
}

.login-to-book-btn:hover {
    background: rgba(212, 175, 55, 0.2);
    border-color: #d4af37;
    transform: translateY(-2px);
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
</style>

<?php include "footer.php"; ?>
