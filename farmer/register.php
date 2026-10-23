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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Account | PalmHaul</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
          rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            background: #f1f5f1;
            font-family: Arial, sans-serif;
        }

        .register-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
        }

        .register-card {
            width: 100%;
            max-width: 980px;
            border: 0;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 45px rgba(0, 0, 0, .10);
        }

        .brand-panel {
            background: linear-gradient(145deg, #14532d, #228b48);
            color: white;
            padding: 45px 35px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .brand-icon {
            width: 65px;
            height: 65px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,.15);
            border-radius: 18px;
            font-size: 32px;
            margin-bottom: 25px;
        }

        .form-panel {
            background: #fff;
            padding: 40px;
        }

        .form-control, .form-select {
            min-height: 48px;
            border-radius: 10px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #228b48;
            box-shadow: 0 0 0 .2rem rgba(34,139,72,.13);
        }

        .btn-register {
            min-height: 48px;
            border: none;
            border-radius: 10px;
            background: #198754;
            color: white;
            font-weight: 600;
        }

        .btn-register:hover {
            background: #146c43;
            color: white;
        }

        .field-label {
            font-size: .9rem;
            font-weight: 600;
            margin-bottom: 7px;
        }

        @media (max-width: 767px) {
            .brand-panel {
                padding: 30px 25px;
            }

            .form-panel {
                padding: 28px 22px;
            }
        }
    </style>
</head>
<body>

<div class="register-wrapper">
    <div class="card register-card">
        <div class="row g-0">

            <!-- Brand panel -->
            <div class="col-md-5 brand-panel">
                <div class="brand-icon">
                    <i class="bi bi-tree-fill"></i>
                </div>

                <h2 class="fw-bold mb-3">PalmHaul</h2>

                <h5 class="mb-3">Join our hauling management system.</h5>

                <p class="text-white-50 mb-4">
                    Manage harvest hauling requests, schedules, deliveries,
                    and farm transportation in one place.
                </p>

                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Easy account registration</span>
                </div>

                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-truck"></i>
                    <span>Hauling and delivery management</span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-check"></i>
                    <span>Secure password storage</span>
                </div>
            </div>

            <!-- Registration form -->
            <div class="col-md-7 form-panel">
                <div class="mb-4">
                    <h3 class="fw-bold mb-2">Create Account</h3>
                    <p class="text-secondary mb-0">
                        Fill in your details to get started.
                    </p>
                </div>


               
                    <div class="mb-3">
                        <label for="full_name" class="form-label field-label">
                            Full Name
                        </label>
                        <input type="text"
                               class="form-control"
                               id="full_name"
                               name="full_name"
                               placeholder="Enter your full name"
                               value=""
                               maxlength="150"
                               autocomplete="name"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label field-label">
                            Email Address
                        </label>
                        <input type="email"
                               class="form-control"
                               id="email"
                               name="email"
                               placeholder="you@example.com"
                               value=""
                               maxlength="150"
                               autocomplete="email"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="contact_no" class="form-label field-label">
                            Contact Number
                        </label>
                        <input type="tel"
                               class="form-control"
                               id="contact_no"
                               name="contact_no"
                               placeholder="09XXXXXXXXX"
                               value=""
                               maxlength="30"
                               autocomplete="tel"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label field-label">
                            Account Type
                        </label>
                        <input type="text" class="form-control" id="role"
                               value="Farmer" readonly>
                        <div class="form-text">
                            This form creates a Farmer account only. Admin,
                            driver and weighbridge accounts are created
                            through a protected administrative process.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label field-label">
                            Password
                        </label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control"
                                   id="password"
                                   name="password"
                                   placeholder="At least 8 characters"
                                   minlength="8"
                                   autocomplete="new-password"
                                   required>
                            <button class="btn btn-outline-secondary"
                                    type="button"
                                    id="togglePassword"
                                    aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password"
                               class="form-label field-label">
                            Confirm Password
                        </label>
                        <input type="password"
                               class="form-control"
                               id="confirm_password"
                               name="confirm_password"
                               placeholder="Re-enter your password"
                               minlength="8"
                               autocomplete="new-password"
                               required>
                    </div>

                    <button onclick="Register_Users()" 
                            class="btn btn-register w-100">
                        <i class="bi bi-person-plus me-2"></i>
                        Create Account
                    </button>

                    <p class="text-center text-secondary mt-4 mb-0">
                        Already have an account?
                        <a href="login.php"
                           class="text-success fw-semibold text-decoration-none">
                            Sign In
                        </a>
                    </p>
               
            </div>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script>

    const CSRF = '<?php echo $csrf; ?>';

    function Register_Users() {
        let full_name = $('#full_name').val().trim();
        let email = $('#email').val().trim();
        let contact_no = $('#contact_no').val().trim();
        let password = $('#password').val();
        let confirm_password = $('#confirm_password').val();

        if (full_name === "" || email === "" || contact_no === "" || password === "") {
            alert("Please fill all fields!");
            return false;
        }

        if (password !== confirm_password) {
            alert("Password Not Match!");
            return false;
        }

        $.ajax({
                url: '../auth/register.php',
                type: 'post',
                dataType: 'json',
                data: {
                    full_name: full_name,
                    email: email,
                    contact_no: contact_no,
                    password: password,
                    confirm_password: confirm_password,
                    csrf_token: CSRF
                },
                success: function (res) {
                    if (res.ok) {
                        alert('Account created. Please sign in.');
                        window.location.href = res.redirect;
                    } else {
                        alert(res.error || 'Registration failed.');
                    }
                },
                error: function () {
                    alert('Something went wrong. Please try again.');
                }
            });
    }

     const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    togglePassword.addEventListener('click', function () {
        const isHidden = passwordInput.type === 'password';

        passwordInput.type = isHidden ? 'text' : 'password';
        this.innerHTML = isHidden
            ? '<i class="bi bi-eye-slash"></i>'
            : '<i class="bi bi-eye"></i>';

        this.setAttribute(
            'aria-label',
            isHidden ? 'Hide password' : 'Show password'
        );
    });
</script>

</body>
</html>
