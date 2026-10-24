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
    <!-- DataTables (Bootstrap 5 integration) -->
    <link href="https://cdn.datatables.net/v/bs5/dt-2.1.8/datatables.min.css" rel="stylesheet">
    <!-- Shared portal design system -->
    <link href="../Styles/portal.css" rel="stylesheet">
    <link href="../Styles/datatables.css" rel="stylesheet">
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
<script src="https://cdn.datatables.net/v/bs5/dt-2.1.8/datatables.min.js"></script>
<script src="../js/portal-datatables.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).on('click', '.page_click', function () {
    let page = $(this).data('page');
    load_page(page);
    $('.nav-link').removeClass('active');
    $(this).addClass('active');
});

// Load default page
load_page('dashboard.php');

function load_page(page) {
    window.dtDestroy && window.dtDestroy('#page_result');
    $.ajax({
        url: 'pages/' + page,
        type: 'GET',
        success: function (result) {
            $('#page_result').html(result);
            window.dtApply && window.dtApply('#page_result');
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

</body>
</html>
