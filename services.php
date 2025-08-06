<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "spa_center";

// Връзка с базата
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Грешка при връзка: " . $conn->connect_error);
}

// Извличане на всички услуги
$sql = "SELECT id, name, description, duration, price, category FROM services";
$result = $conn->query($sql);

// HTML изход
echo "<h2>Списък с услуги</h2>";
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr>
            <th>ID</th>
            <th>Име</th>
            <th>Описание</th>
            <th>Времетраене (мин)</th>
            <th>Цена</th>
            <th>Категория</th>
          </tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>".$row['id']."</td>
                <td>".$row['name']."</td>
                <td>".$row['description']."</td>
                <td>".$row['duration']."</td>
                <td>".$row['price']."</td>
                <td>".$row['category']."</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "Няма намерени услуги.";
}
$conn->close();
?>
