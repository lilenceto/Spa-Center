<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Можеш да добавиш повече потребителски данни, ако са нужни
$user_name = $_SESSION["user_name"];
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>SPA Center - Начало</title>
</head>
<body>
    <h2>Добре дошъл, <?php echo htmlspecialchars($user_name); ?>!</h2>
    <ul>
        <li><a href="services.php">Виж всички услуги</a></li>
        <li><a href="add_reservation.php">Направи нова резервация</a></li>
        <li><a href="reservations.php">Моите резервации</a></li>
        <li><a href="logout.php">Изход</a></li>
    </ul>

    <p>Това е началната страница на твоя SPA център. Тук можеш да резервираш услуги, да разглеждаш своите резервации и да управляваш профила си.</p>
</body>
</html>
