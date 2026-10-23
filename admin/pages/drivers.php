<?php require_once __DIR__ . '/../includes/view.php';
$drivers = $admin->listDrivers();
$AVAIL = ['available', 'on_trip', 'unavailable'];
?>
<div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <div>
        <h4 class="page-title">Driver Management</h4>
        <div class="page-subtitle">Manage driver accounts, licences and current availability.</div>
    </div>
    <button class="btn-green" data-toggle="modal" data-target="#driverModal" onclick="driverForm()"><i class="fas fa-plus mr-1"></i> Add Driver</button>
</div>

<div class="panel">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Driver</th><th>Contact</th><th>Licence</th><th>Home Base</th><th>Completed Trips</th><th>Availability</th><th>Account</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (!$drivers): ?><tr><td colspan="8" class="text-muted">No drivers yet.</td></tr><?php endif; ?>
            <?php foreach ($drivers as $d): ?>
                <tr>
                    <td><div class="table-name"><?= e($d['fullname']) ?></div><div class="table-sub"><?= e($d['email'] ?? '') ?></div></td>
                    <td><?= e($d['contact_no']) ?></td>
                    <td><?= e($d['license_no'] ?: '—') ?><div class="table-sub"><?= $d['license_expiry'] ? 'exp ' . fdate($d['license_expiry']) : '' ?></div></td>
                    <td><?= e($d['home_base'] ?: '—') ?></td>
                    <td><?= (int) $d['completed_trips'] ?></td>
                    <td>
                        <select class="form-control form-control-sm" onchange="setAvail(<?= (int) $d['driver_id'] ?>, this.value)" style="width:130px;">
                            <?php foreach ($AVAIL as $a): ?>
                                <option value="<?= $a ?>" <?= $d['availability'] === $a ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $a)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><?= account_badge($d['status']) ?></td>
                    <td>
                        <button class="action-btn" title="Trips" data-toggle="modal" data-target="#tripsModal" onclick="showTrips(<?= (int) $d['driver_id'] ?>, '<?= e($d['fullname']) ?>')"><i class="fas fa-route"></i></button>
                        <button class="action-btn" title="Edit" data-toggle="modal" data-target="#driverModal"
                            onclick='driverForm(<?= htmlspecialchars(json_encode($d), ENT_QUOTES) ?>)'><i class="fas fa-edit"></i></button>
                        <button class="action-btn" title="Toggle status" onclick="toggleDriver(<?= (int) $d['driver_id'] ?>, '<?= $d['status'] === 'active' ? 'inactive' : 'active' ?>')">
                            <i class="fas fa-<?= $d['status'] === 'active' ? 'user-slash' : 'user-check' ?>"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="driverModal" tabindex="-1"><div class="modal-dialog">
    <form class="modal-content" id="driverFormEl" onsubmit="return saveDriver(event)">
        <div class="modal-header"><h5 class="modal-title" id="driverModalTitle">Add Driver</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body">
            <input type="hidden" name="driver_id" id="d_id">
            <div class="form-group"><label>Full name</label><input class="form-control" name="fullname" id="d_name" required></div>
            <div class="form-group" id="wrapEmail"><label>Email</label><input type="email" class="form-control" name="email" id="d_email"></div>
            <div class="form-group" id="wrapPass"><label>Initial password</label><input type="text" class="form-control" name="password" id="d_pass" minlength="6"></div>
            <div class="row">
                <div class="col-6 form-group"><label>Contact</label><input class="form-control" name="contact_no" id="d_contact"></div>
                <div class="col-6 form-group"><label>Home base</label><input class="form-control" name="home_base" id="d_base"></div>
            </div>
            <div class="row">
                <div class="col-6 form-group"><label>License no.</label><input class="form-control" name="license_no" id="d_lic"></div>
                <div class="col-6 form-group"><label>License expiry</label><input type="date" class="form-control" name="license_expiry" id="d_exp"></div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Save</button></div>
    </form>
</div></div>

<div class="modal fade" id="tripsModal" tabindex="-1"><div class="modal-dialog modal-lg">
    <div class="modal-content"><div class="modal-header"><h5 class="modal-title">Trip history — <span id="tripName"></span></h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
    <div class="modal-body"><div id="tripsBody" class="table-responsive">Loading…</div></div></div>
</div></div>

<script>
function driverForm(row) {
    $('#driverFormEl')[0].reset();
    if (row) {
        $('#driverModalTitle').text('Edit Driver');
        $('#d_id').val(row.driver_id); $('#d_name').val(row.fullname); $('#d_contact').val(row.contact_no);
        $('#d_base').val(row.home_base); $('#d_lic').val(row.license_no); $('#d_exp').val((row.license_expiry || '').slice(0,10));
        $('#wrapEmail, #wrapPass').hide();
    } else {
        $('#driverModalTitle').text('Add Driver'); $('#d_id').val('');
        $('#wrapEmail').show().find('input').prop('required', true);
        $('#wrapPass').show().find('input').prop('required', true);
    }
}
function saveDriver(e) {
    e.preventDefault();
    const id = $('#d_id').val();
    apiPost(id ? 'driver_update' : 'driver_create', $('#driverFormEl').serialize(), function (res) {
        if (res.ok) { $('#driverModal').modal('hide'); flash('Driver saved.', true); load_pages('drivers.php'); }
        else flash(res.error || 'Save failed.', false);
    });
    return false;
}
function setAvail(id, val) {
    apiPost('driver_availability', { driver_id: id, availability: val }, function (res) {
        flash(res.ok ? 'Availability updated.' : res.error, res.ok);
    });
}
function toggleDriver(id, status) {
    apiPost('driver_status', { driver_id: id, status: status }, function (res) {
        if (res.ok) { flash('Status updated.', true); load_pages('drivers.php'); } else flash(res.error, false);
    });
}
function showTrips(id, name) {
    $('#tripName').text(name); $('#tripsBody').html('Loading…');
    $.getJSON('query/admin_api.php', { action: 'driver_trips', driver_id: id }, function (res) {
        if (!res.ok) { $('#tripsBody').html('<div class="text-danger">'+res.error+'</div>'); return; }
        let rows = res.trips.map(t =>
            `<tr><td>${t.scheduled_date}</td><td>${t.farmer_name||'—'}</td><td>${t.plate_number||'—'}</td>
             <td>${t.net_weight?Number(t.net_weight).toLocaleString()+' kg':'—'}</td><td>${t.status}</td></tr>`).join('');
        $('#tripsBody').html(res.trips.length
            ? `<table class="table table-sm"><thead><tr><th>Date</th><th>Farmer</th><th>Truck</th><th>Net</th><th>Status</th></tr></thead><tbody>${rows}</tbody></table>`
            : '<div class="text-muted">No trips recorded.</div>');
    });
}
</script>
