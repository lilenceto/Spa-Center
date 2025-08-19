<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION["user_name"];
include 'header.php';
?>
<div class="container">
    <h2>Добре дошъл, <?php echo htmlspecialchars($user_name); ?>!</h2>
    <ul>
        <li><a href="services.php">Виж всички услуги</a></li>
        <li><a href="add_reservation.php">Направи нова резервация</a></li>
        <li><a href="reservations.php">Моите резервации</a></li>
        <li><a href="logout.php">Изход</a></li>
    </ul>
    <p>Това е твоето лично табло за управление. Тук можеш да управляваш резервациите си и профила си.</p>
</div>
<?php include 'footer.php'; ?>
