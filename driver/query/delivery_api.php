<?php
/**
 * Driver portal API (prompt §66-79, workflow §5 step 10, §7 driver statuses).
 *
 * Every action is scoped to the authenticated driver's OWN assignments — the
 * driver_id / user_id always come from the session, never the request — so a
 * driver can never read or act on another driver's trips. Delivery status
 * changes are delegated to Delivery::transition(), which re-enforces the
 * role + ownership + legal-transition rules on the server. Financial data is
 * intentionally absent here.
 */
require_once __DIR__ . '/../../includes/access_control.php';
require_once __DIR__ . '/../../class/Delivery.php';
require_once __DIR__ . '/../../class/Notification.php';

require_role('driver');

$action   = $_POST['action'] ?? $_GET['action'] ?? '';
$driverId = current_driver_id();
$userId   = (int) Auth::userId();

if (!$driverId) {
    json_fail('No driver profile is linked to this account.');
}

$delivery = new Delivery();

switch ($action) {
    // ---- current + previous assignments (§71, 72, 76) ----
    case 'assignments':
        $rows = $delivery->assignmentsForDriver($driverId);
        foreach ($rows as &$r) {
            $r['driver_actions'] = $delivery->driverActions($r);
        }
        unset($r);
        json_success(['assignments' => $rows]);
        break;

    // ---- monthly trip totals + availability (§77) ----
    case 'summary':
        $year  = (int) ($_GET['year']  ?: date('Y'));
        $month = (int) ($_GET['month'] ?: date('n'));
        json_success(['summary' => $delivery->summaryForDriver($driverId, $year, $month)]);
        break;

    // ---- authorized status update + departure timestamp (§74, 75) ----
    case 'update_status':
        require_post_csrf();
        $res = $delivery->transition(
            (int) ($_POST['delivery_id'] ?? 0),
            $_POST['status'] ?? '',
            $userId,
            'driver',
            $driverId
        );
        $res['ok'] ? json_success(['status' => $res['status']]) : json_fail($res['error']);
        break;

    // ---- assignment / schedule-change notifications (§73) ----
    case 'notifications':
        $notif = new Notification();
        json_success([
            'notifications' => $notif->forUser($userId),
            'unread'        => $notif->unreadCount($userId),
        ]);
        break;

    case 'mark_read':
        require_post_csrf();
        (new Notification())->markRead((int) ($_POST['notification_id'] ?? 0), $userId);
        json_success();
        break;

    case 'mark_all_read':
        require_post_csrf();
        (new Notification())->markAllRead($userId);
        json_success();
        break;

    default:
        json_fail('Unknown action.');
}
