<?php
require_once __DIR__ . '/../includes/access_control.php';
require_once __DIR__ . '/../class/Notification.php';
require_role('admin');

$unread  = (new Notification())->unreadCount((int) Auth::userId());
$userName = Auth::name() ?: 'Administrator';
$initials = strtoupper(substr(preg_replace('/[^A-Za-z ]/', '', $userName), 0, 2)) ?: 'AD';
$csrf    = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ePalmHaul | Admin</title>

    <!-- Bootstrap 4.6.2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/v/bs4/dt-1.13.11/datatables.min.css">
    <link rel="stylesheet" type="text/css" href="css/main.css">
    <link rel="stylesheet" type="text/css" href="../Styles/datatables.css">
</head>

<body>

<div class="mobile-overlay" id="mobileOverlay"></div>

<?php include 'includes/sidebar.php'; ?>

<main class="main">
    <header class="topbar">
        <div class="d-flex align-items-center">
            <button class="toggle-btn" id="sidebarToggle" type="button"><i class="fas fa-bars"></i></button>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="tableSearch" placeholder="Search records...">
            </div>
        </div>

        <div class="topbar-right">
            <button class="notification-btn page_click" type="button" data-page="notifications.php">
                <i class="far fa-bell"></i>
                <?php if ($unread > 0): ?><span class="notification-dot"></span><?php endif; ?>
            </button>

            <div class="dropdown">
                <button class="btn p-0 d-flex align-items-center" data-toggle="dropdown">
                    <div class="admin-avatar mr-2"><?= htmlspecialchars($initials) ?></div>
                    <div class="user-details text-left">
                        <div class="font-weight-bold" style="font-size:12px;"><?= htmlspecialchars($userName) ?></div>
                        <div class="text-muted" style="font-size:10px;">System Admin</div>
                    </div>
                    <i class="fas fa-chevron-down ml-2 text-muted small"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-right border-0 shadow">
                    <a class="dropdown-item small page_click" href="#" data-page="settings.php"><i class="fas fa-cog mr-2"></i> Settings</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item small text-danger" href="pages/logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="content">
        <div id="page_result"><div class="text-center text-muted p-5">Loading…</div></div>
    </div>
</main>

<!-- Libraries -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/v/bs4/dt-1.13.11/datatables.min.js"></script>
<script src="../js/portal-datatables.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
const CSRF = '<?= $csrf ?>';
const API  = 'query/admin_api.php';

// Central AJAX helper for the admin JSON API. Accepts an object or a
// jQuery-serialized query string as `data`.
function apiPost(action, data, cb) {
    let payload;
    if (typeof data === 'string') {
        payload = data + '&action=' + encodeURIComponent(action) + '&csrf_token=' + encodeURIComponent(CSRF);
    } else {
        data = data || {};
        data.action = action;
        data.csrf_token = CSRF;
        payload = data;
    }
    $.post(API, payload, function (res) {
        if (typeof cb === 'function') cb(res);
    }, 'json').fail(function () {
        alert('Request failed. Please try again.');
    });
}

// Simple message toast using Bootstrap alert bar at top of content.
function flash(msg, ok) {
    const cls = ok ? 'alert-success' : 'alert-danger';
    const $bar = $('<div class="alert ' + cls + ' py-2 px-3 rounded shadow-sm" style="position:fixed;top:16px;right:16px;z-index:2000;">' + msg + '</div>');
    $('body').append($bar);
    setTimeout(function () { $bar.fadeOut(400, function(){ $bar.remove(); }); }, 2600);
}

$(document).on('click', '.page_click', function (e) {
    e.preventDefault();
    const page = $(this).data('page');
    $('.sidebar-link').removeClass('active');
    $(this).closest('.sidebar-link').addClass('active');
    load_pages(page);
});

load_pages('dashboard.php');

function load_pages(page) {
    // Bootstrap appends the modal backdrop to <body>, but a modal's markup
    // lives inside #page_result. Swapping the fragment (after a save, or when
    // navigating away with a modal open) orphans that backdrop and leaves a
    // stuck dark overlay covering the whole admin. Close any open modal first.
    $('#page_result .modal.in').modal('hide');
    window.dtDestroy && window.dtDestroy('#page_result');
    $.ajax({ url: 'pages/' + page, type: 'get',
        success: function (result) {
            $('#page_result').html(result);
            // A freshly loaded page never renders with a modal open, so drop
            // any backdrop / body-scroll-lock the previous page left behind.
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css({ overflow: '', 'padding-right': '' });
            window.dtApply && window.dtApply('#page_result');
        },
        error: function () { $('#page_result').html('<div class="alert alert-danger m-4">Failed to load page.</div>'); }
    });
}

$(document).ready(function () {
    $('#sidebarToggle').on('click', function () {
        if (window.innerWidth <= 767) {
            $('#sidebar').toggleClass('mobile-open');
            $('#mobileOverlay').toggleClass('show');
        } else {
            $('body').toggleClass('sidebar-collapsed');
        }
    });
    $('#mobileOverlay').on('click', function () {
        $('#sidebar').removeClass('mobile-open');
        $(this).removeClass('show');
    });

    // Live search over the currently loaded tables. When DataTables is bound we
    // drive its own filter so paging/ordering stay consistent; otherwise fall
    // back to a simple row toggle.
    $('#tableSearch').on('keyup', function () {
        let value = $(this).val();
        if (window.jQuery && $.fn && $.fn.dataTable) {
            $.fn.dataTable.tables({ visible: true, api: true }).search(value).draw();
            return;
        }
        let v = value.toLowerCase();
        $('#page_result table tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(v) > -1);
        });
    });
});
</script>

</body>
</html>
