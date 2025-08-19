<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "spa_center");
if ($mysqli->connect_error) die("DB error: ".$mysqli->connect_error);
$mysqli->set_charset("utf8");

$service_id = intval($_GET['id']);

// Взимаме данни за услугата
$stmt = $mysqli->prepare("
    SELECT s.name, s.description, s.duration, s.price, c.name AS category_name
    FROM services s
    JOIN service_categories c ON c.id = s.category_id
    WHERE s.id=?
");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$stmt->bind_result($name, $description, $duration, $price, $category_name);

if(!$stmt->fetch()) {
    die("Няма такава услуга.");
}
$stmt->close();

require_once __DIR__."/header.php";
?>

<div class="container">
    <div class="service-detail">
        <h1><?= htmlspecialchars($name) ?></h1>
        <img src="images/service_<?= $service_id ?>.jpg" 
             alt="<?= htmlspecialchars($name) ?>" class="service-img">
        <p><strong>Категория:</strong> <?= htmlspecialchars($category_name) ?></p>
        <p><?= nl2br(htmlspecialchars($description)) ?></p>
        <p><strong>Продължителност:</strong> <?= $duration ?> мин.</p>
        <p><strong>Цена:</strong> <?= number_format($price, 2) ?> лв.</p>

        <?php if (!empty($_SESSION['user_id'])): ?>
            <a href="add_reservation.php?service_id=<?= $service_id ?>" class="btn">
                Запази час
            </a>
        <?php else: ?>
            <p>
                <a href="login.php?next=add_reservation.php?service_id=<?= $service_id ?>">Влез</a> 
                или 
                <a href="register.php?next=add_reservation.php?service_id=<?= $service_id ?>">Регистрирай се</a>, 
                за да направиш резервация.
            </p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__."/footer.php"; ?>
