<?php
session_start();
require_once "db.php";

if (empty($_SESSION['user_id'])) {
    $next = 'add_reservation.php';
    if (!empty($_GET['service_id'])) {
        $next .= '?service_id='.(int)$_GET['service_id'];
    }
    header('Location: login.php?next='.urlencode($next));
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// взимаме услугата ако е избрана
$service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

$service = null;
if ($service_id > 0) {
    $stmt = $mysqli->prepare("SELECT id, name, duration FROM services WHERE id=?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $service = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

include "header.php";
?>
<!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="UTF-8">
<title>Нова резервация</title>
<style>
body { font-family: 'Segoe UI', sans-serif; background: #f5f6fa; margin:0; padding:0;}
.container { max-width:600px; margin:30px auto; background:#fff; padding:25px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,.1);}
h2 { text-align:center; margin-bottom:20px; }
label { font-weight:bold; display:block; margin-top:15px; }
select, input[type=date], input[type=time], button {
    width:100%; padding:10px; margin-top:5px; border-radius:8px; border:1px solid #ccc;
}
button { background:#28a745; color:#fff; font-weight:bold; border:none; cursor:pointer; margin-top:20px; }
button:hover { background:#218838; }
</style>
<script>
function onDateChange() {
    const date = document.getElementById("reservation_date").value;
    const serviceId = <?= $service_id ?>;
    if(date && serviceId){
        window.location.href = "add_reservation.php?service_id=" + serviceId + "&date=" + date;
    }
}
</script>
</head>
<body>
<div class="container">
  <h2>Резервирай услуга</h2>
  <?php if ($service): ?>
    <form method="POST" action="save_reservation.php">
        <input type="hidden" name="service_id" value="<?= $service['id'] ?>">

        <label>Услуга:</label>
        <p><b><?= htmlspecialchars($service['name']) ?></b> (<?= $service['duration'] ?> мин.)</p>

        <label>Дата:</label>
        <input type="date" name="reservation_date" id="reservation_date" required 
               value="<?= isset($_GET['date']) ? htmlspecialchars($_GET['date']) : '' ?>" onchange="onDateChange()">

        <?php if (!empty($_GET['date'])): ?>
            <label>Свободни часове:</label>
            <select name="selected_time" required>
                <?php
                $date = $_GET['date'];
                $duration = (int)$service['duration'];

                // генерираме часовете от 09:00 до 18:00
                $start = strtotime("$date 09:00:00");
                $end   = strtotime("$date 18:00:00");

                while ($start < $end) {
                    $slot_start = date("Y-m-d H:i:s", $start);
                    $slot_end   = date("Y-m-d H:i:s", strtotime("+$duration minutes", $start));

                    // проверка дали този час е вече зает
                    $stmt = $mysqli->prepare("SELECT id FROM reservations WHERE service_id=? AND start_datetime=? LIMIT 1");
                    $stmt->bind_param("is", $service['id'], $slot_start);
                    $stmt->execute();
                    $taken = $stmt->get_result()->num_rows > 0;
                    $stmt->close();

                    if (!$taken) {
                        $display = date("H:i", $start) . " - " . date("H:i", strtotime("+$duration minutes", $start));
                        echo "<option value='".date("H:i", $start)."'>$display</option>";
                    }

                    $start = strtotime("+30 minutes", $start); // стъпка през 30 мин.
                }
                ?>
            </select>
        <?php endif; ?>

        <button type="submit">Резервирай</button>
    </form>
  <?php else: ?>
    <p>Не е избрана валидна услуга.</p>
  <?php endif; ?>
</div>
</body>
</html>
<?php include "footer.php"; ?>
