<?php
require_once "includes/db.php";

$user_id = intval($_GET["user"]);

$stmt = $conn->prepare("SELECT is_typing FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo ($result["is_typing"] == 1) ? "typing" : "not_typing";
?>