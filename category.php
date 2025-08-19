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
?>
<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($cat_name) ?> - Услуги</title>
  <style>
    body {
        font-family: 'Segoe UI', sans-serif;
        background: #f7f8fa;
        margin: 0;
        padding: 0;
    }
    .container {
        max-width: 1200px;
        margin: 30px auto;
        padding: 0 20px;
    }
    h2 {
        text-align: center;
        margin-bottom: 20px;
        color: #333;
    }
    .back-link {
        display: inline-block;
        margin-bottom: 20px;
        color: #007bff;
        text-decoration: none;
        font-size: 0.95em;
    }
    .back-link:hover {
        text-decoration: underline;
    }
    .services-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }
    .service-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: transform 0.2s ease;
    }
    .service-card:hover {
        transform: translateY(-5px);
    }
    .card-header {
        height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
        color: #fff;
        font-size: 2.5em;
        font-weight: bold;
    }
    .card-header .icon {
        background: rgba(255,255,255,0.2);
        padding: 20px;
        border-radius: 50%;
    }
    .card-body {
        padding: 20px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .card-body h3 {
        margin: 0 0 10px;
        font-size: 1.2em;
        color: #333;
    }
    .card-body p {
        color: #555;
        font-size: 0.95em;
        margin-bottom: 15px;
        flex-grow: 1;
    }
    .service-info {
        font-size: 0.9em;
        color: #444;
        margin-bottom: 15px;
    }
    .service-card a {
        display: inline-block;
        padding: 10px 16px;
        background: #28a745;
        color: #fff;
        border-radius: 6px;
        text-decoration: none;
        text-align: center;
        transition: background 0.2s ease;
    }
    .service-card a:hover {
        background: #1e7e34;
    }
  </style>
</head>
<body>
<div class="container">
  <a href="index.php" class="back-link">← Назад към категориите</a>
  <h2><?= htmlspecialchars($cat_name) ?></h2>
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
            ⏱ <?= (int)$srv['duration'] ?> мин <br>
            💰 <?= number_format($srv['price'], 2) ?> лв.
          </div>
          <a href="add_reservation.php?service_id=<?= $srv['id'] ?>">Резервирай</a>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>
</body>
</html>
<?php include "footer.php"; ?>
