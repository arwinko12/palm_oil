<?php require_once __DIR__ . '/../includes/view.php';
$deliverySvc = new Delivery();
$statusFilter = $_GET['status'] ?? '';
$deliveries = $admin->listDeliveries($statusFilter !== '' ? $statusFilter : null);
// Statuses an admin is permitted to move a delivery into (in_transit is driver-only).
$ADMIN_ALLOWED = ['arrived', 'delivered', 'completed', 'cancelled'];
$S = ['', 'scheduled', 'in_transit', 'arrived', 'delivered', 'completed', 'cancelled'];
?>
<div class="mb-3">
    <h4 class="page-title">Delivery Progress</h4>
    <div class="page-subtitle">Monitor every haul end-to-end and advance the workflow where you are authorised.</div>
</div>

<div class="mb-3">
    <?php foreach ($S as $f): ?>
        <button class="btn btn-sm mb-1 <?= $f === $statusFilter ? 'btn-success' : 'btn-outline-secondary' ?> page_click"
                data-page="deliveries.php<?= $f ? '?status=' . $f : '' ?>"><?= $f === '' ? 'All' : ucfirst(str_replace('_',' ',$f)) ?></button>
    <?php endforeach; ?>
</div>

<div class="panel">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Delivery</th><th>Farmer</th><th>Driver / Truck</th><th>Scheduled</th><th>Departure</th><th>Arrival</th><th>Ticket / Net</th><th>Status</th><th>Advance</th></tr></thead>
            <tbody>
            <?php if (!$deliveries): ?><tr><td colspan="9" class="text-muted">No deliveries in this view.</td></tr><?php endif; ?>
            <?php foreach ($deliveries as $dv):
                $next = array_values(array_intersect($deliverySvc->allowedFrom($dv['status']), $ADMIN_ALLOWED)); ?>
                <tr>
                    <td class="font-weight-bold">DV-<?= (int) $dv['delivery_id'] ?><div class="table-sub">SCH-<?= (int) $dv['schedule_id'] ?></div></td>
                    <td><?= e($dv['farmer_name']) ?></td>
                    <td><div class="table-name"><?= e($dv['driver_name'] ?: '—') ?></div><div class="table-sub"><?= e($dv['plate_number'] ?: '—') ?></div></td>
                    <td><?= fdate($dv['scheduled_date']) ?> <span class="table-sub"><?= ftime($dv['scheduled_time']) ?></span></td>
                    <td><?= $dv['departure_time'] ? fdtime($dv['departure_time']) : '—' ?></td>
                    <td><?= $dv['actual_arrival'] ? fdtime($dv['actual_arrival']) : '—' ?></td>
                    <td><?= e($dv['ticket_number'] ?: '—') ?><div class="table-sub"><?= $dv['net_weight'] !== null ? kg($dv['net_weight']) : '' ?></div></td>
                    <td><?= status_badge($dv['status']) ?></td>
                    <td class="text-nowrap">
                        <?php if (!$next): ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                        <?php foreach ($next as $to): ?>
                            <button class="btn btn-sm <?= $to === 'cancelled' ? 'btn-outline-danger' : 'btn-outline-primary' ?> mr-1"
                                    onclick="move(<?= (int) $dv['delivery_id'] ?>, '<?= $to ?>')"><?= ucfirst(str_replace('_', ' ', $to)) ?></button>
                        <?php endforeach; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function move(id, to) {
    if (!confirm('Move delivery #' + id + ' to "' + to + '"?')) return;
    apiPost('delivery_transition', { delivery_id: id, to: to }, function (res) {
        if (res.ok) { flash('Delivery updated to ' + to + '.', true); load_pages('deliveries.php'); }
        else flash(res.error || 'Update failed.', false);
    });
}
</script>
