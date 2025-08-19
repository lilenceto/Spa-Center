<?php
session_start();
$mysqli = new mysqli("localhost","root","","spa_center");
if ($mysqli->connect_error) die("DB грешка: " . $mysqli->connect_error);
$mysqli->set_charset("utf8");

require_once __DIR__.'/header.php';

// Взимаме всички услуги с категории
$sql = "
    SELECT s.id, s.name, s.description, s.price, s.duration, c.name AS category
    FROM services s
    JOIN service_categories c ON c.id = s.category_id
    ORDER BY c.name, s.name
";
$result = $mysqli->query($sql);

$services = [];
while ($row = $result->fetch_assoc()) {
    $services[$row['category']][] = $row;
}
?>

<h2>Нашите услуги</h2>

<?php foreach ($services as $category => $items): ?>
  <h3><?= htmlspecialchars($category) ?></h3>
  <table border="1" cellpadding="8" cellspacing="0" style="margin-bottom:20px; border-collapse:collapse;">
    <tr style="background:#eee">
      <th>Име</th>
      <th>Описание</th>
      <th>Продължителност (мин)</th>
      <th>Цена (лв.)</th>
      <th>Действие</th>
    </tr>
    <?php foreach ($items as $srv): ?>
      <tr>
        <td><?= htmlspecialchars($srv['name']) ?></td>
        <td><?= htmlspecialchars($srv['description']) ?></td>
        <td><?= (int)$srv['duration'] ?></td>
        <td><?= number_format($srv['price'], 2) ?></td>
        <td>
          <?php if (!empty($_SESSION['user_id'])): ?>
            <a href="add_reservation.php?service_id=<?= $srv['id'] ?>">Резервирай</a>
          <?php else: ?>
            <a href="login.php?next=add_reservation.php?service_id=<?= $srv['id'] ?>">Вход за резервация</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endforeach; ?>

<?php require_once __DIR__.'/footer.php'; ?>
