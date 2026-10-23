<?php
require_once __DIR__ . '/../includes/view.php';
require_once __DIR__ . '/../../class/Notification.php';

$notif = new Notification();
$items = $notif->forUser($userId, false, 100);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-bell-fill text-success"></i> Notifications</h4>
        <div class="text-secondary" style="font-size:12px;">Assignment and schedule-change alerts.</div>
    </div>
    <button class="btn btn-sm btn-outline-secondary" id="markAll" <?= $items ? '' : 'disabled' ?>>
        <i class="bi bi-check2-all me-1"></i> Mark all read
    </button>
</div>

<div class="panel">
    <div class="activity-list" id="notifFull">
        <?php if (empty($items)): ?>
            <div class="text-secondary" style="font-size:12px;">You have no notifications.</div>
        <?php else: foreach ($items as $n): ?>
            <div class="activity-item js-notif <?= $n['is_read'] ? '' : 'fw-bold' ?>"
                 data-id="<?= (int) $n['notification_id'] ?>" style="cursor:pointer;">
                <div class="activity-icon <?= $n['is_read'] ? '' : 'blue' ?>"><i class="bi bi-bell-fill"></i></div>
                <div class="flex-grow-1">
                    <div class="activity-text"><?= e($n['title']) ?></div>
                    <div class="activity-sub"><?= e($n['message']) ?></div>
                </div>
                <div class="activity-time"><?= e($n['created_at']) ?></div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<script>
const NF_CSRF = '<?= e($csrf) ?>';
$('#notifFull').on('click', '.js-notif', function () {
    const id = $(this).data('id');
    $.post('query/delivery_api.php', { action: 'mark_read', notification_id: id, csrf_token: NF_CSRF },
        function (res) { if (res.ok) { location.reload(); } }, 'json');
});
$('#markAll').on('click', function () {
    $.post('query/delivery_api.php', { action: 'mark_all_read', csrf_token: NF_CSRF },
        function (res) { if (res.ok) { location.reload(); } }, 'json');
});
</script>
