<?php
$isLogged = !empty($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';
?>

<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8">
  <title>Spa Center</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 24px; }
    nav { background:#f5f5f5; padding:10px; margin-bottom:20px; border-radius:5px; }
    nav a { margin-right:15px; text-decoration:none; color:#0073aa; font-weight:bold; }
    nav a:hover { text-decoration:underline; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border:1px solid #ddd; padding:8px; text-align:left; }
    th { background:#f5f5f5; }
  </style>
</head>
<body>

  <!-- Навигация -->
  <nav>
  <a href="index.php">🏠 Начало</a>
  <a href="services.php">💆 Услуги</a>
  <a href="reservations.php">📅 Резервации</a>
  
  <?php if ($isLogged): ?>
    <span style="margin-right:15px; font-weight:bold; color:#0073aa;">
      👋 Добре дошъл, <?= htmlspecialchars($userName) ?>
    </span>
    <a href="logout.php">🚪 Изход</a>
  <?php else: ?>
    <a href="login.php">🔐 Вход</a>
    <a href="register.php">📝 Регистрация</a>
  <?php endif; ?>
</nav>
