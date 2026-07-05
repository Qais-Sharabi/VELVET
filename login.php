<?php
include 'dbconnect.php';
include 'auth.php';

// Already logged in? Go home (or manager if admin)
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'Manager.php' : 'index.php'));
    exit;
}

$error = $_GET['error'] ?? '';
$next  = $_GET['next'] ?? '';
$errorMessages = [
    'wrong_password'  => 'Incorrect password. Please try again.',
    'no_account'      => 'No account found with that email.',
    'missing_fields'  => 'Please fill in all fields.',
    'account_inactive'=> 'Your account has been deactivated. Contact support.',
];
$errorMsg = $errorMessages[$error] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — VELVET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="login_style.css">
</head>
<body>
<div class="login-wrapper">
    <div class="left-panel">
        <div class="brand-mark"><a href="index.php">VELVET</a></div>
        <div class="left-headline">
            <span class="eyebrow">Members Area</span>
            <h2>Style is a way<br>to say <em>who you are</em><br>without speaking.</h2>
            <p>Sign in to access your orders and exclusive member-only collections curated just for you.</p>
        </div>
        <div class="trust-row">
            <div class="trust-item"><i class="fas fa-lock"></i> Secure Login</div>
            <div class="trust-item"><i class="fas fa-shield-halved"></i> Data Protected</div>
            <div class="trust-item"><i class="fas fa-headset"></i> 24/7 Support</div>
        </div>
        <div class="deco-word">VELVET</div>
    </div>

    <div class="right-panel">
        <?php if ($errorMsg): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="border-radius:0;font-size:.9rem;">
            <i class="fas fa-circle-exclamation me-2"></i><?= htmlspecialchars($errorMsg) ?>
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form action="login_process.php<?= $next ? '?next='.urlencode($next) : '' ?>" method="POST" id="loginForm">
            <div class="form-header">
                <p class="greeting">Welcome back</p>
                <h1>Sign In</h1>
                <p>Enter your credentials to access your account.</p>
            </div>

            <div class="field-group" id="emailGroup">
                <label class="field-label" for="emailField">Email Address</label>
                <div class="field-wrap">
                    <i class="fas fa-envelope field-icon"></i>
                    <input type="email" id="emailField" name="email" placeholder="your@email.com"
                           value="<?= htmlspecialchars($_GET['email'] ?? '') ?>"
                           autocomplete="email" oninput="clearError('emailGroup')" required>
                </div>
                <span class="field-error" id="emailError">Please enter a valid email address.</span>
            </div>

            <div class="field-group" id="passwordGroup">
                <label class="field-label" for="passwordField">Password</label>
                <div class="field-wrap">
                    <i class="fas fa-lock field-icon"></i>
                    <input type="password" id="passwordField" name="password" placeholder="••••••••"
                           autocomplete="current-password" oninput="clearError('passwordGroup')" required>
                    <button class="pw-toggle" type="button" onclick="togglePassword()" tabindex="-1">
                        <i class="fas fa-eye" id="pwIcon"></i>
                    </button>
                </div>
                <span class="field-error" id="passwordError">Password must be at least 6 characters.</span>
            </div>

            <div class="options-row">
                <label class="remember-label">
                    <input type="checkbox" name="remember" id="rememberMe"> Remember me
                </label>
                <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
            </div>

            <button type="submit" name="login_btn" class="btn-signin" id="signinBtn">
                <span>Sign In</span>
            </button>

            <div class="signup-cta">
                Don't have an account? <a href="signup.php">Create one</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePassword() {
    const input = document.getElementById('passwordField');
    const icon  = document.getElementById('pwIcon');
    const show  = input.type === 'password';
    input.type  = show ? 'text' : 'password';
    icon.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
}
function clearError(groupId) {
    document.getElementById(groupId).classList.remove('has-error');
}
function validateForm() {
    const email    = document.getElementById('emailField').value.trim();
    const password = document.getElementById('passwordField').value;
    let valid = true;
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        document.getElementById('emailGroup').classList.add('has-error'); valid = false;
    }
    if (password.length < 6) {
        document.getElementById('passwordGroup').classList.add('has-error'); valid = false;
    }
    return valid;
}
document.getElementById('loginForm').addEventListener('submit', function(e) {
    if (!validateForm()) e.preventDefault();
});
</script>
</body>
</html>
