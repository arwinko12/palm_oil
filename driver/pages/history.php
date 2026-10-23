<?php
require_once __DIR__ . '/../includes/view.php';
require_once __DIR__ . '/../../class/Delivery.php';

$delivery    = new Delivery();
$assignments = $delivery->assignmentsForDriver($driverId);

// Optional month filter for the totals row (?month=1..12&year=YYYY).
$year  = (int) ($_GET['year']  ?: date('Y'));
$month = (int) ($_GET['month'] ?: date('n'));
$summary = $delivery->summaryForDriver($driverId, $year, $month);

$past = array_values(array_filter($assignments, fn($r) => in_array($r['delivery_status'], ['completed', 'cancelled'], true)));
$monthLabel = date('F Y', mktime(0, 0, 0, $month, 1, $year));
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-clock-history text-success"></i> Trip History &amp; Totals</h4>
        <div class="text-secondary" style="font-size:12px;">Your current and previous hauling assignments with recorded timestamps.</div>
    </div>
    <form id="monthForm" class="d-flex gap-2 align-items-center">
        <select name="month" class="form-select form-select-sm" style="width:auto;">
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,10)) ?></option>
            <?php endfor; ?>
        </select>
        <select name="year" class="form-select form-select-sm" style="width:auto;">
            <?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 3; $y--): ?>
                <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <button class="btn btn-sm btn-success" type="submit"><i class="bi bi-funnel"></i></button>
    </form>
</div>

<!-- Monthly totals -->
<section class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Trips in <?= e($monthLabel) ?></div>
            <div class="stat-number"><?= (int) $summary['month_total'] ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Completed</div>
            <div class="stat-number"><?= (int) $summary['month_completed'] ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Cancelled</div>
            <div class="stat-number"><?= (int) $summary['month_cancelled'] ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Lifetime Completed</div>
            <div class="stat-number"><?= (int) $summary['lifetime_completed'] ?></div>
        </div>
    </div>
</section>

<!-- Full assignment log -->
<div class="panel">
    <div class="panel-header">
        <h5 class="panel-title"><i class="bi bi-list-check"></i> All Assignments</h5>
        <span class="badge bg-light text-secondary"><?= count($past) ?> finished of <?= count($assignments) ?> total</span>
    </div>
    <div class="table-responsive">
        <table class="table custom-table">
            <thead>
                <tr>
                    <th>Sched</th><th>Farm Location</th><th>Farmer</th><th>Truck</th>
                    <th>Scheduled</th><th>Departure</th><th>Arrival</th><th>Completed</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assignments)): ?>
                    <tr><td colspan="9" class="text-center text-secondary py-4">No trips recorded yet.</td></tr>
                <?php else: foreach ($assignments as $r): ?>
                    <tr>
                        <td class="fw-bold">SCH-<?= (int) $r['schedule_id'] ?></td>
                        <td><?= e($r['farm_location']) ?></td>
                        <td><?= e($r['farmer_name'] ?: '—') ?></td>
                        <td><?= e($r['plate_number'] ?: '—') ?></td>
                        <td><?= e(fdate($r['scheduled_date'])) ?> <?= e(ftime($r['scheduled_time'])) ?></td>
                        <td><?= e(fdtime($r['departure_time'])) ?></td>
                        <td><?= e(fdtime($r['actual_arrival'])) ?></td>
                        <td><?= e(fdtime($r['completion_time'])) ?></td>
                        <td><?= status_badge($r['delivery_status']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="text-secondary mt-2" style="font-size:11px;">
        Financial figures (weights, payments) are recorded at the weighbridge and are not shown in the driver portal.
    </div>
</div>

<script>
// Re-fetch this partial with the chosen month/year via the shell loader.
document.getElementById('monthForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    $.ajax({
        url: 'pages/history.php?month=' + fd.get('month') + '&year=' + fd.get('year'),
        type: 'GET',
        success: function (html) { $('#page_result').html(html); }
    });
});
</script>
