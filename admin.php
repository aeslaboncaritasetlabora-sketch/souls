<?php
date_default_timezone_set('Asia/Manila');
session_start();
require_once "includes/db.php";

// ── Auth Guard ──
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// ── Safe query helper — never crashes, returns 0 on failure ──
function safeCount($conn, $sql) {
    $result = $conn->query($sql);
    if (!$result) return 0;
    $row = $result->fetch_assoc();
    return (int)($row['c'] ?? 0);
}

function safeQuery($conn, $sql) {
    $result = $conn->query($sql);
    return $result ?: false;
}

// ── Handle report dismiss ──
if (isset($_POST["dismiss_report"])) {
    $report_id = (int) $_POST["dismiss_report"];
    $conn->query("UPDATE reports SET status = 'dismissed' WHERE id = $report_id");
    http_response_code(200);
    exit();
}

// ── Handle report resolve ──
if (isset($_POST["resolve_report"])) {
    $report_id = (int) $_POST["resolve_report"];
    $conn->query("UPDATE reports SET status = 'resolved' WHERE id = $report_id");
    http_response_code(200);
    exit();
}

// ── AJAX real-time refresh ──
if (isset($_GET["ajax"])) {
    header("Content-Type: application/json");
    echo json_encode([
        "totalUsers"    => safeCount($conn, "SELECT COUNT(*) as c FROM users"),
        "activeUsers"   => safeCount($conn, "SELECT COUNT(*) as c FROM users WHERE last_active >= NOW() - INTERVAL 5 MINUTE"),
        "totalMatches"  => safeCount($conn, "SELECT COUNT(*) as c FROM matches"),
        "matchesToday"  => safeCount($conn, "SELECT COUNT(*) as c FROM matches WHERE DATE(matched_at) = CURDATE()"),
        "messagesToday" => safeCount($conn, "SELECT COUNT(*) as c FROM messages WHERE DATE(created_at) = CURDATE()"),
        "pendingReports"=> safeCount($conn, "SELECT COUNT(*) as c FROM reports WHERE status = 'pending'"),
    ]);
    exit();
}

// ── Stats ──
$totalUsers     = safeCount($conn, "SELECT COUNT(*) as c FROM users");
$activeUsers    = safeCount($conn, "SELECT COUNT(*) as c FROM users WHERE last_active >= NOW() - INTERVAL 5 MINUTE");
$totalMatches   = safeCount($conn, "SELECT COUNT(*) as c FROM matches");
$matchesToday   = safeCount($conn, "SELECT COUNT(*) as c FROM matches WHERE DATE(matched_at) = CURDATE()");
$messagesToday  = safeCount($conn, "SELECT COUNT(*) as c FROM messages WHERE DATE(created_at) = CURDATE()");
$pendingReports = safeCount($conn, "SELECT COUNT(*) as c FROM reports WHERE status = 'pending'");

// ── Detect available columns in users table ──
$hasEmail     = false;
$hasCreatedAt = false;
$colResult = $conn->query("SHOW COLUMNS FROM users");
if ($colResult) {
    while ($col = $colResult->fetch_assoc()) {
        if ($col['Field'] === 'email')      $hasEmail     = true;
        if ($col['Field'] === 'created_at') $hasCreatedAt = true;
    }
}

$userCols = "id, username, profile_pic, last_active";
if ($hasEmail)     $userCols .= ", email";
if ($hasCreatedAt) $userCols .= ", created_at";

// ── All Users ──
$usersResult = safeQuery($conn, "SELECT $userCols FROM users ORDER BY last_active DESC");

// ── Detect matched_at column in matches table ──
$hasMatchedAt = false;
$mColResult = $conn->query("SHOW COLUMNS FROM matches");
if ($mColResult) {
    while ($col = $mColResult->fetch_assoc()) {
        if ($col['Field'] === 'matched_at') $hasMatchedAt = true;
    }
}

$matchOrderCol  = $hasMatchedAt ? "m.matched_at" : "m.id";
$matchSelectCol = $hasMatchedAt ? "m.matched_at" : "NULL AS matched_at";

// ── Matches Table ──
$matchesResult = safeQuery($conn, "
    SELECT
        m.id,
        u1.username AS user1,
        u2.username AS user2,
        $matchSelectCol,
        MAX(msg.created_at) AS last_message_time
    FROM matches m
    JOIN users u1 ON m.user1_id = u1.id
    JOIN users u2 ON m.user2_id = u2.id
    LEFT JOIN messages msg
        ON (msg.sender_id = u1.id AND msg.receiver_id = u2.id)
        OR (msg.sender_id = u2.id AND msg.receiver_id = u1.id)
    GROUP BY m.id, u1.username, u2.username, $matchOrderCol
    ORDER BY $matchOrderCol DESC
    LIMIT 30
");

// ── Reports Table ──
$reportsResult = safeQuery($conn, "
    SELECT
        r.id,
        r.reason,
        r.created_at,
        r.status,
        reporter.username  AS reporter_name,
        reporter.profile_pic AS reporter_pic,
        reported.username  AS reported_name,
        reported.profile_pic AS reported_pic
    FROM reports r
    JOIN users reporter ON r.reporter_id = reporter.id
    JOIN users reported ON r.reported_id = reported.id
    ORDER BY
        CASE WHEN r.status = 'pending' THEN 0 ELSE 1 END,
        r.created_at DESC
    LIMIT 50
");

// ── Base URL ──
$protocol   = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
$baseUrl    = $protocol . "://" . $_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/\\");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin — Soulence</title>
  <link rel="stylesheet" href="css/admin.css" />
</head>
<body>

<!-- ── SIDEBAR ── -->
<div class="sidebar">
  <div class="sidebar-logo">Soul<span>ence</span></div>
  <p class="sidebar-sub">Admin Panel</p>

  <nav class="sidebar-nav">
    <a href="#stats"   class="nav-item active"><span class="nav-icon">📊</span> Overview</a>
    <a href="#users"   class="nav-item"><span class="nav-icon">👤</span> Users</a>
    <a href="#matches" class="nav-item"><span class="nav-icon">💘</span> Matches</a>
    <a href="#reports" class="nav-item">
      <span class="nav-icon">🚩</span> Reports
      <?php if ($pendingReports > 0): ?>
        <span class="nav-badge" id="nav-report-badge"><?php echo $pendingReports; ?></span>
      <?php endif; ?>
    </a>
  </nav>

  <div class="sidebar-footer">
    &copy; <?php echo date("Y"); ?> Soulence
  </div>
</div>

<!-- ── MAIN ── -->
<div class="main">

  <!-- Topbar -->
  <div class="topbar">
    <h1 class="topbar-title">Dashboard</h1>
    <div class="topbar-right">
      <div class="live-badge">
        <span class="live-dot"></span> Live
      </div>
      <a href="logout.php" class="logout-link">Logout</a>
    </div>
  </div>

  <!-- ── STAT CARDS ── -->
  <div class="stat-grid" id="stats">
    <div class="stat-card">
      <span class="stat-icon">👥</span>
      <div class="stat-label">Total Users</div>
      <div class="stat-value" id="stat-totalUsers"><?php echo $totalUsers; ?></div>
    </div>
    <div class="stat-card">
      <span class="stat-icon">🟢</span>
      <div class="stat-label">Active Now</div>
      <div class="stat-value" id="stat-activeUsers"><?php echo $activeUsers; ?></div>
    </div>
    <div class="stat-card">
      <span class="stat-icon">💞</span>
      <div class="stat-label">Total Matches</div>
      <div class="stat-value" id="stat-totalMatches"><?php echo $totalMatches; ?></div>
    </div>
    <div class="stat-card">
      <span class="stat-icon">✨</span>
      <div class="stat-label">Matches Today</div>
      <div class="stat-value" id="stat-matchesToday"><?php echo $matchesToday; ?></div>
    </div>
    <div class="stat-card">
      <span class="stat-icon">💬</span>
      <div class="stat-label">Messages Today</div>
      <div class="stat-value" id="stat-messagesToday"><?php echo $messagesToday; ?></div>
    </div>
    <div class="stat-card stat-card-reports">
      <span class="stat-icon">🚩</span>
      <div class="stat-label">Pending Reports</div>
      <div class="stat-value" id="stat-pendingReports"><?php echo $pendingReports; ?></div>
    </div>
  </div>

  <p class="refresh-note" id="last-updated">Refreshing every 5 seconds...</p>

  <!-- ── USERS TABLE ── -->
  <div class="section-header" id="users">
    <h2 class="section-title">All Users</h2>
    <span class="section-count" id="user-count"><?php echo $totalUsers; ?> total</span>
  </div>

  <div class="table-card">
    <table>
      <thead>
        <tr>
          <th>User</th>
          <th>Status</th>
          <th>Last Active</th>
          <?php if ($hasCreatedAt): ?><th>Joined</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php
        if ($usersResult && $usersResult->num_rows > 0):
            while ($user = $usersResult->fetch_assoc()):
                $isOnline   = !empty($user["last_active"]) && (strtotime($user["last_active"]) > time() - 300);
                $lastActive = !empty($user["last_active"]) ? date("M d, g:i a", strtotime($user["last_active"])) : "—";
                $joined     = !empty($user["created_at"])  ? date("M d, Y",     strtotime($user["created_at"]))  : "—";
                $email      = $hasEmail ? htmlspecialchars($user["email"] ?? "", ENT_QUOTES, "UTF-8") : "";

                if (!empty($user["profile_pic"])) {
                    $pic = $user["profile_pic"];
                    if (strpos($pic, "uploads/") === 0) {
                        $avatar = $baseUrl . "/" . htmlspecialchars(rawurlencode($pic), ENT_QUOTES, "UTF-8");
                        $avatar = str_replace("%2F", "/", $avatar);
                    } else {
                        $avatar = $baseUrl . "/uploads/" . htmlspecialchars(rawurlencode($pic), ENT_QUOTES, "UTF-8");
                    }
                } else {
                    $avatar = $baseUrl . "/uploads/default.png";
                }
        ?>
        <tr>
          <td>
            <div class="user-cell">
              <img
                src="<?php echo $avatar; ?>"
                class="user-avatar"
                alt=""
                onerror="this.src='<?php echo $baseUrl; ?>/uploads/default.png'"
              >
              <div>
                <div class="user-name"><?php echo htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8"); ?></div>
                <?php if ($hasEmail && $email): ?>
                  <div class="user-email"><?php echo $email; ?></div>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td>
            <?php if ($isOnline): ?>
              <span class="online-dot"></span>&nbsp;Online
            <?php else: ?>
              <span class="offline-dot"></span>&nbsp;Offline
            <?php endif; ?>
          </td>
          <td><span class="last-seen"><?php echo $lastActive; ?></span></td>
          <?php if ($hasCreatedAt): ?>
            <td><span class="last-seen"><?php echo $joined; ?></span></td>
          <?php endif; ?>
        </tr>
        <?php
            endwhile;
        else: ?>
        <tr>
          <td colspan="4">
            <div class="empty-state">No users found.</div>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ── MATCHES TABLE ── -->
  <div class="section-header" id="matches">
    <h2 class="section-title">Recent Matches</h2>
    <span class="section-count"><?php echo $totalMatches; ?> total</span>
  </div>

  <div class="table-card">
    <table>
      <thead>
        <tr>
          <th>Pair</th>
          <th>Matched At</th>
          <th>Status</th>
          <th>Last Message</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $matchCount = 0;
        if ($matchesResult && $matchesResult->num_rows > 0):
            while ($row = $matchesResult->fetch_assoc()):
                $matchCount++;

                if (empty($row["last_message_time"])) {
                    $badgeClass = "badge-nomsg";
                    $label      = "No Messages";
                } else {
                    $diff = time() - strtotime($row["last_message_time"]);
                    if ($diff < 3600) {
                        $badgeClass = "badge-active";
                        $label      = "Active";
                    } else {
                        $badgeClass = "badge-inactive";
                        $label      = "Inactive";
                    }
                }

                $matchedAt   = !empty($row["matched_at"])        ? date("M d, Y g:i a", strtotime($row["matched_at"]))        : "—";
                $lastMsgTime = !empty($row["last_message_time"])  ? date("M d, Y g:i a", strtotime($row["last_message_time"])) : "—";
        ?>
        <tr>
          <td>
            <div class="match-pair">
              <?php echo htmlspecialchars($row["user1"], ENT_QUOTES, "UTF-8"); ?>
              <span class="heart">♥</span>
              <?php echo htmlspecialchars($row["user2"], ENT_QUOTES, "UTF-8"); ?>
            </div>
          </td>
          <td><span class="last-seen"><?php echo $matchedAt; ?></span></td>
          <td>
            <span class="badge <?php echo $badgeClass; ?>">
              <span class="badge-dot"></span>
              <?php echo $label; ?>
            </span>
          </td>
          <td><span class="last-seen"><?php echo $lastMsgTime; ?></span></td>
        </tr>
        <?php
            endwhile;
        endif;

        if ($matchCount === 0): ?>
        <tr>
          <td colspan="4">
            <div class="empty-state">No matches yet — love is on its way 🌸</div>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ── REPORTS TABLE ── -->
  <div class="section-header" id="reports">
    <h2 class="section-title">User Reports</h2>
    <span class="section-count">
      <?php echo $pendingReports; ?> pending
    </span>
  </div>

  <div class="table-card">
    <table>
      <thead>
        <tr>
          <th>Reporter</th>
          <th>Reported</th>
          <th>Reason</th>
          <th>Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="reports-tbody">
        <?php
        $reportCount = 0;
        if ($reportsResult && $reportsResult->num_rows > 0):
            while ($r = $reportsResult->fetch_assoc()):
                $reportCount++;
                $reportDate = !empty($r["created_at"]) ? date("M d, Y g:i a", strtotime($r["created_at"])) : "—";
                $status     = $r["status"] ?? "pending";

                $statusClass = match($status) {
                    "resolved"  => "badge-active",
                    "dismissed" => "badge-inactive",
                    default     => "badge-nomsg",
                };
                $statusLabel = match($status) {
                    "resolved"  => "Resolved",
                    "dismissed" => "Dismissed",
                    default     => "Pending",
                };

                // Reporter avatar
                if (!empty($r["reporter_pic"])) {
                    $rPic = $r["reporter_pic"];
                    $rAvatar = (strpos($rPic, "uploads/") === 0)
                        ? $baseUrl . "/" . str_replace("%2F", "/", rawurlencode($rPic))
                        : $baseUrl . "/uploads/" . rawurlencode($rPic);
                } else {
                    $rAvatar = $baseUrl . "/uploads/default.png";
                }

                // Reported avatar
                if (!empty($r["reported_pic"])) {
                    $dPic = $r["reported_pic"];
                    $dAvatar = (strpos($dPic, "uploads/") === 0)
                        ? $baseUrl . "/" . str_replace("%2F", "/", rawurlencode($dPic))
                        : $baseUrl . "/uploads/" . rawurlencode($dPic);
                } else {
                    $dAvatar = $baseUrl . "/uploads/default.png";
                }
        ?>
        <tr id="report-row-<?php echo $r["id"]; ?>">
          <td>
            <div class="user-cell">
              <img src="<?php echo $rAvatar; ?>" class="user-avatar" alt=""
                   onerror="this.src='<?php echo $baseUrl; ?>/uploads/default.png'">
              <div class="user-name"><?php echo htmlspecialchars($r["reporter_name"], ENT_QUOTES, "UTF-8"); ?></div>
            </div>
          </td>
          <td>
            <div class="user-cell">
              <img src="<?php echo $dAvatar; ?>" class="user-avatar" alt=""
                   onerror="this.src='<?php echo $baseUrl; ?>/uploads/default.png'">
              <div class="user-name"><?php echo htmlspecialchars($r["reported_name"], ENT_QUOTES, "UTF-8"); ?></div>
            </div>
          </td>
          <td>
            <div class="report-reason"><?php echo htmlspecialchars($r["reason"], ENT_QUOTES, "UTF-8"); ?></div>
          </td>
          <td><span class="last-seen"><?php echo $reportDate; ?></span></td>
          <td>
            <span class="badge <?php echo $statusClass; ?>">
              <span class="badge-dot"></span>
              <?php echo $statusLabel; ?>
            </span>
          </td>
          <td>
            <?php if ($status === "pending"): ?>
            <div class="report-action-btns">
              <button
                class="action-btn action-resolve"
                onclick="updateReport(<?php echo $r['id']; ?>, 'resolve')"
              >Resolve</button>
              <button
                class="action-btn action-dismiss"
                onclick="updateReport(<?php echo $r['id']; ?>, 'dismiss')"
              >Dismiss</button>
            </div>
            <?php else: ?>
              <span class="last-seen">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php
            endwhile;
        endif;

        if ($reportCount === 0): ?>
        <tr>
          <td colspan="6">
            <div class="empty-state">No reports yet — all is well 🌸</div>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div><!-- .main -->

<script>
  const statIds = ["totalUsers", "activeUsers", "totalMatches", "matchesToday", "messagesToday", "pendingReports"];

function refreshStats() {
  const xhr = new XMLHttpRequest();
  xhr.open("GET", "admin.php?ajax=1", true);
  xhr.onload = function () {
    if (xhr.status !== 200) return;
    try {
      const data = JSON.parse(xhr.responseText);

      statIds.forEach(key => {
        const el = document.getElementById("stat-" + key);
        if (!el) return;
        const newVal = String(data[key]);
        if (el.textContent.trim() !== newVal) {
          el.textContent = newVal;
          el.classList.add("updated");
          setTimeout(() => el.classList.remove("updated"), 800);
        }
      });

      const countEl = document.getElementById("user-count");
      if (countEl) countEl.textContent = data.totalUsers + " total";

      const badge = document.getElementById("nav-report-badge");
      if (badge) {
        badge.textContent = data.pendingReports;
        badge.style.display = data.pendingReports > 0 ? "inline-flex" : "none";
      }

      document.getElementById("last-updated").textContent =
        "Last updated: " + new Date().toLocaleTimeString();

    } catch(e) {}
  };
  xhr.send();
}

  setInterval(refreshStats, 5000);

  // ── Report actions ──
  function updateReport(id, action) {
    const formData = new FormData();
    formData.append(action + "_report", id);

    fetch("admin.php", { method: "POST", body: formData })
      .then(() => {
        const row = document.getElementById("report-row-" + id);
        if (!row) return;

        const badge = row.querySelector(".badge");
        const actionsCell = row.querySelector(".report-action-btns");

        if (action === "resolve") {
          badge.className = "badge badge-active";
          badge.innerHTML = '<span class="badge-dot"></span> Resolved';
        } else {
          badge.className = "badge badge-inactive";
          badge.innerHTML = '<span class="badge-dot"></span> Dismissed';
        }

        if (actionsCell) actionsCell.innerHTML = '<span class="last-seen">—</span>';
        refreshStats();
      });
  }

  // ── Sidebar scroll highlight ──
  const navItems = document.querySelectorAll(".nav-item");
  const anchors  = ["stats", "users", "matches", "reports"];

  window.addEventListener("scroll", () => {
    let current = "stats";
    anchors.forEach(id => {
      const el = document.getElementById(id);
      if (el && window.scrollY >= el.offsetTop - 140) current = id;
    });
    navItems.forEach(n => {
      n.classList.toggle("active", n.getAttribute("href") === "#" + current);
    });
  }, { passive: true });
</script>

</body>
</html>