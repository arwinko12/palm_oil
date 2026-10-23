<?php require_once __DIR__ . '/../includes/view.php';
$ACTION = $_GET['action_filter'] ?? '';
$logs = $admin->activityLogs(200, $ACTION !== '' ? $ACTION : null);
$ACTIONS = ['', 'login', 'logout', 'create', 'update', 'status', 'availability', 'cancel', 'delivery_status', 'reset_password'];
?>
<div class="mb-3">
    <h4 class="page-title">System Activity Logs</h4>
    <div class="page-subtitle">An audit trail of sign-ins and every record change made across the platform.</div>
</div>

<div class="mb-3">
    <?php foreach ($ACTIONS as $a): ?>
        <button class="btn btn-sm mb-1 <?= $a === $ACTION ? 'btn-success' : 'btn-outline-secondary' ?> page_click"
                data-page="activity_logs.php<?= $a ? '?action_filter=' . $a : '' ?>"><?= $a === '' ? 'All' : str_replace('_', ' ', ucfirst($a)) ?></button>
    <?php endforeach; ?>
</div>

<div class="panel">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>When</th><th>User</th><th>Action</th><th>Table</th><th>Record</th><th>Details</th><th>IP</th></tr></thead>
            <tbody>
            <?php if (!$logs): ?><tr><td colspan="7" class="text-muted">No activity recorded.</td></tr><?php endif; ?>
            <?php foreach ($logs as $l): ?>
                <tr>
                    <td><?= fdtime($l['created_at']) ?></td>
                    <td><?= e($l['user_name'] ?: 'System') ?></td>
                    <td><span class="status scheduled"><?= e(str_replace('_',' ',$l['action'])) ?></span></td>
                    <td><?= e($l['table_name'] ?: '—') ?></td>
                    <td><?= e($l['record_id'] ?: '—') ?></td>
                    <td class="small"><?= e($l['details']) ?></td>
                    <td class="small text-muted"><?= e($l['ip_address'] ?: '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
