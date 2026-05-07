<?php
require_once "includes/db.php";

if (!isset($_GET["user"])) exit();

$id = intval($_GET["user"]);

$stmt = $conn->prepare("SELECT last_active FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

// ✅ Convert properly
$lastActive = strtotime($data["last_active"]);
$currentTime = time();

// ✅ Debug (optional)
// echo "last: $lastActive | now: $currentTime";

if (($currentTime - $lastActive) <= 10) {
    echo "online";
} else {
    echo "offline";
}