<?php
require_once __DIR__ . '/../includes/view.php';
require_once __DIR__ . '/../../class/Delivery.php';
require_once __DIR__ . '/../../class/ScaleTicket.php';

$delivery = new Delivery();
$arrivals = $delivery->arrivalsBoard();
$awaiting = $delivery->awaitingWeighing();
$tickets  = (new ScaleTicket())->listTickets(200);

$today = date('Y-m-d');
$ticketedToday = 0;
$netToday = 0.0;
foreach ($tickets as $t) {
    if (substr((string) $t['created_at'], 0, 10) === $today) {
        $ticketedToday++;
        $netToday += (float) $t['net_weight'];
    }
}
$recent = array_slice($arrivals, 0, 6);
$opName = Auth::name();
?>
<section class="welcome-banner">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2>Welcome, <?= e($opName) ?> 👋</h2>
            <p>Record truck arrivals, weigh loads and issue scale tickets.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <span class="badge rounded-pill bg-light text-success px-3 py-2">
                <i class="bi bi-circle-fill me-1" style="font-size:7px"></i>
                <?= count($awaiting) ?> delivery(s) awaiting weighing
            </span>
        </div>
    </div>
</section>

<section class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">On the Road</div>
                    <div class="stat-number"><?= count($arrivals) ?></div>
                    <div class="stat-change neutral">scheduled / in transit</div>
                </div>
                <div class="stat-icon icon-blue"><i class="bi bi-truck-front"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Awaiting Weighing</div>
                    <div class="stat-number"><?= count($awaiting) ?></div>
                    <div class="stat-change neutral">arrived, no ticket</div>
                </div>
                <div class="stat-icon icon-orange"><i class="bi bi-receipt-cutoff"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Tickets Today</div>
                    <div class="stat-number"><?= (int) $ticketedToday ?></div>
                    <div class="stat-change"><?= money($netToday) ?> kg net</div>
                </div>
                <div class="stat-icon icon-green"><i class="bi bi-file-earmark-check"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Total Tickets</div>
                    <div class="stat-number"><?= count($tickets) ?></div>
                    <div class="stat-change neutral">recorded to date</div>
                </div>
                <div class="stat-icon icon-red"><i class="bi bi-journal-text"></i></div>
            </div>
        </div>
    </div>
</section>

<section class="row g-3">
    <div class="col-12 col-xl-7">
        <div class="panel">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-truck"></i>Recent Arrivals</h5>
                <a class="view-all page_click" data-page="arrivals.php">Arrivals board <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table custom-table">
                    <thead>
                        <tr><th>Sched</th><th>Farmer</th><th>Plate</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent)): ?>
                            <tr><td colspan="5" class="text-center text-secondary py-4">No deliveries in transit.</td></tr>
                        <?php else: foreach ($recent as $r): ?>
                            <tr>
                                <td class="fw-bold">SCH-<?= (int) $r['schedule_id'] ?></td>
                                <td><?= e($r['farmer_name'] ?: '—') ?></td>
                                <td><?= e($r['plate_number'] ?: '—') ?></td>
                                <td><?= status_badge($r['status']) ?></td>
                                <td>
                                    <?php if ($r['status'] === 'in_transit'): ?>
                                        <button class="btn btn-sm btn-outline-success js-arrive" data-delivery="<?= (int) $r['delivery_id'] ?>">
                                            <i class="bi bi-download"></i> Arrival
                                        </button>
                                    <?php elseif (in_array($r['status'], ['arrived', 'delivered'], true) && (int) $r['has_ticket'] === 0): ?>
                                        <button class="btn btn-sm btn-success page_click" data-page="weighing.php">
                                            <i class="bi bi-receipt"></i> Weigh
                                        </button>
                                    <?php elseif ((int) $r['has_ticket'] > 0): ?>
                                        <span class="text-secondary" style="font-size:11px;">Ticketed</span>
                                    <?php else: ?>
                                        <span class="text-secondary" style="font-size:11px;">Awaiting departure</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="panel">
            <div class="panel-header"><h5 class="panel-title"><i class="bi bi-lightning-charge"></i>Quick Actions</h5></div>
            <div class="d-grid gap-2">
                <button class="btn btn-outline-success page_click" data-page="arrivals.php">
                    <i class="bi bi-search me-1"></i> Search deliveries (§87)
                </button>
                <button class="btn btn-success page_click" data-page="weighing.php">
                    <i class="bi bi-receipt-cutoff me-1"></i> Open weighing queue
                </button>
                <button class="btn btn-outline-secondary page_click" data-page="tickets.php">
                    <i class="bi bi-file-earmark-text me-1"></i> View &amp; print tickets
                </button>
            </div>
            <div class="text-secondary mt-3" style="font-size:11px;">
                Operators record arrivals, weigh loads and print tickets. Schedule creation, cancellations
                and admin accounts are out of scope for this role.
            </div>
        </div>
    </div>
</section>

<script>
const WB_CSRF = '<?= e($csrf) ?>';
$(document).on('click', '.js-arrive', function () {
    const btn = $(this).prop('disabled', true);
    $.post('query/weighing_api.php', { action: 'mark_arrived', csrf_token: WB_CSRF, delivery_id: btn.data('delivery') },
        function (res) {
            if (res.ok) { Swal.fire('Arrival recorded', 'Actual arrival time saved.', 'success').then(() => location.reload()); }
            else { btn.prop('disabled', false); Swal.fire('Not allowed', res.error || 'Failed.', 'error'); }
        }, 'json');
});
</script>
