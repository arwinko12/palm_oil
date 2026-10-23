<?php
/**
 * Farmer portal API (workflow §5 steps 1–3, §6 dashboard dates, farmer
 * features prompt §52-65). Every action is scoped to the authenticated
 * farmer's own records — a farmer can never read or act on another farmer's
 * data because the farmer_id/user_id always come from the session, never the
 * request.
 */
require_once __DIR__ . '/../../includes/access_control.php';
require_once __DIR__ . '/../../class/HaulingRequest.php';
require_once __DIR__ . '/../../class/HarvestCycle.php';
require_once __DIR__ . '/../../class/ScaleTicket.php';
require_once __DIR__ . '/../../class/Payment.php';
require_once __DIR__ . '/../../class/Notification.php';
require_once __DIR__ . '/../../class/FarmerProfile.php';

require_role('farmer');

$action   = $_POST['action'] ?? $_GET['action'] ?? '';
$farmerId = current_farmer_id();
$userId   = (int) Auth::userId();

if (!$farmerId) {
    json_fail('No farmer profile is linked to this account.');
}

switch ($action) {
    // ---- hauling requests (§5.1-3) ----
    case 'submit':
        require_post_csrf();
        $req = new HaulingRequest();
        $res = $req->submit($farmerId, [
            'preferred_date'         => $_POST['preferred_date'] ?? '',
            'preferred_time'         => $_POST['preferred_time'] ?? '',
            'farm_location'          => trim($_POST['farm_location'] ?? ''),
            'estimated_fresh_weight' => $_POST['estimated_fresh_weight'] ?? 0,
            'requested_truck_type'   => $_POST['requested_truck_type'] ?? '',
            'distance_to_mill'       => $_POST['distance_to_mill'] ?? 0,
            'notes'                  => $_POST['notes'] ?? null,
        ]);
        $res['ok'] ? json_success(['request_id' => $res['id']]) : json_fail($res['error']);
        break;

    // ---- request + assignment overview (own records only, §58-59) ----
    case 'list':
    case 'history':
        $req = new HaulingRequest();
        json_success([
            'requests'  => $req->listForFarmer($farmerId),
            'history'   => $req->historyForFarmer($farmerId),
            'summary'   => $req->summaryForFarmer($farmerId),
        ]);
        break;

    // ---- harvest cycle date (§60, §6) ----
    case 'next_harvest':
        $cycle = new HarvestCycle();
        json_success([
            'next_harvest_date' => $cycle->nextHarvestDate($farmerId),
            'cycle_days'        => $cycle->cycleDays(),
            'current'           => $cycle->current($farmerId),
        ]);
        break;

    // ---- scale tickets + payment breakdown (§62, 5.12-13) ----
    case 'tickets':
        json_success(['tickets' => (new ScaleTicket())->forFarmer($farmerId)]);
        break;

    case 'ticket':
        $ticketId = (int) ($_GET['ticket_id'] ?? 0);
        $tickets  = (new ScaleTicket())->forFarmer($farmerId);
        $mine     = null;
        foreach ($tickets as $t) {
            if ((int) $t['ticket_id'] === $ticketId) { $mine = $t; break; }
        }
        if (!$mine) {
            json_fail('Ticket not found.');
        }
        json_success([
            'ticket'  => $mine,
            'payment' => (new Payment())->forTicket($ticketId),
        ]);
        break;

    // ---- profile / farm management (§54-55) ----
    case 'profile':
        json_success(['profile' => (new FarmerProfile())->get($farmerId)]);
        break;

    case 'save_profile':
        require_post_csrf();
        $res = (new FarmerProfile())->update($farmerId, $userId, $_POST);
        $res['ok'] ? json_success() : json_fail($res['error']);
        break;

    // ---- reminders & scheduling updates (§61) ----
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
