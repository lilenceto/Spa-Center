<?php
session_start();
require_once "db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?next=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method");
}

$user_id = (int)$_SESSION['user_id'];
$service_id = (int)($_POST['service_id'] ?? 0);
$employee_id = (int)($_POST['employee_id'] ?? 0);
$reservation_date = $_POST['reservation_date'] ?? "";
$reservation_time = $_POST['reservation_time'] ?? "";

if (!$service_id || !$reservation_date || !$reservation_time) {
    die("Missing required fields");
}

// Get service details
$stmt = $mysqli->prepare("SELECT duration, category_id, name FROM services WHERE id=?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$stmt->bind_result($duration, $category_id, $service_name);
$stmt->fetch();
$stmt->close();

if (!$duration) {
    die("Invalid service");
}

// Check if this is a pool service
$isPoolService = false;
if ($category_id == 4 && strpos(strtolower($service_name), 'pool') !== false) {
    $isPoolService = true;
}

// Check if this is a SPA service (category 4, but not pool)
$isSPAService = ($category_id == 4 && !$isPoolService);

if ($isPoolService) {
    // For pool services, check capacity instead of employee availability
    $weekday = date('N', strtotime($reservation_date));
    
    // Pool is available every day from 9 AM to 9 PM
    $start_time = '09:00:00';
    $end_time = '21:00:00';
    
    // Validate time is within pool hours
    $normalized_time = $reservation_time;
    if (strlen($reservation_time) === 5 && substr($reservation_time, 2, 1) === ':') {
        $normalized_time = $reservation_time . ':00';
    }
    
    if ($normalized_time < $start_time || $normalized_time > $end_time) {
        die("Избраният час е извън работното време на басейна (09:00 - 21:00).");
    }
    
    // Check pool capacity for this date and time
    $stmt = $mysqli->prepare("
        SELECT COUNT(*) as current_capacity
        FROM reservations 
        WHERE service_id = ? AND reservation_date = ? AND reservation_time = ?
    ");
    $stmt->bind_param("iss", $service_id, $reservation_date, $reservation_time);
    $stmt->execute();
    $stmt->bind_result($current_capacity);
    $stmt->fetch();
    $stmt->close();
    
    if ($current_capacity >= 50) {
        die("Басейнът е пълен за този час. Моля, изберете друг час.");
    }
    
    // For pool, we don't need employee_id
    $employee_id = null;
    
} elseif ($isSPAService) {
    // For SPA services, auto-assign employee
    $weekday = date('N', strtotime($reservation_date));
    
    // Find available SPA employee for this time slot
    $stmt = $mysqli->prepare("
        SELECT e.id, w.start_time, w.end_time
        FROM employees e
        JOIN employee_working_hours w ON w.employee_id = e.id
        WHERE e.category_id = 4 AND w.weekday = ?
        ORDER BY e.id
    ");
    $stmt->bind_param("i", $weekday);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $availableEmployee = null;
    while ($row = $result->fetch_assoc()) {
        $emp_id = $row['id'];
        $start_time = $row['start_time'];
        $end_time = $row['end_time'];
        
        // Validate time is within working hours
        $normalized_time = $reservation_time;
        if (strlen($reservation_time) === 5 && substr($reservation_time, 2, 1) === ':') {
            $normalized_time = $reservation_time . ':00';
        }
        
        if ($normalized_time < $start_time || $normalized_time > $end_time) {
            continue; // This employee doesn't work at this time
        }
        
        // Check if this employee is available for this time slot
        $stmt2 = $mysqli->prepare("
            SELECT COUNT(*) as conflicts
            FROM reservations r
            JOIN services s ON s.id = r.service_id
            WHERE r.employee_id = ? AND r.reservation_date = ? 
            AND (
                (r.reservation_time <= ? AND r.reservation_time + INTERVAL s.duration MINUTE > ?) OR
                (r.reservation_time < ? + INTERVAL ? MINUTE AND r.reservation_time >= ?)
            )
        ");
        $stmt2->bind_param("issssss", $emp_id, $reservation_date, $reservation_time, $reservation_time, $reservation_time, $duration, $reservation_time);
        $stmt2->execute();
        $stmt2->bind_result($conflicts);
        $stmt2->fetch();
        $stmt2->close();
        
        if ($conflicts == 0) {
            $availableEmployee = $emp_id;
            break;
        }
    }
    $stmt->close();
    
    if (!$availableEmployee) {
        die("Няма свободен специалист за този час. Моля, изберете друг час.");
    }
    
    // Auto-assign the available employee
    $employee_id = $availableEmployee;
    
} else {
    // Regular service - validate employee selection
    if (!$employee_id) {
        die("Моля, изберете специалист");
    }
    
    // Get employee working hours for this day
    $weekday = date('N', strtotime($reservation_date));
    $stmt = $mysqli->prepare("SELECT start_time, end_time FROM employee_working_hours WHERE employee_id=? AND weekday=?");
    $stmt->bind_param("ii", $employee_id, $weekday);
    $stmt->execute();
    $stmt->bind_result($start_time, $end_time);
    if (!$stmt->fetch()) {
        $stmt->close();
        die("Специалистът не работи в този ден.");
    }
    $stmt->close();
    
    // Validate time is within working hours
    $normalized_time = $reservation_time;
    if (strlen($reservation_time) === 5 && substr($reservation_time, 2, 1) === ':') {
        $normalized_time = $reservation_time . ':00';
    }
    
    if ($normalized_time < $start_time || $normalized_time > $end_time) {
        die("Избраният час е извън работното време ({$start_time} - {$end_time}).");
    }
    
    // Check for conflicts with existing reservations
    $stmt = $mysqli->prepare("
        SELECT COUNT(*) as conflicts
        FROM reservations r
        JOIN services s ON s.id = r.service_id
        WHERE r.employee_id = ? AND r.reservation_date = ? 
        AND (
            (r.reservation_time <= ? AND r.reservation_time + INTERVAL s.duration MINUTE > ?) OR
            (r.reservation_time < ? + INTERVAL ? MINUTE AND r.reservation_time >= ?)
        )
    ");
    $stmt->bind_param("issssss", $employee_id, $reservation_date, $reservation_time, $reservation_time, $reservation_time, $duration, $reservation_time);
    $stmt->execute();
    $stmt->bind_result($conflicts);
    $stmt->fetch();
    $stmt->close();
    
    if ($conflicts > 0) {
        die("Избраният час вече е зает. Моля, изберете друг час.");
    }
}

// Insert the reservation
$stmt = $mysqli->prepare("
    INSERT INTO reservations (user_id, service_id, employee_id, reservation_date, reservation_time, status) 
    VALUES (?, ?, ?, ?, ?, 'Awaiting')
");
$stmt->bind_param("iiiss", $user_id, $service_id, $employee_id, $reservation_date, $reservation_time);

if ($stmt->execute()) {
    $reservation_id = $mysqli->insert_id;
    
    // Insert status history
    $stmt2 = $mysqli->prepare("
        INSERT INTO reservation_status_history (reservation_id, old_status, new_status, changed_by) 
        VALUES (?, NULL, 'Awaiting', ?)
    ");
    $stmt2->bind_param("ii", $reservation_id, $user_id);
    $stmt2->execute();
    $stmt2->close();
    
    $stmt->close();
    
    // Send email confirmation
    require_once "email_helper_working.php";
    $emailHelper = new EmailHelperWorking();
    
    // Get user details
    $stmt3 = $mysqli->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt3->bind_param("i", $user_id);
    $stmt3->execute();
    $stmt3->bind_result($user_name, $user_email);
    $stmt3->fetch();
    $stmt3->close();
    
    // Get service details
    $stmt4 = $mysqli->prepare("SELECT name, duration FROM services WHERE id = ?");
    $stmt4->bind_param("i", $service_id);
    $stmt4->execute();
    $stmt4->bind_result($service_name, $service_duration);
    $stmt4->fetch();
    $stmt4->close();
    
    // Get employee name if assigned
    $employee_name = null;
    if ($employee_id) {
        $stmt5 = $mysqli->prepare("SELECT name FROM employees WHERE id = ?");
        $stmt5->bind_param("i", $employee_id);
        $stmt5->execute();
        $stmt5->bind_result($employee_name);
        $stmt5->fetch();
        $stmt5->close();
    }
    
    // Prepare reservation data for email
    $reservation_data = [
        'service_name' => $service_name,
        'date' => $reservation_date,
        'time' => $reservation_time,
        'duration' => $service_duration,
        'employee_name' => $employee_name
    ];
    
    // Send confirmation email
    $emailHelper->sendReservationConfirmation($user_email, $user_name, $reservation_data);
    
    // Redirect to success page or show success message
    header("Location: reservations.php?success=1");
    exit;
} else {
    die("Грешка при запазването на резервацията: " . $mysqli->error);
}
?>
