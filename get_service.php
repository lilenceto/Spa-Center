<?php
// get_service.php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if user has admin role
$mysqli = new mysqli("localhost", "root", "", "spa_center");
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit();
}

$adminCheckStmt = $mysqli->prepare("
    SELECT COUNT(*) as is_admin 
    FROM user_roles ur 
    JOIN roles r ON ur.role_id = r.id 
    WHERE ur.user_id = ? AND r.name = 'admin'
");
$adminCheckStmt->bind_param("i", $_SESSION['user_id']);
$adminCheckStmt->execute();
$adminResult = $adminCheckStmt->get_result();
$adminCheck = $adminResult->fetch_assoc();

if ($adminCheck['is_admin'] == 0) {
    $adminCheckStmt->close();
    $mysqli->close();
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}
$adminCheckStmt->close();

// Get service ID from request
$service_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($service_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid service ID']);
    exit();
}

// Fetch service data
$stmt = $mysqli->prepare("SELECT id, name, description, duration, price FROM services WHERE id = ?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $mysqli->close();
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Service not found']);
    exit();
}

$service = $result->fetch_assoc();
$stmt->close();
$mysqli->close();

// Return service data as JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'service' => $service
]);
?>
