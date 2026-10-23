<?php require_once __DIR__ . '/../includes/view.php';
$notif = new Notification();
$items = $notif->forUser($userId, false, 100);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="page-title">Notifications</h4>
        <div class="page-subtitle">System alerts and operational updates for administrators.</div>
    </div>
    <?php if ($items): ?><button class="btn btn-sm btn-outline-secondary" onclick="markAll()"><i class="far fa-check-circle mr-1"></i> Mark all read</button><?php endif; ?>
</div>

<div class="panel">
    <?php if (!$items): ?>
        <div class="empty-state"><i class="fas fa-bell d-block"></i>No notifications.</div>
    <?php else: ?>
        <?php foreach ($items as $n): $unread = !$n['is_read']; ?>
            <div class="d-flex justify-content-between align-items-start border-bottom p-3" style="<?= $unread ? 'background:#f6fbf7;' : '' ?>">
                <div>
                    <div class="font-weight-bold"><?= e($n['title']) ?> <?= $unread ? '<span class="badge badge-success">New</span>' : '' ?></div>
                    <div class="text-muted small"><?= e($n['message']) ?></div>
                    <div class="text-muted" style="font-size:11px;"><?= fdtime($n['created_at']) ?></div>
                </div>
                <span class="status <?= $n['type'] === 'success' ? 'completed' : ($n['type'] === 'warning' || $n['type'] === 'error' ? 'pending' : 'scheduled') ?>"><?= e(ucfirst($n['type'])) ?></span>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function markAll() {
    apiPost('mark_all_read', {}, function (res) { if (res.ok) { flash('All marked read.', true); load_pages('notifications.php'); } });
}
</script>
