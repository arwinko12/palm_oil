<?php
/**
 * ScaleTicket — weighbridge recording of gross / tare / net weight per
 * delivery. Net weight is always recomputed server-side (gross - tare).
 * After a ticket is saved, the payment breakdown is computed and the farmer
 * is notified that results are available.
 */
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Notification.php';
require_once __DIR__ . '/Payment.php';

class ScaleTicket extends Model
{
    private $notifier;
    private $payment;

    public function __construct()
    {
        parent::__construct();
        $this->notifier = new Notification();
        $this->payment  = new Payment();
    }

    /**
     * @return array ['ok'=>bool,'error'=>?,'ticket_id'=>?,'net_weight'=>?]
     */
    public function create(array $d, $operatorUserId)
    {
        $deliveryId = (int) ($d['delivery_id'] ?? 0);
        $gross = (float) ($d['gross_weight'] ?? -1);
        $tare  = (float) ($d['tare_weight'] ?? -1);
        $net   = $gross - $tare;

        if ($deliveryId <= 0) {
            return ['ok' => false, 'error' => 'A valid delivery is required.', 'ticket_id' => null, 'net_weight' => null];
        }
        if ($gross <= 0 || $tare < 0) {
            return ['ok' => false, 'error' => 'Gross weight must be positive and tare cannot be negative.', 'ticket_id' => null, 'net_weight' => null];
        }
        if ($net <= 0) {
            return ['ok' => false, 'error' => 'Net weight must be positive (gross must exceed tare).', 'ticket_id' => null, 'net_weight' => null];
        }
        if (empty($d['ticket_number'])) {
            return ['ok' => false, 'error' => 'Ticket number is required.', 'ticket_id' => null, 'net_weight' => null];
        }

        // Resolve farmer + truck + farm from the delivery's schedule.
        $ctx = $this->one(
            "SELECT s.farmer_id, dv.truck_id, s.request_id, hr.farm_location, hr.preferred_date
             FROM deliveries dv
             JOIN hauling_schedules s ON s.schedule_id = dv.schedule_id
             LEFT JOIN hauling_requests hr ON hr.request_id = s.request_id
             WHERE dv.delivery_id = ?",
            "i",
            [$deliveryId]
        );
        if (!$ctx) {
            return ['ok' => false, 'error' => 'Delivery not found.', 'ticket_id' => null, 'net_weight' => null];
        }

        // One ticket per delivery (matches the schema UNIQUE key).
        if ((int) $this->scalar("SELECT COUNT(*) FROM scale_tickets WHERE delivery_id = ?", "i", [$deliveryId])) {
            return ['ok' => false, 'error' => 'A scale ticket already exists for this delivery.', 'ticket_id' => null, 'net_weight' => null];
        }

        $harvestDate = !empty($d['harvest_date']) ? $d['harvest_date'] : ($ctx['preferred_date'] ?? date('Y-m-d'));
        $farmLocation = $d['farm_location'] ?? $ctx['farm_location'];

        $this->run(
            "INSERT INTO scale_tickets
             (ticket_number, delivery_id, farmer_id, truck_id, farm_location, harvest_date,
              fruit_grade, gross_weight, tare_weight, net_weight, weighed_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "siiisssdddi",
            [
                $d['ticket_number'], $deliveryId, $ctx['farmer_id'], $ctx['truck_id'],
                $farmLocation, $harvestDate, $d['fruit_grade'] ?? null,
                $gross, $tare, $net, $operatorUserId,
            ]
        );
        $ticketId = $this->insertId();

        // Compute the payment breakdown (§5 step 12).
        $this->payment->computeForTicket($ticketId, $operatorUserId);

        // Notify the farmer results are available.
        $farmerUserId = $this->notifier->userIdForFarmer($ctx['farmer_id']);
        if ($farmerUserId) {
            $this->notifier->send(
                $farmerUserId,
                'Scale ticket ready',
                "Ticket {$d['ticket_number']} recorded. Net weight: $net kg.",
                'success'
            );
        }

        return ['ok' => true, 'error' => null, 'ticket_id' => $ticketId, 'net_weight' => $net];
    }

    /**
     * All recorded tickets (newest first) with farmer, truck and payment
     * columns joined — the weighbridge records/print list (§89, 92, 93).
     * Optional $search filters by ticket number, farmer name or plate.
     */
    public function listTickets($limit = 200, $search = null)
    {
        $sql = "SELECT st.*, f.fullname AS farmer_name, t.plate_number, t.truck_type,
                       pc.gross_payment, pc.incentive_amount, pc.quality_bonus,
                       pc.total_deductions, pc.net_payable,
                       dv.status AS delivery_status
                FROM scale_tickets st
                LEFT JOIN farmers f ON f.farmer_id = st.farmer_id
                LEFT JOIN trucks t ON t.truck_id = st.truck_id
                LEFT JOIN deliveries dv ON dv.delivery_id = st.delivery_id
                LEFT JOIN payment_computations pc ON pc.ticket_id = st.ticket_id";
        $types  = '';
        $params = [];
        if ($search !== null && $search !== '') {
            $like   = '%' . $search . '%';
            $sql   .= " WHERE st.ticket_number LIKE ? OR f.fullname LIKE ? OR t.plate_number LIKE ?";
            $types  = 'sss';
            $params = [$like, $like, $like];
        }
        $sql .= " ORDER BY st.created_at DESC LIMIT " . (int) $limit;
        return $this->all($sql, $types, $params);
    }

    /** Full ticket + payment + names for the view/print screen (§93). */
    public function ticketBundle($ticketId)
    {
        $ticket = $this->one(
            "SELECT st.*, f.fullname AS farmer_name, f.contact_no, t.plate_number, t.truck_type,
                    t.carrying_capacity, u.full_name AS weighed_by_name
             FROM scale_tickets st
             LEFT JOIN farmers f ON f.farmer_id = st.farmer_id
             LEFT JOIN trucks t ON t.truck_id = st.truck_id
             LEFT JOIN users u ON u.user_id = st.weighed_by
             WHERE st.ticket_id = ?",
            "i",
            [$ticketId]
        );
        if (!$ticket) {
            return null;
        }
        $ticket['payment'] = $this->payment->forTicket($ticketId);
        return $ticket;
    }

    public function get($ticketId)
    {
        return $this->one("SELECT * FROM scale_tickets WHERE ticket_id = ?", "i", [$ticketId]);
    }

    public function forDelivery($deliveryId)
    {
        return $this->one("SELECT * FROM scale_tickets WHERE delivery_id = ?", "i", [$deliveryId]);
    }

    /** Full ticket + payment view for a farmer (own records only). */
    public function forFarmer($farmerId)
    {
        return $this->all(
            "SELECT st.*, pc.net_payable, pc.gross_payment, pc.total_deductions
             FROM scale_tickets st
             LEFT JOIN payment_computations pc ON pc.ticket_id = st.ticket_id
             WHERE st.farmer_id = ?
             ORDER BY st.created_at DESC",
            "i",
            [$farmerId]
        );
    }
}
