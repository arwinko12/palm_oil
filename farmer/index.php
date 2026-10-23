<?php
require_once __DIR__ . '/../includes/access_control.php';
require_once __DIR__ . '/../class/Notification.php';
require_role('farmer');

$farmerName   = Auth::name();
$unreadCount  = (new Notification())->unreadCount((int) Auth::userId());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ePalmHaul | Dashboard</title>

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    
    <link href="https://cdn.datatables.net/v/dt/dt-3.1.4/datatables.min.css" rel="stylesheet">


    <style>
        :root {
            --primary: #16864b;
            --primary-dark: #075c38;
            --sidebar-bg: #082d26;
            --body-bg: #f4f7f6;
            --text-dark: #19342c;
            --text-muted: #84928c;
            --border-color: #e7eeea;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--body-bg);
            color: var(--text-dark);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            cursor: pointer;
        }
.form-control{
    border-color: grey;
}
        a {
            text-decoration: none;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: var(--sidebar-bg);
            color: white;
            display: flex;
            flex-direction: column;
            z-index: 1040;
            transition: transform .3s ease;
        }

        .brand {
            padding: 25px 22px;
            border-bottom: 1px solid rgba(255,255,255,.09);
        }

        .brand-icon {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            background: rgba(41, 180, 103, .18);
            border-radius: 12px;
            color: #66dc8c;
            font-size: 25px;
        }

        .brand-name {
            font-size: 21px;
            font-weight: 800;
            letter-spacing: -.8px;
        }

        .brand-name span {
            color: #55d780;
        }

        .brand small {
            color: #a3bdb2;
            font-size: 10px;
        }

        .sidebar-label {
            color: #789b8c;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.5px;
            padding: 25px 24px 10px;
        }

        .sidebar .nav-link {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 12px 15px;
            margin: 3px 12px;
            border-radius: 9px;
            color: #c4d6ce;
            font-size: 13px;
            font-weight: 500;
            transition: .2s;
        }

        .sidebar .nav-link i {
            font-size: 18px;
            width: 22px;
            text-align: center;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background: linear-gradient(120deg, #16864b, #269957);
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 18px;
        }

        .sidebar-footer .footer-card {
            background: rgba(255,255,255,.06);
            padding: 14px;
            border-radius: 12px;
            color: #b5d0c1;
            font-size: 11px;
            line-height: 1.7;
        }

        /* Main layout */
        .main-content {
            margin-left: 260px;
            min-height: 100vh;
            transition: margin-left .3s ease;
        }

        .topbar {
            height: 76px;
            background: #fff;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            gap: 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .search-box {
            width: min(420px, 100%);
            position: relative;
        }

        .search-box i {
            position: absolute;
            top: 50%;
            left: 14px;
            transform: translateY(-50%);
            color: #8a9b92;
        }

        .search-box input {
            padding: 11px 15px 11px 40px;
            border: 1px solid #e6ece8;
            border-radius: 9px;
            background: #f8faf9;
            font-size: 12px;
        }

        .search-box input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(22,134,75,.08);
        }

        .icon-button {
            width: 39px;
            height: 39px;
            border: 1px solid var(--border-color);
            background: #fff;
            color: #476056;
            border-radius: 10px;
            display: grid;
            place-items: center;
            position: relative;
        }

        .notification-dot {
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
            position: absolute;
            top: 7px;
            right: 7px;
            border: 1px solid #fff;
        }

        .avatar {
            width: 39px;
            height: 39px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #dff2e6;
            color: var(--primary-dark);
            font-size: 18px;
        }

        .page-content {
            padding: 27px 30px;
            max-width: 1800px;
            margin: auto;
        }

        /* Welcome banner */
        .welcome-banner {
            background: linear-gradient(110deg, #0b5938, #16864b);
            color: white;
            border-radius: 16px;
            padding: 27px 30px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }

        .welcome-banner::after {
            content: '\F4D0';
            font-family: "bootstrap-icons";
            position: absolute;
            right: 35px;
            top: -35px;
            font-size: 175px;
            color: rgba(255,255,255,.08);
            transform: rotate(-15deg);
        }

        .welcome-banner h2 {
            font-size: clamp(20px, 2.5vw, 27px);
            font-weight: 800;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .welcome-banner p {
            margin: 0;
            color: #d5f1df;
            font-size: 12px;
            position: relative;
            z-index: 1;
        }

        .welcome-banner .banner-date {
            position: relative;
            z-index: 1;
            background: rgba(255,255,255,.12);
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 12px;
        }

        /* Cards */
        .stat-card,
        .panel {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 13px;
            box-shadow: 0 2px 8px rgba(23, 65, 43, .025);
        }

        .stat-card {
            padding: 20px;
            height: 100%;
            transition: transform .2s, box-shadow .2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 22px rgba(23,65,43,.07);
        }

        .stat-icon {
            width: 47px;
            height: 47px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .icon-green {
            background: #e1f5e8;
            color: #16864b;
        }

        .icon-blue {
            background: #e5efff;
            color: #3478e5;
        }

        .icon-orange {
            background: #fff0dc;
            color: #ed941b;
        }

        .icon-red {
            background: #ffe7e7;
            color: #e55353;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 500;
        }

        .stat-number {
            font-size: 27px;
            font-weight: 800;
            letter-spacing: -1px;
            margin: 6px 0;
        }

        .stat-change {
            font-size: 11px;
            color: var(--primary);
        }

        .stat-change.neutral {
            color: #82918a;
        }

        /* Panels */
        .panel {
            padding: 21px;
            height: 100%;
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 22px;
        }

        .panel-title {
            font-size: 14px;
            font-weight: 700;
            margin: 0;
        }

        .panel-title i {
            color: var(--primary);
            margin-right: 8px;
            font-size: 17px;
        }

        .view-all {
            font-size: 11px;
            color: var(--primary);
            font-weight: 600;
        }

        .chart-container {
            height: 255px;
            position: relative;
        }

        .status-chart-container {
            height: 210px;
            position: relative;
        }

        .status-item {
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 11px;
            margin-bottom: 14px;
            color: #60736a;
        }

        .status-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .status-item strong {
            color: var(--text-dark);
            display: block;
            font-size: 12px;
            margin-bottom: 3px;
        }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
        }

        .custom-table {
            margin-bottom: 0;
            white-space: nowrap;
        }

        .custom-table thead th {
            background: #f7faf8;
            color: #718078;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            padding: 13px 12px;
            border-bottom: 1px solid var(--border-color);
        }

        .custom-table tbody td {
            font-size: 11px;
            padding: 14px 12px;
            vertical-align: middle;
            color: #435b50;
            border-color: #edf2ef;
        }

        .custom-table tbody tr:hover {
            background: #f9fcfa;
        }

        .farmer-avatar {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            background: #e1f5e8;
            color: var(--primary);
            display: inline-grid;
            place-items: center;
            margin-right: 7px;
            font-size: 13px;
        }

        .badge-status {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 600;
        }

        .badge-completed {
            background: #e1f5e8;
            color: #16864b;
        }

        .badge-transit {
            background: #e5efff;
            color: #3478e5;
        }

        .badge-pending {
            background: #fff0dc;
            color: #bd790b;
        }

        .badge-cancelled {
            background: #ffe7e7;
            color: #d14343;
        }

        /* Activity */
        .activity-list {
            position: relative;
        }

        .activity-item {
            display: flex;
            gap: 12px;
            margin-bottom: 21px;
            position: relative;
        }

        .activity-item:last-child {
            margin-bottom: 0;
        }

        .activity-icon {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: #e1f5e8;
            color: var(--primary);
            flex-shrink: 0;
        }

        .activity-icon.blue {
            background: #e5efff;
            color: #3478e5;
        }

        .activity-icon.orange {
            background: #fff0dc;
            color: #ed941b;
        }

        .activity-text {
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .activity-sub {
            font-size: 10px;
            color: var(--text-muted);
        }

        .activity-time {
            font-size: 10px;
            color: var(--text-muted);
            white-space: nowrap;
            margin-left: auto;
        }

        .footer {
            text-align: center;
            color: #93a199;
            font-size: 11px;
            padding: 24px 0 5px;
        }

        .mobile-overlay {
            display: none;
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .mobile-overlay.show {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,.4);
                z-index: 1035;
            }

            .main-content {
                margin-left: 0;
            }

            .topbar {
                padding: 0 18px;
            }

            .page-content {
                padding: 22px 18px;
            }
        }

        @media (max-width: 575.98px) {
            .topbar {
                height: 65px;
                gap: 10px;
            }

            .search-box {
                flex: 1;
                min-width: 0;
            }

            .search-box input {
                font-size: 11px;
            }

            .topbar .user-details {
                display: none;
            }

            .page-content {
                padding: 15px 12px;
            }

            .welcome-banner {
                padding: 23px 19px;
            }

            .welcome-banner .banner-date {
                margin-top: 18px;
                display: inline-block;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-number {
                font-size: 23px;
            }

            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 19px;
            }

            .panel {
                padding: 15px;
            }

            .chart-container {
                height: 220px;
            }

            .status-chart-container {
                height: 190px;
            }
        }
    </style>
</head>

<body>

<!-- Sidebar -->
<?php include 'includes/sidebar.php'; ?>
<div class="mobile-overlay" id="mobileOverlay"></div>

<!-- Main Content -->
<main class="main-content">

    <!-- Topbar -->
    <header class="topbar">
        <div class="d-flex align-items-center gap-3 flex-grow-1">
            <button class="icon-button" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="bi bi-list fs-5"></i>
            </button>

            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="search" class="form-control"
                       id="globalSearch" placeholder="Search records, farmers, drivers...">
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <button class="icon-button page_click" data-page="notifications.php" aria-label="Notifications"
                    title="Notifications" style="cursor:pointer">
                <i class="bi bi-bell fs-5"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="notification-dot"></span>
                <?php endif; ?>
            </button>

            <div class="d-flex align-items-center gap-2">
                <div class="avatar">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div class="user-details">
                    <div class="fw-bold" style="font-size:12px"><?= htmlspecialchars($farmerName, ENT_QUOTES) ?></div>
                    <div class="text-secondary" style="font-size:10px">Farmer</div>
                </div>
                <i class="bi bi-chevron-down small text-secondary"></i>
            </div>
        </div>
    </header>

    <div class="page-content">

        
                <div id="page_result"></div>
       

        <footer class="footer">
            © <span id="currentYear"></span> ePalmHaul Palm Harvest Hauling System.
            All rights reserved.
        </footer>

    </div>
</main>


<script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/v/dt/dt-3.1.4/datatables.min.js" ></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>



$(document).on('click', '.page_click', function () {
    let page = $(this).data('page');

    // Load the selected page
    load_page(page);

    // Update active menu
    $('.nav-link').removeClass('active');
    $(this).addClass('active');
});

// Load default page
load_page('dashboard.php');

function load_page(page) {
    $.ajax({
        url: 'pages/' + page,
        type: 'GET',
        success: function (result) {
            $('#page_result').html(result);
        },
        error: function (xhr, status, error) {
            $('#page_result').html(
                '<div class="alert alert-danger">Failed to load page.</div>'
            );
            console.error(error);
        }
    });
}

    



    // Sidebar toggle for desktop and mobile
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobileOverlay');

    document.getElementById('sidebarToggle').addEventListener('click', function () {
        if (window.innerWidth < 992) {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        } else {
            sidebar.classList.toggle('collapsed');

            if (sidebar.classList.contains('collapsed')) {
                sidebar.style.transform = 'translateX(-100%)';
                document.querySelector('.main-content').style.marginLeft = '0';
            } else {
                sidebar.style.transform = '';
                document.querySelector('.main-content').style.marginLeft = '260px';
            }
        }
    });

    overlay.addEventListener('click', function () {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');

            if (!sidebar.classList.contains('collapsed')) {
                sidebar.style.transform = '';
                document.querySelector('.main-content').style.marginLeft = '260px';
            }
        } else {
            document.querySelector('.main-content').style.marginLeft = '0';
            if (!sidebar.classList.contains('show')) {
                sidebar.style.transform = '';
            }
        }
    });

    // Footer year
    document.getElementById('currentYear').textContent =
        new Date().getFullYear();
</script>

<style>
    .spin-once {
        animation: spin .5s linear;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>

</body>
</html>
