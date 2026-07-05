<?php
date_default_timezone_set('Asia/Hebron');
include 'dbconnect.php';
include 'auth.php';
include 'mailer.php';
/** @var PDO $pdo */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (isLoggedIn()) { header('Location: index.php'); exit; }

$step    = (int)($_SESSION['reset_step'] ?? 1);
$message = '';
$error   = '';
$show_code_on_screen = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_code'])) {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
        $step  = 1;
    } else {
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $code    = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires = date('Y-m-d H:i:s', strtotime('+2 hours'));
            // Clean up old tokens and insert new one
            $pdo->prepare("DELETE FROM password_reset_tokens WHERE user_id = ?")->execute([$user['id']]);
            $pdo->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?,?,?)")
                    ->execute([$user['id'], $code, $expires]);

            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_uid']   = $user['id'];
            $_SESSION['reset_step']  = 2;

            $mailResult = sendResetCode($email, $user['full_name'], $code);

            if ($mailResult === true) {
                $message = "A 6-digit code has been sent to <strong>" . htmlspecialchars($email) . "</strong>.";
            } else {
                // If SMTP fails, show code on screen for testing
                $show_code_on_screen = $code;
                $message = "Mail server error. Your reset code is shown below for testing:";
            }
        } else {
            // Security: Don't confirm if user exists, just act like it worked
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_uid']   = 0;
            $_SESSION['reset_step']  = 2;
            $message = "If an account exists, a code has been sent.";
        }
        $step = 2;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
    $code = trim($_POST['code'] ?? '');
    $uid  = (int)($_SESSION['reset_uid'] ?? 0);

    if (!$uid || !$code) {
        $error = 'Session expired. Please start again.';
        $step  = 1;
        unset($_SESSION['reset_step'], $_SESSION['reset_uid'], $_SESSION['reset_email']);
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM password_reset_tokens
            WHERE user_id = ? AND token = ? AND used = 0 AND expires_at > NOW()
        ");
        $stmt->execute([$uid, $code]);
        $token = $stmt->fetch();

        if ($token) {
            $_SESSION['reset_verified'] = true;
            $_SESSION['reset_token_id'] = $token['id'];
            $_SESSION['reset_step']     = 3;
            $step = 3;
        } else {
            $error = 'Invalid or expired code. Please try again.';
            $step  = 2;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $new_pw  = $_POST['new_password']     ?? '';
    $conf_pw = $_POST['confirm_password'] ?? '';
    $uid     = (int)($_SESSION['reset_uid']      ?? 0);
    $tid     = (int)($_SESSION['reset_token_id'] ?? 0);

    if (!($_SESSION['reset_verified'] ?? false) || !$uid) {
        $error = 'Session expired. Please start again.';
        $step = 1;
    } elseif (strlen($new_pw) < 6) {
        $error = 'Password must be at least 6 characters.';
        $step = 3;
    } elseif ($new_pw !== $conf_pw) {
        $error = 'Passwords do not match.';
        $step = 3;
    } else {
        $hash = password_hash($new_pw, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $uid]);

        $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE id = ?")->execute([$tid]);

        unset($_SESSION['reset_email'], $_SESSION['reset_uid'], $_SESSION['reset_verified'],
                $_SESSION['reset_token_id'], $_SESSION['reset_step']);

        header('Location: login.php?msg=password_reset');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_code'])) {
    unset($_SESSION['reset_step'], $_SESSION['reset_uid'], $_SESSION['reset_email'],
            $_SESSION['reset_verified'], $_SESSION['reset_token_id']);
    header('Location: forgot_password.php');
    exit;
}

$step = (int)($_SESSION['reset_step'] ?? $step);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — VELVET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Forgot%20Password.css">
</head>
<body>
<div class="forgot-card">
    <div class="text-center mb-4">
        <a href="index.php" class="text-decoration-none">
            <h3 class="fw-bold" style="letter-spacing:4px;color:#111;">VELVET</h3>
        </a>
        <p class="text-muted small mt-1">Account Recovery</p>
    </div>

    <div class="d-flex justify-content-center gap-2 mb-4">
        <?php for ($i = 1; $i <= 3; $i++): ?>
            <div style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;
                    font-size:12px;font-weight:700;
                    background:<?= $i <= $step ? '#b30000' : '#e9ecef' ?>;
                    color:<?= $i <= $step ? '#fff' : '#888' ?>;">
                <?= $i ?>
            </div>
            <?php if ($i < 3): ?>
                <div style="flex:1;height:2px;margin:auto;background:<?= $i < $step ? '#b30000' : '#e9ecef' ?>;"></div>
            <?php endif; ?>
        <?php endfor; ?>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><i class="fas fa-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="alert alert-success py-2 small"><i class="fas fa-check-circle me-1"></i><?= $message ?></div>
    <?php endif; ?>

    <?php if ($show_code_on_screen): ?>
        <div class="text-center my-3 p-3" style="background:#fff3cd;border:1px solid #ffc107;border-radius:8px;">
            <small class="text-muted d-block mb-1">Your verification code:</small>
            <span style="font-size:2rem;font-weight:800;letter-spacing:8px;color:#111;"><?= $show_code_on_screen ?></span>
            <small class="text-muted d-block mt-1">Valid for 15 minutes</small>
        </div>
    <?php endif; ?>

    <?php if ($step == 1): ?>
        <div class="step active">
            <h5 class="fw-bold mb-1">Forgot your password?</h5>
            <p class="text-muted small mb-3">Enter your email and we'll send you a reset code.</p>
            <form method="POST" action="forgot_password.php">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="your@email.com" required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <button type="submit" name="send_code" class="btn-signin btn mt-2">
                    <i class="fas fa-paper-plane me-2"></i>Send Code
                </button>
            </form>
            <div class="text-center mt-3">
                <a href="login.php" class="text-muted small text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i> Back to Login
                </a>
            </div>
        </div>

    <?php elseif ($step == 2): ?>
        <div class="step active">
            <h5 class="fw-bold mb-1">Enter Verification Code</h5>
            <p class="text-muted small mb-3">
                Enter the 6-digit code sent to <strong><?= htmlspecialchars($_SESSION['reset_email'] ?? '') ?></strong>
            </p>
            <form method="POST" action="forgot_password.php">
                <div class="mb-3">
                    <label class="form-label small fw-bold">6-Digit Code</label>
                    <input type="text" name="code" class="form-control text-center fw-bold"
                           style="font-size:1.6rem;letter-spacing:8px;"
                           placeholder="000000" maxlength="6" inputmode="numeric"
                           autocomplete="one-time-code" required autofocus>
                </div>
                <button type="submit" name="verify_code" class="btn-signin btn mt-2">
                    <i class="fas fa-check me-2"></i>Verify Code
                </button>
            </form>
            <form method="POST" action="forgot_password.php" class="text-center mt-3">
                <button type="submit" name="resend_code"
                        class="btn btn-link text-muted small p-0 text-decoration-none">
                    <i class="fas fa-rotate-left me-1"></i> Resend code
                </button>
            </form>
        </div>

    <?php elseif ($step == 3): ?>
        <div class="step active">
            <h5 class="fw-bold mb-1">Set New Password</h5>
            <p class="text-muted small mb-3">Choose a strong password (at least 6 characters).</p>
            <form method="POST" action="forgot_password.php" id="resetForm">
                <div class="mb-3">
                    <label class="form-label small fw-bold">New Password</label>
                    <div class="input-group">
                        <input type="password" id="pw1" name="new_password" class="form-control"
                               placeholder="At least 6 characters" required autofocus>
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePw('pw1','icon1')">
                            <i class="fas fa-eye" id="icon1"></i>
                        </button>
                    </div>
                    <div class="error-msg" id="pwErr" style="display:none; color:red; font-size:12px;">Minimum 6 characters.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Confirm Password</label>
                    <div class="input-group">
                        <input type="password" id="pw2" name="confirm_password" class="form-control"
                               placeholder="Repeat password" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePw('pw2','icon2')">
                            <i class="fas fa-eye" id="icon2"></i>
                        </button>
                    </div>
                    <div class="error-msg" id="confErr" style="display:none; color:red; font-size:12px;">Passwords do not match.</div>
                </div>
                <button type="submit" name="update_password" class="btn-signin btn mt-2">
                    <i class="fas fa-shield-halved me-2"></i>Update Password
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
    function togglePw(fieldId, iconId) {
        const f = document.getElementById(fieldId);
        const i = document.getElementById(iconId);
        f.type = f.type === 'password' ? 'text' : 'password';
        i.className = f.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
    }
    document.addEventListener('DOMContentLoaded', function() {
        const c = document.querySelector('input[name="code"]');
        if (c) c.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g,'').slice(0,6);
        });
    });
    const rf = document.getElementById('resetForm');
    if (rf) rf.addEventListener('submit', function(e) {
        let ok = true;
        const pw = document.getElementById('pw1').value;
        const cp = document.getElementById('pw2').value;
        document.getElementById('pwErr').style.display   = 'none';
        document.getElementById('confErr').style.display = 'none';
        if (pw.length < 6) { document.getElementById('pwErr').style.display   = 'block'; ok = false; }
        if (pw !== cp)      { document.getElementById('confErr').style.display = 'block'; ok = false; }
        if (!ok) e.preventDefault();
    });
</script>
</body>
</html>