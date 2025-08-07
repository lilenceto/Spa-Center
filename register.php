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

// Функция за проверка на телефон (само цифри, 10 знака)
function valid_phone($phone) {
    return preg_match('/^[0-9]{10}$/', $phone);
}

// Функция за проверка на име (само букви и интервали)
function valid_name($name) {
    return preg_match('/^[\p{L} ]+$/u', $name); // Unicode букви
}

// Ако формата е изпратена
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $pass1 = $_POST["password"];
    $pass2 = $_POST["confirm_password"];

    // Валидация
    if (!valid_name($name) || mb_strlen($name) < 3) {
        $message = "Моля въведете валидно име (само букви, поне 3 символа)!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Невалиден имейл!";
    } elseif (!valid_phone($phone)) {
        $message = "Телефонът трябва да съдържа точно 10 цифри!";
    } elseif (strlen($pass1) < 6) {
        $message = "Паролата трябва да е поне 6 символа!";
    } elseif ($pass1 !== $pass2) {
        $message = "Паролите не съвпадат!";
    } else {
        // Проверка за уникален имейл
        $check_email = $conn->prepare("SELECT id FROM users WHERE email=?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $check_email->store_result();
        if ($check_email->num_rows > 0) {
            $message = "Този имейл вече е регистриран!";
        } else {
            // Хеширане на паролата
            $pass_hash = password_hash($pass1, PASSWORD_BCRYPT);

            // Въвеждане в базата
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $pass_hash, $phone);

            if ($stmt->execute()) {
                $message = "Регистрацията е успешна! Може да влезете.";
            } else {
                $message = "Грешка при регистрация: " . $conn->error;
            }
        }
        $check_email->close();
    }
}
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <script>
        // JS: Клиентска валидация
        function validateForm() {
            let name = document.forms["regForm"]["name"].value.trim();
            let email = document.forms["regForm"]["email"].value.trim();
            let phone = document.forms["regForm"]["phone"].value.trim();
            let pass1 = document.forms["regForm"]["password"].value;
            let pass2 = document.forms["regForm"]["confirm_password"].value;
            let namePattern = /^[\p{L} ]+$/u;
            let phonePattern = /^[0-9]{10}$/;
            if (!namePattern.test(name) || name.length < 3) {
                alert("Въведете валидно име (само букви, поне 3 символа)!");
                return false;
            }
            if (!email.match(/^[^@]+@[^@]+\.[a-z]{2,}$/i)) {
                alert("Невалиден имейл!");
                return false;
            }
            if (!phonePattern.test(phone)) {
                alert("Телефонът трябва да съдържа точно 10 цифри!");
                return false;
            }
            if (pass1.length < 6) {
                alert("Паролата трябва да е поне 6 символа!");
                return false;
            }
            if (pass1 !== pass2) {
                alert("Паролите не съвпадат!");
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
    <h2>Регистрация</h2>
    <?php if($message) echo "<p><b>$message</b></p>"; ?>
    <form name="regForm" method="POST" action="" onsubmit="return validateForm()">
        <label>Име:</label><br>
        <input type="text" name="name" required><br><br>
        <label>Имейл:</label><br>
        <input type="email" name="email" required><br><br>
        <label>Телефон (10 цифри):</label><br>
        <input type="text" name="phone" required maxlength="10"><br><br>
        <label>Парола:</label><br>
        <input type="password" name="password" required><br><br>
        <label>Потвърди паролата:</label><br>
        <input type="password" name="confirm_password" required><br><br>
        <button type="submit">Регистрация</button>
    </form>
    <br>
    <a href="login.php">Имаш акаунт? Влез тук</a>
</body>
</html>
