<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "spa_center";
$message = "";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Грешка при връзка: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Получаване и филтриране на данните
    $name = $conn->real_escape_string($_POST["name"]);
    $description = $conn->real_escape_string($_POST["description"]);
    $duration = intval($_POST["duration"]);
    $price = floatval($_POST["price"]);
    $category = $conn->real_escape_string($_POST["category"]);

    // Добавяне в базата
    $sql = "INSERT INTO services (name, description, duration, price, category) VALUES ('$name', '$description', $duration, $price, '$category')";
    if ($conn->query($sql) === TRUE) {
        $message = "Услугата е добавена успешно!";
    } else {
        $message = "Грешка: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Добавяне на нова услуга</title>
</head>
<body>
    <h2>Добавяне на нова услуга</h2>
    <?php if($message) echo "<p><b>$message</b></p>"; ?>
    <form method="POST" action="">
        <label>Име на услуга:</label><br>
        <input type="text" name="name" required><br><br>
        
        <label>Описание:</label><br>
        <textarea name="description" required></textarea><br><br>
        
        <label>Времетраене (минути):</label><br>
        <input type="number" name="duration" min="1" required><br><br>
        
        <label>Цена:</label><br>
        <input type="number" step="0.01" name="price" min="0" required><br><br>
        
        <label>Категория:</label><br>
        <input type="text" name="category" required><br><br>
        
        <button type="submit">Добави услуга</button>
    </form>
    <br>
    <a href="services.php">Виж всички услуги</a>
</body>
</html>
