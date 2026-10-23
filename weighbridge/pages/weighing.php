<?php
require_once __DIR__ . '/../includes/view.php';
require_once __DIR__ . '/../../class/Delivery.php';

$delivery = new Delivery();
$awaiting = $delivery->awaitingWeighing();
$grades   = fruit_grades();
?>
<div class="mb-3">
    <h4 class="mb-0 fw-bold"><i class="bi bi-receipt-cutoff text-success"></i> Weighing Queue</h4>
    <div class="text-secondary" style="font-size:12px;">Deliveries that have arrived and still need a scale ticket. Net weight is computed automatically.</div>
</div>

<div class="row g-3">
    <!-- Queue -->
    <div class="col-12 col-xl-6">
        <div class="panel">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-truck"></i> Awaiting Weighing</h5>
                <span class="badge bg-light text-secondary"><?= count($awaiting) ?> pending</span>
            </div>
            <div class="table-responsive">
                <table class="table custom-table">
                    <thead>
                        <tr><th>Sched</th><th>Farmer</th><th>Plate</th><th>Est. Load</th><th>Arrived</th><th></th></tr>
                    </thead>
                    <tbody id="queueBody">
                        <?php if (empty($awaiting)): ?>
                            <tr><td colspan="6" class="text-center text-secondary py-4">Nothing to weigh. All arrived loads have tickets.</td></tr>
                        <?php else: foreach ($awaiting as $r): ?>
                            <tr>
                                <td class="fw-bold">SCH-<?= (int) $r['schedule_id'] ?></td>
                                <td><?= e($r['farmer_name'] ?: '—') ?></td>
                                <td><?= e($r['plate_number'] ?: '—') ?></td>
                                <td><?= money($r['estimated_fresh_weight']) ?> t</td>
                                <td><?= e(fdtime($r['actual_arrival'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-success js-weigh"
                                        data-delivery="<?= (int) $r['delivery_id'] ?>"
                                        data-schedule="<?= (int) $r['schedule_id'] ?>"
                                        data-farmer="<?= e($r['farmer_name']) ?>"
                                        data-plate="<?= e($r['plate_number']) ?>"
                                        data-truck="<?= e($r['truck_type']) ?>"
                                        data-location="<?= e($r['farm_location']) ?>"
                                        data-grade="<?= e($r['fruit_grade'] ?? '') ?>">Weigh</button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Ticket form -->
    <div class="col-12 col-xl-6">
        <div class="panel" id="ticketPanel" style="<?= empty($awaiting) ? '' : '' ?>">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-file-earmark-plus"></i> New Scale Ticket</h5>
            </div>

            <div id="noSelection" class="text-secondary" style="font-size:13px;">
                Select <strong>Weigh</strong> on a delivery to start a ticket.
            </div>

            <form id="ticketForm" style="display:none;">
                <input type="hidden" name="delivery_id" id="f_delivery">
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Ticket No.</label>
                        <input type="text" name="ticket_number" id="f_ticket" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Delivery (Sched #)</label>
                        <input type="text" id="f_sched" class="form-control form-control-sm bg-light" readonly>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Farmer</label>
                        <input type="text" id="f_farmer" class="form-control form-control-sm bg-light" readonly>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Truck Plate</label>
                        <input type="text" id="f_plate" class="form-control form-control-sm bg-light" readonly>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Truck Type</label>
                        <input type="text" id="f_truck" class="form-control form-control-sm bg-light text-capitalize" readonly>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Registered At</label>
                        <input type="text" id="f_reg" class="form-control form-control-sm bg-light" readonly>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Farm Location</label>
                        <input type="text" name="farm_location" id="f_location" class="form-control form-control-sm">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Harvest Date</label>
                        <input type="date" name="harvest_date" id="f_harvest" class="form-control form-control-sm">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Fruit Grade / Class</label>
                        <select name="fruit_grade" id="f_grade" class="form-select form-select-sm">
                            <?php foreach ($grades as $g): ?><option value="<?= e($g) ?>"><?= e($g) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-bold">Gross (kg)</label>
                        <input type="number" step="0.01" min="0" name="gross_weight" id="f_gross" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-bold">Tare (kg)</label>
                        <input type="number" step="0.01" min="0" name="tare_weight" id="f_tare" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-bold">Net (auto)</label>
                        <input type="text" id="f_net" class="form-control form-control-sm fw-bold bg-light" readonly value="0.00">
                    </div>
                </div>
                <div class="text-secondary mt-2" style="font-size:11px;">
                    Net weight, gross payment, incentives, haulage deduction and net payable are calculated on save, and the farmer is notified.
                </div>
                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-success" id="saveTicket">
                        <i class="bi bi-save"></i> Save Ticket
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="resetForm">Clear</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const WB_CSRF = '<?= e($csrf) ?>';

function calcNet() {
    const g = parseFloat(document.getElementById('f_gross').value) || 0;
    const t = parseFloat(document.getElementById('f_tare').value) || 0;
    document.getElementById('f_net').value = (g - t).toFixed(2);
}
['f_gross', 'f_tare'].forEach(id => document.getElementById(id).addEventListener('input', calcNet));

$(document).on('click', '.js-weigh', function () {
    const b = $(this).data();
    document.getElementById('f_delivery').value = b.delivery;
    document.getElementById('f_sched').value = 'SCH-' + b.schedule;
    document.getElementById('f_farmer').value = b.farmer || '';
    document.getElementById('f_plate').value = b.plate || '';
    document.getElementById('f_truck').value = b.truck || '';
    document.getElementById('f_location').value = b.location || '';
    document.getElementById('f_ticket').value = 'ST-' + Date.now().toString().slice(-8);
    document.getElementById('f_harvest').value = new Date().toISOString().slice(0, 10);
    document.getElementById('f_reg').value = new Date().toLocaleString();
    if (b.grade) document.getElementById('f_grade').value = b.grade;
    document.getElementById('f_gross').value = '';
    document.getElementById('f_tare').value = '';
    calcNet();
    document.getElementById('noSelection').style.display = 'none';
    document.getElementById('ticketForm').style.display = 'block';
});

$('#resetForm').on('click', function () {
    document.getElementById('ticketForm').style.display = 'none';
    document.getElementById('noSelection').style.display = 'block';
});

$('#ticketForm').on('submit', function (e) {
    e.preventDefault();
    const data = $(this).serialize() + '&action=create_ticket&csrf_token=' + WB_CSRF;
    $('#saveTicket').prop('disabled', true);
    $.post('query/weighing_api.php', data, function (res) {
        if (res.ok) {
            Swal.fire({
                title: 'Ticket saved',
                text: 'Net weight ' + res.net_weight + ' kg. Payment computed and farmer notified.',
                icon: 'success', confirmButtonText: 'Reload queue'
            }).then(() => location.reload());
        } else {
            $('#saveTicket').prop('disabled', false);
            Swal.fire('Could not save', res.error || 'Unexpected error.', 'error');
        }
    }, 'json').fail(function () {
        $('#saveTicket').prop('disabled', false);
        Swal.fire('Error', 'Request failed.', 'error');
    });
});
</script>
