<?php
require_once "db.php";

$service_id = (int)($_GET['service_id'] ?? 0);
$employee_id = (int)($_GET['employee_id'] ?? 0);
$date = $_GET['date'] ?? "";

if (!$service_id || !$date) {
    echo json_encode([]);
    exit;
}

// Get service details to check category
$stmt = $mysqli->prepare("SELECT duration, category_id FROM services WHERE id=?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$stmt->bind_result($duration, $category_id);
$stmt->fetch();
$stmt->close();
if (!$duration) { echo json_encode([]); exit; }

// Check if this is a pool service
$isPoolService = false;
if ($category_id == 4) {
    $stmt = $mysqli->prepare("SELECT name FROM services WHERE id=?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $stmt->bind_result($service_name);
    $stmt->fetch();
    $stmt->close();
    
    if (strpos(strtolower($service_name), 'pool') !== false) {
        $isPoolService = true;
    }
}

if ($isPoolService) {
    // For pool services, generate time slots from 9 AM to 9 PM
    $slots = [];
    $startTime = strtotime("$date 09:00:00");
    $endTime = strtotime("$date 21:00:00");
    
    // Generate slots every 30 minutes
    for ($currentTime = $startTime; $currentTime < $endTime; $currentTime += 30 * 60) {
        $slots[] = date('H:i', $currentTime);
    }
    
    echo json_encode($slots);
    exit;
}

// For SPA services without employee, or regular services with employee
if ($category_id == 4 && !$employee_id) {
    // SPA service without employee - get available slots from all SPA employees
    $slots = [];
    $weekday = date('N', strtotime($date));
    
    // Get all SPA employees working on this day
    $stmt = $mysqli->prepare("
        SELECT e.id, w.start_time, w.end_time
        FROM employees e
        JOIN employee_working_hours w ON w.employee_id = e.id
        WHERE e.category_id = 4 AND w.weekday = ?
        ORDER BY w.start_time
    ");
    $stmt->bind_param("i", $weekday);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Get the earliest start time and latest end time for this day
        $startTimes = [];
        $endTimes = [];
        $employeeIds = [];
        
        while ($row = $result->fetch_assoc()) {
            $startTimes[] = $row['start_time'];
            $endTimes[] = $row['end_time'];
            $employeeIds[] = $row['id'];
        }
        $stmt->close();
        
        $earliestStart = min($startTimes);
        $latestEnd = max($endTimes);
        
        // Convert to timestamps
        $dayStartTs = strtotime("$date $earliestStart");
        $dayEndTs = strtotime("$date $latestEnd");
        
        // Get existing reservations for all SPA employees on this date
        $stmt = $mysqli->prepare("
            SELECT r.reservation_time, s.duration 
            FROM reservations r
            JOIN services s ON s.id = r.service_id
            WHERE r.employee_id IN (" . implode(',', $employeeIds) . ") AND r.reservation_date = ?
            ORDER BY r.reservation_time
        ");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $busyIntervals = [];
        while ($row = $result->fetch_assoc()) {
            $reservationStart = strtotime("$date " . $row['reservation_time']);
            $reservationEnd = $reservationStart + ($row['duration'] * 60);
            $busyIntervals[] = [$reservationStart, $reservationEnd];
        }
        $stmt->close();
        
        // Generate available time slots
        $currentTime = $dayStartTs;
        while (($currentTime + ($duration * 60)) <= $dayEndTs) {
            $slotStart = $currentTime;
            $slotEnd = $currentTime + ($duration * 60);
            
            // Check if this slot conflicts with any existing reservation
            $overlaps = false;
            foreach ($busyIntervals as [$busyStart, $busyEnd]) {
                if ($slotStart < $busyEnd && $slotEnd > $busyStart) {
                    $overlaps = true;
                    break;
                }
            }
            
            if (!$overlaps) {
                $slots[] = date('H:i', $slotStart);
            }
            
            // Move to next possible slot (based on service duration)
            $currentTime += $duration * 60;
        }
    }
    
    echo json_encode($slots);
    exit;
}

// Regular service with employee (existing logic)
if (!$employee_id) {
    echo json_encode([]);
    exit;
}

// Get weekday (1=Monday, 7=Sunday)
$weekday = date('N', strtotime($date));

// Get employee working hours for this day
$stmt = $mysqli->prepare("SELECT start_time, end_time FROM employee_working_hours WHERE employee_id=? AND weekday=?");
$stmt->bind_param("ii", $employee_id, $weekday);
$stmt->execute();
$stmt->bind_result($start_time, $end_time);
if (!$stmt->fetch()) {
    // No working hours for this day
    $stmt->close();
    echo json_encode([]);
    exit;
}
$stmt->close();

// Convert working hours to timestamps
$dayStartTs = strtotime("$date $start_time");
$dayEndTs   = strtotime("$date $end_time");

// Get existing reservations for this employee on this date
$stmt = $mysqli->prepare("
    SELECT r.reservation_time, s.duration 
    FROM reservations r
    JOIN services s ON s.id = r.service_id
    WHERE r.employee_id = ? AND r.reservation_date = ?
    ORDER BY r.reservation_time
");
$stmt->bind_param("is", $employee_id, $date);
$stmt->execute();
$result = $stmt->get_result();
$busyIntervals = [];
while ($row = $result->fetch_assoc()) {
    $reservationStart = strtotime("$date " . $row['reservation_time']);
    $reservationEnd = $reservationStart + ($row['duration'] * 60);
    $busyIntervals[] = [$reservationStart, $reservationEnd];
}
$stmt->close();

// Generate available time slots based on service duration
// Each slot must fit the service duration within working hours
$slots = [];

// Start from the beginning of working hours
$currentTime = $dayStartTs;

// Generate slots until we can't fit a complete service
while (($currentTime + ($duration * 60)) <= $dayEndTs) {
    $slotStart = $currentTime;
    $slotEnd = $currentTime + ($duration * 60);

    // Check if this slot conflicts with any existing reservation
    $overlaps = false;
    foreach ($busyIntervals as [$busyStart, $busyEnd]) {
        if ($slotStart < $busyEnd && $slotEnd > $busyStart) {
            $overlaps = true;
            break;
        }
    }
    
    if (!$overlaps) {
        $slots[] = date('H:i', $slotStart);
    }
    
    // Move to next possible slot (based on service duration)
    // This ensures we don't have overlapping slots for the same service
    $currentTime += $duration * 60;
}

echo json_encode($slots);
?>
