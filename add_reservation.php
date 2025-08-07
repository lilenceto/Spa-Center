<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "spa_center";
$message = "";

// Връзка с базата
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Грешка при връзка: " . $conn->connect_error);
}

// Зареждаме услугите от базата за dropdown менюто
$services = [];
$sql = "SELECT id, name FROM services";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
}

// Ако формата е изпратена
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service_id = intval($_POST["service_id"]);
    $user_id = 1; // ТУК ЗА СЕГА слагаме фиксирано user_id = 1, после ще има login!
    $reservation_date = $conn->real_escape_string($_POST["reservation_date"]);
    $reservation_time = $conn->real_escape_string($_POST["reservation_time"]);
    $status = "pending";

    $sql = "INSERT INTO reservations (user_id, service_id, reservation_date, reservation_time, status)
            VALUES ($user_id, $service_id, '$reservation_date', '$reservation_time', '$status')";
    if ($conn->query($sql) === TRUE) {
        $message = "Резервацията е записана успешно!";
    } else {
        $message = "Грешка: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Създаване на резервация</title>
</head>
<body>
    <h2>Създаване на резервация</h2>
    <?php if($message) echo "<p><b>$message</b></p>"; ?>
    <form method="POST" action="">
        <label>Услуга:</label><br>
        <select name="service_id" required>
            <option value="">--Избери услуга--</option>
            <?php foreach ($services as $service): ?>
                <option value="<?php echo $service['id']; ?>"><?php echo htmlspecialchars($service['name']); ?></option>
            <?php endforeach; ?>
        </select><br><br>
        <label>Дата:</label><br>
        <input type="date" name="reservation_date" required><br><br>
        <label>Час:</label><br>
        <input type="time" name="reservation_time" required><br><br>
        <button type="submit">Резервирай</button>
    </form>
    <br>
    <a href="reservations.php">Виж всички резервации</a>
</body>
</html>
