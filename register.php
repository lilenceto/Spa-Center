<?php
session_start();
$mysqli = new mysqli("localhost","root","","spa_center");
if ($mysqli->connect_error) die("DB грешка: ".$mysqli->connect_error);
$mysqli->set_charset("utf8");

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $password2 = $_POST['password2'];
    $next = $_POST['next'] ?? 'index.php';

    if (strlen($name) < 2) $error = "Името е твърде късо.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = "Невалиден имейл.";
    elseif (!preg_match('/^[0-9]{9,15}$/', $phone)) $error = "Телефонът трябва да съдържа само цифри (9–15).";
    elseif ($password !== $password2) $error = "Паролите не съвпадат.";
    else {
        // проверка за вече съществуващ имейл
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s",$email);
        $stmt->execute(); $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Имейлът вече е регистриран.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $role = 'client';
            $stmt = $mysqli->prepare("INSERT INTO users (name,email,phone,password,role) VALUES (?,?,?,?,?)");
            $stmt->bind_param("sssss",$name,$email,$phone,$hash,$role);
            if ($stmt->execute()) {
                $uid = $stmt->insert_id;
                // авто-логин
                $_SESSION['user_id']=$uid;
                $_SESSION['user_name']=$name;
                $_SESSION['role']=$role;
                if (preg_match('~^(https?:)?//~',$next)) $next="index.php";
                header("Location: ".$next);
                exit;
            } else {
                $error="Грешка при регистрация: ".$mysqli->error;
            }
        }
        $stmt->close();
    }
}
require_once __DIR__.'/header.php';
?>
<h2>Регистрация</h2>
<?php if($error): ?><p style="color:red"><?=htmlspecialchars($error)?></p><?php endif; ?>

<form method="post">
  <label>Име: <input type="text" name="name" required></label><br><br>
  <label>Имейл: <input type="email" name="email" required></label><br><br>
  <label>Телефон: <input type="text" name="phone" required></label><br><br>
  <label>Парола: <input type="password" name="password" required></label><br><br>
  <label>Повтори парола: <input type="password" name="password2" required></label><br><br>
  <input type="hidden" name="next" value="<?= htmlspecialchars($_GET['next'] ?? 'index.php') ?>">
  <button type="submit">Регистрация</button>
</form>

<p>Вече имаш акаунт? <a href="login.php?next=<?=urlencode($_GET['next'] ?? 'index.php')?>">Вход</a></p>
<?php require_once __DIR__.'/footer.php'; ?>
