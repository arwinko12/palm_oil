<?php
require_once __DIR__ . '/../includes/view.php';
require_once __DIR__ . '/../../class/Delivery.php';
require_once __DIR__ . '/../../class/Notification.php';

$delivery  = new Delivery();
$assignments = $delivery->assignmentsForDriver($driverId);
$summary     = $delivery->summaryForDriver($driverId);

$active    = array_values(array_filter($assignments, fn($r) => !in_array($r['delivery_status'], ['completed', 'cancelled'], true)));
$upcoming  = array_slice($active, 0, 5);

$notifier = new Notification();
$notifs   = $notifier->forUser($userId, false, 5);
$unread   = $notifier->unreadCount($userId);

$avail        = $summary['availability'] ?? 'available';
$monthLabel   = date('F Y', mktime(0, 0, 0, $summary['month'], 1, $summary['year']));
$driverLabel  = Auth::name();
?>
<!-- Welcome banner -->
<section class="welcome-banner">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2>Hello, <?= e($driverLabel) ?>! 👋</h2>
            <p>Your assigned hauling trips, farmer contacts and delivery status — all in one place.</p>
            <div class="mt-3">
                <span class="badge rounded-pill bg-light text-success px-3 py-2">
                    <i class="bi bi-circle-fill me-1" style="font-size:7px"></i>
                    <?= count($active) ?> active trip(s)
                </span>
            </div>
        </div>
        <div class="col-md-4 text-md-end">
            <div class="banner-date"><i class="bi bi-calendar3 me-2"></i><span id="currentDate"></span></div>
        </div>
    </div>
</section>

<!-- Availability + monthly totals -->
<section class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Trips This Month</div>
                    <div class="stat-number"><?= (int) $summary['month_total'] ?></div>
                    <div class="stat-change neutral"><?= e($monthLabel) ?></div>
                </div>
                <div class="stat-icon icon-green"><i class="bi bi-signpost-split-fill"></i></div>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Active Now</div>
                    <div class="stat-number"><?= (int) $summary['month_active'] ?></div>
                    <div class="stat-change neutral">in progress</div>
                </div>
                <div class="stat-icon icon-blue"><i class="bi bi-truck"></i></div>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Completed</div>
                    <div class="stat-number"><?= (int) $summary['lifetime_completed'] ?></div>
                    <div class="stat-change"><i class="bi bi-check2-circle"></i> <?= (int) $summary['month_completed'] ?> this month</div>
                </div>
                <div class="stat-icon icon-orange"><i class="bi bi-check2-all"></i></div>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Availability</div>
                    <div class="mt-2"><?= availability_badge($avail) ?></div>
                    <div class="stat-change neutral mt-2">set by dispatch</div>
                </div>
                <div class="stat-icon icon-red"><i class="bi bi-person-badge"></i></div>
            </div>
        </div>
    </div>
</section>

<!-- Next trips + notifications -->
<section class="row g-3 mb-4">
    <div class="col-12 col-xl-7">
        <div class="panel">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-truck"></i>Upcoming / Active Trips</h5>
                <a class="view-all page_click" data-page="assignments.php">View all assignments <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table custom-table">
                    <thead>
                        <tr><th>Sched</th><th>Date &amp; Time</th><th>Farm Location</th><th>Est. Load</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($upcoming)): ?>
                            <tr><td colspan="5" class="text-center text-secondary py-4">No active trips assigned to you.</td></tr>
                        <?php else: foreach ($upcoming as $r): ?>
                            <tr>
                                <td class="fw-bold">SCH-<?= (int) $r['schedule_id'] ?></td>
                                <td><?= e(fdate($r['scheduled_date'])) ?> <?= e(ftime($r['scheduled_time'])) ?></td>
                                <td><?= e($r['farm_location']) ?></td>
                                <td><?= money($r['estimated_fresh_weight']) ?> t</td>
                                <td><?= status_badge($r['delivery_status']) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="panel">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-bell-fill"></i>Notifications</h5>
                <button class="btn btn-sm btn-light" id="markAllRead" <?= $unread ? '' : 'disabled' ?>>Mark all read</button>
            </div>
            <div class="activity-list">
                <?php if (empty($notifs)): ?>
                    <div class="text-secondary" style="font-size:12px;">No notifications yet.</div>
                <?php else: foreach ($notifs as $n): ?>
                    <div class="activity-item">
                        <div class="activity-icon <?= $n['is_read'] ? '' : 'blue' ?>"><i class="bi bi-dot"></i></div>
                        <div class="flex-grow-1">
                            <div class="activity-text"><?= e($n['title']) ?></div>
                            <div class="activity-sub"><?= e($n['message']) ?></div>
                        </div>
                        <div class="activity-time"><?= e($n['created_at']) ?></div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
const CSRF = '<?= e($csrf) ?>';
document.getElementById('currentDate').textContent =
    new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
document.getElementById('markAllRead').addEventListener('click', function () {
    $.post('query/delivery_api.php', { action: 'mark_all_read', csrf_token: CSRF }, function (res) {
        if (res.ok) { window.location.reload(); }
    }, 'json');
});
</script>
