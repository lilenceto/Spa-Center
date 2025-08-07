<?php
session_start();
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
    $email = trim($_POST["email"]);
    $pass = $_POST["password"];

    $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $name, $pass_hash);
        $stmt->fetch();
        if (password_verify($pass, $pass_hash)) {
            $_SESSION["user_id"] = $id;
            $_SESSION["user_name"] = $name;
            header("Location: index.php"); // или друга защитена страница
            exit();
        } else {
            $message = "Грешна парола!";
        }
    } else {
        $message = "Няма такъв имейл!";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Вход</title>
</head>
<body>
    <h2>Вход</h2>
    <?php if($message) echo "<p><b>$message</b></p>"; ?>
    <form method="POST" action="">
        <label>Имейл:</label><br>
        <input type="email" name="email" required><br><br>
        <label>Парола:</label><br>
        <input type="password" name="password" required><br><br>
        <button type="submit">Вход</button>
    </form>
    <br>
    <a href="register.php">Нямаш акаунт? Регистрирай се тук</a>
</body>
</html>
