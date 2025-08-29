<?php
/**
 * Automatic Reminder System for Spa Center
 * This script sends reminder emails for appointments scheduled for tomorrow
 * Can be run via cron job daily at 9 AM
 */

require_once "db.php";
require_once "email_helper_working.php";

// Initialize email helper
$emailHelper = new EmailHelperWorking();

// Get tomorrow's date
$tomorrow = date('Y-m-d', strtotime('+1 day'));

echo "Sending reminders for appointments on: $tomorrow\n";

// Get all approved reservations for tomorrow
$stmt = $mysqli->prepare("
            SELECT r.id, r.reservation_time, s.name as service_name, s.duration,
               u.name as user_name, u.email as user_email, e.name as employee_name
    FROM reservations r
    JOIN services s ON s.id = r.service_id
    JOIN users u ON u.id = r.user_id
    LEFT JOIN employees e ON e.id = r.employee_id
    WHERE r.reservation_date = ? AND r.status = 'Approved'
    ORDER BY r.reservation_time
");

$stmt->bind_param("s", $tomorrow);
$stmt->execute();
$result = $stmt->get_result();

$sent_count = 0;
$failed_count = 0;

while ($row = $result->fetch_assoc()) {
    $reservation_data = [
        'service_name' => $row['service_name'],
        'date' => $tomorrow,
        'time' => $row['reservation_time'],
        'duration' => $row['duration'],
        'employee_name' => $row['employee_name']
    ];
    
    // Send reminder email
    if ($emailHelper->sendAppointmentReminder($row['user_email'], $row['user_name'], $reservation_data)) {
        echo "✓ Reminder sent to {$row['user_name']} ({$row['user_email']}) for {$row['service_name']} at {$row['reservation_time']}\n";
        $sent_count++;
    } else {
        echo "✗ Failed to send reminder to {$row['user_name']} ({$row['user_email']})\n";
        $failed_count++;
    }
}

$stmt->close();
$mysqli->close();

echo "\n=== Summary ===\n";
echo "Total reminders sent: $sent_count\n";
echo "Failed reminders: $failed_count\n";
echo "Script completed at: " . date('Y-m-d H:i:s') . "\n";
?>

