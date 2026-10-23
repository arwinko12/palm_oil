<?php require_once __DIR__ . '/../includes/view.php';
$statusFilter = $_GET['status'] ?? '';
$schedules = $admin->listSchedules($statusFilter !== '' ? $statusFilter : null);
$drivers = $admin->listDrivers();
$trucks  = $admin->listTrucks();
$S = ['','scheduled','in_transit','arrived','delivered','completed','cancelled'];
?>
<div class="mb-3">
    <h4 class="page-title">Hauling Schedules</h4>
    <div class="page-subtitle">Review created schedules, cancel where needed, and test a slot for conflicts before committing.</div>
</div>

<div class="mb-3">
    <?php foreach ($S as $f): ?>
        <button class="btn btn-sm mb-1 <?= $f === $statusFilter ? 'btn-success' : 'btn-outline-secondary' ?> page_click"
                data-page="schedules.php<?= $f ? '?status=' . $f : '' ?>"><?= $f === '' ? 'All' : ucfirst(str_replace('_',' ',$f)) ?></button>
    <?php endforeach; ?>
</div>

<div class="row">
    <div class="col-12 col-xl-8 mb-3">
        <div class="panel">
            <div class="panel-header"><div><div class="panel-title">Schedules</div><div class="panel-subtitle"><?= count($schedules) ?> shown</div></div></div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>#</th><th>Farmer</th><th>Driver</th><th>Truck</th><th>Date / Time</th><th>Delivery</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php if (!$schedules): ?><tr><td colspan="8" class="text-muted">No schedules.</td></tr><?php endif; ?>
                    <?php foreach ($schedules as $s): ?>
                        <tr>
                            <td class="font-weight-bold">SCH-<?= (int) $s['schedule_id'] ?></td>
                            <td><?= e($s['farmer_name']) ?></td>
                            <td><?= e($s['driver_name'] ?: '—') ?></td>
                            <td><?= e($s['plate_number'] ?: '—') ?> <span class="table-sub"><?= e($s['truck_type'] ?: '') ?></span></td>
                            <td><?= fdate($s['scheduled_date']) ?> <span class="table-sub"><?= ftime($s['scheduled_time']) ?></span></td>
                            <td><?= status_badge($s['delivery_status'] ?: $s['status']) ?></td>
                            <td><?= status_badge($s['status']) ?></td>
                            <td>
                                <?php if (!in_array($s['status'], ['completed','cancelled'], true)): ?>
                                    <button class="action-btn text-danger" title="Cancel schedule" onclick="cancelSched(<?= (int) $s['schedule_id'] ?>)"><i class="fas fa-ban"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4 mb-3">
        <div class="panel">
            <div class="panel-header"><div><div class="panel-title">Conflict Checker</div><div class="panel-subtitle">Test a driver + truck against a slot</div></div></div>
            <div class="panel-body p-3">
                <div class="form-group"><label>Driver</label>
                    <select class="form-control" id="c_driver"><option value="">Choose…</option>
                    <?php foreach ($drivers as $d): ?><option value="<?= (int) $d['driver_id'] ?>"><?= e($d['fullname']) ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Truck</label>
                    <select class="form-control" id="c_truck"><option value="">Choose…</option>
                    <?php foreach ($trucks as $t): ?><option value="<?= (int) $t['truck_id'] ?>"><?= e($t['plate_number']) ?> (<?= e($t['truck_type']) ?>)</option><?php endforeach; ?></select></div>
                <div class="row">
                    <div class="col-6 form-group"><label>Date</label><input type="date" class="form-control" id="c_date"></div>
                    <div class="col-6 form-group"><label>Time</label><input type="time" class="form-control" id="c_time"></div>
                </div>
                <button class="btn btn-primary btn-block" onclick="checkConflict()"><i class="fas fa-exclamation-triangle mr-1"></i> Check for conflicts</button>
                <div id="cResult" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<script>
function cancelSched(id) {
    if (!confirm('Cancel schedule #' + id + '? This also cancels its delivery and frees the driver/truck.')) return;
    apiPost('schedule_cancel', { schedule_id: id }, function (res) {
        if (res.ok) { flash('Schedule cancelled.', true); load_pages('schedules.php'); } else flash(res.error, false);
    });
}
function checkConflict() {
    const p = { driver_id: $('#c_driver').val(), truck_id: $('#c_truck').val(), date: $('#c_date').val(), time: $('#c_time').val() };
    if (!p.driver_id || !p.truck_id || !p.date || !p.time) { $('#cResult').html('<div class="alert alert-danger py-2">Fill in all fields.</div>'); return; }
    $.getJSON('query/admin_api.php', { action: 'conflict_check' , ...p }, function (res) {
        if (res.warnings && res.warnings.length) {
            $('#cResult').html('<div class="alert alert-warning py-2"><strong>Conflicts:</strong><ul class="mb-0">' + res.warnings.map(w=>`<li>${w}</li>`).join('') + '</ul></div>');
        } else {
            $('#cResult').html('<div class="alert alert-success py-2">Slot is clear — no conflicts.</div>');
        }
    });
}
</script>
