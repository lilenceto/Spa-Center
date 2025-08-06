<?php
// Данни за достъп до MySQL базата
$servername = "localhost";
$username = "root";       // Смени, ако имаш друг потребител
$password = "";           // Ако имаш парола, попълни тук
$dbname = "spa_center";   // Смени, ако базата се казва различно

// Създаване на връзка
$conn = new mysqli($servername, $username, $password, $dbname);

// Проверка за грешка във връзката
if ($conn->connect_error) {
    die("Грешка при връзка: " . $conn->connect_error);
}
echo "Връзката е успешна!";

// Затваряне на връзката (по желание)
$conn->close();
?>
