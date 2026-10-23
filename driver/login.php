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
    <title>ePalmHaul | Driver Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; margin: 0; font-family: Arial, sans-serif; background: #f5f8f5; }
        .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 30px 16px; }
        .card-login { width: 100%; max-width: 420px; background: #fff; border-radius: 18px;
            box-shadow: 0 15px 40px rgba(26,65,39,.08); padding: 38px 32px; }
        .brand { display: flex; align-items: center; gap: 10px; justify-content: center;
            color: #198754; font-weight: 800; font-size: 22px; margin-bottom: 6px; }
        .brand i { font-size: 26px; }
        .muted { color: #7b877f; font-size: 14px; text-align: center; margin-bottom: 26px; }
        .form-control { min-height: 48px; border-radius: 9px; }
        .btn-main { width: 100%; min-height: 48px; border: none; border-radius: 9px;
            background: #198754; color: #fff; font-weight: 700; }
        .btn-main:hover { background: #126640; color: #fff; }
        .back { margin-top: 12px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card-login">
        <div class="brand"><i class="bi bi-truck-front-fill"></i> ePalmHaul</div>
        <p class="muted">Driver portal — sign in to view your assignments.</p>

        <form onsubmit="return doLogin();">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label fw-bold">Email</label>
                <input type="email" id="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Password</label>
                <input type="password" id="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-main">Sign In</button>
            <button type="button" class="btn btn-outline-secondary w-100 back"
                    onclick="location.href='../index.php'">Back to Main Menu</button>
        </form>
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
            data: {
                email: $('#email').val(),
                password: $('#password').val(),
                role: 'driver',
                csrf_token: CSRF
            },
            success: function (res) {
                if (res.ok) { window.location.href = res.redirect; }
                else { alert(res.error || 'Login failed.'); }
            },
            error: function () { alert('Something went wrong. Please try again.'); }
        });
        return false;
    }
</script>
</body>
</html>
