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
  <title>SPA Center - Категории</title>
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
        margin-bottom: 30px;
        color: #333;
    }
    .categories {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    .category-card {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        text-align: center;
        transition: transform 0.2s ease;
        text-decoration: none;
        color: inherit;
    }
    .category-card:hover {
        transform: translateY(-5px);
    }
    .category-card img {
        width: 100%;
        height: 160px;
        object-fit: cover;
    }
    .category-card h3 {
        margin: 15px 0;
        color: #444;
    }
  </style>
</head>
<body>
<div class="container">
  <h2>Нашите категории</h2>
  <div class="categories">
    <?php while($row = $res->fetch_assoc()): ?>
      <a class="category-card" href="category.php?id=<?= $row['id'] ?>">
        <img src="images/category_<?= $row['id'] ?>.jpg" alt="<?= htmlspecialchars($row['name']) ?>">
        <h3><?= htmlspecialchars($row['name']) ?></h3>
      </a>
    <?php endwhile; ?>
  </div>
</div>
</body>
</html>
<?php include "footer.php"; ?>
