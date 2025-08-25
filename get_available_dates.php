<?php
require_once "db.php";

$employee_id = (int)($_GET['employee_id'] ?? 0);
$service_id = (int)($_GET['service_id'] ?? 0);

if (!$service_id) {
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

// Check if this is a pool service (category 4, specific services)
$isPoolService = false;
if ($category_id == 4) {
    $stmt = $mysqli->prepare("SELECT name FROM services WHERE id=?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $stmt->bind_result($service_name);
    $stmt->fetch();
    $stmt->close();
    
    // Check if it's the pool service
    if (strpos(strtolower($service_name), 'pool') !== false) {
        $isPoolService = true;
    }
}

if ($isPoolService) {
    // For pool services, generate dates for 7 days a week, 9 AM to 9 PM
    $availableDates = [];
    $currentDate = date('Y-m-d');
    
    for ($i = 0; $i < 30; $i++) {
        $checkDate = date('Y-m-d', strtotime($currentDate . " +$i days"));
        $weekday = date('N', strtotime($checkDate)); // 1=Monday, 7=Sunday
        
        // Pool is available every day
        $availableDates[] = [
            'date' => $checkDate,
            'weekday' => $weekday,
            'start_time' => '09:00:00',
            'end_time' => '21:00:00'
        ];
    }
    
    echo json_encode($availableDates);
    exit;
}

// For SPA services without employee, or regular services with employee
if ($category_id == 4 && !$employee_id) {
    // SPA service without employee - get all available dates from all SPA employees
    $availableDates = [];
    $currentDate = date('Y-m-d');
    
    for ($i = 0; $i < 30; $i++) {
        $checkDate = date('Y-m-d', strtotime($currentDate . " +$i days"));
        $weekday = date('N', strtotime($checkDate)); // 1=Monday, 7=Sunday
        
        // Check if any SPA employee works on this day
        $stmt = $mysqli->prepare("
            SELECT DISTINCT w.start_time, w.end_time
            FROM employee_working_hours w
            JOIN employees e ON e.id = w.employee_id
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
            while ($row = $result->fetch_assoc()) {
                $startTimes[] = $row['start_time'];
                $endTimes[] = $row['end_time'];
            }
            $stmt->close();
            
            $earliestStart = min($startTimes);
            $latestEnd = max($endTimes);
            
            // Check if there's enough time for at least one service
            $startTs = strtotime("$checkDate $earliestStart");
            $endTs = strtotime("$checkDate $latestEnd");
            
            if (($endTs - $startTs) >= ($duration * 60)) {
                $availableDates[] = [
                    'date' => $checkDate,
                    'weekday' => $weekday,
                    'start_time' => $earliestStart,
                    'end_time' => $latestEnd
                ];
            }
        } else {
            $stmt->close();
        }
    }
    
    echo json_encode($availableDates);
    exit;
}

// Regular service with employee (existing logic)
if (!$employee_id) {
    echo json_encode([]);
    exit;
}

// Get employee working hours for all weekdays
$stmt = $mysqli->prepare("SELECT weekday, start_time, end_time FROM employee_working_hours WHERE employee_id=? ORDER BY weekday");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$workingDays = [];
while ($row = $result->fetch_assoc()) {
    $workingDays[$row['weekday']] = [
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time']
    ];
}
$stmt->close();

if (empty($workingDays)) {
    echo json_encode([]);
    exit;
}

// Generate available dates for next 30 days
$availableDates = [];
$currentDate = date('Y-m-d');
for ($i = 0; $i < 30; $i++) {
    $checkDate = date('Y-m-d', strtotime($currentDate . " +$i days"));
    $weekday = date('N', strtotime($checkDate)); // 1=Monday, 7=Sunday
    
    if (isset($workingDays[$weekday])) {
        $startTime = $workingDays[$weekday]['start_time'];
        $endTime = $workingDays[$weekday]['end_time'];
        
        // Calculate if there's enough time for at least one service
        $startTs = strtotime("$checkDate $startTime");
        $endTs = strtotime("$checkDate $endTime");
        
        if (($endTs - $startTs) >= ($duration * 60)) {
            // Check existing reservations for this date
            $stmt = $mysqli->prepare("
                SELECT r.reservation_time, s.duration 
                FROM reservations r
                JOIN services s ON s.id = r.service_id
                WHERE r.employee_id = ? AND r.reservation_date = ?
                ORDER BY r.reservation_time
            ");
            $stmt->bind_param("is", $employee_id, $checkDate);
            $stmt->execute();
            $result = $stmt->get_result();
            $busyIntervals = [];
            while ($row = $result->fetch_assoc()) {
                $reservationStart = strtotime("$checkDate " . $row['reservation_time']);
                $reservationEnd = $reservationStart + ($row['duration'] * 60);
                $busyIntervals[] = [$reservationStart, $reservationEnd];
            }
            $stmt->close();
            
            // Check if there's at least one free slot based on service duration
            $hasFreeSlot = false;
            $currentTime = $startTs;
            
            while ($currentTime + ($duration * 60) <= $endTs) {
                $slotStart = $currentTime;
                $slotEnd = $currentTime + ($duration * 60);
                
                $overlaps = false;
                foreach ($busyIntervals as [$busyStart, $busyEnd]) {
                    if ($slotStart < $busyEnd && $slotEnd > $busyStart) {
                        $overlaps = true;
                        break;
                    }
                }
                
                if (!$overlaps) {
                    $hasFreeSlot = true;
                    break;
                }
                
                // Move to next possible slot (based on service duration)
                $currentTime += $duration * 60;
            }
            
            if ($hasFreeSlot) {
                $availableDates[] = [
                    'date' => $checkDate,
                    'weekday' => $weekday,
                    'start_time' => $startTime,
                    'end_time' => $endTime
                ];
            }
        }
    }
}

echo json_encode($availableDates);
?>
