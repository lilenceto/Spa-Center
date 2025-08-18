<?php
// reservation_status.php
session_start();
$role = $_SESSION['role'] ?? 'client';
$uid  = $_SESSION['user_id'] ?? 0;

$id     = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

$mysqli = new mysqli("localhost","root","","spa_center");
if ($mysqli->connect_error) die("DB грешка");

$stmt = $mysqli->prepare("SELECT user_id, status, start_datetime FROM reservations WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$stmt->bind_result($resUserId, $old_status, $start_dt);
if(!$stmt->fetch()){ $stmt->close(); header("Location: reservations.php"); exit; }
$stmt->close();

if ($role === 'client') {
  // клиент НИКОГА не може confirm
  if ($action === 'confirm') { header("Location: reservations.php"); exit; }
  // cancel само ако е негова, pending и ≥24ч преди старт
  if ($action === 'cancel') {
    if ($resUserId !== $uid) { header("Location: reservations.php"); exit; }
    if ($old_status !== 'pending') { header("Location: reservations.php"); exit; }
    $startsIn = strtotime($start_dt) - time();
    if ($startsIn < 24*3600) { header("Location: reservations.php"); exit; }
  } else {
    header("Location: reservations.php"); exit;
  }
}

// 1) Вземаме текущия статус
$stmt = $mysqli->prepare("SELECT status FROM reservations WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$stmt->bind_result($old_status);
if (!$stmt->fetch()) { $stmt->close(); header("Location: reservations.php"); exit; }
$stmt->close();

// 2) Определяме новия статус
$new_status = ($action==='confirm') ? 'confirmed' : 'canceled';
if ($new_status === $old_status) { header("Location: reservations.php"); exit; }

// 3) Обновяваме резервацията
$up = $mysqli->prepare("UPDATE reservations SET status=? WHERE id=?");
$up->bind_param("si", $new_status, $id);
$ok = $up->execute();
$up->close();

// 4) Лог в историята
if ($ok) {
  $ins = $mysqli->prepare("
    INSERT INTO reservation_status_history (reservation_id, old_status, new_status, changed_by)
    VALUES (?, ?, ?, ?)
  ");
  $ins->bind_param("isss", $id, $old_status, $new_status, $uid);
  // changed_by е INT, но bind_param приема 's' за string/‘i’ за int -> коректно е "issi":
  // Поправяме:
  $ins->close();
  $ins = $mysqli->prepare("
    INSERT INTO reservation_status_history (reservation_id, old_status, new_status, changed_by)
    VALUES (?, ?, ?, ?)
  ");
  $ins->bind_param("issi", $id, $old_status, $new_status, $uid);
  $ins->execute();
  $ins->close();
}

header("Location: reservations.php");
exit;
