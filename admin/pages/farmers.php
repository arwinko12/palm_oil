<?php require_once __DIR__ . '/../includes/view.php';
$farmers = $admin->listFarmers();
?>
<div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <div>
        <h4 class="page-title">Farmer Accounts</h4>
        <div class="page-subtitle">Manage farmer profiles and their login status.</div>
    </div>
    <button class="btn-green" data-toggle="modal" data-target="#farmerModal" onclick="farmerForm()"><i class="fas fa-plus mr-1"></i> Add Farmer</button>
</div>

<div class="panel">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Farmer</th><th>Contact</th><th>Farm Location</th><th>Est. Tons</th><th>Distance</th><th>Hauls</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (!$farmers): ?><tr><td colspan="8" class="text-muted">No farmers yet.</td></tr><?php endif; ?>
            <?php foreach ($farmers as $f): ?>
                <tr>
                    <td><div class="table-name"><?= e($f['fullname']) ?></div><div class="table-sub"><?= e($f['email'] ?? '') ?></div></td>
                    <td><?= e($f['contact_no']) ?></td>
                    <td><?= e($f['farm_location']) ?></td>
                    <td><?= number_format((float) $f['estimated_tons'], 1) ?> t</td>
                    <td><?= number_format((float) $f['distance_to_mill'], 1) ?> km</td>
                    <td><?= (int) $f['completed_hauls'] ?></td>
                    <td><?= account_badge($f['status']) ?></td>
                    <td>
                        <button class="action-btn" title="Edit" data-toggle="modal" data-target="#farmerModal"
                            onclick='farmerForm(<?= htmlspecialchars(json_encode($f), ENT_QUOTES) ?>)'><i class="fas fa-edit"></i></button>
                        <button class="action-btn" title="Toggle status" onclick="toggleFarmer(<?= (int) $f['farmer_id'] ?>, '<?= $f['status'] === 'active' ? 'inactive' : 'active' ?>')">
                            <i class="fas fa-<?= $f['status'] === 'active' ? 'user-slash' : 'user-check' ?>"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="farmerModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" id="farmerForm" onsubmit="return saveFarmer(event)">
            <div class="modal-header"><h5 class="modal-title" id="farmerModalTitle">Add Farmer</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <input type="hidden" name="farmer_id" id="f_id">
                <div class="form-group"><label>Full name</label><input class="form-control" name="fullname" id="f_name" required></div>
                <div class="form-group" id="wrapEmail"><label>Email</label><input type="email" class="form-control" name="email" id="f_email"></div>
                <div class="form-group" id="wrapPass"><label>Initial password</label><input type="text" class="form-control" name="password" id="f_pass" minlength="6"></div>
                <div class="form-group"><label>Contact number</label><input class="form-control" name="contact_no" id="f_contact"></div>
                <div class="form-group"><label>Farm location</label><input class="form-control" name="farm_location" id="f_loc"></div>
                <div class="row">
                    <div class="col-6 form-group"><label>Estimated tons</label><input type="number" step="0.01" min="0" class="form-control" name="estimated_tons" id="f_tons"></div>
                    <div class="col-6 form-group"><label>Distance to mill (km)</label><input type="number" step="0.01" min="0" class="form-control" name="distance_to_mill" id="f_dist"></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Save</button></div>
        </form>
    </div>
</div>

<script>
function farmerForm(row) {
    $('#farmerForm')[0].reset();
    if (row) {
        $('#farmerModalTitle').text('Edit Farmer');
        $('#f_id').val(row.farmer_id); $('#f_name').val(row.fullname); $('#f_contact').val(row.contact_no);
        $('#f_loc').val(row.farm_location); $('#f_tons').val(row.estimated_tons); $('#f_dist').val(row.distance_to_mill);
        $('#wrapEmail, #wrapPass').hide();
    } else {
        $('#farmerModalTitle').text('Add Farmer'); $('#f_id').val('');
        $('#wrapEmail').show().find('input').prop('required', true);
        $('#wrapPass').show().find('input').prop('required', true);
    }
}
function saveFarmer(e) {
    e.preventDefault();
    const id = $('#f_id').val();
    const data = $('#farmerForm').serialize();
    apiPost(id ? 'farmer_update' : 'farmer_create', data, function (res) {
        if (res.ok) { $('#farmerModal').modal('hide'); flash('Farmer saved.', true); load_pages('farmers.php'); }
        else flash(res.error || 'Save failed.', false);
    });
    return false;
}
function toggleFarmer(id, status) {
    apiPost('farmer_status', { farmer_id: id, status: status }, function (res) {
        if (res.ok) { flash('Status updated.', true); load_pages('farmers.php'); } else flash(res.error, false);
    });
}
</script>
