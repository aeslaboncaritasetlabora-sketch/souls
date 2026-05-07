<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// ✅ Update last active
$stmt = $conn->prepare("UPDATE users SET last_active = NOW() WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();

// 🔥 HANDLE LIKE (AJAX)
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $liked_id = intval($_POST["liked_id"]);

    if ($liked_id != $user_id) {

        $check = $conn->prepare("SELECT id FROM likes WHERE sender_id = ? AND receiver_id = ?");
        $check->bind_param("ii", $user_id, $liked_id);
        $check->execute();

        if ($check->get_result()->num_rows == 0) {

            $stmt = $conn->prepare("INSERT INTO likes (sender_id, receiver_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $liked_id);
            $stmt->execute();

            // MATCH CHECK
            $matchCheck = $conn->prepare("SELECT id FROM likes WHERE sender_id = ? AND receiver_id = ?");
            $matchCheck->bind_param("ii", $liked_id, $user_id);
            $matchCheck->execute();

            if ($matchCheck->get_result()->num_rows > 0) {

                $checkMatch = $conn->prepare("
                    SELECT id FROM matches 
                    WHERE (user1_id = ? AND user2_id = ?) 
                       OR (user1_id = ? AND user2_id = ?)
                ");
                $checkMatch->bind_param("iiii", $user_id, $liked_id, $liked_id, $user_id);
                $checkMatch->execute();

                if ($checkMatch->get_result()->num_rows == 0) {

                    $insertMatch = $conn->prepare("INSERT INTO matches (user1_id, user2_id) VALUES (?, ?)");
                    $insertMatch->bind_param("ii", $user_id, $liked_id);
                    $insertMatch->execute();

                    echo "match";
                    exit();
                }
            }
        }
    }

    echo "ok";
    exit();
}

// 🔥 GET NEXT USER (with hobbies + preference)
$stmt = $conn->prepare("
    SELECT id, username, age, bio, profile_pic, hobbies, looking_for
    FROM users 
    WHERE id != ?
    AND id NOT IN (SELECT receiver_id FROM likes WHERE sender_id = ?)
    AND id NOT IN (
        SELECT user1_id FROM matches WHERE user2_id = ?
        UNION
        SELECT user2_id FROM matches WHERE user1_id = ?
    )
    ORDER BY RAND()
    LIMIT 1
");

$stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Parse hobbies (stored as comma-separated string)
$hobbies = [];
if (!empty($user["hobbies"])) {
    $hobbies = array_filter(array_map('trim', explode(',', $user["hobbies"])));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Browse · Soulence</title>
    <link rel="stylesheet" href="css/browse.css">
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <div class="top-left">
        <a href="javascript:history.back()" class="back-btn">←</a>
    </div>
    <div class="top-center">
        <span class="logo">Soulence</span>
    </div>
    <div class="top-right">
        <div class="topbar-settings-wrapper">
            <button class="settings-btn" onclick="toggleSettings()" aria-label="Settings">
                <svg class="settings-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
            </button>

            <!-- Settings Dropdown -->
            <div class="settings-dropdown" id="settingsDropdown">
                <a href="dashboard.php" class="settings-item">👤 Profile</a>
                <button class="settings-item" onclick="openModal('guidelines')">📋 Community Guidelines</button>
                <button class="settings-item" onclick="openModal('privacy')">🔒 Privacy Control</button>
                <button class="settings-item" onclick="openModal('terms')">📄 Terms of Use</button>
                <button class="settings-item" onclick="openModal('about')">💜 About Us</button>
                <div class="settings-divider"></div>
                <a href="login.php" class="settings-item settings-logout">🚪 Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- MODALS -->

<!-- Community Guidelines -->
<div class="modal-overlay" id="modal-guidelines" onclick="closeModalOutside(event, 'guidelines')">
    <div class="modal-card">
        <button class="modal-close" onclick="closeModal('guidelines')">✕</button>
        <h3 class="modal-title">📋 Community Guidelines</h3>
        <div class="modal-body">
            <p>Welcome to Soulence. To keep this a safe and loving space for everyone, we ask that all members follow these guidelines:</p>
            <ul>
                <li><strong>Be respectful.</strong> Treat every person you meet with kindness. Harassment, hate speech, or discrimination of any kind will not be tolerated.</li>
                <li><strong>Be honest.</strong> Use real photos and accurate information on your profile. Catfishing or impersonation leads to immediate removal.</li>
                <li><strong>Consent matters.</strong> Always respect boundaries. Never pressure anyone into conversations or meetings they are not comfortable with.</li>
                <li><strong>Keep it safe.</strong> Do not share personal information such as your home address, workplace, or financial details early on.</li>
                <li><strong>Report concerns.</strong> If someone makes you feel unsafe or violates these guidelines, please report them immediately.</li>
                <li><strong>No explicit content.</strong> Unsolicited explicit photos or messages are strictly prohibited and may result in a permanent ban.</li>
            </ul>
            <p>Soulence is built on trust. Let's protect it together. 💜</p>
        </div>
    </div>
</div>

<!-- Privacy Control -->
<div class="modal-overlay" id="modal-privacy" onclick="closeModalOutside(event, 'privacy')">
    <div class="modal-card">
        <button class="modal-close" onclick="closeModal('privacy')">✕</button>
        <h3 class="modal-title">🔒 Privacy Control</h3>
        <div class="modal-body">
            <p>Your privacy is our priority. Here's how we handle your data on Soulence:</p>
            <ul>
                <li><strong>Profile visibility.</strong> Your profile is only visible to other registered members. You control what information you share publicly.</li>
                <li><strong>Location data.</strong> We do not share your exact location with other users. Any distance-based features use approximate data only.</li>
                <li><strong>Data storage.</strong> Your personal information is stored securely and is never sold to third parties.</li>
                <li><strong>Photos.</strong> Your uploaded photos are stored privately and only displayed within the Soulence platform.</li>
                <li><strong>Account deletion.</strong> You may request full deletion of your account and all associated data at any time by contacting support.</li>
                <li><strong>Cookies.</strong> We use cookies strictly for session management and improving your experience — not for advertising.</li>
            </ul>
            <p>For detailed information, please review our full Privacy Policy or reach out to our support team.</p>
        </div>
    </div>
</div>

<!-- Terms of Use -->
<div class="modal-overlay" id="modal-terms" onclick="closeModalOutside(event, 'terms')">
    <div class="modal-card">
        <button class="modal-close" onclick="closeModal('terms')">✕</button>
        <h3 class="modal-title">📄 Terms of Use</h3>
        <div class="modal-body">
            <p>By using Soulence, you agree to the following terms:</p>
            <ul>
                <li><strong>Eligibility.</strong> You must be at least 18 years old to use this platform. By registering, you confirm that you meet this requirement.</li>
                <li><strong>Account responsibility.</strong> You are responsible for maintaining the confidentiality of your login credentials. Any activity under your account is your responsibility.</li>
                <li><strong>Prohibited conduct.</strong> You may not use Soulence for illegal activities, spam, commercial solicitation, or any behavior that harms other users.</li>
                <li><strong>Content ownership.</strong> You retain ownership of the content you post. By posting, you grant Soulence a non-exclusive license to display it within the platform.</li>
                <li><strong>Termination.</strong> We reserve the right to suspend or terminate accounts that violate these terms at any time without prior notice.</li>
                <li><strong>Changes.</strong> These terms may be updated periodically. Continued use of Soulence after updates constitutes your acceptance.</li>
            </ul>
            <p>If you have questions about these terms, please contact us at support@soulence.app.</p>
        </div>
    </div>
</div>

<!-- About Us -->
<div class="modal-overlay" id="modal-about" onclick="closeModalOutside(event, 'about')">
    <div class="modal-card">
        <button class="modal-close" onclick="closeModal('about')">✕</button>
        <h3 class="modal-title">💜 About Us</h3>
        <div class="modal-body">
            <p>Soulence was born from a simple belief — that real connection should feel effortless.</p>
            <p>In a world full of swipes and algorithms, we wanted to build something more intentional. A place where people come not just to match, but to truly meet someone who resonates with their soul.</p>
            <p>Whether you're searching for a lifelong partner, a meaningful friendship, or simply someone who shares your love for late-night coffee and anime — Soulence is your space.</p>
            <p>We are a small, passionate team dedicated to making online dating feel a little more human, a little more warm, and a lot more real.</p>
            <p><strong>Our values:</strong></p>
            <ul>
                <li>💜 Authenticity over perfection</li>
                <li>🔒 Safety and trust above all</li>
                <li>✨ Connections that actually mean something</li>
                <li>🌸 Inclusivity for every kind of love</li>
            </ul>
            <p>Thank you for being part of Soulence. We're rooting for you. 💜</p>
        </div>
    </div>
</div>

<div class="swipe-container">

<?php if ($user): ?>
    <div class="card" id="card" data-id="<?php echo $user["id"]; ?>">

        <img id="user-img"
            src="uploads/<?php echo !empty($user["profile_pic"]) ? htmlspecialchars($user["profile_pic"]) : 'default.png'; ?>">

        <div class="card-gradient"></div>

        <div class="info">

            <div class="info-header">
                <h2 id="user-name">
                    <?php echo htmlspecialchars($user["username"]); ?><span class="age">, <?php echo htmlspecialchars($user["age"]); ?></span>
                </h2>
                <?php if (!empty($user["looking_for"])): ?>
                <div class="preference-pill">
                    <?php echo htmlspecialchars($user["looking_for"]); ?>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($user["bio"])): ?>
            <p class="bio"><?php echo htmlspecialchars($user["bio"]); ?></p>
            <?php endif; ?>

            <?php if (!empty($hobbies)): ?>
            <div class="hobbies-row">
                <?php foreach (array_slice($hobbies, 0, 5) as $hobby): ?>
                    <span class="hobby-chip"><?php echo htmlspecialchars($hobby); ?></span>
                <?php endforeach; ?>
                <?php if (count($hobbies) > 5): ?>
                    <span class="hobby-chip hobby-chip--more">+<?php echo count($hobbies) - 5; ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div>

        <div class="like-label">LIKE</div>
        <div class="pass-label">NOPE</div>

        <div class="actions">
            <button class="pass-btn" id="passBtn">❌</button>
            <button class="like-btn" id="likeBtn">❤️</button>
        </div>

    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">💫</div>
        <p class="empty-title">You've seen everyone</p>
        <p class="empty-sub">Check back later for new souls</p>
    </div>
<?php endif; ?>

</div>

<!-- 💘 MATCH SCREEN -->
<div id="match-popup" class="match-overlay">
    <div class="match-card">
        <div class="match-sparkle">✦</div>
        <h1>It's a Match</h1>
        <p>You and <span id="match-name"></span> liked each other</p>

        <div class="match-images">
            <img src="uploads/<?php echo $_SESSION['profile_pic'] ?? 'default.png'; ?>" class="me">
            <div class="match-heart">💘</div>
            <img id="match-user-img" src="" class="them">
        </div>

        <div class="match-actions">
            <button onclick="location.href='matches.php'" class="chat-btn">
                💬 Send Message
            </button>
            <button onclick="closeMatch()" class="swipe-btn">
                🔥 Keep Swiping
            </button>
        </div>
    </div>
</div>

<script>
const card = document.getElementById("card");

if (card) {
    const likeBtn = document.getElementById("likeBtn");
    const passBtn = document.getElementById("passBtn");
    const userId = card.dataset.id;
    const userName = document.getElementById("user-name").innerText;
    const userImg = document.getElementById("user-img").src;

    likeBtn.addEventListener("click", () => {
        fetch("browse.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: "liked_id=" + userId
        })
        .then(res => res.text())
        .then(res => {
            if (res.trim() === "match") {
                document.getElementById("match-popup").style.display = "flex";
                document.getElementById("match-name").innerText = userName;
                document.getElementById("match-user-img").src = userImg;
                return;
            }
            swipeRight();
        });
    });

    passBtn.addEventListener("click", swipeLeft);

    function swipeRight() {
        card.classList.add("swipe-right");
        setTimeout(() => location.reload(), 400);
    }
    function swipeLeft() {
        card.classList.add("swipe-left");
        setTimeout(() => location.reload(), 400);
    }

    likeBtn.addEventListener("mouseenter", () => {
        document.querySelector(".like-label").style.opacity = "1";
    });
    likeBtn.addEventListener("mouseleave", () => {
        document.querySelector(".like-label").style.opacity = "0";
    });
    passBtn.addEventListener("mouseenter", () => {
        document.querySelector(".pass-label").style.opacity = "1";
    });
    passBtn.addEventListener("mouseleave", () => {
        document.querySelector(".pass-label").style.opacity = "0";
    });
}

function closeMatch() {
    document.getElementById("match-popup").style.display = "none";
    location.reload();
}

// ── Settings dropdown ──
function toggleSettings() {
    document.getElementById('settingsDropdown').classList.toggle('open');
}
document.addEventListener('click', function(e) {
    const wrapper = document.querySelector('.topbar-settings-wrapper');
    if (wrapper && !wrapper.contains(e.target)) {
        document.getElementById('settingsDropdown').classList.remove('open');
    }
});

function openModal(id) {
    document.getElementById('modal-' + id).classList.add('open');
    document.getElementById('settingsDropdown').classList.remove('open');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById('modal-' + id).classList.remove('open');
    document.body.style.overflow = '';
}
function closeModalOutside(event, id) {
    if (event.target === document.getElementById('modal-' + id)) {
        closeModal(id);
    }
}
</script>

</body>
</html>