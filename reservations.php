<?php
// reservations.php
session_start();

$role = $_SESSION['role']    ?? 'client';        // 'admin' | 'employee' | 'client'
$uid  = (int)($_SESSION['user_id'] ?? 0);

$mysqli = new mysqli("localhost","root","","spa_center");
if ($mysqli->connect_error) { die("Грешка при връзка: ".$mysqli->connect_error); }

// Базов SELECT с имена вместо ID
$baseSql = "
SELECT
  r.id,
  r.user_id,
  u.name  AS user_name,
  s.name  AS service_name,
  e.name  AS employee_name,
  rm.name AS room_name,
  r.start_datetime,
  r.end_datetime,
  r.status
FROM reservations r
JOIN users    u  ON u.id  = r.user_id
JOIN services s  ON s.id  = r.service_id
LEFT JOIN employees e ON e.id = r.employee_id
LEFT JOIN rooms     rm ON rm.id= r.room_id
";

// Клиентът вижда само своите, staff/admin виждат всички
if ($role === 'client') {
  $sql = $baseSql . " WHERE r.user_id = ? ORDER BY r.start_datetime DESC, r.id DESC";
  $stmt = $mysqli->prepare($sql);
  $stmt->bind_param("i", $uid);
  $stmt->execute();
  $result = $stmt->get_result();
} else {
  $sql = $baseSql . " ORDER BY r.start_datetime DESC, r.id DESC";
  $result = $mysqli->query($sql);
}

function fmtDT($dt) {
  return $dt ? date('Y-m-d H:i', strtotime($dt)) : '—';
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8">
  <title>Списък с резервации</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 24px; }
    h2 { margin-bottom: 10px; }
    nav a { margin-right: 12px; }
    table { border-collapse: collapse; width: 100%; margin-top: 12px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f5f5f5; }
    .muted { color:#777; }
  </style>
</head>
<body>
  <h2>Списък с резервации</h2>
  <nav>
    <a href="index.php">Начало</a>
    <a href="add_reservation.php">Нова резервация</a>
    <a href="logout.php">Изход</a>
  </nav>

  <table>
    <tr>
      <th>#</th>
      <?php if ($role !== 'client'): ?>
        <th>Клиент</th>
      <?php endif; ?>
      <th>Услуга</th>
      <th>Служител</th>
      <th>Стая</th>
      <th>Начало</th>
      <th>Край</th>
      <th>Статус</th>
      <th>Действия</th>
    </tr>

    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <?php
          // Правила за действия
          $isOwner = ((int)$row['user_id'] === $uid);
          $startsIn = $row['start_datetime'] ? (strtotime($row['start_datetime']) - time()) : -1;
          $canStaffEdit = ($role === 'admin' || $role === 'employee');

          // Клиентът може да отменя само своя pending резервация ≥24ч предварително
          $canClientCancel = ($role === 'client'
                              && $isOwner
                              && $row['status'] === 'pending'
                              && $startsIn >= 24*3600);
        ?>
        <tr>
          <td><?= (int)$row['id'] ?></td>

          <?php if ($role !== 'client'): ?>
            <td><?= htmlspecialchars($row['user_name']) ?></td>
          <?php endif; ?>

          <td><?= htmlspecialchars($row['service_name']) ?></td>
          <td><?= htmlspecialchars($row['employee_name'] ?? '—') ?></td>
          <td><?= htmlspecialchars($row['room_name'] ?? '—') ?></td>
          <td><?= fmtDT($row['start_datetime']) ?></td>
          <td><?= fmtDT($row['end_datetime']) ?></td>
          <td><?= htmlspecialchars($row['status']) ?></td>
          <td>
            <?php if ($canStaffEdit): ?>
              <a href="reservation_edit.php?id=<?= (int)$row['id'] ?>">Редакция</a> |
              <a href="reservation_status.php?id=<?= (int)$row['id'] ?>&action=confirm">Потвърди</a> |
              <a href="reservation_status.php?id=<?= (int)$row['id'] ?>&action=cancel">Откажи</a>
            <?php elseif ($canClientCancel): ?>
              <a href="reservation_status.php?id=<?= (int)$row['id'] ?>&action=cancel">Откажи</a>
            <?php else: ?>
              <span class="muted">—</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr>
        <td colspan="<?= ($role !== 'client') ? 9 : 8; ?>">Няма намерени резервации.</td>
      </tr>
    <?php endif; ?>
  </table>
</body>
</html>
<?php
if (isset($stmt) && $stmt) { $stmt->close(); }
$mysqli->close();
