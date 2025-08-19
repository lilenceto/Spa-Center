<?php
session_start();
require_once "db.php";

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = (int)$_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $service_id = (int)$_POST["service_id"];
    $date       = $_POST["reservation_date"] ?? '';
    $time       = $_POST["selected_time"] ?? '';

    if ($service_id <= 0 || !$date || !$time) {
        die("Невалидни данни.");
    }

    // взимаме продължителността
    $stmt = $mysqli->prepare("SELECT duration, name FROM services WHERE id=?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $srv = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$srv) die("Услугата не е намерена.");

    $duration = (int)$srv['duration'];
    $start_dt = date("Y-m-d H:i:s", strtotime("$date $time"));
    $end_dt   = date("Y-m-d H:i:s", strtotime("$start_dt +$duration minutes"));

    // проверка дали вече е зает
    $stmt = $mysqli->prepare("SELECT id FROM reservations WHERE service_id=? AND start_datetime=? LIMIT 1");
    $stmt->bind_param("is", $service_id, $start_dt);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($exists) {
        die("Този час вече е зает. Моля, изберете друг!");
    }

    // записваме
    $stmt = $mysqli->prepare("INSERT INTO reservations (user_id, service_id, start_datetime, end_datetime, status)
                              VALUES (?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iiss", $user_id, $service_id, $start_dt, $end_dt);
    if ($stmt->execute()) {
        echo "Резервацията е записана успешно!";
    } else {
        echo "Грешка при запис: " . $mysqli->error;
    }
    $stmt->close();
} else {
    die("Невалидна заявка.");
}
?>
<br><a href="reservations.php">Виж моите резервации</a>
