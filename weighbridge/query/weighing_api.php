<?php
/**
 * Weighbridge operator API (prompt §81-96, workflow §5 steps 11-12, §7).
 *
 * The operator records physical arrivals, creates scale tickets (which
 * triggers the payment computation) and advances deliveries through the
 * weighbridge-authorized stages. Operators cannot manage admin accounts or
 * alter unrelated schedules — every mutation is guarded by require_role() and
 * Delivery::transition()'s server-side role/ownership rules.
 */
require_once __DIR__ . '/../../includes/access_control.php';
require_once __DIR__ . '/../../class/Delivery.php';
require_once __DIR__ . '/../../class/ScaleTicket.php';
require_once __DIR__ . '/../../class/Payment.php';

require_role('weighbridge');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userId = (int) Auth::userId();
$delivery = new Delivery();

switch ($action) {
    // ---- expected truck arrivals (§85) ----
    case 'expected':
        json_success(['deliveries' => $delivery->expectedArrivals()]);
        break;

    case 'arrivals':
        json_success(['deliveries' => $delivery->arrivalsBoard()]);
        break;

    // ---- weighing queue: arrived but not yet ticketed (§88) ----
    case 'awaiting':
        json_success(['deliveries' => $delivery->awaitingWeighing()]);
        break;

    // ---- search by schedule number, farmer, ticket number or plate (§87) ----
    case 'search':
        $term = trim($_GET['term'] ?? '');
        if ($term === '') {
            json_fail('Enter a search term.');
        }
        json_success(['deliveries' => $delivery->search($term)]);
        break;

    // ---- record actual arrival date/time (§86) ----
    case 'mark_arrived':
        require_post_csrf();
        $res = $delivery->transition((int) ($_POST['delivery_id'] ?? 0), 'arrived', $userId, 'weighbridge');
        $res['ok'] ? json_success(['status' => $res['status']]) : json_fail($res['error']);
        break;

    // ---- advance delivered / completed (§92, §7) ----
    case 'update_status':
        require_post_csrf();
        $res = $delivery->transition(
            (int) ($_POST['delivery_id'] ?? 0),
            $_POST['status'] ?? '',
            $userId,
            'weighbridge'
        );
        $res['ok'] ? json_success(['status' => $res['status']]) : json_fail($res['error']);
        break;

    // ---- create + manage scale tickets (§88-92) ----
    case 'create_ticket':
        require_post_csrf();
        $res = (new ScaleTicket())->create([
            'ticket_number' => trim($_POST['ticket_number'] ?? ''),
            'delivery_id'   => $_POST['delivery_id'] ?? 0,
            'farm_location' => $_POST['farm_location'] ?? null,
            'harvest_date'  => $_POST['harvest_date'] ?? null,
            'fruit_grade'   => $_POST['fruit_grade'] ?? null,
            'gross_weight'  => $_POST['gross_weight'] ?? -1,
            'tare_weight'   => $_POST['tare_weight'] ?? -1,
        ], $userId);
        $res['ok']
            ? json_success(['ticket_id' => $res['ticket_id'], 'net_weight' => $res['net_weight']])
            : json_fail($res['error']);
        break;

    // ---- tickets for a delivery (weighing form context + payment preview) ----
    case 'ticket':
        $deliveryId = (int) ($_GET['delivery_id'] ?? 0);
        $ticket = (new ScaleTicket())->forDelivery($deliveryId);
        if (!$ticket) {
            json_fail('No ticket for this delivery.');
        }
        json_success([
            'ticket'  => $ticket,
            'payment' => (new Payment())->forTicket($ticket['ticket_id']),
        ]);
        break;

    // ---- records list (view / print) (§93) ----
    case 'list_tickets':
        $search = trim($_GET['search'] ?? '');
        json_success(['tickets' => (new ScaleTicket())->listTickets(200, $search !== '' ? $search : null)]);
        break;

    case 'ticket_by_id':
        $bundle = (new ScaleTicket())->ticketBundle((int) ($_GET['ticket_id'] ?? 0));
        if (!$bundle) {
            json_fail('Ticket not found.');
        }
        json_success(['ticket' => $bundle]);
        break;

    default:
        json_fail('Unknown action.');
}
