<?php require_once __DIR__ . '/../includes/view.php';
$settings = $admin->listSettings();
?>
<div class="mb-3">
    <h4 class="page-title">System Settings</h4>
    <div class="page-subtitle">Global configuration for the hauling operation.</div>
</div>

<div class="row">
    <div class="col-12 col-xl-7 mb-3">
        <div class="panel">
            <div class="panel-header"><div><div class="panel-title">Configuration</div><div class="panel-subtitle">Edit a value and it saves automatically.</div></div></div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Setting</th><th>Value</th><th></th></tr></thead>
                    <tbody>
                    <?php if (!$settings): ?><tr><td colspan="3" class="text-muted">No settings found.</td></tr><?php endif; ?>
                    <?php foreach ($settings as $s): ?>
                        <tr>
                            <td><div class="table-name"><?= e($s['setting_key']) ?></div><div class="table-sub"><?= e($s['description'] ?: '') ?></div></td>
                            <td><input class="form-control form-control-sm" data-key="<?= e($s['setting_key']) ?>" value="<?= e($s['setting_value']) ?>"></td>
                            <td><button class="btn btn-sm btn-outline-success" onclick="saveSetting(this)"><i class="fas fa-save"></i></button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5 mb-3">
        <div class="panel">
            <div class="panel-header"><div><div class="panel-title">Add Setting</div><div class="panel-subtitle">Create a new key/value pair.</div></div></div>
            <div class="p-3">
                <form id="newSetting" onsubmit="return addSetting(event)">
                    <div class="form-group"><label>Key</label><input class="form-control" name="setting_key" required></div>
                    <div class="form-group"><label>Value</label><input class="form-control" name="setting_value" required></div>
                    <button class="btn btn-success btn-block" type="submit">Add Setting</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function saveSetting(btn) {
    const $inp = $(btn).closest('tr').find('input');
    apiPost('setting_save', { setting_key: $inp.data('key'), setting_value: $inp.val() }, function (res) {
        flash(res.ok ? 'Setting saved.' : res.error, res.ok);
    });
}
function addSetting(e) {
    e.preventDefault();
    apiPost('setting_save', $('#newSetting').serialize(), function (res) {
        if (res.ok) { flash('Setting added.', true); load_pages('settings.php'); } else flash(res.error, false);
    });
    return false;
}
</script>
