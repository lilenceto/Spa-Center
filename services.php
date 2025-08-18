<?php
// services.php
session_start();

// 1) Връзка с базата
$mysqli = new mysqli("localhost","root","","spa_center");
if ($mysqli->connect_error) {
    die("Грешка при връзка: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8");

// 2) Флаг за логнат потребител
$isLogged = !empty($_SESSION['user_id']);

// 3) Извличане на услуги + категория
$sql = "
SELECT s.id,
       s.name,
       s.description,
       s.duration,
       s.price,
       COALESCE(c.name, '—') AS category
FROM services s
LEFT JOIN service_categories c ON c.id = s.category_id
ORDER BY c.name, s.name
";
$result = $mysqli->query($sql);

// 4) Хедър с навигация
require_once __DIR__ . '/header.php';
?>
<h2>Списък с услуги</h2>

<table>
  <tr>
    <th>ID</th>
    <th>Име</th>
    <th>Описание</th>
    <th>Времетраене (мин)</th>
    <th>Цена</th>
    <th>Категория</th>
    <th>Действия</th>
  </tr>

  <?php if ($result && $result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
      <?php
        // Умна препратка за бутона "Резервирай"
        $target = 'add_reservation.php?service_id='.(int)$row['id'];
        $bookUrl = $isLogged
          ? $target
          : ('login.php?next='.urlencode($target));
      ?>
      <tr>
        <td><?= (int)$row['id'] ?></td>
        <td><?= htmlspecialchars($row['name']) ?></td>
        <td><?= htmlspecialchars($row['description'] ?? '') ?></td>
        <td><?= (int)$row['duration'] ?></td>
        <td><?= number_format((float)$row['price'], 2) ?> лв.</td>
        <td><?= htmlspecialchars($row['category']) ?></td>
        <td><a href="<?= $bookUrl ?>">Резервирай</a></td>
      </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr><td colspan="7">Няма намерени услуги.</td></tr>
  <?php endif; ?>
</table>

<?php
require_once __DIR__ . '/footer.php';
$mysqli->close();
