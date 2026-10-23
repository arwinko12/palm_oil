<?php
require_once __DIR__ . '/../includes/view.php';
require_once __DIR__ . '/../../class/HaulingRequest.php';
require_once __DIR__ . '/../../class/HarvestCycle.php';
require_once __DIR__ . '/../../class/FarmerProfile.php';
require_once __DIR__ . '/../../class/Notification.php';

$summary   = (new HaulingRequest())->summaryForFarmer($farmerId);
$history   = (new HaulingRequest())->historyForFarmer($farmerId);
$cycle     = new HarvestCycle();
$nextDate  = $cycle->nextHarvestDate($farmerId);
$profile   = (new FarmerProfile())->get($farmerId);
$notifier  = new Notification();
$notifs    = $notifier->forUser($userId, false, 6);
$unread    = $notifier->unreadCount($userId);
$recent    = array_slice($history, 0, 5);
$farmName  = $profile['fullname'] ?? Auth::name();
?>
<!-- Welcome Banner -->
<section class="welcome-banner">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2>Welcome back, <?= e($farmName) ?>! 👋</h2>
            <p>Here's what's happening with your palm harvest and hauling operations.</p>
            <div class="mt-3">
                <span class="badge rounded-pill bg-light text-success px-3 py-2">
                    <i class="bi bi-circle-fill me-1" style="font-size:7px"></i>
                    <?= $summary['pending'] ?> request(s) awaiting review
                </span>
            </div>
        </div>
        <div class="col-md-4 text-md-end">
            <div class="banner-date">
                <i class="bi bi-calendar3 me-2"></i>
                <span id="currentDate"></span>
            </div>
        </div>
    </div>
</section>

<!-- Statistics (the farmer's own records only) -->
<section class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Total Requests</div>
                    <div class="stat-number"><?= (int) $summary['total_requests'] ?></div>
                    <div class="stat-change neutral"><?= (int) $summary['pending'] ?> pending</div>
                </div>
                <div class="stat-icon icon-green"><i class="bi bi-file-earmark-text-fill"></i></div>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Scheduled / Active</div>
                    <div class="stat-number"><?= (int) $summary['scheduled'] ?></div>
                    <div class="stat-change neutral"><?= (int) $summary['approved'] ?> approved</div>
                </div>
                <div class="stat-icon icon-blue"><i class="bi bi-truck"></i></div>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Completed Hauls</div>
                    <div class="stat-number"><?= (int) $summary['completed'] ?></div>
                    <div class="stat-change"><i class="bi bi-box-seam"></i> <?= money($summary['total_kg'] / 1000) ?> tons hauled</div>
                </div>
                <div class="stat-icon icon-orange"><i class="bi bi-check2-circle"></i></div>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Net Payable (total)</div>
                    <div class="stat-number"><?= money($summary['net_payable']) ?></div>
                    <div class="stat-change neutral">across all deliveries</div>
                </div>
                <div class="stat-icon icon-red"><i class="bi bi-cash-stack"></i></div>
            </div>
        </div>
    </div>
</section>

<!-- Next harvest + notifications -->
<section class="row g-3 mb-4">
    <div class="col-12 col-xl-5">
        <div class="panel">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-flower1"></i>Next Harvest Date</h5>
                <span class="badge bg-light text-secondary"><?= (int) $cycle->cycleDays() ?>-day cycle</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon icon-green" style="width:56px;height:56px;font-size:26px;">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <div>
                    <?php if ($nextDate): ?>
                        <div style="font-size:22px;font-weight:800;"><?= e(fdate($nextDate)) ?></div>
                        <div class="text-secondary" style="font-size:12px;">
                            Based on your last confirmed harvest (<?= e(fdate($profile['last_harvest_date'] ?? null)) ?>).
                        </div>
                    <?php else: ?>
                        <div style="font-size:16px;font-weight:700;">No harvest recorded yet</div>
                        <div class="text-secondary" style="font-size:12px;">
                            Your 20-day cycle starts once a harvest is confirmed through a completed haul.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <a class="btn btn-sm btn-success page_click" data-page="hauling_request.php">
                    <i class="bi bi-plus-lg me-1"></i> New Hauling Request
                </a>
                <a class="btn btn-sm btn-outline-secondary page_click" data-page="harvest.php">
                    <i class="bi bi-calendar3 me-1"></i> Harvest Schedule
                </a>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-7">
        <div class="panel">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-bell-fill"></i>Reminders &amp; Updates</h5>
                <button class="btn btn-sm btn-light" id="markAllRead" <?= $unread ? '' : 'disabled' ?>>
                    Mark all read
                </button>
            </div>
            <div class="activity-list" id="notifList">
                <?php if (empty($notifs)): ?>
                    <div class="text-secondary" style="font-size:12px;">No notifications yet.</div>
                <?php else: foreach ($notifs as $n): ?>
                    <div class="activity-item">
                        <div class="activity-icon <?= $n['is_read'] ? '' : 'blue' ?>">
                            <i class="bi bi-dot"></i>
                        </div>
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

<!-- Recent records -->
<section class="row g-3">
    <div class="col-12">
        <div class="panel">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-truck"></i>Recent Hauling Requests</h5>
                <a href="#" class="view-all page_click" data-page="records.php">View all records <i class="bi bi-arrow-right"></i></a>
            </div>

            <div class="table-responsive">
                <table class="table custom-table">
                    <thead>
                        <tr>
                            <th>Ref</th>
                            <th>Preferred</th>
                            <th>Location</th>
                            <th>Est. Tons</th>
                            <th>Driver</th>
                            <th>Truck</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent)): ?>
                            <tr><td colspan="7" class="text-center text-secondary py-4">No requests yet. Submit your first hauling request.</td></tr>
                        <?php else: foreach ($recent as $row): ?>
                            <tr>
                                <td class="fw-bold">REQ-<?= (int) $row['request_id'] ?></td>
                                <td><?= e(fdate($row['preferred_date'])) ?> <?= e(ftime($row['preferred_time'])) ?></td>
                                <td><?= e($row['farm_location']) ?></td>
                                <td><?= money($row['estimated_fresh_weight']) ?></td>
                                <td><?= e($row['driver_name'] ?: '—') ?></td>
                                <td><?= e($row['plate_number'] ?: '—') ?></td>
                                <td><?= status_badge(display_status($row)) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script>
const CSRF = '<?= e($csrf) ?>';

document.getElementById('currentDate').textContent =
    new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });

// NOTE: .page_click links are handled by the shell's delegated click handler.

document.getElementById('markAllRead').addEventListener('click', function () {
    $.post('query/request_api.php', { action: 'mark_all_read', csrf_token: CSRF }, function (res) {
        if (res.ok) { window.location.reload(); }
    }, 'json');
});
</script>
