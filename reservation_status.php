<?php
// reservation_status.php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$uid = (int)($_SESSION['user_id'] ?? 0);

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$id || !$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Debug information
error_log("Processing request: ID=$id, Action=$action, Role=$role, UserID=$uid");

$mysqli = new mysqli("localhost", "root", "", "spa_center");
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}

// Check user role from user_roles table
$role = 'client'; // default
if ($uid) {
    $roleStmt = $mysqli->prepare("
        SELECT r.name as role_name
        FROM user_roles ur 
        JOIN roles r ON ur.role_id = r.id 
        WHERE ur.user_id = ?
    ");
    $roleStmt->bind_param("i", $uid);
    $roleStmt->execute();
    $roleResult = $roleStmt->get_result();
    $roleRow = $roleResult->fetch_assoc();
    $role = $roleRow['role_name'] ?? 'client';
    $roleStmt->close();
}

// Get reservation details
$stmt = $mysqli->prepare("SELECT user_id, status, reservation_date, reservation_time FROM reservations WHERE id = ?");
if (!$stmt) {
    error_log("Prepare statement failed: " . $mysqli->error);
    $mysqli->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database prepare error']);
    exit;
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    $stmt->close();
    $mysqli->close();
    error_log("Reservation not found: ID=$id");
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Reservation not found']);
    exit;
}

$reservation = $result->fetch_assoc();
$stmt->close();

$resUserId = (int)$reservation['user_id'];
$oldStatus = $reservation['status'];
$reservationDateTime = $reservation['reservation_date'] . ' ' . $reservation['reservation_time'];

error_log("Reservation details: UserID=$resUserId, Status=$oldStatus, DateTime=$reservationDateTime");

// Security checks
if ($role === 'client') {
    // Clients can only cancel their own reservations
    if ($resUserId !== $uid) {
        $mysqli->close();
        error_log("Client access denied: ClientID=$uid, ReservationUserID=$resUserId");
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Clients can only cancel 'Awaiting' or 'Approved' reservations
    if (!in_array($oldStatus, ['Awaiting', 'Approved'])) {
        $mysqli->close();
        error_log("Client cannot cancel status: $oldStatus");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot cancel this reservation']);
        exit;
    }
    
    // Clients can cancel any future reservation (more flexible rule)
    $startsIn = strtotime($reservationDateTime) - time();
    if ($startsIn <= 0) {
        $mysqli->close();
        error_log("Client cannot cancel past reservation: $startsIn seconds before reservation");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot cancel past reservations']);
        exit;
    }
}

// Handle different actions
if ($action === 'cancel') {
    error_log("Processing cancel action for reservation ID: $id");
    
    // Start transaction to ensure data consistency
    $mysqli->begin_transaction();
    
    try {
        // First, delete any related records in reservation_status_history
        $deleteHistoryStmt = $mysqli->prepare("DELETE FROM reservation_status_history WHERE reservation_id = ?");
        if ($deleteHistoryStmt) {
            $deleteHistoryStmt->bind_param("i", $id);
            $deleteHistoryStmt->execute();
            $deleteHistoryStmt->close();
            error_log("Deleted related history records for reservation ID: $id");
        }
        
        // Now delete the main reservation
        $deleteStmt = $mysqli->prepare("DELETE FROM reservations WHERE id = ?");
        if (!$deleteStmt) {
            throw new Exception("Delete prepare failed: " . $mysqli->error);
        }
        
        $deleteStmt->bind_param("i", $id);
        
        if ($deleteStmt->execute()) {
            $deleteStmt->close();
            
            // Commit the transaction
            $mysqli->commit();
            $mysqli->close();
            
            error_log("Reservation cancelled successfully: ID=$id");
            echo json_encode([
                'success' => true, 
                'message' => 'Reservation cancelled and deleted successfully'
            ]);
        } else {
            throw new Exception("Delete failed: " . $deleteStmt->error);
        }
        
    } catch (Exception $e) {
        // Rollback the transaction on error
        $mysqli->rollback();
        $mysqli->close();
        
        error_log("Error during cancellation: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Error cancelling reservation: ' . $e->getMessage()
        ]);
    }
    
} elseif ($action === 'approve') {
    error_log("Processing approve action for reservation ID: $id");
    
    // Only staff/admin can approve
    if ($role === 'client') {
        $mysqli->close();
        error_log("Client attempted to approve reservation");
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Can only approve 'Awaiting' reservations
    if ($oldStatus !== 'Awaiting') {
        $mysqli->close();
        error_log("Cannot approve non-awaiting reservation: $oldStatus");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Can only approve awaiting reservations']);
        exit;
    }
    
    // Update status to 'Approved'
    $updateStmt = $mysqli->prepare("UPDATE reservations SET status = 'Approved' WHERE id = ?");
    if (!$updateStmt) {
        error_log("Update prepare failed: " . $mysqli->error);
        $mysqli->close();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database prepare error for update']);
        exit;
    }
    
    $updateStmt->bind_param("i", $id);
    
    if ($updateStmt->execute()) {
        $updateStmt->close();
        
        // Send email notification about status change
        require_once "email_helper_working.php";
        $emailHelper = new EmailHelperWorking();
        
        // Get reservation details for email
        $stmt = $mysqli->prepare("
            SELECT r.reservation_date, r.reservation_time, s.name as service_name, s.duration, 
                   u.name as user_name, u.email as user_email, e.name as employee_name
            FROM reservations r
            JOIN services s ON s.id = r.service_id
            JOIN users u ON u.id = r.user_id
            LEFT JOIN employees e ON e.id = r.employee_id
            WHERE r.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $reservation_data = [
                'service_name' => $row['service_name'],
                'date' => $row['reservation_date'],
                'time' => $row['reservation_time'],
                'duration' => $row['duration'],
                'employee_name' => $row['employee_name']
            ];
            
            // Send status update email
            $emailHelper->sendStatusUpdate(
                $row['user_email'], 
                $row['user_name'], 
                $reservation_data, 
                'Awaiting', 
                'Approved'
            );
        }
        $stmt->close();
        
        $mysqli->close();
        
        error_log("Reservation approved successfully: ID=$id");
        echo json_encode([
            'success' => true, 
            'message' => 'Reservation approved successfully'
        ]);
    } else {
        error_log("Update failed: " . $updateStmt->error);
        $updateStmt->close();
        $mysqli->close();
        
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Error approving reservation'
        ]);
    }
    
} elseif ($action === 'reject') {
    error_log("Processing reject action for reservation ID: $id");
    
    // Only staff/admin can reject
    if ($role === 'client') {
        $mysqli->close();
        error_log("Client attempted to reject reservation");
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Can only reject 'Awaiting' reservations
    if ($oldStatus !== 'Awaiting') {
        $mysqli->close();
        error_log("Cannot reject non-awaiting reservation: $oldStatus");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Can only reject awaiting reservations']);
        exit;
    }
    
    // Update status to 'Cancelled'
    $updateStmt = $mysqli->prepare("UPDATE reservations SET status = 'Cancelled' WHERE id = ?");
    if (!$updateStmt) {
        error_log("Update prepare failed: " . $mysqli->error);
        $mysqli->close();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database prepare error for update']);
        exit;
    }
    
    $updateStmt->bind_param("i", $id);
    
    if ($updateStmt->execute()) {
        $updateStmt->close();
        
        // Send email notification about cancellation
        require_once "email_helper_working.php";
        $emailHelper = new EmailHelperWorking();
        
        // Get reservation details for email
        $stmt = $mysqli->prepare("
            SELECT r.reservation_date, r.reservation_time, s.name as service_name, s.duration, 
                   u.name as user_name, u.email as user_email, e.name as employee_name
            FROM reservations r
            JOIN services s ON s.id = r.service_id
            JOIN users u ON u.id = r.user_id
            LEFT JOIN employees e ON e.id = r.employee_id
            WHERE r.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $reservation_data = [
                'service_name' => $row['service_name'],
                'date' => $row['reservation_date'],
                'time' => $row['reservation_time'],
                'duration' => $row['duration'],
                'employee_name' => $row['employee_name']
            ];
            
            // Send cancellation confirmation email
            $emailHelper->sendCancellationConfirmation(
                $row['user_email'], 
                $row['user_name'], 
                $reservation_data
            );
        }
        $stmt->close();
        
        $mysqli->close();
        
        error_log("Reservation rejected successfully: ID=$id");
        echo json_encode([
            'success' => true, 
            'message' => 'Reservation rejected successfully'
        ]);
    } else {
        error_log("Update failed: " . $updateStmt->error);
        $updateStmt->close();
        $mysqli->close();
        
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Error rejecting reservation'
        ]);
    }
    
} elseif ($action === 'status') {
    // Change reservation status
    if ($role !== 'admin') {
        $mysqli->close();
        error_log("Status change denied: Role=$role, UserID=$uid");
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only admins can change reservation status']);
        exit;
    }
    
    $newStatus = $_POST['status'] ?? '';
    if (!in_array($newStatus, ['Awaiting', 'Approved', 'Completed', 'Cancelled'])) {
        $mysqli->close();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status value']);
        exit;
    }
    
    if ($newStatus === $oldStatus) {
        $mysqli->close();
        echo json_encode(['success' => true, 'message' => 'Status unchanged']);
        exit;
    }
    
    // Update reservation status
    $updateStmt = $mysqli->prepare("UPDATE reservations SET status = ? WHERE id = ?");
    if (!$updateStmt) {
        error_log("Update prepare failed: " . $mysqli->error);
        $mysqli->close();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database update error']);
        exit;
    }
    
    $updateStmt->bind_param("si", $newStatus, $id);
    if ($updateStmt->execute()) {
        // Log status change in history
        $historyStmt = $mysqli->prepare("
            INSERT INTO reservation_status_history (reservation_id, old_status, new_status, changed_by) 
            VALUES (?, ?, ?, ?)
        ");
        if ($historyStmt) {
            $historyStmt->bind_param("issi", $id, $oldStatus, $newStatus, $uid);
            $historyStmt->execute();
            $historyStmt->close();
        }
        
        // Send email notification about status change
        require_once "email_helper_working.php";
        $emailHelper = new EmailHelperWorking();
        
        // Get reservation details for email
        $stmt = $mysqli->prepare("
            SELECT r.reservation_date, r.reservation_time, s.name as service_name, s.duration, 
                   u.name as user_name, u.email as user_email, e.name as employee_name
            FROM reservations r
            JOIN services s ON s.id = r.service_id
            JOIN users u ON u.id = r.user_id
            LEFT JOIN employees e ON e.id = r.employee_id
            WHERE r.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $reservation_data = [
                'service_name' => $row['service_name'],
                'date' => $row['reservation_date'],
                'time' => $row['reservation_time'],
                'duration' => $row['duration'],
                'employee_name' => $row['employee_name']
            ];
            
            // Send status update email
            $emailHelper->sendStatusUpdate(
                $row['user_email'], 
                $row['user_name'], 
                $reservation_data, 
                $oldStatus, 
                $newStatus
            );
        }
        $stmt->close();
        
        $updateStmt->close();
        $mysqli->close();
        
        error_log("Status changed successfully: ID=$id, Old=$oldStatus, New=$newStatus, By=$uid");
        echo json_encode(['success' => true, 'message' => "Status changed from $oldStatus to $newStatus"]);
    } else {
        $updateStmt->close();
        $mysqli->close();
        error_log("Status update failed: " . $mysqli->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
} elseif ($action === 'delete') {
    error_log("Processing delete action for reservation ID: $id");
    
    // Only staff/admin can delete
    if ($role === 'client') {
        $mysqli->close();
        error_log("Client attempted to delete reservation");
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Start transaction for safe deletion
    $mysqli->begin_transaction();
    
    try {
        // Delete related records first
        $deleteHistoryStmt = $mysqli->prepare("DELETE FROM reservation_status_history WHERE reservation_id = ?");
        if (!$deleteHistoryStmt) {
            throw new Exception("Failed to prepare history deletion: " . $mysqli->error);
        }
        
        $deleteHistoryStmt->bind_param("i", $id);
        if (!$deleteHistoryStmt->execute()) {
            throw new Exception("Failed to delete history: " . $deleteHistoryStmt->error);
        }
        $deleteHistoryStmt->close();
        
        // Delete the main reservation
        $deleteReservationStmt = $mysqli->prepare("DELETE FROM reservations WHERE id = ?");
        if (!$deleteReservationStmt) {
            throw new Exception("Failed to prepare reservation deletion: " . $mysqli->error);
        }
        
        $deleteReservationStmt->bind_param("i", $id);
        if (!$deleteReservationStmt->execute()) {
            throw new Exception("Failed to delete reservation: " . $deleteReservationStmt->error);
        }
        $deleteReservationStmt->close();
        
        // Commit transaction
        $mysqli->commit();
        
        error_log("Reservation deleted successfully: ID=$id");
        echo json_encode([
            'success' => true,
            'message' => 'Reservation deleted successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback on error
        $mysqli->rollback();
        error_log("Delete failed: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting reservation: ' . $e->getMessage()
        ]);
    }
    
    $mysqli->close();
    
} else {
    $mysqli->close();
    error_log("Invalid action: $action");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}
?>
