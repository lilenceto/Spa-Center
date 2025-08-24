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

<!-- Register Page Container -->
<div class="page-container">
  <div class="auth-container">
    <!-- Lotus Logo -->
    <div class="auth-logo">
      <div class="lotus-icon">
        <i class="fas fa-spa"></i>
      </div>
      <h1>Lotus Temple</h1>
      <p>Begin your wellness journey with us</p>
    </div>

    <!-- Register Form -->
    <div class="auth-form">
      <h2>Create Account</h2>
      
      <?php if($error): ?>
        <div class="error-message">
          <i class="fas fa-exclamation-circle"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="post">
        <div class="form-group">
          <label for="name">
            <i class="fas fa-user"></i> Full Name
          </label>
          <input 
            type="text" 
            id="name"
            name="name" 
            placeholder="Enter your full name"
            required
            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
          >
        </div>

        <div class="form-group">
          <label for="email">
            <i class="fas fa-envelope"></i> Email Address
          </label>
          <input 
            type="email" 
            id="email"
            name="email" 
            placeholder="Enter your email"
            required
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
          >
        </div>

        <div class="form-group">
          <label for="phone">
            <i class="fas fa-phone"></i> Phone Number
          </label>
          <input 
            type="text" 
            id="phone"
            name="phone" 
            placeholder="Enter your phone number"
            required
            value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
          >
        </div>

        <div class="form-group">
          <label for="password">
            <i class="fas fa-lock"></i> Password
          </label>
          <input 
            type="password" 
            id="password"
            name="password" 
            placeholder="Create a strong password"
            required
          >
        </div>

        <div class="form-group">
          <label for="password2">
            <i class="fas fa-lock"></i> Confirm Password
          </label>
          <input 
            type="password" 
            id="password2"
            name="password2" 
            placeholder="Confirm your password"
            required
          >
        </div>

        <input type="hidden" name="next" value="<?= htmlspecialchars($_GET['next'] ?? 'index.php') ?>">
        
        <button type="submit" class="auth-submit">
          <i class="fas fa-user-plus"></i> Create Account
        </button>
      </form>

      <div class="auth-links">
        <p>Already have an account? 
          <a href="login.php?next=<?= urlencode($_GET['next'] ?? 'index.php') ?>">
            Sign In
          </a>
        </p>
      </div>
    </div>
  </div>
</div>

<style>
  .auth-container {
    max-width: 500px;
    margin: 2rem auto;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 25px;
    padding: 3rem 2rem;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    position: relative;
    overflow: hidden;
  }

  .auth-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(212, 175, 55, 0.1), rgba(15, 76, 58, 0.1));
    z-index: -1;
  }

  .auth-logo {
    text-align: center;
    margin-bottom: 2.5rem;
  }

  .lotus-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #d4af37, #b8941f);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    box-shadow: 0 8px 25px rgba(212, 175, 55, 0.3);
  }

  .lotus-icon i {
    font-size: 2.5rem;
    color: #0f4c3a;
  }

  .auth-logo h1 {
    font-family: 'Playfair Display', serif;
    font-size: 2.5rem;
    font-weight: 700;
    color: #d4af37;
    margin-bottom: 0.5rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
  }

  .auth-logo p {
    color: #f8f9fa;
    font-size: 1rem;
    opacity: 0.8;
  }

  .auth-form h2 {
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    font-weight: 600;
    color: #f8f9fa;
    text-align: center;
    margin-bottom: 2rem;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
  }

  .error-message {
    background: rgba(231, 76, 60, 0.2);
    border: 1px solid rgba(231, 76, 60, 0.4);
    color: #ff6b6b;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
  }

  .form-group {
    margin-bottom: 1.5rem;
  }

  .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #d4af37;
    font-weight: 500;
    font-size: 0.95rem;
  }

  .form-group label i {
    margin-right: 0.5rem;
    width: 16px;
  }

  .form-group input {
    width: 100%;
    padding: 1rem;
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 15px;
    background: rgba(255, 255, 255, 0.1);
    color: #f8f9fa;
    font-size: 1rem;
    transition: all 0.3s ease;
  }

  .form-group input:focus {
    outline: none;
    border-color: #d4af37;
    box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
    background: rgba(255, 255, 255, 0.15);
  }

  .form-group input::placeholder {
    color: rgba(248, 249, 250, 0.5);
  }

  .auth-submit {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #d4af37, #b8941f);
    color: #0f4c3a;
    border: none;
    border-radius: 15px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
  }

  .auth-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
    background: linear-gradient(135deg, #b8941f, #d4af37);
  }

  .auth-links {
    text-align: center;
    line-height: 1.6;
  }

  .auth-links p {
    color: #f8f9fa;
    margin-bottom: 1rem;
    opacity: 0.9;
  }

  .auth-links a {
    color: #d4af37;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
  }

  .auth-links a:hover {
    color: #f8f9fa;
    text-decoration: underline;
  }

  /* Floating Elements */
  .auth-container::after {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 100px;
    height: 100px;
    background: radial-gradient(circle, rgba(212, 175, 55, 0.1), transparent);
    border-radius: 50%;
    animation: float 6s ease-in-out infinite;
  }

  @keyframes float {
    0%, 100% {
      transform: translateY(0px);
    }
    50% {
      transform: translateY(-20px);
    }
  }

  /* Responsive */
  @media (max-width: 768px) {
    .auth-container {
      margin: 1rem;
      padding: 2rem 1.5rem;
    }
    
    .auth-logo h1 {
      font-size: 2rem;
    }
    
    .auth-form h2 {
      font-size: 1.5rem;
    }
  }
</style>

<?php require_once __DIR__.'/footer.php'; ?>
