<?php
session_start();
$mysqli = new mysqli("localhost","root","","spa_center");
if ($mysqli->connect_error) die("DB грешка: " . $mysqli->connect_error);
$mysqli->set_charset("utf8");

// Ако вече е логнат → към index
if (!empty($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $next = $_POST['next'] ?? 'index.php';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Невалиден имейл!";
    } else {
        // Вземаме потребител + роля чрез JOIN
        $sql = "
            SELECT u.id, u.name, u.password, r.name AS role
            FROM users u
            JOIN user_roles ur ON ur.user_id = u.id
            JOIN roles r ON r.id = ur.role_id
            WHERE u.email = ?
            LIMIT 1
        ";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($uid, $uname, $hash, $role);

        if ($stmt->fetch()) {
            if (password_verify($password, $hash)) {
                $_SESSION['user_id'] = $uid;
                $_SESSION['user_name'] = $uname;
                $_SESSION['role'] = $role; // client, employee, admin
                if (preg_match('~^(https?:)?//~',$next)) $next = "index.php";
                header("Location: ".$next);
                exit;
            } else {
                $error = "Грешна парола.";
            }
        } else {
            $error = "Потребителят не е намерен.";
        }
        $stmt->close();
    }
}

require_once __DIR__.'/header.php';
?>
<h2>Вход</h2>
<?php if ($error): ?>
  <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="post">
  <label>Имейл: <input type="email" name="email" required></label><br><br>
  <label>Парола: <input type="password" name="password" required></label><br><br>
  <input type="hidden" name="next" value="<?= htmlspecialchars($_GET['next'] ?? 'index.php') ?>">
  <button type="submit">Вход</button>
</form>

<p>Нямаш акаунт? 
  <a href="register.php?next=<?= urlencode($_GET['next'] ?? 'index.php') ?>">Регистрация</a>
</p>

<?php require_once __DIR__.'/footer.php'; ?>
