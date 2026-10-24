<?php
require_once __DIR__ . '/../includes/view.php';
require_once __DIR__ . '/../../class/ScaleTicket.php';

$tickets = (new ScaleTicket())->listTickets(200);
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text text-success"></i> Scale Tickets</h4>
        <div class="text-secondary" style="font-size:12px;">Weighed loads with net weight and the full payment breakdown. Print or complete as needed.</div>
    </div>
    <div class="input-group input-group-sm" style="width:auto;">
        <span class="input-group-text bg-white border"><i class="bi bi-search"></i></span>
        <input type="search" id="ticketSearch" class="form-control" placeholder="Filter ticket / farmer / plate">
    </div>
</div>

<div class="panel">
    <div class="table-responsive">
        <table class="table custom-table" id="ticketTable" data-search="#ticketSearch">
            <thead>
                <tr>
                    <th>Ticket</th><th>Farmer</th><th>Plate</th><th>Harvest</th><th>Grade</th>
                    <th>Gross</th><th>Tare</th><th>Net</th><th>Gross Pay</th><th>+Incentive</th>
                    <th>+Quality</th><th>−Deduct</th><th>Net Payable</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                    <tr><td colspan="15" class="text-center text-secondary py-4">No scale tickets recorded yet.</td></tr>
                <?php else: foreach ($tickets as $t): ?>
                    <tr>
                        <td class="fw-bold"><?= e($t['ticket_number']) ?></td>
                        <td><?= e($t['farmer_name'] ?: '—') ?></td>
                        <td><?= e($t['plate_number'] ?: '—') ?></td>
                        <td><?= e(fdate($t['harvest_date'])) ?></td>
                        <td><?= e($t['fruit_grade'] ?: '—') ?></td>
                        <td><?= money($t['gross_weight']) ?></td>
                        <td><?= money($t['tare_weight']) ?></td>
                        <td class="fw-bold"><?= money($t['net_weight']) ?></td>
                        <td><?= money($t['gross_payment']) ?></td>
                        <td class="text-success">+<?= money($t['incentive_amount']) ?></td>
                        <td class="text-success">+<?= money($t['quality_bonus']) ?></td>
                        <td class="text-danger">−<?= money($t['total_deductions']) ?></td>
                        <td class="fw-bold text-success"><?= money($t['net_payable']) ?></td>
                        <td><?= status_badge($t['delivery_status']) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-outline-secondary js-print" data-ticket="<?= (int) $t['ticket_id'] ?>"
                                        title="Print ticket"><i class="bi bi-printer"></i></button>
                                <?php if ($t['delivery_status'] === 'arrived'): ?>
                                    <button class="btn btn-sm btn-outline-success js-status" data-delivery="<?= (int) $t['delivery_id'] ?>" data-status="delivered"
                                            title="Mark delivered"><i class="bi bi-check2"></i></button>
                                <?php elseif ($t['delivery_status'] === 'delivered'): ?>
                                    <button class="btn btn-sm btn-success js-status" data-delivery="<?= (int) $t['delivery_id'] ?>" data-status="completed"
                                            title="Complete haul"><i class="bi bi-flag-fill"></i></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="text-secondary mt-2" style="font-size:11px;">
        Net Payable = Gross + Incentive + Quality − Haulage deduction. Completing a haul frees the driver/truck and confirms the farmer's next harvest cycle.
    </div>
</div>

<script>
const WB_CSRF = '<?= e($csrf) ?>';

$(document).on('click', '.js-status', function () {
    const btn = $(this).prop('disabled', true);
    $.post('query/weighing_api.php', {
        action: 'update_status', csrf_token: WB_CSRF,
        delivery_id: btn.data('delivery'), status: btn.data('status')
    }, function (res) {
        if (res.ok) { Swal.fire('Done', 'Status set to ' + res.status, 'success').then(() => location.reload()); }
        else { btn.prop('disabled', false); Swal.fire('Not allowed', res.error || 'Failed.', 'error'); }
    }, 'json');
});

// The header filter box is wired to the DataTables instance by
// js/portal-datatables.js via the table's data-search attribute.
</script>
