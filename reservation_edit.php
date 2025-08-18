<?php
// reservation_edit.php
session_start();
$role = $_SESSION['role'] ?? 'client';
if ($role === 'client') { header("Location: reservations.php"); exit; }

$mysqli = new mysqli("localhost","root","","spa_center");
if ($mysqli->connect_error) die("DB грешка: ".$mysqli->connect_error);

function fetchAll($db,$sql,$types="",$params=[]) {
  if ($types) {
    $st=$db->prepare($sql); $st->bind_param($types, ...$params); $st->execute();
    $res=$st->get_result(); $rows=$res?$res->fetch_all(MYSQLI_ASSOC):[]; $st->close(); return $rows;
  } else {
    $res=$db->query($sql); $rows=[]; if($res){ while($r=$res->fetch_assoc()) $rows[]=$r; } return $rows;
  }
}

function getServiceDuration($db,$sid){ $r=fetchAll($db,"SELECT duration FROM services WHERE id=?","i",[$sid]); return $r? (int)$r[0]['duration']:60; }
function employeeHasSkill($db,$eid,$sid){ return !empty(fetchAll($db,"SELECT 1 FROM employee_services WHERE employee_id=? AND service_id=? LIMIT 1","ii",[$eid,$sid])); }
function withinWorkingHours($db,$eid,$start_dt,$end_dt){
  $weekday=(int)date('N', strtotime($start_dt)); $ts=date('H:i:s',strtotime($start_dt)); $te=date('H:i:s',strtotime($end_dt));
  return !empty(fetchAll($db,"SELECT 1 FROM employee_working_hours WHERE employee_id=? AND weekday=? AND start_time<=? AND end_time>=? LIMIT 1","iiss",[$eid,$weekday,$ts,$te]));
}
function hasOverlap($db,$reservation_id,$eid,$rid,$start_dt,$end_dt){
  // Изключваме текущата резервация (id != ?)
  $sql="
    SELECT r.id FROM reservations r
    WHERE r.id<>?
      AND (
           (? IS NOT NULL AND r.employee_id = ?)
        OR (? IS NOT NULL AND r.room_id = ?)
      )
      AND r.start_datetime < ?
      AND r.end_datetime   > ?
    LIMIT 1";
  $st=$db->prepare($sql);
  $st->bind_param("iiii ss", $reservation_id, $eid, $eid, $rid, $rid, $end_dt, $start_dt);
  $st->execute();
  $res=$st->get_result(); $exists = ($res && $res->num_rows>0); $st->close();
  return $exists;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: reservations.php"); exit; }

// Текуща резервация
$cur = fetchAll($mysqli,"
  SELECT r.*, s.duration
  FROM reservations r
  JOIN services s ON s.id=r.service_id
  WHERE r.id=?
","i",[$id]);
if (!$cur){ header("Location: reservations.php"); exit; }
$cur = $cur[0];

// Данни за селектите
$services  = fetchAll($mysqli,"SELECT id,name,duration FROM services ORDER BY name");
$rooms = fetchAll($conn, "SELECT id, name FROM rooms WHERE is_active = 1 ORDER BY name");

// Филтър служители по услуга
$employees = fetchAll($mysqli,"
  SELECT e.id, e.name
  FROM employee_services es
  JOIN employees e ON e.id=es.employee_id
  WHERE es.service_id=?
  ORDER BY e.name
","i",[$cur['service_id']]);

$msg="";

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $service_id  = (int)$_POST['service_id'];
  $employee_id = $_POST['employee_id']!=="" ? (int)$_POST['employee_id'] : null;
  $room_id     = $_POST['room_id']!=="" ? (int)$_POST['room_id'] : null;
  $date        = trim($_POST['reservation_date']);
  $time        = trim($_POST['reservation_time']);

  if ($service_id<=0 || !$date || !$time) {
    $msg="Моля попълнете услуга, дата и час.";
  } else {
    $start_dt = $date." ".$time.":00";
    $duration = getServiceDuration($mysqli,$service_id);
    $end_dt   = date('Y-m-d H:i:s', strtotime($start_dt." +$duration minutes"));

    if ($employee_id && !employeeHasSkill($mysqli,$employee_id,$service_id)) {
      $msg="Избраният служител няма компетентност за тази услуга.";
    } elseif ($employee_id && !withinWorkingHours($mysqli,$employee_id,$start_dt,$end_dt)) {
      $msg="Интервалът е извън работното време на служителя.";
    } elseif (($employee_id || $room_id) && hasOverlap($mysqli,$id,$employee_id,$room_id,$start_dt,$end_dt)) {
      $msg="Служителят или стаята са заети в този интервал.";
    } else {
      $st=$mysqli->prepare("
        UPDATE reservations
        SET service_id=?, employee_id=?, room_id=?, start_datetime=?, end_datetime=?
        WHERE id=?
      ");
      // позволяваме NULL за employee_id/room_id
      $emp = $employee_id ?: null;
      $rm  = $room_id ?: null;
      $st->bind_param("ii ss si", $service_id, $emp, $rm, $start_dt, $end_dt, $id);
      // поправка на типовете:
      $st->close();
      $st=$mysqli->prepare("
        UPDATE reservations
        SET service_id=?, employee_id=?, room_id=?, start_datetime=?, end_datetime=?
        WHERE id=?
      ");
      $st->bind_param("iisssi", $service_id, $emp, $rm, $start_dt, $end_dt, $id);
      if ($st->execute()) { $msg="Резервацията е обновена успешно."; } else { $msg="Грешка: ".$mysqli->error; }
      $st->close();
      // по желание: при редакция върни статуса на 'pending' и логни промяната
      // ...
    }
  }
}

// Предварителни стойности за форма
$pref_service = (int)$cur['service_id'];
$pref_emp     = $cur['employee_id'];
$pref_room    = $cur['room_id'];
$pref_date    = $cur['start_datetime'] ? substr($cur['start_datetime'],0,10) : ($cur['reservation_date'] ?? '');
$pref_time    = $cur['start_datetime'] ? substr($cur['start_datetime'],11,5) : ($cur['reservation_time'] ?? '');
?>
<!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="UTF-8">
<title>Редакция на резервация #<?= $id ?></title>
</head>
<body>
<h2>Редакция на резервация #<?= $id ?></h2>
<?php if($msg) echo "<p><b>".htmlspecialchars($msg)."</b></p>"; ?>
<form method="POST">
  <label>Услуга:</label><br>
  <select name="service_id" required>
    <?php foreach($services as $s): ?>
      <option value="<?= $s['id'] ?>" <?= $s['id']===$pref_service?'selected':''; ?>>
        <?= htmlspecialchars($s['name']) ?> (<?= (int)$s['duration'] ?> мин)
      </option>
    <?php endforeach; ?>
  </select><br><br>

  <label>Служител:</label><br>
  <select name="employee_id">
    <option value="">--По избор--</option>
    <?php foreach($employees as $e): ?>
      <option value="<?= $e['id'] ?>" <?= ($pref_emp && $e['id']==$pref_emp)?'selected':''; ?>>
        <?= htmlspecialchars($e['name']) ?>
      </option>
    <?php endforeach; ?>
  </select><br><br>

  <label>Стая/Зала:</label><br>
  <select name="room_id">
    <option value="">--По избор--</option>
    <?php foreach($rooms as $r): ?>
      <option value="<?= $r['id'] ?>" <?= ($pref_room && $r['id']==$pref_room)?'selected':''; ?>>
        <?= htmlspecialchars($r['name']) ?>
      </option>
    <?php endforeach; ?>
  </select><br><br>

  <label>Дата:</label><br>
  <input type="date" name="reservation_date" value="<?= htmlspecialchars($pref_date) ?>" required><br><br>

  <label>Начален час:</label><br>
  <input type="time" name="reservation_time" value="<?= htmlspecialchars($pref_time) ?>" required><br><br>

  <button type="submit">Запази промените</button>
  &nbsp; <a href="reservations.php">Назад</a>
</form>
</body>
</html>
