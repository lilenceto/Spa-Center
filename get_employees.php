<?php
require_once "db.php";

$service_id = intval($_GET['service_id'] ?? 0);

$sql = "
SELECT e.id, e.name
FROM employees e
JOIN employee_services es ON es.employee_id = e.id
WHERE es.service_id = ?
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
