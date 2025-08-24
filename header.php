<?php
$isLogged = !empty($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';
?>

<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lotus Temple - Luxury Wellness</title>
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
      margin: 0;
      background: linear-gradient(135deg, #0f4c3a 0%, #1a5f4a 50%, #2d7a5f 100%);
      color: #f8f9fa;
      min-height: 100vh;
    }

    /* Navigation */
    nav { 
      background: rgba(15, 76, 58, 0.95);
      backdrop-filter: blur(20px);
      padding: 1rem 2rem;
      margin-bottom: 2rem;
      border-radius: 0 0 15px 15px;
      border-bottom: 2px solid #d4af37;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    nav a { 
      margin-right: 1.5rem;
      text-decoration: none; 
      color: #f8f9fa;
      font-weight: 500;
      padding: 0.5rem 1rem;
      border-radius: 25px;
      transition: all 0.3s ease;
      position: relative;
      display: inline-block;
    }

    nav a:hover { 
      background: rgba(212, 175, 55, 0.2);
      color: #d4af37;
      transform: translateY(-2px);
    }

    nav a::before {
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

    nav a:hover::before {
      width: 80%;
    }

    /* Welcome Message */
    .welcome-message {
      background: rgba(212, 175, 55, 0.1);
      border: 1px solid rgba(212, 175, 55, 0.3);
      border-radius: 15px;
      padding: 1rem 1.5rem;
      margin-right: 1.5rem;
      font-weight: 600;
      color: #d4af37;
      backdrop-filter: blur(10px);
    }

    /* Tables */
    table { 
      border-collapse: collapse; 
      width: 100%; 
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(20px);
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }

    th, td { 
      border: 1px solid rgba(212, 175, 55, 0.2); 
      padding: 1rem; 
      text-align: left; 
    }

    th { 
      background: rgba(212, 175, 55, 0.2);
      color: #0f4c3a;
      font-weight: 600;
      font-size: 1.1rem;
    }

    td {
      color: #f8f9fa;
    }

    tr:hover {
      background: rgba(212, 175, 55, 0.05);
    }

    /* Buttons */
    .btn {
      display: inline-block;
      padding: 0.5rem 1.5rem;
      background: linear-gradient(135deg, #d4af37, #b8941f);
      color: #0f4c3a;
      text-decoration: none;
      border-radius: 25px;
      font-weight: 600;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
      font-size: 0.9rem;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
    }

    .btn-danger {
      background: linear-gradient(135deg, #e74c3c, #c0392b);
      color: white;
    }

    .btn-success {
      background: linear-gradient(135deg, #27ae60, #2ecc71);
      color: white;
    }

    /* Forms */
    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      color: #d4af37;
      font-weight: 500;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid rgba(212, 175, 55, 0.3);
      border-radius: 10px;
      background: rgba(255, 255, 255, 0.1);
      color: #f8f9fa;
      font-size: 1rem;
      transition: all 0.3s ease;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: #d4af37;
      box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
    }

    .form-group input::placeholder {
      color: rgba(248, 249, 250, 0.6);
    }

    /* Page Container */
    .page-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem;
    }

    .page-title {
      font-family: 'Playfair Display', serif;
      font-size: 2.5rem;
      font-weight: 600;
      color: #d4af37;
      text-align: center;
      margin-bottom: 2rem;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }

    /* Responsive */
    @media (max-width: 768px) {
      nav {
        padding: 1rem;
        text-align: center;
      }
      
      nav a {
        display: block;
        margin: 0.5rem 0;
      }
      
      .welcome-message {
        margin: 1rem 0;
        text-align: center;
      }
    }
  </style>
</head>
<body>

  <!-- Navigation -->
  <nav>
          <a href="index.php">
        <i class="fas fa-spa"></i> Lotus Temple
      </a>
    <a href="services.php">
      <i class="fas fa-concierge-bell"></i> Услуги
    </a>
    <a href="reservations.php">
      <i class="fas fa-calendar-alt"></i> Резервации
    </a>
    
    <?php if ($isLogged): ?>
      <span class="welcome-message">
        <i class="fas fa-user-circle"></i> Добре дошъл, <?= htmlspecialchars($userName) ?>
      </span>
      <a href="logout.php">
        <i class="fas fa-sign-out-alt"></i> Изход
      </a>
    <?php else: ?>
      <a href="login.php">
        <i class="fas fa-sign-in-alt"></i> Вход
      </a>
      <a href="register.php">
        <i class="fas fa-user-plus"></i> Регистрация
      </a>
    <?php endif; ?>
  </nav>
