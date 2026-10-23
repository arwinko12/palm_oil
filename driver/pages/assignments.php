<?php
require_once __DIR__ . '/../includes/view.php';
require_once __DIR__ . '/../../class/Delivery.php';

$delivery    = new Delivery();
$assignments = $delivery->assignmentsForDriver($driverId);

// Split into active (still moving) and finished for context.
$isActive = function ($r) { return !in_array($r['delivery_status'], ['completed', 'cancelled'], true); };
$active   = array_values(array_filter($assignments, $isActive));
$done     = array_values(array_filter($assignments, fn($r) => !$isActive($r)));
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-truck text-success"></i> My Assignments</h4>
        <div class="text-secondary" style="font-size:12px;">Farmer contact, farm location, load, truck details and delivery instructions.</div>
    </div>
    <span class="badge bg-light text-success"><?= count($active) ?> active · <?= count($done) ?> finished</span>
</div>

<?php if (empty($assignments)): ?>
    <div class="panel text-center text-secondary py-5">
        <i class="bi bi-inboxes fs-1 d-block mb-2 opacity-50"></i>
        No hauling assignments yet. New assignments will appear here and in your notifications.
    </div>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($active as $r):
        $actions = $delivery->driverActions($r); ?>
        <div class="col-12 col-lg-6">
            <div class="panel">
                <div class="panel-header">
                    <h5 class="panel-title mb-0">
                        <i class="bi bi-geo-alt-fill"></i> SCH-<?= (int) $r['schedule_id'] ?>
                        <span class="text-secondary" style="font-size:11px;font-weight:500;">· REQ-<?= (int) $r['request_id'] ?></span>
                    </h5>
                    <?= status_badge($r['delivery_status']) ?>
                </div>

                <div class="row g-2" style="font-size:12px;">
                    <div class="col-6">
                        <div class="text-muted">Hauling Date</div>
                        <div class="fw-bold"><?= e(fdate($r['scheduled_date'])) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted">Time</div>
                        <div class="fw-bold"><?= e(ftime($r['scheduled_time'])) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted">Farmer</div>
                        <div class="fw-bold"><?= e($r['farmer_name'] ?: '—') ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted">Contact No.</div>
                        <div class="fw-bold"><?= e($r['contact_no'] ?: '—') ?></div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted">Farm Location</div>
                        <div class="fw-bold"><?= e($r['farm_location'] ?: '—') ?></div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted">Est. Load</div>
                        <div class="fw-bold"><?= money($r['estimated_fresh_weight']) ?> t</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted">Distance</div>
                        <div class="fw-bold"><?= money($r['distance_to_mill']) ?> km</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted">Truck</div>
                        <div class="fw-bold"><?= e($r['plate_number'] ?: '—') ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted">Truck Type</div>
                        <div class="fw-bold text-capitalize"><?= e($r['truck_type'] ?: '—') ?> (<?= money($r['carrying_capacity']) ?> t)</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted">Departure</div>
                        <div class="fw-bold"><?= e(fdtime($r['departure_time'])) ?></div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted">Delivery Instructions</div>
                        <div><?= e($r['delivery_notes'] ?: ($r['request_notes'] ?: 'No special instructions.')) ?></div>
                    </div>
                </div>

                <?php if (!empty($actions)): ?>
                    <div class="mt-3 d-flex gap-2 flex-wrap">
                        <?php foreach ($actions as $to): ?>
                            <button class="btn btn-sm btn-success js-status"
                                    data-delivery="<?= (int) $r['delivery_id'] ?>"
                                    data-status="<?= e($to) ?>">
                                <i class="bi bi-play-fill"></i> <?= e(action_label($to)) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-secondary mt-2" style="font-size:11px;">
                        Arrival, weighing and completion are handled at the mill weighbridge.
                    </div>
                <?php else: ?>
                    <div class="text-secondary mt-3" style="font-size:11px;">No driver action available for this status.</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (!empty($done)): ?>
    <div class="panel mt-4">
        <div class="panel-header">
            <h5 class="panel-title"><i class="bi bi-check2-circle"></i> Recently Finished</h5>
            <a class="view-all page_click" data-page="history.php">Full trip history <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="table-responsive">
            <table class="table custom-table">
                <thead>
                    <tr><th>Sched</th><th>Date</th><th>Farm Location</th><th>Truck</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($done as $r): ?>
                        <tr>
                            <td class="fw-bold">SCH-<?= (int) $r['schedule_id'] ?></td>
                            <td><?= e(fdate($r['scheduled_date'])) ?></td>
                            <td><?= e($r['farm_location']) ?></td>
                            <td><?= e($r['plate_number'] ?: '—') ?></td>
                            <td><?= status_badge($r['delivery_status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script>
const CSRF = '<?= e($csrf) ?>';
$(document).on('click', '.js-status', function () {
    const btn = $(this);
    Swal.fire({
        title: 'Confirm status update',
        text: 'This records your trip and cannot be undone from the driver portal.',
        icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, update',
    }).then((result) => {
        if (!result.isConfirmed) return;
        btn.prop('disabled', true);
        $.post('query/delivery_api.php', {
            action: 'update_status', csrf_token: CSRF,
            delivery_id: btn.data('delivery'), status: btn.data('status')
        }, function (res) {
            if (res.ok) {
                Swal.fire('Updated', 'Delivery status set to ' + res.status, 'success').then(() => window.location.reload());
            } else {
                btn.prop('disabled', false);
                Swal.fire('Not allowed', res.error || 'Could not update status.', 'error');
            }
        }, 'json').fail(function () {
            btn.prop('disabled', false);
            Swal.fire('Error', 'Request failed. Try again.', 'error');
        });
    });
});
</script>
