<?php require_once __DIR__ . '/../includes/view.php';
$tickets = (new ScaleTicket())->listTickets(300, $_GET['search'] ?? null);
?>
<div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <div>
        <h4 class="page-title">Weighbridge Tickets</h4>
        <div class="page-subtitle">All recorded scale tickets with their payment breakdowns.</div>
    </div>
    <input type="text" class="form-control form-control-sm" id="ticketSearch" placeholder="Filter tickets…" style="max-width:220px;">
</div>

<div class="panel">
    <div class="table-responsive">
        <table class="table" id="ticketsTable">
            <thead><tr><th>Ticket</th><th>Farmer</th><th>Plate</th><th>Harvest</th><th>Grade</th><th>Gross</th><th>Tare</th><th>Net</th><th>Gross Pay</th><th>Net Payable</th><th>Delivery</th><th></th></tr></thead>
            <tbody>
            <?php if (!$tickets): ?><tr><td colspan="12" class="text-muted">No tickets recorded yet.</td></tr><?php endif; ?>
            <?php foreach ($tickets as $t): ?>
                <tr>
                    <td class="font-weight-bold"><?= e($t['ticket_number']) ?></td>
                    <td><?= e($t['farmer_name'] ?: '—') ?></td>
                    <td><?= e($t['plate_number'] ?: '—') ?></td>
                    <td><?= fdate($t['harvest_date']) ?></td>
                    <td><?= e($t['fruit_grade'] ?: '—') ?></td>
                    <td><?= kg($t['gross_weight']) ?></td>
                    <td><?= kg($t['tare_weight']) ?></td>
                    <td class="font-weight-bold"><?= kg($t['net_weight']) ?></td>
                    <td><?= money($t['gross_payment'] ?? 0) ?></td>
                    <td class="text-success font-weight-bold"><?= money($t['net_payable'] ?? 0) ?></td>
                    <td><?= status_badge($t['delivery_status'] ?? 'scheduled') ?></td>
                    <td><a class="action-btn" title="Print" target="_blank" href="pages/print_ticket.php?ticket_id=<?= (int) $t['ticket_id'] ?>"><i class="fas fa-print"></i></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$('#ticketSearch').on('keyup', function () {
    const v = $(this).val().toLowerCase();
    $('#ticketsTable tbody tr').each(function () { $(this).toggle($(this).text().toLowerCase().indexOf(v) > -1); });
});
</script>
