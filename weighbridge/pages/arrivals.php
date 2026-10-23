<?php
require_once __DIR__ . '/../includes/view.php';
require_once __DIR__ . '/../../class/Delivery.php';

$delivery = new Delivery();
$board    = $delivery->arrivalsBoard();
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-truck-front text-success"></i> Truck Arrivals</h4>
        <div class="text-secondary" style="font-size:12px;">Scheduled deliveries and expected arrivals. Record actual arrival on site.</div>
    </div>
    <div class="search-box">
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-white border"><i class="bi bi-search"></i></span>
            <input type="search" id="wbSearch" class="form-control"
                   placeholder="Search schedule #, farmer, ticket # or plate...">
        </div>
    </div>
</div>

<div id="searchResults" class="mb-3" style="display:none;">
    <div class="panel">
        <div class="panel-header"><h5 class="panel-title"><i class="bi bi-list-check"></i> Search Results</h5></div>
        <div class="table-responsive"><table class="table custom-table" id="searchTable"></table></div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h5 class="panel-title"><i class="bi bi-signpost-split"></i> Arrivals Board</h5>
        <span class="badge bg-light text-secondary"><?= count($board) ?> on the road</span>
    </div>
    <div class="table-responsive">
        <table class="table custom-table">
            <thead>
                <tr>
                    <th>Sched</th><th>Farmer</th><th>Contact</th><th>Farm Location</th><th>Est. Load</th>
                    <th>Plate</th><th>Scheduled</th><th>Arrived</th><th>Status</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($board)): ?>
                    <tr><td colspan="10" class="text-center text-secondary py-4">No deliveries currently scheduled to arrive.</td></tr>
                <?php else: foreach ($board as $r): ?>
                    <tr>
                        <td class="fw-bold">SCH-<?= (int) $r['schedule_id'] ?></td>
                        <td><?= e($r['farmer_name'] ?: '—') ?></td>
                        <td><?= e($r['contact_no'] ?: '—') ?></td>
                        <td><?= e($r['farm_location'] ?: '—') ?></td>
                        <td><?= money($r['estimated_fresh_weight']) ?> t</td>
                        <td><?= e($r['plate_number'] ?: '—') ?></td>
                        <td><?= e(fdate($r['scheduled_date'])) ?></td>
                        <td><?= e(fdtime($r['actual_arrival'])) ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <td>
                            <?php if ($r['status'] === 'in_transit'): ?>
                                <button class="btn btn-sm btn-outline-success js-arrive" data-delivery="<?= (int) $r['delivery_id'] ?>">
                                    <i class="bi bi-download"></i> Record Arrival
                                </button>
                            <?php elseif (in_array($r['status'], ['arrived', 'delivered'], true) && (int) $r['has_ticket'] === 0): ?>
                                <button class="btn btn-sm btn-success page_click" data-page="weighing.php"><i class="bi bi-receipt"></i> Weigh</button>
                            <?php elseif ((int) $r['has_ticket'] > 0): ?>
                                <span class="text-success" style="font-size:11px;"><i class="bi bi-check2"></i> Ticketed</span>
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

// Client-side search (§87)
let searchTimer = null;
$('#wbSearch').on('input', function () {
    const term = $(this).val().trim();
    clearTimeout(searchTimer);
    if (term.length < 2) { $('#searchResults').hide(); return; }
    searchTimer = setTimeout(function () {
        $.get('query/weighing_api.php', { action: 'search', term: term }, function (res) {
            if (!res.ok) { return; }
            let rows = res.deliveries.map(function (d) {
                return '<tr><td class="fw-bold">SCH-' + d.schedule_id + '</td><td>' + (d.farmer_name || '—')
                    + '</td><td>' + (d.plate_number || '—') + '</td><td>' + (d.ticket_number || '—')
                    + '</td><td>' + d.scheduled_date + '</td><td>' + d.status.replace('_',' ') + '</td></tr>';
            }).join('');
            $('#searchTable').html(
                '<thead><tr><th>Sched</th><th>Farmer</th><th>Plate</th><th>Ticket</th><th>Scheduled</th><th>Status</th></tr></thead><tbody>'
                + (rows || '<tr><td colspan="6" class="text-center text-secondary py-3">No matches.</td></tr>') + '</tbody>');
            $('#searchResults').show();
        }, 'json');
    }, 300);
});
</script>
