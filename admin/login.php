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

    <title>PalmHaul | Login</title>

    <!-- Bootstrap 5.3.8 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #198754;
            --primary-dark: #126640;
            --light-green: #eaf6ee;
            --text: #25352c;
            --muted: #7b877f;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f8f5;
            color: var(--text);
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 16px;
        }

        .login-container {
            width: 100%;
            max-width: 1050px;
            min-height: 600px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 50px rgba(26, 65, 39, 0.08);
        }

        /* Left panel */
        .login-banner {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 45px;
            overflow: hidden;
            color: white;
            background: linear-gradient(145deg, #198754, #105c39);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 24px;
            font-weight: 800;
            color: white;
            text-decoration: none;
        }

        .brand-icon {
            width: 45px;
            height: 45px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.15);
            font-size: 25px;
        }

        .banner-content {
            position: relative;
            z-index: 1;
            margin: 45px 0;
        }

        .banner-content h1 {
            max-width: 390px;
            margin-bottom: 20px;
            font-size: clamp(30px, 3vw, 43px);
            font-weight: 800;
            line-height: 1.2;
        }

        .banner-content p {
            max-width: 370px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 15px;
            line-height: 1.8;
        }

        .banner-illustration {
            position: absolute;
            right: -25px;
            bottom: 85px;
            font-size: 190px;
            opacity: 0.10;
            transform: rotate(-12deg);
        }

        .banner-footer {
            position: relative;
            z-index: 1;
            color: rgba(255, 255, 255, 0.7);
            font-size: 12px;
        }

        /* Right panel */
        .login-form-area {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 45px;
        }

        .login-form {
            width: 100%;
            max-width: 360px;
        }

        .mobile-brand {
            display: none;
        }

        .login-heading {
            margin-bottom: 8px;
            font-size: 29px;
            font-weight: 800;
        }

        .login-description {
            margin-bottom: 30px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .form-label {
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 700;
        }

        .form-control,
        .form-select,
        .input-group-text {
            min-height: 48px;
            border-color: #e2e9e3;
            font-size: 13px;
        }

        .form-control,
        .form-select {
            border-radius: 9px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.10);
        }

        .input-group .form-control {
            border-radius: 9px 0 0 9px;
        }

        .input-group .btn {
            border-color: #e2e9e3;
            border-radius: 0 9px 9px 0;
            color: #69766d;
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .form-check-label {
            color: #69766d;
            font-size: 12px;
        }

        .forgot-link {
            color: var(--primary);
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
        }

        .forgot-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            min-height: 48px;
            margin-top: 25px;
            border: none;
            border-radius: 9px;
            background: var(--primary);
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            transition: background 0.2s, transform 0.2s;
        }

        .btn-login:hover {
            background: var(--primary-dark);
            color: #fff;
            transform: translateY(-1px);
        }

        .security-note {
            margin-top: 25px;
            color: var(--muted);
            font-size: 11px;
            text-align: center;
        }

        .security-note i {
            color: var(--primary);
        }

        /* Responsive */
        @media (max-width: 767.98px) {
            .login-wrapper {
                padding: 20px 14px;
            }

            .login-container {
                max-width: 460px;
                min-height: auto;
                grid-template-columns: 1fr;
                border-radius: 16px;
            }

            .login-banner {
                display: none;
            }

            .login-form-area {
                padding: 35px 25px;
            }

            .mobile-brand {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                margin-bottom: 35px;
                color: var(--primary);
                font-size: 22px;
                font-weight: 800;
            }

            .mobile-brand i {
                font-size: 28px;
            }

            .login-heading {
                font-size: 26px;
            }
        }

        @media (max-width: 360px) {
            .login-form-area {
                padding: 28px 18px;
            }

            .login-heading {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>

<div class="login-wrapper">
    <div class="login-container">

        <!-- Left Branding Panel -->
        <section class="login-banner">

            <a href="index.php" class="brand">
                <span class="brand-icon">
                    <i class="bi bi-tree-fill"></i>
                </span>
                PalmHaul
            </a>

            <div class="banner-content">
                <h1>Manage your harvest. Move forward.</h1>

                <p>
                    A smarter way to manage palm harvest requests,
                    hauling schedules, drivers, and weighing operations
                    in one convenient platform.
                </p>

                <div class="mt-4">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Simple and efficient harvest management
                    <br><br>

                    <i class="bi bi-check-circle-fill me-2"></i>
                    Organized hauling operations
                    <br><br>

                    <i class="bi bi-check-circle-fill me-2"></i>
                    Reliable weighing records
                </div>
            </div>

            <i class="bi bi-tree-fill banner-illustration"></i>

            <div class="banner-footer">
                &copy; 2026 PalmHaul. All rights reserved.
            </div>

        </section>

        <!-- Login Form -->
        <section class="login-form-area">

            <div class="login-form">

                <a href="index.php" class="mobile-brand">
                    <i class="bi bi-tree-fill"></i>
                    PalmHaul
                </a>

                <h2 class="login-heading">Welcome back!</h2>

                <p class="login-description">
                    Please enter your account details to sign in.
                </p>

                <!-- Connect this form to your PHP login handler -->
                <form onsubmit="return doLogin();">

                    <?= csrf_field() ?>
                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            Email
                        </label>

                        <input
                            type="email"
                            class="form-control"
                            id="email"
                            name="email"
                            placeholder="Enter your email"
                            autocomplete="email"
                            required>
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            Password
                        </label>

                        <div class="input-group">
                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required>

                            <button
                                class="btn btn-outline-secondary"
                                type="button"
                                id="togglePassword"
                                aria-label="Show password"
                                aria-pressed="false">
                                <i class="bi bi-eye" id="passwordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember / Forgot -->
                    <div class="d-flex justify-content-between align-items-center mt-3 gap-2">

                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                value="1"
                                id="remember"
                                name="remember">

                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>

                        <a href="forgot-password.php" class="forgot-link">
                            Forgot password?
                        </a>

                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn btn-login">
                        Sign In
                        <i class="bi bi-arrow-right ms-2"></i>
                    </button>

                </form>

                    <button type="button" class="btn btn-login" onclick="back_to_main_menu()">
                        <i class="bi bi-arrow-left ms-2"></i>
                        Back to Main Menu
                    </button>

                <div class="security-note">
                    <i class="bi bi-shield-lock-fill me-1"></i>
                    Your account is protected by secure authentication.
                </div>

            </div>

        </section>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

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
                role: 'admin',
                csrf_token: CSRF
            },
            success: function (res) {
                if (res.ok) {
                    window.location.href = res.redirect;
                } else {
                    alert(res.error || 'Login failed.');
                }
            },
            error: function () {
                alert('Something went wrong. Please try again.');
            }
        });
        return false;
    }

    function back_to_main_menu() {
        window.location.href = '../index.php';
    }

    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const passwordIcon = document.getElementById('passwordIcon');

    togglePassword.addEventListener('click', function () {
        const isPassword = passwordInput.type === 'password';

        passwordInput.type = isPassword ? 'text' : 'password';
        passwordIcon.className = isPassword
            ? 'bi bi-eye-slash'
            : 'bi bi-eye';

        togglePassword.setAttribute(
            'aria-label',
            isPassword ? 'Hide password' : 'Show password'
        );

        togglePassword.setAttribute('aria-pressed', String(isPassword));
    });
</script>

</body>
</html>
```