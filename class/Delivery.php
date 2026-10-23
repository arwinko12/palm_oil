<?php
/**
 * Delivery — validated delivery-status workflow (workflow §7).
 *
 * Permitted transitions and the roles allowed to perform each are enforced
 * HERE (backend), not only in the UI. Each change records the acting user,
 * a timestamp (updated_at) and an activity_logs entry. Cancelled deliveries
 * are retained for history — never deleted.
 */
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Notification.php';
require_once __DIR__ . '/HarvestCycle.php';

class Delivery extends Model
{
    const STATUSES = ['scheduled', 'in_transit', 'arrived', 'delivered', 'completed', 'cancelled'];

    /** Allowed next statuses from each current status. */
    private const TRANSITIONS = [
        'scheduled'  => ['in_transit', 'cancelled'],
        'in_transit' => ['arrived', 'cancelled'],
        'arrived'    => ['delivered', 'cancelled'],
        'delivered'  => ['completed', 'cancelled'],
        'completed'  => [],
        'cancelled'  => [],
    ];

    /** Roles permitted to move a delivery INTO each status. */
    private const ROLE_ALLOWED = [
        'in_transit' => ['driver'],
        'arrived'    => ['weighbridge', 'admin'],
        'delivered'  => ['weighbridge', 'admin'],
        'completed'  => ['weighbridge', 'admin'],
        'cancelled'  => ['admin'],
    ];

    private $notifier;

    public function __construct()
    {
        parent::__construct();
        $this->notifier = new Notification();
    }

    public static function allowedFrom($from)
    {
        return self::TRANSITIONS[$from] ?? [];
    }

    public function get($deliveryId)
    {
        return $this->one("SELECT * FROM deliveries WHERE delivery_id = ?", "i", [$deliveryId]);
    }

    /**
     * Transition a delivery to a new status.
     * @param int    $deliveryId
     * @param string $to          target status
     * @param int    $actorUserId session user performing the change
     * @param string $actorRole   session role
     * @param int|null $actorDriverId driver_id when the actor is a driver (ownership check)
     * @return array ['ok'=>bool,'error'=>?,'status'=>?]
     */
    public function transition($deliveryId, $to, $actorUserId, $actorRole, $actorDriverId = null)
    {
        $delivery = $this->get($deliveryId);
        if (!$delivery) {
            return $this->err('Delivery not found.');
        }

        $from = $delivery['status'];

        // 1. Legal transition?
        if (!in_array($to, self::allowedFrom($from), true)) {
            return $this->err("Illegal transition: $from -> $to.");
        }

        // 2. Role authorized for the target status?
        if (!in_array($actorRole, self::ROLE_ALLOWED[$to] ?? [], true)) {
            return $this->err("Role '$actorRole' is not allowed to set status '$to'.");
        }

        // 3. Ownership: a driver may only touch their own delivery.
        if ($actorRole === 'driver') {
            if ($actorDriverId === null || (int) $delivery['driver_id'] !== (int) $actorDriverId) {
                return $this->err('You may only update your own assignments.');
            }
        }

        // 4. Completion requires a scale ticket.
        if ($to === 'completed') {
            $hasTicket = (int) $this->scalar(
                "SELECT COUNT(*) FROM scale_tickets WHERE delivery_id = ?",
                "i",
                [$deliveryId]
            );
            if ($hasTicket === 0) {
                return $this->err('Cannot complete: no scale ticket recorded for this delivery.');
            }
        }

        $this->conn->begin_transaction();
        try {
            $this->applySideEffects($delivery, $to, $actorUserId);
            $this->mirrorScheduleStatus($delivery['schedule_id'], $to);
            $this->logActivity($actorUserId, $from, $to, $deliveryId);
            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollback();
            return $this->err('Update failed: ' . $e->getMessage());
        }

        // Post-commit notifications / harvest confirmation.
        if ($to === 'completed') {
            $this->confirmHarvest($delivery, $actorUserId);
        }

        return ['ok' => true, 'error' => null, 'status' => $to];
    }

    /** Set timestamps and free/allocate resources for the target status. */
    private function applySideEffects(array $delivery, $to, $actorUserId)
    {
        $deliveryId = $delivery['delivery_id'];

        switch ($to) {
            case 'in_transit':
                $this->run(
                    "UPDATE deliveries SET status='in_transit', departure_time=NOW(), updated_at=NOW()
                     WHERE delivery_id=?",
                    "i",
                    [$deliveryId]
                );
                $this->setDriverAvailability($delivery['driver_id'], 'on_trip');
                $this->setTruckStatus($delivery['truck_id'], 'in_use');
                break;

            case 'arrived':
                $this->run(
                    "UPDATE deliveries SET status='arrived', actual_arrival=NOW(), updated_at=NOW()
                     WHERE delivery_id=?",
                    "i",
                    [$deliveryId]
                );
                break;

            case 'delivered':
                $this->run(
                    "UPDATE deliveries SET status='delivered', updated_at=NOW() WHERE delivery_id=?",
                    "i",
                    [$deliveryId]
                );
                break;

            case 'completed':
                $this->run(
                    "UPDATE deliveries SET status='completed', completion_time=NOW(), updated_at=NOW()
                     WHERE delivery_id=?",
                    "i",
                    [$deliveryId]
                );
                // Release resources for reuse.
                $this->setDriverAvailability($delivery['driver_id'], 'available');
                $this->setTruckStatus($delivery['truck_id'], 'available');
                break;

            case 'cancelled':
                $this->run(
                    "UPDATE deliveries SET status='cancelled', updated_at=NOW() WHERE delivery_id=?",
                    "i",
                    [$deliveryId]
                );
                // Retain record; just free the resources.
                $this->setDriverAvailability($delivery['driver_id'], 'available');
                $this->setTruckStatus($delivery['truck_id'], 'available');
                break;
        }
    }

    private function mirrorScheduleStatus($scheduleId, $to)
    {
        $this->run(
            "UPDATE hauling_schedules SET status = ?, updated_at = NOW() WHERE schedule_id = ?",
            "si",
            [$to, $scheduleId]
        );
    }

    private function confirmHarvest(array $delivery, $actorUserId)
    {
        $schedule = $this->one("SELECT farmer_id FROM hauling_schedules WHERE schedule_id = ?", "i", [$delivery['schedule_id']]);
        if (!$schedule) {
            return;
        }
        $farmerId = $schedule['farmer_id'];

        // Prefer the harvest date recorded on the scale ticket.
        $harvestDate = $this->scalar(
            "SELECT harvest_date FROM scale_tickets WHERE delivery_id = ? ORDER BY ticket_id DESC LIMIT 1",
            "i",
            [$delivery['delivery_id']]
        );
        if (!$harvestDate) {
            $harvestDate = date('Y-m-d');
        }

        $cycle = new HarvestCycle();
        $cycle->confirm($farmerId, $harvestDate, $actorUserId);

        $farmerUserId = $this->notifier->userIdForFarmer($farmerId);
        if ($farmerUserId) {
            $this->notifier->send(
                $farmerUserId,
                'Delivery completed',
                "Delivery #{$delivery['delivery_id']} is complete. View your scale ticket and payment.",
                'success'
            );
        }
    }

    private function setDriverAvailability($driverId, $value)
    {
        if ($driverId) {
            $this->run("UPDATE drivers SET availability = ? WHERE driver_id = ?", "si", [$value, $driverId]);
        }
    }

    private function setTruckStatus($truckId, $value)
    {
        if ($truckId) {
            $this->run("UPDATE trucks SET status = ? WHERE truck_id = ?", "si", [$value, $truckId]);
        }
    }

    private function logActivity($userId, $from, $to, $deliveryId)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $this->run(
            "INSERT INTO activity_logs (user_id, action, table_name, record_id, details, ip_address)
             VALUES (?, 'delivery_status', 'deliveries', ?, ?, ?)",
            "isss",
            [$userId, (string) $deliveryId, "$from -> $to", $ip]
        );
    }

    /** Deliveries assigned to a driver (current + past). */
    public function forDriver($driverId)
    {
        return $this->all(
            "SELECT d.*, s.scheduled_date, s.scheduled_time, s.request_id, s.farmer_id,
                    f.fullname AS farmer_name, f.contact_no, f.farm_location,
                    t.plate_number, t.truck_type
             FROM deliveries d
             JOIN hauling_schedules s ON s.schedule_id = d.schedule_id
             LEFT JOIN farmers f ON f.farmer_id = s.farmer_id
             LEFT JOIN trucks t ON t.truck_id = d.truck_id
             WHERE d.driver_id = ?
             ORDER BY s.scheduled_date DESC, s.scheduled_time DESC",
            "i",
            [$driverId]
        );
    }

    /**
     * Full assignment view for a driver (§66-79): schedule + farmer contact +
     * farm location + estimated load + truck details + delivery instructions.
     * Deliberately excludes financial columns — a driver must never see
     * payment computation data. Scoped to the one driver, always.
     */
    public function assignmentsForDriver($driverId)
    {
        return $this->all(
            "SELECT d.delivery_id, d.status AS delivery_status,
                    d.departure_time, d.estimated_arrival, d.actual_arrival,
                    d.completion_time, d.notes AS delivery_notes,
                    s.schedule_id, s.scheduled_date, s.scheduled_time,
                    s.status AS schedule_status,
                    r.request_id, r.farm_location, r.estimated_fresh_weight,
                    r.distance_to_mill, r.requested_truck_type, r.notes AS request_notes,
                    f.fullname AS farmer_name, f.contact_no,
                    t.plate_number, t.truck_type, t.carrying_capacity
             FROM deliveries d
             JOIN hauling_schedules s ON s.schedule_id = d.schedule_id
             JOIN hauling_requests  r ON r.request_id  = s.request_id
             LEFT JOIN farmers f ON f.farmer_id = s.farmer_id
             LEFT JOIN trucks t ON t.truck_id   = d.truck_id
             WHERE d.driver_id = ?
             ORDER BY s.scheduled_date DESC, s.scheduled_time DESC",
            "i",
            [$driverId]
        );
    }

    /** The status transitions this driver may perform on one delivery. */
    public function driverActions(array $delivery)
    {
        $from = $delivery['delivery_status'] ?? $delivery['status'] ?? '';
        return array_values(array_filter(
            self::allowedFrom($from),
            function ($to) {
                return in_array('driver', self::ROLE_ALLOWED[$to] ?? [], true);
            }
        ));
    }

    /**
     * Monthly trip totals + lifetime completed count + current availability.
     * @param int      $driverId
     * @param int|null $year  defaults to current year
     * @param int|null $month 1-12, defaults to current month
     */
    public function summaryForDriver($driverId, $year = null, $month = null)
    {
        $year  = $year  ?: (int) date('Y');
        $month = $month ?: (int) date('n');

        $row = $this->one(
            "SELECT COUNT(*) AS month_total,
                    SUM(d.status = 'completed')                        AS month_completed,
                    SUM(d.status IN ('scheduled','in_transit'))        AS month_active,
                    SUM(d.status = 'cancelled')                        AS month_cancelled
             FROM deliveries d
             JOIN hauling_schedules s ON s.schedule_id = d.schedule_id
             WHERE d.driver_id = ? AND YEAR(s.scheduled_date) = ? AND MONTH(s.scheduled_date) = ?",
            "iii",
            [$driverId, $year, $month]
        );

        $lifetime = (int) $this->scalar(
            "SELECT COUNT(*) FROM deliveries WHERE driver_id = ? AND status = 'completed'",
            "i",
            [$driverId]
        );

        return [
            'year'               => $year,
            'month'              => $month,
            'month_total'        => (int) ($row['month_total'] ?? 0),
            'month_completed'    => (int) ($row['month_completed'] ?? 0),
            'month_active'       => (int) ($row['month_active'] ?? 0),
            'month_cancelled'    => (int) ($row['month_cancelled'] ?? 0),
            'lifetime_completed' => $lifetime,
            'availability'       => $this->scalar("SELECT availability FROM drivers WHERE driver_id = ?", "i", [$driverId]),
        ];
    }

    /** Deliveries expected at the weighbridge (arriving / in transit). */
    public function expectedArrivals()
    {
        return $this->all(
            "SELECT d.*, s.scheduled_date, s.farmer_id, f.fullname AS farmer_name,
                    t.plate_number, t.truck_type
             FROM deliveries d
             JOIN hauling_schedules s ON s.schedule_id = d.schedule_id
             LEFT JOIN farmers f ON f.farmer_id = s.farmer_id
             LEFT JOIN trucks t ON t.truck_id = d.truck_id
             WHERE d.status IN ('scheduled','in_transit')
             ORDER BY s.scheduled_date ASC"
        );
    }

    /**
     * Arrivals board for the weighbridge (§85): every delivery still on the
     * road — scheduled, in transit, arrived or delivered — with the farmer,
     * plate, load and whether a scale ticket already exists.
     */
    public function arrivalsBoard()
    {
        return $this->all(
            "SELECT d.delivery_id, d.status, d.departure_time, d.actual_arrival,
                    s.schedule_id, s.scheduled_date, s.farmer_id,
                    f.fullname AS farmer_name, f.contact_no,
                    hr.farm_location, hr.estimated_fresh_weight,
                    t.plate_number, t.truck_type,
                    (SELECT COUNT(*) FROM scale_tickets st WHERE st.delivery_id = d.delivery_id) AS has_ticket
             FROM deliveries d
             JOIN hauling_schedules s ON s.schedule_id = d.schedule_id
             LEFT JOIN hauling_requests hr ON hr.request_id = s.request_id
             LEFT JOIN farmers f ON f.farmer_id = s.farmer_id
             LEFT JOIN trucks t ON t.truck_id = d.truck_id
             WHERE d.status IN ('scheduled','in_transit','arrived','delivered')
             ORDER BY s.scheduled_date ASC, s.scheduled_time ASC"
        );
    }

    /**
     * Deliveries that have physically arrived but have no scale ticket yet
     * (§88) — the operator's weighing queue.
     */
    public function awaitingWeighing()
    {
        return $this->all(
            "SELECT d.delivery_id, d.status, d.actual_arrival,
                    s.schedule_id, s.scheduled_date, s.farmer_id,
                    f.fullname AS farmer_name, f.contact_no,
                    hr.farm_location, hr.estimated_fresh_weight,
                    t.plate_number, t.truck_type
             FROM deliveries d
             JOIN hauling_schedules s ON s.schedule_id = d.schedule_id
             LEFT JOIN hauling_requests hr ON hr.request_id = s.request_id
             LEFT JOIN farmers f ON f.farmer_id = s.farmer_id
             LEFT JOIN trucks t ON t.truck_id = d.truck_id
             WHERE d.status IN ('arrived','delivered')
               AND NOT EXISTS (SELECT 1 FROM scale_tickets st WHERE st.delivery_id = d.delivery_id)
             ORDER BY d.actual_arrival ASC"
        );
    }

    /**
     * One delivery with everything the weighbridge needs to record a ticket:
     * farmer, plate/type, farm location, expected load, current status and the
     * permitted weighbridge transitions.
     */
    public function weighbridgeView($deliveryId)
    {
        $row = $this->one(
            "SELECT d.delivery_id, d.status, d.actual_arrival,
                    s.schedule_id, s.scheduled_date, s.farmer_id,
                    f.fullname AS farmer_name,
                    hr.farm_location, hr.estimated_fresh_weight,
                    t.plate_number, t.truck_type
             FROM deliveries d
             JOIN hauling_schedules s ON s.schedule_id = d.schedule_id
             LEFT JOIN hauling_requests hr ON hr.request_id = s.request_id
             LEFT JOIN farmers f ON f.farmer_id = s.farmer_id
             LEFT JOIN trucks t ON t.truck_id = d.truck_id
             WHERE d.delivery_id = ?",
            "i",
            [$deliveryId]
        );
        if ($row) {
            $row['weighbridge_actions'] = array_values(array_filter(
                self::allowedFrom($row['status']),
                function ($to) {
                    return in_array('weighbridge', self::ROLE_ALLOWED[$to] ?? [], true);
                }
            ));
        }
        return $row;
    }

    /** Search by schedule id, plate number, ticket number, or farmer name. */
    public function search($term)
    {
        $like = '%' . $term . '%';
        return $this->all(
            "SELECT d.*, s.schedule_id, s.scheduled_date, f.fullname AS farmer_name, t.plate_number, st.ticket_number
             FROM deliveries d
             JOIN hauling_schedules s ON s.schedule_id = d.schedule_id
             LEFT JOIN farmers f ON f.farmer_id = s.farmer_id
             LEFT JOIN trucks t ON t.truck_id = d.truck_id
             LEFT JOIN scale_tickets st ON st.delivery_id = d.delivery_id
             WHERE s.schedule_id LIKE ? OR t.plate_number LIKE ? OR st.ticket_number LIKE ? OR f.fullname LIKE ?
             ORDER BY s.scheduled_date DESC
             LIMIT 50",
            "ssss",
            [$like, $like, $like, $like]
        );
    }

    private function err($message)
    {
        return ['ok' => false, 'error' => $message, 'status' => null];
    }
}
