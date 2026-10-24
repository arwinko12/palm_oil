<?php
require_once __DIR__ . '/../includes/access_control.php';

if (Auth::isLoggedIn()) {
    redirect_to(home_url_for(Auth::role()));
}

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ePalmHaul | Farmer Sign In</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../Styles/portal.css" rel="stylesheet">
</head>
<body>

<div class="login-shell">
    <div class="login-card">

        <!-- Brand panel -->
        <section class="login-brand">
            <a href="../index.php" class="lb-logo">
                <span class="lb-mark"><i class="bi bi-tree-fill"></i></span>
                ePalmHaul
            </a>

            <div>
                <h1>Raise a harvest request, track every haul.</h1>
                <p class="lb-sub">Sign in to the farmer portal to manage your farm records, request hauling and follow your deliveries and settlement.</p>
                <ul class="lb-list">
                    <li><i class="bi bi-check2-circle"></i> Submit and track hauling requests</li>
                    <li><i class="bi bi-check2-circle"></i> Follow your harvest cycle and schedule</li>
                    <li><i class="bi bi-check2-circle"></i> View weights and payment settlement</li>
                </ul>
            </div>

            <div class="lb-foot">&copy; 2026 Kenram Palm Oil Industry Incorporation</div>
        </section>

        <!-- Form panel -->
        <section class="login-pane">
            <div class="login-pane-inner">
                <a href="../index.php" class="login-mobile-logo"><i class="bi bi-tree-fill"></i> ePalmHaul</a>

                <div class="login-eyebrow">Farmer Portal</div>
                <h2>Sign in to continue</h2>
                <p class="login-desc">Enter your account details to manage your harvest and hauling.</p>

                <form onsubmit="return login();" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email address</label>
                        <input type="email" id="email" name="email" class="form-control" autocomplete="email" placeholder="you@example.com" required>
                    </div>
                    <div class="mb-1">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-group">
                            <input type="password" id="password" name="password" class="form-control" autocomplete="current-password" placeholder="Enter your password" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Show password" aria-pressed="false">
                                <i class="bi bi-eye" id="passwordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="login-row">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <a href="register.php" class="login-link">Create an account</a>
                    </div>

                    <button type="submit" class="btn-signin">Sign in <i class="bi bi-arrow-right"></i></button>
                    <button type="button" class="btn-ghost" onclick="back_to_main_menu()">
                        <i class="bi bi-arrow-left"></i> Back to main menu
                    </button>

                    <div class="login-note"><i class="bi bi-shield-lock-fill"></i> Your account is protected by secure authentication.</div>
                </form>
            </div>
        </section>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const CSRF = '<?= $csrf ?>';

    function login() {
        $.ajax({
            url: '../auth/handle_login.php',
            type: 'post',
            dataType: 'json',
            data: { email: $('#email').val(), password: $('#password').val(), role: 'farmer', csrf_token: CSRF },
            success: function (res) {
                if (res.ok) { window.location.href = res.redirect; }
                else { alert(res.error || 'Login failed.'); }
            },
            error: function () { alert('Something went wrong. Please try again.'); }
        });
        return false;
    }

    function back_to_main_menu() { window.location.href = '../index.php'; }

    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const passwordIcon = document.getElementById('passwordIcon');
    togglePassword.addEventListener('click', function () {
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        passwordIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
        togglePassword.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        togglePassword.setAttribute('aria-pressed', String(isPassword));
    });
</script>
</body>
</html>
