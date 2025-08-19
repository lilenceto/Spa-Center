<?php
require_once "db.php";

$service_id = (int)($_GET['service_id'] ?? 0);
$date = $_GET['date'] ?? "";
$gender = $_GET['gender'] ?? "";

if (!$service_id || !$date || !$gender) {
    echo json_encode([]);
    exit;
}

// Взимаме продължителността на услугата
$stmt = $mysqli->prepare("SELECT duration FROM services WHERE id=?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$stmt->bind_result($duration);
$stmt->fetch();
$stmt->close();
if (!$duration) { echo json_encode([]); exit; }

// Генерираме слотове (пример: от 09:00 до 18:00)
$slots = [];
$start = strtotime($date." 09:00");
$end   = strtotime($date." 18:00");
for ($t=$start; $t+$duration*60 <= $end; $t+=$duration*60) {
    $slots[] = date("H:i", $t);
}

// Тук може да се добави логика за реална проверка на заетост в reservations по избрания пол
// Засега връщаме всички възможни слотове
echo json_encode($slots);
