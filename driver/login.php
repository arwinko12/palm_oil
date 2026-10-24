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
    <title>ePalmHaul | Driver Sign In</title>
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
                <h1>Your hauls, routes and delivery status in one view.</h1>
                <p class="lb-sub">Sign in to the driver portal to see the trips dispatched to you and keep every delivery up to date.</p>
                <ul class="lb-list">
                    <li><i class="bi bi-check2-circle"></i> Assigned hauling trips and schedules</li>
                    <li><i class="bi bi-check2-circle"></i> Farm locations and load details</li>
                    <li><i class="bi bi-check2-circle"></i> Update arrival and delivery progress</li>
                </ul>
            </div>

            <div class="lb-foot">&copy; 2026 Kenram Palm Oil Industry Incorporation</div>
        </section>

        <!-- Form panel -->
        <section class="login-pane">
            <div class="login-pane-inner">
                <a href="../index.php" class="login-mobile-logo"><i class="bi bi-tree-fill"></i> ePalmHaul</a>

                <div class="login-eyebrow">Driver Portal</div>
                <h2>Sign in to continue</h2>
                <p class="login-desc">Enter your account details to access your assignments.</p>

                <form onsubmit="return doLogin();" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email address</label>
                        <input type="email" id="email" class="form-control" autocomplete="email" placeholder="you@example.com" required>
                    </div>
                    <div class="mb-1">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-group">
                            <input type="password" id="password" class="form-control" autocomplete="current-password" placeholder="Enter your password" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Show password">
                                <i class="bi bi-eye" id="passwordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-signin">Sign in <i class="bi bi-arrow-right"></i></button>
                    <button type="button" class="btn-ghost" onclick="location.href='../index.php'">
                        <i class="bi bi-arrow-left"></i> Back to main menu
                    </button>

                    <div class="login-note"><i class="bi bi-shield-lock-fill"></i> Secure, role-based sign-in for authorised personnel.</div>
                </form>
            </div>
        </section>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script>
    const CSRF = document.querySelector('input[name="csrf_token"]').value;
    function doLogin() {
        $.ajax({
            url: '../auth/handle_login.php',
            type: 'post',
            dataType: 'json',
            data: { email: $('#email').val(), password: $('#password').val(), role: 'driver', csrf_token: CSRF },
            success: function (res) {
                if (res.ok) { window.location.href = res.redirect; }
                else { alert(res.error || 'Login failed.'); }
            },
            error: function () { alert('Something went wrong. Please try again.'); }
        });
        return false;
    }

    const tp = document.getElementById('togglePassword'), pw = document.getElementById('password'), pi = document.getElementById('passwordIcon');
    tp.addEventListener('click', function () {
        const show = pw.type === 'password';
        pw.type = show ? 'text' : 'password';
        pi.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        tp.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
</script>
</body>
</html>
