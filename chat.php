<?php
date_default_timezone_set('Asia/Manila');
session_start();
require_once "includes/db.php";

// ── Auth guard ──
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];

// ── Update last active ──
$stmt = $conn->prepare("UPDATE users SET last_active = NOW() WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();

// ── Validate other user ──
if (!isset($_GET["user"]) || !is_numeric($_GET["user"])) {
    header("Location: matches.php");
    exit();
}

$other_user_id = (int) $_GET["user"];

// ── Prevent self-chat ──
if ($user_id === $other_user_id) {
    header("Location: matches.php");
    exit();
}

// ── Match check ──
$check = $conn->prepare("
    SELECT id FROM matches
    WHERE (user1_id = ? AND user2_id = ?)
       OR (user1_id = ? AND user2_id = ?)
    LIMIT 1
");
$check->bind_param("iiii", $user_id, $other_user_id, $other_user_id, $user_id);
$check->execute();

if ($check->get_result()->num_rows === 0) {
    header("Location: matches.php");
    exit();
}

// ── Fetch other user data ──
$stmt = $conn->prepare("
    SELECT username, profile_pic, last_active
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $other_user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();

if (!$userData) {
    header("Location: matches.php");
    exit();
}

// ── Online status ──
$isOnline = (strtotime($userData["last_active"]) > time() - 10);

// ── Handle POST (TEXT ONLY) ──
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST["report_reason"])) {

    $message = trim($_POST["message"] ?? "");

    if (!empty($message)) {
        $stmt = $conn->prepare("
            INSERT INTO messages (sender_id, receiver_id, message, image, created_at)
            VALUES (?, ?, ?, NULL, NOW())
        ");
        $stmt->bind_param("iis", $user_id, $other_user_id, $message);
        $stmt->execute();
    }

    http_response_code(200);
    exit();
}

// ── Handle Report POST ──
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["report_reason"])) {

    $reason = trim($_POST["report_reason"] ?? "");

    if (!empty($reason)) {
        $stmt = $conn->prepare("
            INSERT INTO reports (reporter_id, reported_id, reason, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param("iis", $user_id, $other_user_id, $reason);
        $stmt->execute();
    }

    http_response_code(200);
    exit();
}

// ── ✅ FIXED AVATAR PATH ──
$avatarFile = !empty($userData["profile_pic"]) ? $userData["profile_pic"] : "default.png";
$avatarUrl  = "uploads/" . rawurlencode($avatarFile);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with <?php echo htmlspecialchars($userData["username"]); ?></title>
    <link rel="stylesheet" href="css/chat.css">
</head>
<body>

<div class="chat-container">

    <!-- 🔴 HEADER -->
    <div class="chat-header">

        <a href="matches.php" class="back-btn">&#8592;</a>

        <div class="chat-user">
            <img
                src="<?php echo $avatarUrl; ?>"
                class="chat-avatar"
                alt="<?php echo htmlspecialchars($userData["username"]); ?>"
                onerror="this.src='uploads/default.png'"
            >
            <div class="chat-user-info">
                <strong><?php echo htmlspecialchars($userData["username"]); ?></strong>
                <small id="status-text" style="color: <?php echo $isOnline ? '#4caf50' : 'gray'; ?>">
                    <?php echo $isOnline ? 'Online' : 'Offline'; ?>
                </small>
            </div>
        </div>

        <!-- 🚩 REPORT BUTTON -->
        <button class="report-btn" id="reportBtn" title="Report user">&#9872;</button>

    </div>

    <!-- 💬 MESSAGES -->
    <div class="chat-box" id="chat-box"></div>

    <!-- ✍️ TYPING -->
    <div id="typing-indicator" style="display:none;">Typing...</div>

    <!-- 📝 FORM -->
    <form id="chat-form" class="chat-form">
        <input
            type="text"
            name="message"
            id="messageInput"
            placeholder="Type a message..."
            autocomplete="off"
        >
        <button type="submit">➤</button>
    </form>

</div>

<!-- 🚩 REPORT MODAL -->
<div class="report-overlay" id="reportOverlay">
    <div class="report-modal">
        <h3 class="report-title">Report <?php echo htmlspecialchars($userData["username"]); ?></h3>
        <p class="report-sub">Let us know what's going on. Your report is sent directly to our admin team.</p>
        <textarea
            id="reportReason"
            class="report-textarea"
            placeholder="Describe the issue..."
            rows="5"
        ></textarea>
        <p class="report-error" id="reportError" style="display:none;">Please describe the issue before submitting.</p>
        <div class="report-actions">
            <button class="report-submit-btn" id="reportSubmit">Send Report</button>
            <button class="report-cancel-btn" id="reportCancel">Cancel</button>
        </div>
    </div>
</div>

<script>
const chatBox = document.getElementById("chat-box");
const otherUserId = <?php echo $other_user_id; ?>;
const form = document.getElementById("chat-form");
const messageInput = document.getElementById("messageInput");
const typingEl = document.getElementById("typing-indicator");
const statusEl = document.getElementById("status-text");

let typingTimeout;

// 🔄 Load messages
function loadMessages() {
    const isNearBottom = chatBox.scrollHeight - chatBox.scrollTop <= chatBox.clientHeight + 80;

    fetch("fetch_messages.php?user=" + otherUserId)
        .then(res => res.text())
        .then(data => {
            chatBox.innerHTML = data;
            if (isNearBottom) chatBox.scrollTop = chatBox.scrollHeight;
        });
}

// 🟢 Status
function loadStatus() {
    fetch("check_status.php?user=" + otherUserId)
        .then(res => res.text())
        .then(status => {
            const online = status.trim() === "online";
            statusEl.innerText = online ? "Online" : "Offline";
            statusEl.style.color = online ? "#4caf50" : "gray";
        });
}

// ✍️ Typing
function checkTyping() {
    fetch("check_typing.php?user=" + otherUserId)
        .then(res => res.text())
        .then(status => {
            typingEl.style.display = status.trim() === "typing" ? "block" : "none";
        });
}

function setTyping(val) {
    fetch("update_typing.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "typing=" + (val ? "1" : "0")
    });
}

// 🔁 Auto refresh
setInterval(() => {
    loadMessages();
    loadStatus();
    checkTyping();
}, 2000);

loadMessages();
loadStatus();

// 📩 Send
form.addEventListener("submit", e => {
    e.preventDefault();

    const text = messageInput.value.trim();
    if (!text) return;

    const formData = new FormData(form);

    fetch("chat.php?user=" + otherUserId, {
        method: "POST",
        body: formData
    }).then(() => {
        form.reset();
        setTyping(false);
        loadMessages();
    });
});

// ⌨️ Typing detect
messageInput.addEventListener("input", () => {
    setTyping(true);

    clearTimeout(typingTimeout);
    typingTimeout = setTimeout(() => setTyping(false), 2000);
});

// ⌨️ Enter = send
messageInput.addEventListener("keypress", e => {
    if (e.key === "Enter") {
        e.preventDefault();
        form.dispatchEvent(new Event("submit"));
    }
});

// 🚩 Report modal
const reportBtn    = document.getElementById("reportBtn");
const reportOverlay = document.getElementById("reportOverlay");
const reportCancel = document.getElementById("reportCancel");
const reportSubmit = document.getElementById("reportSubmit");
const reportReason = document.getElementById("reportReason");
const reportError  = document.getElementById("reportError");

reportBtn.addEventListener("click", () => {
    reportOverlay.style.display = "flex";
    reportReason.value = "";
    reportError.style.display = "none";
});

reportCancel.addEventListener("click", () => {
    reportOverlay.style.display = "none";
});

reportOverlay.addEventListener("click", e => {
    if (e.target === reportOverlay) reportOverlay.style.display = "none";
});

reportSubmit.addEventListener("click", () => {
    const reason = reportReason.value.trim();

    if (!reason) {
        reportError.style.display = "block";
        return;
    }

    reportError.style.display = "none";

    const formData = new FormData();
    formData.append("report_reason", reason);

    fetch("chat.php?user=" + otherUserId, {
        method: "POST",
        body: formData
    }).then(() => {
        reportOverlay.style.display = "none";
        alert("Report sent. Thank you — our admin team will review it.");
    });
});
</script>

</body>
</html>