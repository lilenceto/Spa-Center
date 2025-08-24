<?php
// footer.php
?>

<!-- Footer -->
<footer class="site-footer">
  <!-- Tropical Background Pattern -->
  <div class="footer-bg-pattern"></div>

  <div class="footer-container">
    
    <!-- Footer Content -->
    <div class="footer-content">
      
      <!-- Company Info -->
      <div class="footer-section">
        <h3 class="footer-logo">
          <i class="fas fa-spa"></i> Lotus Temple
        </h3>
        <p class="footer-description">
          Experience ultimate luxury and wellness in our serene sanctuary. 
          Your journey to relaxation begins here.
        </p>
        <div class="social-links">
          <a href="#" class="social-link">
            <i class="fab fa-facebook"></i>
          </a>
          <a href="#" class="social-link">
            <i class="fab fa-instagram"></i>
          </a>
          <a href="#" class="social-link">
            <i class="fab fa-twitter"></i>
          </a>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="footer-section">
        <h4 class="footer-title">Quick Links</h4>
        <ul class="footer-links">
          <li><a href="index.php" class="footer-link">
            <i class="fas fa-chevron-right"></i> Home
          </a></li>
          <li><a href="services.php" class="footer-link">
            <i class="fas fa-chevron-right"></i> Services
          </a></li>
          <li><a href="reservations.php" class="footer-link">
            <i class="fas fa-chevron-right"></i> Reservations
          </a></li>
          <li><a href="about.php" class="footer-link">
            <i class="fas fa-chevron-right"></i> About Us
          </a></li>
        </ul>
      </div>

      <!-- Contact Info -->
      <div class="footer-section">
        <h4 class="footer-title">Contact Info</h4>
        <div class="contact-info">
          <p><i class="fas fa-map-marker-alt"></i> 123 Wellness Street, Spa City</p>
          <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
          <p><i class="fas fa-envelope"></i> info@spacenter.com</p>
          <p><i class="fas fa-clock"></i> Mon-Sun: 9:00 AM - 9:00 PM</p>
        </div>
      </div>

      <!-- Newsletter -->
      <div class="footer-section">
        <h4 class="footer-title">Newsletter</h4>
        <p class="newsletter-desc">
          Subscribe to receive special offers and wellness tips.
        </p>
        <form class="newsletter-form">
          <input type="email" placeholder="Your email" class="newsletter-input">
          <button type="submit" class="newsletter-button">
            <i class="fas fa-paper-plane"></i>
          </button>
        </form>
      </div>
    </div>

    <!-- Bottom Bar -->
    <div class="footer-bottom">
      <p>&copy; 2024 Lotus Temple. All rights reserved. | 
        <a href="#" class="footer-bottom-link">Privacy Policy</a> | 
        <a href="#" class="footer-bottom-link">Terms of Service</a>
      </p>
    </div>
  </div>
</footer>

<style>
  .site-footer {
    background: linear-gradient(135deg, #0a3d2e 0%, #0f4c3a 100%);
    color: #f8f9fa;
    padding: 3rem 0 1rem;
    margin-top: 4rem;
    border-top: 2px solid #d4af37;
    position: relative;
    overflow: hidden;
  }

  .footer-bg-pattern {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
      url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="footer-pattern" x="0" y="0" width="50" height="50" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="%23d4af37" opacity="0.1"/><path d="M10 20 Q25 10 40 20" stroke="%23d4af37" stroke-width="0.3" fill="none" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23footer-pattern)"/></svg>');
    background-size: 50px 50px;
    opacity: 0.3;
    z-index: 1;
  }

  .footer-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
    position: relative;
    z-index: 2;
  }

  .footer-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
  }

  .footer-section {
    padding: 0 1rem;
  }

  .footer-logo {
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem;
    color: #d4af37;
    margin-bottom: 1rem;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
  }

  .footer-description {
    line-height: 1.6;
    margin-bottom: 1rem;
    opacity: 0.9;
  }

  .social-links {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
  }

  .social-link {
    color: #d4af37;
    font-size: 1.2rem;
    transition: all 0.3s ease;
    text-decoration: none;
  }

  .social-link:hover {
    transform: translateY(-3px);
    color: #f8f9fa;
  }

  .footer-title {
    color: #d4af37;
    margin-bottom: 1rem;
    font-size: 1.2rem;
  }

  .footer-links {
    list-style: none;
    line-height: 2;
  }

  .footer-link {
    color: #f8f9fa;
    text-decoration: none;
    transition: color 0.3s ease;
    opacity: 0.9;
  }

  .footer-link:hover {
    color: #d4af37;
    opacity: 1;
  }

  .contact-info {
    line-height: 2;
    opacity: 0.9;
  }

  .contact-info i {
    color: #d4af37;
    margin-right: 0.5rem;
    width: 16px;
  }

  .newsletter-desc {
    margin-bottom: 1rem;
    opacity: 0.9;
  }

  .newsletter-form {
    display: flex;
    gap: 0.5rem;
  }

  .newsletter-input {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 25px;
    background: rgba(255, 255, 255, 0.1);
    color: #f8f9fa;
    font-size: 0.9rem;
  }

  .newsletter-input::placeholder {
    color: rgba(248, 249, 250, 0.6);
  }

  .newsletter-button {
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #d4af37, #b8941f);
    color: #0f4c3a;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
  }

  .newsletter-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
  }

  .footer-bottom {
    border-top: 1px solid rgba(212, 175, 55, 0.3);
    padding-top: 1.5rem;
    text-align: center;
    opacity: 0.8;
  }

  .footer-bottom-link {
    color: #d4af37;
    text-decoration: none;
  }

  .footer-bottom-link:hover {
    text-decoration: underline;
  }

  /* Responsive Design */
  @media (max-width: 768px) {
    .footer-content {
      grid-template-columns: 1fr;
      gap: 1.5rem;
    }
    
    .footer-section {
      text-align: center;
      padding: 0;
    }
    
    .social-links {
      justify-content: center;
    }
  }
</style>

</body>
</html>
