<?php require_once __DIR__ . '/../includes/view.php';
$trucks = $admin->listTrucks();
$TYPES  = ['tipper', 'flatbed', 'container', 'tractor'];
$TSTAT  = ['available', 'in_use', 'maintenance', 'retired'];
?>
<div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <div>
        <h4 class="page-title">Truck Management</h4>
        <div class="page-subtitle">Fleet records, capacities, maintenance status and trip history.</div>
    </div>
    <button class="btn-green" data-toggle="modal" data-target="#truckModal" onclick="truckForm()"><i class="fas fa-plus mr-1"></i> Add Truck</button>
</div>

<div class="panel">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Plate</th><th>Type</th><th>Capacity</th><th>Location</th><th>Completed Trips</th><th>Last Service</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (!$trucks): ?><tr><td colspan="8" class="text-muted">No trucks yet.</td></tr><?php endif; ?>
            <?php foreach ($trucks as $t): ?>
                <tr>
                    <td class="font-weight-bold"><?= e($t['plate_number']) ?></td>
                    <td><?= ucfirst(e($t['truck_type'])) ?></td>
                    <td><?= number_format((float) $t['carrying_capacity'], 1) ?> t</td>
                    <td><?= e($t['current_location'] ?: '—') ?></td>
                    <td><?= (int) $t['completed_trips'] ?></td>
                    <td><?= $t['last_service'] ? fdate($t['last_service']) : '—' ?></td>
                    <td>
                        <select class="form-control form-control-sm" onchange="setStatus(<?= (int) $t['truck_id'] ?>, this.value)" style="width:135px;">
                            <?php foreach ($TSTAT as $s): ?>
                                <option value="<?= $s ?>" <?= $t['status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <button class="action-btn" title="Trips" data-toggle="modal" data-target="#tripsModal" onclick="showTruckTrips(<?= (int) $t['truck_id'] ?>, '<?= e($t['plate_number']) ?>')"><i class="fas fa-route"></i></button>
                        <button class="action-btn" title="Edit" data-toggle="modal" data-target="#truckModal"
                            onclick='truckForm(<?= htmlspecialchars(json_encode($t), ENT_QUOTES) ?>)'><i class="fas fa-edit"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="truckModal" tabindex="-1"><div class="modal-dialog">
    <form class="modal-content" id="truckFormEl" onsubmit="return saveTruck(event)">
        <div class="modal-header"><h5 class="modal-title" id="truckModalTitle">Add Truck</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body">
            <input type="hidden" name="truck_id" id="t_id">
            <div class="form-group"><label>Plate number</label><input class="form-control" name="plate_number" id="t_plate" required></div>
            <div class="row">
                <div class="col-6 form-group"><label>Truck type</label>
                    <select class="form-control" name="truck_type" id="t_type"><?php foreach ($TYPES as $ty): ?><option value="<?= $ty ?>"><?= ucfirst($ty) ?></option><?php endforeach; ?></select>
                </div>
                <div class="col-6 form-group"><label>Carrying capacity (t)</label><input type="number" step="0.01" min="0.01" class="form-control" name="carrying_capacity" id="t_cap" required></div>
            </div>
            <div class="form-group"><label>Current location</label><input class="form-control" name="current_location" id="t_loc"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Save</button></div>
    </form>
</div></div>

<div class="modal fade" id="tripsModal" tabindex="-1"><div class="modal-dialog modal-lg">
    <div class="modal-content"><div class="modal-header"><h5 class="modal-title">Trip history — <span id="tripName"></span></h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
    <div class="modal-body"><div id="tripsBody" class="table-responsive">Loading…</div></div></div>
</div></div>

<script>
function truckForm(row) {
    $('#truckFormEl')[0].reset();
    if (row) {
        $('#truckModalTitle').text('Edit Truck');
        $('#t_id').val(row.truck_id); $('#t_plate').val(row.plate_number); $('#t_type').val(row.truck_type);
        $('#t_cap').val(row.carrying_capacity); $('#t_loc').val(row.current_location);
    } else { $('#truckModalTitle').text('Add Truck'); $('#t_id').val(''); }
}
function saveTruck(e) {
    e.preventDefault();
    const id = $('#t_id').val();
    apiPost(id ? 'truck_update' : 'truck_create', $('#truckFormEl').serialize(), function (res) {
        if (res.ok) { $('#truckModal').modal('hide'); flash('Truck saved.', true); load_pages('trucks.php'); }
        else flash(res.error || 'Save failed.', false);
    });
    return false;
}
function setStatus(id, val) {
    let extra = {};
    if (val === 'maintenance') {
        extra.maintenance_description = prompt('Maintenance description (optional):') || '';
        extra.maintenance_cost = prompt('Estimated cost (RM, optional):') || 0;
    }
    apiPost('truck_status', Object.assign({ truck_id: id, status: val }, extra), function (res) {
        if (res.ok) { flash('Status updated.', true); load_pages('trucks.php'); } else flash(res.error, false);
    });
}
function showTruckTrips(id, name) {
    $('#tripName').text(name); $('#tripsBody').html('Loading…');
    $.getJSON('query/admin_api.php', { action: 'truck_trips', truck_id: id }, function (res) {
        if (!res.ok) { $('#tripsBody').html('<div class="text-danger">'+res.error+'</div>'); return; }
        let rows = res.trips.map(t =>
            `<tr><td>${t.scheduled_date}</td><td>${t.farmer_name||'—'}</td><td>${t.driver_name||'—'}</td>
             <td>${t.net_weight?Number(t.net_weight).toLocaleString()+' kg':'—'}</td><td>${t.status}</td></tr>`).join('');
        $('#tripsBody').html(res.trips.length
            ? `<table class="table table-sm"><thead><tr><th>Date</th><th>Farmer</th><th>Driver</th><th>Net</th><th>Status</th></tr></thead><tbody>${rows}</tbody></table>`
            : '<div class="text-muted">No trips recorded.</div>');
    });
}
</script>
