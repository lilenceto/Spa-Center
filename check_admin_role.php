<?php
// check_admin_role.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['is_admin' => false]);
    exit;
}

$mysqli = new mysqli("localhost", "root", "", "spa_center");
if ($mysqli->connect_error) {
    echo json_encode(['is_admin' => false]);
    exit;
}

// Check if user has admin role
$stmt = $mysqli->prepare("
    SELECT COUNT(*) as is_admin 
    FROM user_roles ur 
    JOIN roles r ON ur.role_id = r.id 
    WHERE ur.user_id = ? AND r.name = 'admin'
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$isAdmin = $row['is_admin'] > 0;

$stmt->close();
$mysqli->close();

echo json_encode(['is_admin' => $isAdmin]);
?>
