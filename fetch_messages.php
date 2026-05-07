<?php
session_start();
require_once "includes/db.php";

// ── Auth guard ──
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    exit();
}

if (!isset($_GET["user"]) || !is_numeric($_GET["user"])) {
    http_response_code(400);
    exit();
}

$user_id       = (int) $_SESSION["user_id"];
$other_user_id = (int) $_GET["user"];

// ── Prevent self-chat ──
if ($user_id === $other_user_id) {
    http_response_code(400);
    exit();
}

// ── Match check (security: only matched users can read messages) ──
$check = $conn->prepare("
    SELECT id FROM matches 
    WHERE (user1_id = ? AND user2_id = ?)
       OR (user1_id = ? AND user2_id = ?)
    LIMIT 1
");
$check->bind_param("iiii", $user_id, $other_user_id, $other_user_id, $user_id);
$check->execute();

if ($check->get_result()->num_rows === 0) {
    http_response_code(403);
    exit();
}

// ── Mark incoming messages as seen ──
$seen = $conn->prepare("
    UPDATE messages 
    SET is_seen = 1 
    WHERE sender_id = ? AND receiver_id = ? AND is_seen = 0
");
$seen->bind_param("ii", $other_user_id, $user_id);
$seen->execute();

// ── Fetch messages ──
$stmt = $conn->prepare("
    SELECT sender_id, message, image, is_seen, created_at
    FROM messages 
    WHERE (sender_id = ? AND receiver_id = ?)
       OR (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC
");
$stmt->bind_param("iiii", $user_id, $other_user_id, $other_user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

// ── No messages ──
if ($result->num_rows === 0) {
    echo "<p class='no-messages'>No messages yet. Say hello! 👋</p>";
    exit();
}

// ── Base URL for chat images ──
$protocol  = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
$baseUrl   = $protocol . "://" . $_SERVER["HTTP_HOST"];
$uploadDir = $_SERVER["DOCUMENT_ROOT"] . "/uploads/chats/";
$uploadUrl = $baseUrl . "/uploads/chats/";

// ── Render messages ──
while ($msg = $result->fetch_assoc()) {

    $isMine = ((int) $msg["sender_id"] === $user_id);
    $class  = $isMine ? "my-msg" : "other-msg";

    echo "<div class='msg-wrapper " . $class . "'>";
    echo    "<div class='bubble'>";

    // Text message
    if (!empty($msg["message"])) {
        echo "<p>" . htmlspecialchars($msg["message"], ENT_QUOTES, "UTF-8") . "</p>";
    }

    // Image message
    if (!empty($msg["image"])) {
        $filename     = basename($msg["image"]); // strip any path injection
        $absolutePath = $uploadDir . $filename;
        $imageUrl     = $uploadUrl . rawurlencode($filename);

        if (file_exists($absolutePath)) {
            echo "<img 
                    src='" . htmlspecialchars($imageUrl, ENT_QUOTES, "UTF-8") . "' 
                    class='chat-img' 
                    alt='Sent image'
                    loading='lazy'
                  >";
        }
        // silently skip if file missing — no broken UI
    }

    echo    "</div>"; // .bubble

    // Seen / Sent status (only on your own messages)
    if ($isMine) {
        $status = $msg["is_seen"] ? "Seen" : "Sent";
        echo "<small class='msg-status'>" . $status . "</small>";
    }

    echo "</div>"; // .msg-wrapper
}
?>