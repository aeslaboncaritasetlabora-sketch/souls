<?php
session_start();
require_once "includes/db.php";

$error   = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username    = trim($_POST["username"]    ?? "");
    $email       = trim($_POST["email"]       ?? "");
    $password    = trim($_POST["password"]    ?? "");
    $age         = intval($_POST["age"]       ?? 0);
    $birthday    = trim($_POST["birthday"]    ?? "");
    $bio         = trim($_POST["bio"]         ?? "");
    $looking_for = trim($_POST["looking_for"] ?? "");

    // Hobbies: array → comma-separated string
    $hobbies_arr = $_POST["hobbies"] ?? [];
    $hobbies     = implode(", ", array_map("trim", $hobbies_arr));

    if (!empty($username) && !empty($email) && !empty($password)) {

        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = "Email already registered.";
        } else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $profile_pic_name = null;
            if (!empty($_FILES["profile_pic"]["name"])) {
                $target_dir       = "uploads/";
                $profile_pic_name = time() . "_" . basename($_FILES["profile_pic"]["name"]);
                move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_dir . $profile_pic_name);
            }

            $stmt = $conn->prepare("
                INSERT INTO users
                    (username, email, password, age, birthday, bio, profile_pic, hobbies, looking_for)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "sssisssss",
                $username, $email, $hashed_password,
                $age, $birthday, $bio, $profile_pic_name,
                $hobbies, $looking_for
            );

            if ($stmt->execute()) {
                $success = "Account created! You can now login.";
            } else {
                $error = "Something went wrong.";
            }
        }

    } else {
        $error = "Please fill in all required fields.";
    }
}

$hobby_options = [
    "Reading", "Gaming", "Cooking", "Hiking", "Traveling",
    "Music", "Art", "Photography", "Fitness", "Movies",
    "Dancing", "Writing", "Yoga", "Coffee", "Anime"
];

$preference_options = [
    "friends"   => "👫 Looking for Friends",
    "fling"     => "✨ Fling",
    "short_term"=> "🌸 Short-term Relationship",
    "long_term" => "💍 Long-term Relationship",
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Soulence — Register</title>
    <link rel="stylesheet" href="css/register.css" />
</head>
<body>

<div class="register-container">
    <div class="register-card">

        <h1 class="logo">Soulence</h1>
        <p class="tagline">Create your account</p>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <div class="form-group">
                <input type="text" name="username" placeholder="Username" required />
            </div>
            <div class="form-group">
                <input type="email" name="email" placeholder="Email" required />
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required />
            </div>
            <div class="form-group">
                <input type="number" name="age" placeholder="Age" min="18" max="99" />
            </div>
            <div class="form-group">
                <label class="field-label">Birthday</label>
                <input type="date" name="birthday" />
            </div>
            <div class="form-group">
                <textarea name="bio" placeholder="Short bio — tell people about yourself..."></textarea>
            </div>
            <div class="form-group">
                <label class="field-label">Profile Photo</label>
                <input type="file" name="profile_pic" accept="image/*" />
            </div>

            <!-- ── Hobbies ── -->
            <div class="form-group">
                <label class="field-label">Hobbies <span class="field-hint">(pick as many as you like)</span></label>
                <div class="hobby-grid">
                    <?php foreach ($hobby_options as $hobby): ?>
                        <label class="hobby-chip">
                            <input type="checkbox" name="hobbies[]" value="<?php echo $hobby; ?>" />
                            <span><?php echo $hobby; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ── Looking For ── -->
            <div class="form-group">
                <label class="field-label">I'm looking for…</label>
                <div class="preference-grid">
                    <?php foreach ($preference_options as $val => $label): ?>
                        <label class="preference-chip">
                            <input type="radio" name="looking_for" value="<?php echo $val; ?>" />
                            <span><?php echo $label; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="submit-btn">Create Account</button>

        </form>

        <p class="login-link">
            Already have an account? <a href="login.php">Login</a>
        </p>

    </div>
</div>

</body>
</html>