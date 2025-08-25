<?php
require_once "db.php";

$service_id = intval($_GET['service_id'] ?? 0);

$sql = "
SELECT e.id, e.name, e.role
FROM employees e
JOIN services s ON s.category_id = e.category_id
WHERE s.id = ?
ORDER BY e.name
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();

$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}

echo json_encode($employees);
?>
