<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION["user_id"])) exit();

$user_id = $_SESSION["user_id"];
$typing = isset($_POST["typing"]) ? intval($_POST["typing"]) : 0;

$stmt = $conn->prepare("UPDATE users SET is_typing = ? WHERE id = ?");
$stmt->bind_param("ii", $typing, $user_id);
$stmt->execute();
?>