<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];

$stmt = $conn->prepare("UPDATE users SET last_active = NOW() WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();

$stmt = $conn->prepare("
    SELECT username, email, age, birthday, bio, profile_pic, hobbies, looking_for
    FROM users WHERE id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$preference_labels = [
    "friends"    => "👫 Looking for Friends",
    "fling"      => "✨ Fling",
    "short_term" => "🌸 Short-term Relationship",
    "long_term"  => "💍 Long-term Relationship",
];

$looking_for_label = !empty($user["looking_for"])
    ? ($preference_labels[$user["looking_for"]] ?? ucfirst($user["looking_for"]))
    : null;

$hobbies = [];
if (!empty($user["hobbies"])) {
    $hobbies = array_filter(array_map("trim", explode(",", $user["hobbies"])));
}

// Format birthday for display
$birthday_display = null;
if (!empty($user["birthday"])) {
    $bd = new DateTime($user["birthday"]);
    $birthday_display = $bd->format("F j, Y");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Soulence — Dashboard</title>
    <link rel="stylesheet" href="css/dashboard.css" />
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <div class="logo">Soulence</div>
    <div class="topbar-right">
        <button class="settings-btn" onclick="toggleSettings()" aria-label="Settings">
            <svg class="settings-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
            </svg>
        </button>

        <!-- Settings Dropdown -->
        <div class="settings-dropdown" id="settingsDropdown">
            <button class="settings-item" onclick="openModal('guidelines')">📋 Community Guidelines</button>
            <button class="settings-item" onclick="openModal('privacy')">🔒 Privacy Control</button>
            <button class="settings-item" onclick="openModal('terms')">📄 Terms of Use</button>
            <button class="settings-item" onclick="openModal('about')">💜 About Us</button>
            <div class="settings-divider"></div>
            <a href="login.php" class="settings-item settings-logout">🚪 Logout</a>
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

<!-- MAIN -->
<div class="dashboard-container">

    <!-- PROFILE CARD -->
    <div class="profile-card">

        <h2 class="welcome-text">
            Welcome, <?php echo htmlspecialchars($user["username"]); ?> 👋
        </h2>

        <div class="profile-content">

            <?php if (!empty($user["profile_pic"])): ?>
                <img src="uploads/<?php echo htmlspecialchars($user["profile_pic"]); ?>" class="profile-pic" alt="Profile photo">
            <?php else: ?>
                <img src="https://via.placeholder.com/120" class="profile-pic" alt="Profile photo">
            <?php endif; ?>

            <div class="profile-info">
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user["email"]); ?></p>
                <p><strong>Age:</strong> <?php echo htmlspecialchars($user["age"]); ?></p>
                <?php if ($birthday_display): ?>
                <p><strong>Birthday:</strong> <?php echo htmlspecialchars($birthday_display); ?></p>
                <?php endif; ?>
                <p><strong>Bio:</strong> <?php echo htmlspecialchars($user["bio"]); ?></p>

                <?php if ($looking_for_label): ?>
                    <p><strong>Looking for:</strong>
                        <span class="preference-badge"><?php echo $looking_for_label; ?></span>
                    </p>
                <?php endif; ?>
            </div>

        </div>

        <!-- Hobbies -->
        <?php if (!empty($hobbies)): ?>
        <div class="hobbies-section">
            <p class="hobbies-label">Hobbies</p>
            <div class="hobbies-list">
                <?php foreach ($hobbies as $hobby): ?>
                    <span class="hobby-tag"><?php echo htmlspecialchars($hobby); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- ACTION BUTTONS -->
    <div class="action-section">
        <a href="browse.php" class="browse-btn">
            <span class="heart">❤️</span>
            Browse for your perfect match
        </a>
        <a href="matches.php" class="match-btn">
            💬 View your matches
        </a>
    </div>

</div>

<script>
function toggleSettings() {
    const dropdown = document.getElementById('settingsDropdown');
    dropdown.classList.toggle('open');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const wrapper = document.querySelector('.topbar-right');
    if (!wrapper.contains(e.target)) {
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