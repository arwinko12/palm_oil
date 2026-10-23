<?php require_once __DIR__ . '/../includes/view.php';
$users = $admin->listUsers();
?>
<div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <div>
        <h4 class="page-title">User Accounts</h4>
        <div class="page-subtitle">Manage staff logins. Farmer &amp; driver accounts are created on their own pages (with linked profiles).</div>
    </div>
    <button class="btn-green" data-toggle="modal" data-target="#userModal" onclick="userForm()"><i class="fas fa-plus mr-1"></i> Add Staff Account</button>
</div>

<div class="panel">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last login</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td class="table-name"><?= e($u['full_name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="status scheduled"><?= e(ucfirst($u['role'])) ?></span></td>
                    <td><?= account_badge($u['status']) ?></td>
                    <td class="small"><?= $u['last_login'] ? fdtime($u['last_login']) : '—' ?></td>
                    <td class="text-nowrap">
                        <button class="action-btn" title="Reset password" onclick="resetPw(<?= (int) $u['user_id'] ?>)"><i class="fas fa-key"></i></button>
                        <?php if ((int) $u['user_id'] !== $userId): ?>
                            <button class="action-btn" title="Toggle status" onclick="toggleUser(<?= (int) $u['user_id'] ?>, '<?= $u['status'] === 'active' ? 'suspended' : 'active' ?>')">
                                <i class="fas fa-<?= $u['status'] === 'active' ? 'user-slash' : 'user-check' ?>"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1"><div class="modal-dialog">
    <form class="modal-content" id="userFormEl" onsubmit="return saveUser(event)">
        <div class="modal-header"><h5 class="modal-title">Add Staff Account</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body">
            <div class="form-group"><label>Full name</label><input class="form-control" name="full_name" required></div>
            <div class="form-group"><label>Email</label><input type="email" class="form-control" name="email" required></div>
            <div class="form-group"><label>Password</label><input type="text" class="form-control" name="password" minlength="6" required></div>
            <div class="row">
                <div class="col-6 form-group"><label>Phone</label><input class="form-control" name="phone"></div>
                <div class="col-6 form-group"><label>Role</label>
                    <select class="form-control" name="role"><option value="admin">Administrator</option><option value="weighbridge">Weighbridge Operator</option></select></div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Create</button></div>
    </form>
</div></div>

<script>
function userForm() { $('#userFormEl')[0].reset(); }
function saveUser(e) {
    e.preventDefault();
    apiPost('user_create', $('#userFormEl').serialize(), function (res) {
        if (res.ok) { $('#userModal').modal('hide'); flash('Account created.', true); load_pages('user_management.php'); }
        else flash(res.error || 'Save failed.', false);
    });
    return false;
}
function toggleUser(id, status) {
    apiPost('user_status', { user_id: id, status: status }, function (res) {
        if (res.ok) { flash('Status updated.', true); load_pages('user_management.php'); } else flash(res.error, false);
    });
}
function resetPw(id) {
    const pw = prompt('New password (min 6 characters):');
    if (pw === null || pw.length < 6) return;
    apiPost('user_reset_pw', { user_id: id, password: pw }, function (res) {
        flash(res.ok ? 'Password reset.' : res.error, res.ok);
    });
}
</script>
