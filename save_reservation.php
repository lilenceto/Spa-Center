<?php
session_start();
require_once "db.php";

if (empty($_SESSION['user_id'])) {
    die("Нямате достъп.");
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $service_id = (int)$_POST['service_id'];
    $date = $_POST['reservation_date'] ?? '';
    $time = $_POST['reservation_time'] ?? ''; // HH:MM

    if ($service_id <= 0 || !$date || !$time) {
        die("Невалидни данни.");
    }

    // взимаме продължителността + категорията
    $stmt = $mysqli->prepare("SELECT duration, category_id FROM services WHERE id=?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $service = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$service) die("Невалидна услуга.");
    $duration = (int)$service['duration'];
    $category_id = (int)$service['category_id'];

    $start_dt = "$date $time:00";
    $end_dt   = date("Y-m-d H:i:s", strtotime("$start_dt +$duration minutes"));

    $employee_id = null;

    // 🔹 Ако е СПА (категория 4) → автоматичен избор
    if ($category_id === 4) {
        $weekday = date("w", strtotime($date)); // 0=Неделя, 1=Пон...
        if ($weekday == 0) $weekday = 7; // да стане 1-7

        $sql = "
            SELECT e.id
            FROM employees e
            JOIN employee_services es ON es.employee_id = e.id
            JOIN employee_working_hours w ON w.employee_id = e.id
            WHERE es.service_id = ?
              AND w.weekday = ?
              AND ? BETWEEN w.start_time AND w.end_time
              AND NOT EXISTS (
                SELECT 1 FROM reservations r
                WHERE r.employee_id = e.id
                  AND r.start_datetime < ?
                  AND r.end_datetime > ?
              )
            ORDER BY (
                SELECT COUNT(*) FROM reservations r2
                WHERE r2.employee_id = e.id
                  AND DATE(r2.start_datetime) = ?
            ) ASC
            LIMIT 1
        ";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("iissss", $service_id, $weekday, $time, $end_dt, $start_dt, $date);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($res) {
            $employee_id = $res['id'];
        } else {
            die("Няма свободен СПА служител в избрания час.");
        }
    } else {
        // иначе идва от POST (примерно масажист, козметик и т.н.)
        if (empty($_POST['employee_id'])) {
            die("Моля изберете служител.");
        }
        $employee_id = (int)$_POST['employee_id'];
    }

    // проверка дали е зает
    $stmt = $mysqli->prepare("SELECT id FROM reservations WHERE employee_id=? AND start_datetime < ? AND end_datetime > ?");
    $stmt->bind_param("iss", $employee_id, $end_dt, $start_dt);
    $stmt->execute();
    $taken = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($taken) {
        die("Избраният час вече е зает.");
    }

    // записваме
    $stmt = $mysqli->prepare("INSERT INTO reservations (user_id, service_id, employee_id, start_datetime, end_datetime, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iiiss", $user_id, $service_id, $employee_id, $start_dt, $end_dt);
    $stmt->execute();
    $stmt->close();

    echo "Резервацията е успешна!";
}
?>
<a href="reservations.php">Виж моите резервации</a>
