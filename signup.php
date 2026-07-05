<?php
include 'dbconnect.php';
include 'auth.php';
/** @var PDO $pdo */

if (isLoggedIn()) { header('Location: index.php'); exit; }

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name        = trim($_POST['full_name']        ?? '');
    $email            = trim($_POST['email']            ?? '');
    $phone            = trim($_POST['phone']            ?? '');
    $password         = $_POST['password']              ?? '';
    $confirm_password = $_POST['confirm_password']      ?? '';

    if (!$full_name || !$email || !$password || !$confirm_password) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'An account with this email already exists. <a href="login.php" class="text-danger">Sign in instead</a>.';
        } else {
            $hash   = password_hash($password, PASSWORD_DEFAULT);
            $letter = strtoupper(substr($full_name, 0, 1));

            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, phone, role, avatar_letter) VALUES (?,?,?,?,'customer',?)");
            $stmt->execute([$full_name, $email, $hash, $phone, $letter]);
            $new_id = (int)$pdo->lastInsertId();

            $pdo->prepare("INSERT INTO user_settings (user_id, display_name) VALUES (?,?)")
                    ->execute([$new_id, $full_name]);

            populateSession(['id' => $new_id, 'full_name' => $full_name, 'role' => 'customer']);
            header('Location: index.php?welcome=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — VELVET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="signup.css">
</head>
<body>
<div class="signup-card">
    <div class="text-center mb-0">
        <a href="index.php" class="text-decoration-none">
            <h3 class="fw-bold" style="letter-spacing:4px;color:#111;">VELVET</h3>
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" action="signup.php" id="signupForm" novalidate>
        <div class="mb-1">
            <label class="form-label small fw-bold">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="full_name" class="form-control"
                   placeholder="Your full name" required
                   value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
        </div>
        <div class="mb-1">
            <label class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control"
                   placeholder="your@email.com" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="mb-1">
            <label class="form-label small fw-bold">Phone Number</label>
            <input type="tel" name="phone" class="form-control"
                   placeholder="+970 5x-xxx-xxxx"
                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>
        <div class="mb-1">
            <label class="form-label small fw-bold">Password <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="password" id="pw1" name="password" class="form-control"
                       placeholder="At least 6 characters" required>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePw('pw1','ic1')">
                    <i class="fas fa-eye" id="ic1"></i>
                </button>
            </div>
        </div>
        <div class="mb-1">
            <label class="form-label small fw-bold">Confirm Password <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="password" id="pw2" name="confirm_password" class="form-control"
                       placeholder="Repeat your password" required>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePw('pw2','ic2')">
                    <i class="fas fa-eye" id="ic2"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-signin btn mt-0 ">Create Account</button>
    </form>

</div>

<script>
    function togglePw(fid, iid) {
        const f = document.getElementById(fid), i = document.getElementById(iid);
        f.type = f.type === 'password' ? 'text' : 'password';
        i.className = f.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
    }
    document.getElementById('signupForm').addEventListener('submit', function(e) {
        const pw = document.getElementById('pw1').value;
        const cp = document.getElementById('pw2').value;
        if (pw.length < 6) { alert('Password must be at least 6 characters.'); e.preventDefault(); return; }
        if (pw !== cp) { alert('Passwords do not match.'); e.preventDefault(); }
    });
</script>
</body>
</html>
