<?php
/**
 * HaulingRequest — farmer request submission and admin review (workflow §5,
 * steps 1–5). A submitted request is stored as 'pending' and every active
 * administrator is notified; review sets approved/rejected and notifies the
 * farmer.
 */
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Notification.php';

class HaulingRequest extends Model
{
    const TRUCK_TYPES = ['tipper', 'flatbed', 'container', 'tractor'];

    private $notifier;

    public function __construct()
    {
        parent::__construct();
        $this->notifier = new Notification();
    }

    /**
     * Validate + create a pending request.
     * @return array ['ok'=>bool,'error'=>string|null,'id'=>int|null]
     */
    public function submit($farmerId, array $d)
    {
        $errors = $this->validate($d);
        if ($errors) {
            return ['ok' => false, 'error' => implode(' ', $errors), 'id' => null];
        }

        // Only an active farmer may submit.
        if (!$this->isFarmerActive($farmerId)) {
            return ['ok' => false, 'error' => 'Farmer account was not found or is inactive.', 'id' => null];
        }

        $sql = "INSERT INTO hauling_requests
                (farmer_id, preferred_date, preferred_time, farm_location,
                 estimated_fresh_weight, requested_truck_type, distance_to_mill, notes, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
        $this->run(
            $sql,
            "isssdsss",
            [
                $farmerId,
                $d['preferred_date'],
                $d['preferred_time'],
                $d['farm_location'],
                $d['estimated_fresh_weight'],
                $d['requested_truck_type'],
                $d['distance_to_mill'],
                $d['notes'] ?? null,
            ]
        );
        $id = $this->insertId();

        $this->notifier->notifyAdmins(
            'New hauling request',
            "Hauling request #$id is awaiting review.",
            'info',
            '/epalm_haul/admin/index.php'
        );

        return ['ok' => true, 'error' => null, 'id' => $id];
    }

    /** Admin approve / reject. */
    public function review($requestId, $decision, $adminUserId, $remarks = null)
    {
        if (!in_array($decision, ['approved', 'rejected'], true)) {
            return ['ok' => false, 'error' => 'Decision must be approved or rejected.'];
        }

        $req = $this->get($requestId);
        if (!$req) {
            return ['ok' => false, 'error' => 'Request not found.'];
        }
        if ($req['status'] !== 'pending') {
            return ['ok' => false, 'error' => 'Only pending requests can be reviewed.'];
        }

        $this->run(
            "UPDATE hauling_requests
             SET status = ?, reviewed_by = ?, review_date = NOW(), review_remarks = ?
             WHERE request_id = ?",
            "sisi",
            [$decision, $adminUserId, $remarks, $requestId]
        );

        $farmerUserId = $this->notifier->userIdForFarmer($req['farmer_id']);
        if ($farmerUserId) {
            $msg = $decision === 'approved'
                ? "Request #$requestId was approved and is ready for scheduling."
                : "Request #$requestId was rejected.";
            $this->notifier->send(
                $farmerUserId,
                'Hauling request ' . $decision,
                $msg,
                $decision === 'approved' ? 'success' : 'warning'
            );
        }

        return ['ok' => true, 'error' => null];
    }

    public function get($requestId)
    {
        return $this->one("SELECT * FROM hauling_requests WHERE request_id = ?", "i", [$requestId]);
    }

    public function listForFarmer($farmerId)
    {
        return $this->all(
            "SELECT * FROM hauling_requests WHERE farmer_id = ? ORDER BY created_at DESC",
            "i",
            [$farmerId]
        );
    }

    public function listByStatus($status = 'pending')
    {
        return $this->all(
            "SELECT r.*, f.fullname AS farmer_name
             FROM hauling_requests r
             JOIN farmers f ON f.farmer_id = r.farmer_id
             WHERE r.status = ?
             ORDER BY r.created_at DESC",
            "s",
            [$status]
        );
    }

    /**
     * Rich, own-records-only view of every request with its latest schedule,
     * assigned driver/truck, delivery progress, scale ticket and payment.
     * Powers the farmer request list, hauling records and dashboard (52-65).
     */
    public function historyForFarmer($farmerId)
    {
        return $this->all(
            "SELECT r.request_id, r.preferred_date, r.preferred_time, r.farm_location,
                    r.estimated_fresh_weight, r.requested_truck_type, r.distance_to_mill,
                    r.status AS request_status, r.review_remarks, r.created_at,
                    s.schedule_id, s.scheduled_date, s.scheduled_time,
                    dr.fullname AS driver_name, t.plate_number, t.truck_type,
                    dv.delivery_id, dv.status AS delivery_status,
                    dv.departure_time, dv.actual_arrival, dv.completion_time,
                    st.ticket_id, st.ticket_number, st.gross_weight, st.tare_weight,
                    st.net_weight, st.fruit_grade,
                    pc.gross_payment, pc.incentive_amount, pc.quality_bonus,
                    pc.total_deductions, pc.net_payable
             FROM hauling_requests r
             LEFT JOIN hauling_schedules s ON s.schedule_id = (
                    SELECT s2.schedule_id FROM hauling_schedules s2
                    WHERE s2.request_id = r.request_id AND s2.status <> 'cancelled'
                    ORDER BY s2.created_at DESC LIMIT 1)
             LEFT JOIN drivers dr ON dr.driver_id = s.driver_id
             LEFT JOIN trucks  t  ON t.truck_id  = s.truck_id
             LEFT JOIN deliveries dv ON dv.schedule_id = s.schedule_id
             LEFT JOIN scale_tickets st ON st.delivery_id = dv.delivery_id
             LEFT JOIN payment_computations pc ON pc.ticket_id = st.ticket_id
             WHERE r.farmer_id = ?
             ORDER BY r.created_at DESC",
            "i",
            [$farmerId]
        );
    }

    /**
     * Dashboard counters for a single farmer: request counts by status,
     * completed hauls, total hauled tonnage and lifetime net payable.
     */
    public function summaryForFarmer($farmerId)
    {
        $counts = $this->one(
            "SELECT
                SUM(status = 'pending')   AS pending,
                SUM(status = 'approved')  AS approved,
                SUM(status = 'rejected')  AS rejected,
                SUM(status = 'scheduled') AS scheduled,
                SUM(status = 'cancelled') AS cancelled,
                COUNT(*)                  AS total
             FROM hauling_requests WHERE farmer_id = ?",
            "i",
            [$farmerId]
        );

        $delivered = $this->one(
            "SELECT COUNT(*) AS completed, COALESCE(SUM(st.net_weight), 0) AS kg
             FROM deliveries dv
             JOIN hauling_schedules s ON s.schedule_id = dv.schedule_id
             LEFT JOIN scale_tickets st ON st.delivery_id = dv.delivery_id
             WHERE s.farmer_id = ? AND dv.status = 'completed'",
            "i",
            [$farmerId]
        );

        $payable = $this->scalar(
            "SELECT COALESCE(SUM(pc.net_payable), 0)
             FROM payment_computations pc WHERE pc.farmer_id = ?",
            "i",
            [$farmerId]
        );

        return [
            'pending'        => (int) ($counts['pending'] ?? 0),
            'approved'       => (int) ($counts['approved'] ?? 0),
            'rejected'       => (int) ($counts['rejected'] ?? 0),
            'scheduled'      => (int) ($counts['scheduled'] ?? 0),
            'cancelled'      => (int) ($counts['cancelled'] ?? 0),
            'total_requests' => (int) ($counts['total'] ?? 0),
            'completed'      => (int) ($delivered['completed'] ?? 0),
            'total_kg'       => (float) ($delivered['kg'] ?? 0),
            'net_payable'    => (float) ($payable ?? 0),
        ];
    }

    private function isFarmerActive($farmerId)
    {
        return (bool) $this->scalar(
            "SELECT COUNT(*) FROM farmers WHERE farmer_id = ? AND status = 'active'",
            "i",
            [$farmerId]
        );
    }

    private function validate(array $d)
    {
        $errors = [];

        if (empty($d['preferred_date']) || strtotime($d['preferred_date']) === false) {
            $errors[] = 'A valid preferred date is required.';
        } elseif (strtotime($d['preferred_date']) < strtotime(date('Y-m-d'))) {
            $errors[] = 'Preferred date cannot be in the past.';
        }

        if (empty($d['preferred_time'])) {
            $errors[] = 'A preferred time is required.';
        }

        if (empty($d['farm_location'])) {
            $errors[] = 'Farm location is required.';
        }

        if (!isset($d['estimated_fresh_weight']) || !is_numeric($d['estimated_fresh_weight'])
            || $d['estimated_fresh_weight'] <= 0) {
            $errors[] = 'Estimated FFB weight must be greater than zero.';
        }

        if (!in_array($d['requested_truck_type'] ?? '', self::TRUCK_TYPES, true)) {
            $errors[] = 'Requested truck type is not valid.';
        }

        return $errors;
    }
}
