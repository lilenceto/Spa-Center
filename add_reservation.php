<?php
// add_reservation.php

session_start();
if (empty($_SESSION['user_id'])) {
    // пазим къде иска да отиде човекът (вкл. избрана услуга)
    $next = 'add_reservation.php';
    if (!empty($_GET['service_id'])) {
        $next .= '?service_id='.(int)$_GET['service_id'];
    }
    header('Location: login.php?next='.urlencode($next));
    exit;
}
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "spa_center";
$message = "";
include 'header.php'; 
// Пример: ако имаш login, вземи реалния user_id от сесията
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Грешка при връзка: " . $conn->connect_error); }

function fetchAll($conn, $sql, $types = "", $params = []) {
    if ($types !== "" && !empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    } else {
        $res = $conn->query($sql);
        if (!$res) return [];
        $rows = [];
        while ($row = $res->fetch_assoc()) $rows[] = $row;  
        return $rows;
    }
}

// 1) Зареждаме услуги (с duration)
$services = fetchAll($conn, "SELECT id, name, duration FROM services ORDER BY name");

// 2) Зареждаме всички стаи
$rooms = fetchAll($conn, "SELECT id, name FROM rooms WHERE is_active = 1 ORDER BY name");

// 3) Ако е избрана услуга (POST/GET), показваме само служителите, които имат компетентност за нея
$employees = [];
$selected_service_id = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $selected_service_id = isset($_POST["service_id"]) ? (int)$_POST["service_id"] : null;
} elseif (isset($_GET["service_id"])) {
    $selected_service_id = (int)$_GET["service_id"];
}

if ($selected_service_id) {
    $employees = fetchAll(
        $conn,
        "SELECT e.id, e.name
         FROM employee_services es
         JOIN employees e ON e.id = es.employee_id
         WHERE es.service_id = ?
         ORDER BY e.name",
        "i",
        [$selected_service_id]
    );
} else {
    // Ако още не е избрана услуга, може да оставим списъка празен
    $employees = [];
}

// Хелпър: вземи duration за услуга
function getServiceDuration($conn, $service_id) {
    $rows = fetchAll($conn, "SELECT duration FROM services WHERE id = ?", "i", [$service_id]);
    return $rows ? (int)$rows[0]["duration"] : 60;
}

// Проверка: служителят има ли компетентност за услугата?
function employeeHasSkill($conn, $employee_id, $service_id) {
    $rows = fetchAll($conn,
        "SELECT 1 FROM employee_services WHERE employee_id = ? AND service_id = ? LIMIT 1",
        "ii",
        [$employee_id, $service_id]
    );
    return !empty($rows);
}

// Проверка: в рамките на работното време ли е интервалът?
function withinWorkingHours($conn, $employee_id, $start_dt, $end_dt) {
    // weekday: 1=Понеделник ... 7=Неделя (MySQL DAYOFWEEK: 1=Неделя ... 7=Събота, затова ще преобразуваме)
    // Да вземем PHP weekday (1..7) през MySQL:
    $weekday = (int)date('N', strtotime($start_dt)); // 1..7 (Mon..Sun)
    $time_start = date('H:i:s', strtotime($start_dt));
    $time_end   = date('H:i:s', strtotime($end_dt));

    $rows = fetchAll($conn,
        "SELECT 1
         FROM employee_working_hours
         WHERE employee_id = ?
           AND weekday = ?
           AND start_time <= ?
           AND end_time >= ?
         LIMIT 1",
        "iiss",
        [$employee_id, $weekday, $time_start, $time_end]
    );
    return !empty($rows);
}

// Проверка: припокриване по служител/стая
function hasOverlap($conn, $employee_id, $room_id, $start_dt, $end_dt) {
    // Припокриване, ако новият интервал се пресича с вече записан интервал:
    // new.start < existing.end AND existing.start < new.end
    $sql = "
        SELECT r.id
        FROM reservations r
        WHERE 
            (
                (? IS NOT NULL AND r.employee_id = ?) OR
                (? IS NOT NULL AND r.room_id = ?)
            )
            AND r.start_datetime < ?
            AND r.end_datetime > ?
        LIMIT 1";
    // Параметри: e_id, e_id, room_id, room_id, end_dt, start_dt
    $stmt = $conn->prepare($sql);
    // Трик: подаваме NULL като PHP null – за да сработи условието, ще подадем 2 пъти employee_id и 2 пъти room_id
    $stmt->bind_param(
        "iiiiss",
        $employee_id, $employee_id,
        $room_id,    $room_id,
        $end_dt, $start_dt
    );
    $stmt->execute();
    $res = $stmt->get_result();
    $exists = ($res && $res->num_rows > 0);
    $stmt->close();
    return $exists;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $service_id  = (int)$_POST["service_id"];
    $employee_id = isset($_POST["employee_id"]) ? (int)$_POST["employee_id"] : null;
    $room_id     = isset($_POST["room_id"]) ? (int)$_POST["room_id"] : null;
    $date        = trim($_POST["reservation_date"]); // YYYY-MM-DD
    $time        = trim($_POST["reservation_time"]); // HH:MM

    // Валидации
    if ($service_id <= 0) {
        $message = "Моля, изберете услуга.";
   } elseif (empty($date) || empty($time)) {
    $message = "Дата и час са задължителни.";
    } else {
        $start_dt = $date . " " . $time . ":00";
        $duration = getServiceDuration($conn, $service_id);
        $end_dt   = date('Y-m-d H:i:s', strtotime($start_dt . " +$duration minutes"));

        // Компетентност на служител (ако е избран)
        if ($employee_id && !employeeHasSkill($conn, $employee_id, $service_id)) {
            $message = "Избраният служител няма компетентност за тази услуга.";
        }
        // Работно време (ако е избран служител)
        elseif ($employee_id && !withinWorkingHours($conn, $employee_id, $start_dt, $end_dt)) {
            $message = "Интервалът е извън работното време на служителя.";
        }
        // Припокриване (служител или стая – ако поддържаме избор и на двете)
        elseif ( ($employee_id || $room_id) && hasOverlap($conn, $employee_id, $room_id, $start_dt, $end_dt) ) {
            $message = "Служителят или стаята са заети в този интервал.";
        }
        else {
            // Запис
            $stmt = $conn->prepare("
                INSERT INTO reservations
                    (user_id, service_id, employee_id, room_id, start_datetime, end_datetime, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            // Позволяваме NULL за employee_id/room_id ако не са избрани
            $emp = $employee_id ?: null;
            $room= $room_id ?: null;
            $stmt->bind_param("iiiiss", $user_id, $service_id, $emp, $room, $start_dt, $end_dt);
            if ($stmt->execute()) {
                $message = "Резервацията е записана успешно!";
            } else {
                $message = "Грешка при запис: " . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="UTF-8">
<title>Нова резервация</title>
<script>
// Нежна помощ: при смяна на услуга—рефреш със service_id в URL (за да филтрираме служителите)
function onServiceChange(sel) {
    const serviceId = sel.value;
    const url = new URL(window.location.href);
    if (serviceId) { url.searchParams.set('service_id', serviceId); }
    else { url.searchParams.delete('service_id'); }
    window.location.href = url.toString();
}
</script>
</head>
<body>
<h2>Създаване на резервация</h2>
<?php if ($message) echo "<p><b>" . htmlspecialchars($message) . "</b></p>"; ?>

<form method="POST" action="">
    <label>Услуга:</label><br>
    <select name="service_id" required onchange="onServiceChange(this)">
        <option value="">--Избери услуга--</option>
        <?php foreach ($services as $s): ?>
            <option value="<?= $s['id']; ?>" <?= ($selected_service_id==$s['id']?'selected':''); ?>>
                <?= htmlspecialchars($s['name']); ?> (<?= (int)$s['duration']; ?> мин)
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Служител (по компетентност за услугата):</label><br>
    <select name="employee_id">
        <option value="">--По избор--</option>
        <?php foreach ($employees as $e): ?>
            <option value="<?= $e['id']; ?>"><?= htmlspecialchars($e['name']); ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Стая/Зала:</label><br>
    <select name="room_id">
        <option value="">--По избор--</option>
        <?php foreach ($rooms as $r): ?>
            <option value="<?= $r['id']; ?>"><?= htmlspecialchars($r['name']); ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Дата:</label><br>
    <input type="date" name="reservation_date" required><br><br>

    <label>Начален час:</label><br>
    <input type="time" name="reservation_time" required><br><br>

    <button type="submit">Резервирай</button>
</form>

<br>
<a href="reservations.php">Виж всички резервации</a>
</body>
</html>
<?php $conn->close(); ?>
