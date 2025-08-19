<?php
$mysqli = new mysqli("localhost", "root", "", "spa_center");

if ($mysqli->connect_error) {
    die("Грешка при връзка с базата: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8");
?>
