<?php
// admin_overview.php
session_start();
// По желание: проверка за логнат потребител/роля
// if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "spa_center";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("DB грешка: " . $conn->connect_error); }

function fetchAll($conn, $sql) {
    $res = $conn->query($sql);
    if (!$res) return [];
    $rows = [];
    while ($row = $res->fetch_assoc()) $rows[] = $row;
    return $rows;
}

$services = fetchAll($conn, "
SELECT s.id, s.name AS service, s.duration, s.price, c.name AS category
FROM services s
LEFT JOIN service_categories c ON c.id = s.category_id
ORDER BY c.name, s.name
");

$employeeSkills = fetchAll($conn, "
SELECT e.name AS employee, s.name AS service
FROM employee_services es
JOIN employees e ON e.id = es.employee_id
JOIN services  s ON s.id = es.service_id
ORDER BY e.name, s.name
");

$workHours = fetchAll($conn, "
SELECT e.name AS employee, w.weekday, w.start_time, w.end_time
FROM employee_working_hours w
JOIN employees e ON e.id = w.employee_id
ORDER BY e.name, w.weekday, w.start_time
");

$reservations = fetchAll($conn, "
SELECT r.id,
       u.name AS user_name,
       s.name AS service,
       COALESCE(e.name,'—') AS employee,
       COALESCE(rm.name,'—') AS room,
       r.start_datetime, r.end_datetime, r.status
FROM reservations r
JOIN users    u  ON u.id  = r.user_id
JOIN services s  ON s.id  = r.service_id
LEFT JOIN employees e ON e.id = r.employee_id
LEFT JOIN rooms     rm ON rm.id= r.room_id
ORDER BY r.start_datetime DESC
");

$topServices = fetchAll($conn, "
SELECT s.name AS service, COUNT(*) AS reservations_count
FROM reservations r
JOIN services s ON s.id = r.service_id
GROUP BY s.id, s.name
ORDER BY reservations_count DESC, s.name
");

function renderTable($title, $rows) {
    echo "<h2>" . htmlspecialchars($title) . "</h2>";
    if (empty($rows)) { echo "<p>Няма данни.</p>"; return; }
    echo "<div class='tablewrap'><table><thead><tr>";
    foreach (array_keys($rows[0]) as $col) {
        echo "<th>" . htmlspecialchars($col) . "</th>";
    }
    echo "</tr></thead><tbody>";
    foreach ($rows as $r) {
        echo "<tr>";
        foreach ($r as $val) {
            echo "<td>" . htmlspecialchars((string)$val) . "</td>";
        }
        echo "</tr>";
    }
    echo "</tbody></table></div>";
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="UTF-8" />
<title>Админ преглед</title>
<style>
body { font-family: Arial, sans-serif; margin: 24px; }
h1 { margin-bottom: 8px; }
h2 { margin-top: 28px; }
.tablewrap { overflow: auto; max-width: 100%; }
table { border-collapse: collapse; width: 100%; }
th, td { border: 1px solid #ddd; padding: 8px; }
th { background: #f4f4f4; text-align: left; }
nav a { margin-right: 12px; }
.small { color:#666; font-size: 0.9em; }
</style>
</head>
<body>
<h1>Админ преглед</h1>
<nav>
  <a href="index.php">Начало</a>
  <a href="services.php">Услуги</a>
  <a href="add_reservation.php">Нова резервация</a>
  <a href="reservations.php">Резервации</a>
  <a href="logout.php">Изход</a>
</nav>
<p class="small">Тази страница показва моментна снимка от базата за проверка на структурата и връзките.</p>

<?php
renderTable("Услуги и категории", $services);
renderTable("Компетентности на служители (кой изпълнява какво)", $employeeSkills);
renderTable("Работно време на служители", $workHours);
renderTable("Последни резервации", $reservations);
renderTable("Най-резервирани услуги", $topServices);
?>

</body>
</html>
<?php $conn->close(); ?>
