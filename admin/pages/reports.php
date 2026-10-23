<?php require_once __DIR__ . '/../includes/view.php';
$type = $_GET['type'] ?? 'hauling';
$from = $_GET['from'] ?? '';
$to   = $_GET['to'] ?? '';
$q    = http_build_query(array_filter(['type' => $type, 'from' => $from, 'to' => $to]));

switch ($type) {
    case 'farmers': $rows = $admin->reportFarmerSummary(); break;
    case 'drivers': $rows = $admin->reportDriverSummary(); break;
    case 'trucks':  $rows = $admin->reportTruckSummary(); break;
    default:        $type = 'hauling'; $rows = $admin->reportHauling($from ?: null, $to ?: null); break;
}
$TOTALS = null;
if ($type === 'hauling') {
    $tons = 0; $pay = 0;
    foreach ($rows as $r) { $tons += (float) ($r['net_weight'] ?? 0); $pay += (float) ($r['net_payable'] ?? 0); }
    $TOTALS = ['tons' => $tons / 1000, 'pay' => $pay];
}
?>
<div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <div>
        <h4 class="page-title">Reports</h4>
        <div class="page-subtitle">Generate operational, financial and fleet reports and export printable or CSV records.</div>
    </div>
    <div class="no-print">
        <a class="btn btn-outline-secondary btn-sm" target="_blank" href="pages/print_report.php?<?= e($q) ?>"><i class="fas fa-print mr-1"></i> Print view</a>
        <a class="btn btn-success btn-sm" href="query/export.php?<?= e($q) ?>"><i class="fas fa-file-csv mr-1"></i> Export CSV</a>
    </div>
</div>

<div class="mb-3 no-print">
    <div class="btn-group mr-2">
        <?php foreach (['hauling' => 'Hauling & Payments', 'farmers' => 'Farmers', 'drivers' => 'Drivers', 'trucks' => 'Trucks'] as $k => $lbl): ?>
            <button class="btn btn-sm <?= $type === $k ? 'btn-success' : 'btn-outline-secondary' ?> page_click" data-page="reports.php?type=<?= $k ?>"><?= $lbl ?></button>
        <?php endforeach; ?>
    </div>
    <?php if ($type === 'hauling'): ?>
        <span class="d-inline-flex align-items-center">
            <input type="date" class="form-control form-control-sm mr-2" id="rFrom" value="<?= e($from) ?>" style="width:auto;">
            <input type="date" class="form-control form-control-sm mr-2" id="rTo" value="<?= e($to) ?>" style="width:auto;">
            <button class="btn btn-sm btn-outline-primary" onclick="applyRange()">Filter</button>
        </span>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="table-responsive">
        <table class="table">
            <?php if ($type === 'hauling'): ?>
                <thead><tr><th>Date</th><th>Farmer</th><th>Driver</th><th>Plate</th><th>Ticket</th><th>Net (kg)</th><th>Net Payable</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (!$rows): ?><tr><td colspan="8" class="text-muted">No records for this range.</td></tr><?php endif; ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= fdate($r['scheduled_date']) ?></td>
                        <td><?= e($r['farmer_name']) ?></td>
                        <td><?= e($r['driver_name'] ?: '—') ?></td>
                        <td><?= e($r['plate_number'] ?: '—') ?></td>
                        <td><?= e($r['ticket_number'] ?: '—') ?></td>
                        <td><?= $r['net_weight'] !== null ? number_format((float) $r['net_weight'], 2) : '—' ?></td>
                        <td><?= isset($r['net_payable']) ? money($r['net_payable']) : '—' ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><?php if ($TOTALS): ?><tr class="font-weight-bold"><td colspan="5">Totals</td><td><?= number_format($TOTALS['tons'], 2) ?> t</td><td><?= money($TOTALS['pay']) ?></td><td></td></tr><?php endif; ?></tfoot>
            <?php elseif ($type === 'farmers'): ?>
                <thead><tr><th>Farmer</th><th>Location</th><th>Requests</th><th>Completed Hauls</th><th>Total kg</th><th>Total Payable</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?><tr><td><?= e($r['fullname']) ?></td><td><?= e($r['farm_location']) ?></td><td><?= (int) $r['requests'] ?></td><td><?= (int) $r['completed_hauls'] ?></td><td><?= number_format((float) $r['total_kg'], 1) ?></td><td><?= money($r['total_payable']) ?></td></tr><?php endforeach; ?>
                </tbody>
            <?php elseif ($type === 'drivers'): ?>
                <thead><tr><th>Driver</th><th>Availability</th><th>Total Trips</th><th>Completed Trips</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?><tr><td><?= e($r['fullname']) ?></td><td><?= avail_badge($r['availability']) ?></td><td><?= (int) $r['total_trips'] ?></td><td><?= (int) $r['completed_trips'] ?></td></tr><?php endforeach; ?>
                </tbody>
            <?php else: ?>
                <thead><tr><th>Plate</th><th>Type</th><th>Capacity</th><th>Status</th><th>Total Trips</th><th>Completed</th><th>Tons Hauled</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?><tr><td><?= e($r['plate_number']) ?></td><td><?= ucfirst(e($r['truck_type'])) ?></td><td><?= number_format((float) $r['carrying_capacity'], 1) ?> t</td><td><?= truck_badge($r['status']) ?></td><td><?= (int) $r['total_trips'] ?></td><td><?= (int) $r['completed_trips'] ?></td><td><?= number_format((float) $r['tons_hauled'], 2) ?> t</td></tr><?php endforeach; ?>
                </tbody>
            <?php endif; ?>
        </table>
    </div>
</div>

<script>
function applyRange() {
    const f = $('#rFrom').val(), t = $('#rTo').val();
    load_pages('reports.php?type=hauling&from=' + encodeURIComponent(f) + '&to=' + encodeURIComponent(t));
}
</script>
