<?php require_once __DIR__ . '/../includes/view.php';
$ov        = $admin->overview();
$recent    = $admin->recentRequests(6);
$upcoming  = $admin->upcomingSchedules(6);
?>
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div class="mb-3">
        <h4 class="page-title">Dashboard Overview</h4>
        <div class="page-subtitle">Welcome back, <?= e($userName) ?>. Here is the current state of your hauling operation.</div>
    </div>
    <button class="btn-green page_click" data-page="requests.php"><i class="fas fa-clipboard-list mr-1"></i> Review Requests (<?= (int) $ov['pending_requests'] ?>)</button>
</div>

<!-- STAT CARDS -->
<div class="row">
    <div class="col-12 col-sm-6 col-xl-3 mb-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between">
                <div class="stat-icon bg-green-light"><i class="fas fa-users"></i></div>
                <span class="text-success small">Farmers</span>
            </div>
            <div class="stat-label">Registered Farmers</div>
            <div class="stat-number"><?= (int) $ov['farmers'] ?></div>
            <div class="stat-footer"><?= (int) $ov['requests'] ?> hauling requests total</div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3 mb-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between">
                <div class="stat-icon bg-orange-light"><i class="fas fa-id-badge"></i></div>
                <span class="text-muted small">Drivers</span>
            </div>
            <div class="stat-label">Available Drivers</div>
            <div class="stat-number"><?= (int) $ov['drivers_available'] ?> <small class="text-muted">/ <?= (int) $ov['drivers'] ?></small></div>
            <div class="stat-footer">Ready to be assigned</div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3 mb-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between">
                <div class="stat-icon bg-blue-light"><i class="fas fa-truck"></i></div>
                <span class="text-muted small">Fleet</span>
            </div>
            <div class="stat-label">Available Trucks</div>
            <div class="stat-number"><?= (int) $ov['trucks_available'] ?> <small class="text-muted">/ <?= (int) $ov['trucks'] ?></small></div>
            <div class="stat-footer"><?= (int) $ov['scheduled_trips'] ?> trips scheduled / in transit</div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3 mb-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between">
                <div class="stat-icon bg-purple-light"><i class="fas fa-box-open"></i></div>
                <span class="text-success small">This month</span>
            </div>
            <div class="stat-label">Palm Fruit Delivered</div>
            <div class="stat-number"><?= number_format($ov['month_tons'], 1) ?> <small>tons</small></div>
            <div class="stat-footer"><?= (int) $ov['completed_deliveries'] ?> completed · <?= money($ov['month_payable']) ?> payable</div>
        </div>
    </div>
</div>

<!-- CHART + PENDING -->
<div class="row mb-3">
    <div class="col-12 col-xl-4 mb-3">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <div class="panel-title">Request Status</div>
                    <div class="panel-subtitle">Breakdown of hauling requests</div>
                </div>
            </div>
            <div class="chart-container" style="height:220px;"><canvas id="statusChart"></canvas></div>
            <div class="d-flex justify-content-between mt-3"><span class="text-muted"><span class="text-warning mr-1">●</span> Pending</span><strong><?= (int) $ov['pending_requests'] ?></strong></div>
            <div class="d-flex justify-content-between mt-2"><span class="text-muted"><span class="text-primary mr-1">●</span> Approved / Scheduled</span><strong><?= (int) ($ov['approved_requests'] + $ov['scheduled_trips']) ?></strong></div>
            <div class="d-flex justify-content-between mt-2"><span class="text-muted"><span class="text-success mr-1">●</span> Completed</span><strong><?= (int) $ov['completed_deliveries'] ?></strong></div>
        </div>
    </div>

    <div class="col-12 col-xl-8 mb-3">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <div class="panel-title">Recent Hauling Requests</div>
                    <div class="panel-subtitle">Latest submissions from farmers</div>
                </div>
                <button class="btn btn-sm btn-outline-secondary page_click" data-page="requests.php">View All <i class="fas fa-arrow-right ml-1"></i></button>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>#</th><th>Farmer</th><th>Preferred</th><th>Est. Tons</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if (!$recent): ?>
                        <tr><td colspan="5" class="text-muted">No requests yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recent as $r): ?>
                        <tr>
                            <td class="font-weight-bold">HR-<?= (int) $r['request_id'] ?></td>
                            <td><div class="table-name"><?= e($r['farmer_name']) ?></div><div class="table-sub"><?= e($r['farm_location']) ?></div></td>
                            <td><?= fdate($r['preferred_date']) ?></td>
                            <td><?= number_format((float) $r['estimated_fresh_weight'], 2) ?> t</td>
                            <td><?= req_badge($r['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- UPCOMING SCHEDULES -->
<div class="panel">
    <div class="panel-header">
        <div>
            <div class="panel-title">Upcoming Hauling Schedules</div>
            <div class="panel-subtitle">Driver and truck assignments in progress</div>
        </div>
        <button class="btn-green page_click" data-page="schedules.php"><i class="fas fa-calendar-alt mr-1"></i> Manage Schedule</button>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Schedule</th><th>Farmer</th><th>Driver</th><th>Truck</th><th>Date / Time</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (!$upcoming): ?>
                <tr><td colspan="6" class="text-muted">Nothing scheduled right now.</td></tr>
            <?php endif; ?>
            <?php foreach ($upcoming as $s): ?>
                <tr>
                    <td class="font-weight-bold">SCH-<?= (int) $s['schedule_id'] ?></td>
                    <td><?= e($s['farmer_name']) ?></td>
                    <td><?= e($s['driver_name'] ?: '—') ?></td>
                    <td><?= e($s['plate_number'] ?: '—') ?></td>
                    <td><?= fdate($s['scheduled_date']) ?> · <?= ftime($s['scheduled_time']) ?></td>
                    <td><?= status_badge($s['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Approved / Scheduled', 'Completed'],
        datasets: [{
            data: [<?= (int) $ov['pending_requests'] ?>, <?= (int) ($ov['approved_requests'] + $ov['scheduled_trips']) ?>, <?= (int) $ov['completed_deliveries'] ?>],
            backgroundColor: ['#e8b34f', '#4788df', '#279653'],
            borderWidth: 0
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { display: false } } }
});
</script>
