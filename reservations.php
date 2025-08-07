<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "spa_center";

// Връзка с базата
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Грешка при връзка: " . $conn->connect_error);
}

// Извличане на всички резервации (с инфо за услугата и user_id)
$sql = "SELECT r.id, r.reservation_date, r.reservation_time, r.status, r.user_id, 
               s.name AS service_name
        FROM reservations r
        JOIN services s ON r.service_id = s.id
        ORDER BY r.reservation_date DESC, r.reservation_time DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Списък с резервации</title>
</head>
<body>
    <h2>Списък с всички резервации</h2>
    <a href="add_reservation.php">Добави нова резервация</a>
    <br><br>
    <table border="1" cellpadding="5">
        <tr>
            <th>ID</th>
            <th>Услуга</th>
            <th>Дата</th>
            <th>Час</th>
            <th>Потребител ID</th>
            <th>Статус</th>
        </tr>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['service_name']) ?></td>
                    <td><?= $row['reservation_date'] ?></td>
                    <td><?= $row['reservation_time'] ?></td>
                    <td><?= $row['user_id'] ?></td>
                    <td><?= $row['status'] ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">Няма намерени резервации.</td></tr>
        <?php endif; ?>
    </table>
</body>
</html>
<?php $conn->close(); ?>
