<?php require_once __DIR__ . '/../includes/view.php';
$statusFilter = $_GET['status'] ?? '';
$requests = $admin->listRequests($statusFilter !== '' ? $statusFilter : null);
$FILTERS = ['', 'pending', 'approved', 'scheduled', 'rejected', 'cancelled'];
$TYPES = ['tipper', 'flatbed', 'container', 'tractor'];
?>
<div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <div>
        <h4 class="page-title">Hauling Requests</h4>
        <div class="page-subtitle">Review, approve, edit and schedule farmer hauling requests.</div>
    </div>
</div>

<div class="mb-3">
    <?php foreach ($FILTERS as $f): ?>
        <button class="btn btn-sm mb-1 <?= $f === $statusFilter ? 'btn-success' : 'btn-outline-secondary' ?> page_click"
                data-page="requests.php<?= $f ? '?status=' . $f : '' ?>"><?= $f === '' ? 'All' : ucfirst($f) ?></button>
    <?php endforeach; ?>
</div>

<div class="panel">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Request</th><th>Farmer</th><th>Preferred</th><th>Location</th><th>Est. Tons</th><th>Truck</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (!$requests): ?><tr><td colspan="8" class="text-muted">No requests in this view.</td></tr><?php endif; ?>
            <?php foreach ($requests as $r): ?>
                <?php $payload = htmlspecialchars(json_encode($r), ENT_QUOTES); ?>
                <tr>
                    <td class="font-weight-bold">HR-<?= (int) $r['request_id'] ?></td>
                    <td><div class="table-name"><?= e($r['farmer_name']) ?></div><div class="table-sub"><?= e($r['contact_no']) ?></div></td>
                    <td><?= fdate($r['preferred_date']) ?><div class="table-sub"><?= ftime($r['preferred_time']) ?></div></td>
                    <td><?= e($r['farm_location']) ?></td>
                    <td><?= number_format((float) $r['estimated_fresh_weight'], 2) ?> t</td>
                    <td><?= ucfirst(e($r['requested_truck_type'])) ?></td>
                    <td><?= req_badge($r['status']) ?></td>
                    <td class="text-nowrap">
                        <?php if ($r['status'] === 'pending'): ?>
                            <button class="btn btn-sm btn-success" onclick="review(<?= (int) $r['request_id'] ?>,'approved')"><i class="fas fa-check"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="review(<?= (int) $r['request_id'] ?>,'rejected')"><i class="fas fa-times"></i></button>
                        <?php endif; ?>
                        <?php if (in_array($r['status'], ['pending', 'approved'], true)): ?>
                            <button class="action-btn" title="Edit" data-toggle="modal" data-target="#editModal" onclick="editForm(<?= $payload ?>)"><i class="fas fa-edit"></i></button>
                        <?php endif; ?>
                        <?php if (in_array($r['status'], ['approved', 'pending'], true) && !$r['schedule_id']): ?>
                            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#schedModal" onclick="schedForm(<?= $payload ?>)"><i class="fas fa-calendar-plus"></i> Schedule</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit modal -->
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog">
    <form class="modal-content" id="editFormEl" onsubmit="return saveEdit(event)">
        <div class="modal-header"><h5 class="modal-title">Edit Request</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body">
            <input type="hidden" name="request_id" id="e_id">
            <div class="row">
                <div class="col-7 form-group"><label>Preferred date</label><input type="date" class="form-control" name="preferred_date" id="e_date" required></div>
                <div class="col-5 form-group"><label>Preferred time</label><input type="time" class="form-control" name="preferred_time" id="e_time" required></div>
            </div>
            <div class="form-group"><label>Farm location</label><input class="form-control" name="farm_location" id="e_loc" required></div>
            <div class="row">
                <div class="col-4 form-group"><label>Est. tons</label><input type="number" step="0.01" min="0.01" class="form-control" name="estimated_fresh_weight" id="e_weight" required></div>
                <div class="col-4 form-group"><label>Distance (km)</label><input type="number" step="0.01" min="0" class="form-control" name="distance_to_mill" id="e_dist"></div>
                <div class="col-4 form-group"><label>Truck type</label><select class="form-control" name="requested_truck_type" id="e_type"><?php foreach ($TYPES as $ty): ?><option value="<?= $ty ?>"><?= ucfirst($ty) ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="form-group"><label>Notes</label><textarea class="form-control" name="notes" id="e_notes" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Save</button></div>
    </form>
</div></div>

<!-- Schedule modal -->
<div class="modal fade" id="schedModal" tabindex="-1"><div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Schedule Request <span id="schedReqId"></span></h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body">
            <input type="hidden" id="s_request_id">
            <input type="hidden" id="s_truck_type">
            <input type="hidden" id="s_capacity">
            <p class="text-muted small mb-2" id="schedSummary"></p>
            <div class="row">
                <div class="col-6 form-group"><label>Hauling date</label><input type="date" class="form-control" id="s_date"></div>
                <div class="col-6 form-group"><label>Hauling time</label><input type="time" class="form-control" id="s_time"></div>
            </div>
            <div class="row">
                <div class="col-6 form-group"><label>Available drivers</label><select class="form-control" id="s_driver"><option value="">Select date &amp; time first…</option></select></div>
                <div class="col-6 form-group"><label>Available trucks (correct type &amp; capacity)</label><select class="form-control" id="s_truck"><option value="">—</option></select></div>
            </div>
            <div id="conflictBox" class="alert alert-warning py-2 d-none"></div>
            <div id="schedResult"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="doSchedule()"><i class="fas fa-check mr-1"></i> Confirm Schedule</button>
        </div>
    </div>
</div></div>

<script>
const FILTER_STATUS = '<?= e($statusFilter) ?>';

function review(id, decision) {
    const remarks = prompt('Review remarks (optional):') || '';
    apiPost('request_review', { request_id: id, decision: decision, remarks: remarks, status_filter: FILTER_STATUS },
        function (res) {
            if (res.ok) { flash('Request ' + decision + '.', true); load_pages('requests.php' + (FILTER_STATUS ? '?status=' + FILTER_STATUS : '')); }
            else flash(res.error, false);
        });
}

/* ---- Edit ---- */
function editForm(r) {
    $('#e_id').val(r.request_id); $('#e_date').val((r.preferred_date||'').slice(0,10)); $('#e_time').val((r.preferred_time||'').slice(0,5));
    $('#e_loc').val(r.farm_location); $('#e_weight').val(r.estimated_fresh_weight); $('#e_dist').val(r.distance_to_mill);
    $('#e_type').val(r.requested_truck_type); $('#e_notes').val(r.notes || '');
}
function saveEdit(e) {
    e.preventDefault();
    apiPost('request_edit', $('#editFormEl').serialize(), function (res) {
        if (res.ok) { $('#editModal').modal('hide'); flash('Request updated.', true); load_pages('requests.php'); }
        else flash(res.error, false);
    });
    return false;
}

/* ---- Scheduling with conflict detection ---- */
function schedForm(r) {
    $('#s_request_id').val(r.request_id);
    $('#schedReqId').text('#' + r.request_id);
    $('#s_truck_type').val(r.requested_truck_type);
    $('#s_capacity').val(r.estimated_fresh_weight);
    $('#s_date').val((r.preferred_date || '').slice(0,10));
    $('#s_time').val((r.preferred_time || '').slice(0,5));
    $('#schedSummary').text(`${r.farmer_name} • ${r.farm_location} • ${r.estimated_fresh_weight} t • ${r.requested_truck_type}`);
    $('#conflictBox').addClass('d-none'); $('#schedResult').empty();
    loadResources();
}
function loadResources() {
    const date = $('#s_date').val(), time = $('#s_time').val();
    if (!date || !time) return;
    $.getJSON('query/admin_api.php', { action: 'available_drivers', date, time }, function (res) {
        let opts = '<option value="">Choose driver…</option>';
        (res.drivers || []).forEach(d => opts += `<option value="${d.driver_id}">${d.fullname}</option>`);
        $('#s_driver').html(res.drivers && res.drivers.length ? opts : '<option value="">No drivers free at this slot</option>');
    });
    $.getJSON('query/admin_api.php', {
        action: 'available_trucks', date, time,
        truck_type: $('#s_truck_type').val(), capacity: $('#s_capacity').val()
    }, function (res) {
        let opts = '<option value="">Choose truck…</option>';
        (res.trucks || []).forEach(t => opts += `<option value="${t.truck_id}">${t.plate_number} (${Number(t.carrying_capacity)} t)</option>`);
        $('#s_truck').html(res.trucks && res.trucks.length ? opts : '<option value="">No suitable trucks available</option>');
    });
}
$('#schedModal').on('change', '#s_date, #s_time', loadResources);
$('#schedModal').on('change', '#s_driver, #s_truck', function () {
    const driver = $('#s_driver').val(), truck = $('#s_truck').val();
    if (!driver || !truck) { $('#conflictBox').addClass('d-none'); return; }
    $.getJSON('query/admin_api.php', {
        action: 'conflict_check', driver_id: driver, truck_id: truck,
        date: $('#s_date').val(), time: $('#s_time').val()
    }, function (res) {
        if (res.warnings && res.warnings.length) {
            $('#conflictBox').removeClass('d-none').html('<strong>Scheduling conflict:</strong><ul class="mb-0">' + res.warnings.map(w => `<li>${w}</li>`).join('') + '</ul>');
        } else {
            $('#conflictBox').removeClass('d-none alert-warning').addClass('alert-success').html('No conflicts detected for this slot.');
        }
    });
});
function doSchedule() {
    const payload = {
        request_id: $('#s_request_id').val(), driver_id: $('#s_driver').val(), truck_id: $('#s_truck').val(),
        date: $('#s_date').val(), time: $('#s_time').val()
    };
    if (!payload.driver_id || !payload.truck_id) { flash('Select a driver and a truck.', false); return; }
    apiPost('request_schedule', payload, function (res) {
        if (res.ok) {
            $('#schedResult').html(`<div class="alert alert-success py-2">Scheduled — delivery #${res.delivery_id} created.</div>`);
            flash('Haul scheduled.', true);
            setTimeout(function(){ $('#schedModal').modal('hide'); load_pages('requests.php'); }, 900);
        } else {
            $('#schedResult').html('<div class="alert alert-danger py-2">' + (res.error || 'Scheduling failed.') + '</div>');
        }
    });
}
</script>
