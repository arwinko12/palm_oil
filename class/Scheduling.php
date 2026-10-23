<?php
/**
 * Scheduling — turns an approved request into a hauling schedule + delivery,
 * assigning a compatible, available driver and truck (workflow §5 steps 6–9).
 *
 * Enforces the §6 rules: driver availability, truck availability, truck
 * capacity vs. estimated load, truck-type compatibility, and overlap /
 * conflict detection. It never silently overwrites an existing schedule.
 */
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Notification.php';

class Scheduling extends Model
{
    private $notifier;

    public function __construct()
    {
        parent::__construct();
        $this->notifier = new Notification();
    }

    /** Drivers that are active, available and free for the slot. */
    public function availableDrivers($date, $time)
    {
        return $this->all(
            "SELECT d.driver_id, d.fullname, d.contact_no, d.availability
             FROM drivers d
             WHERE d.status = 'active'
               AND d.availability = 'available'
               AND d.driver_id NOT IN (
                   SELECT s.driver_id FROM hauling_schedules s
                   WHERE s.driver_id IS NOT NULL
                     AND s.status NOT IN ('cancelled','completed')
                     AND s.scheduled_date = ? AND s.scheduled_time = ?)
             ORDER BY d.fullname",
            "ss",
            [$date, $time]
        );
    }

    /**
     * Trucks that are available, of the requested type, with enough capacity,
     * and free for the slot.
     */
    public function availableTrucks($date, $time, $truckType = null, $minCapacityTons = 0)
    {
        $sql = "SELECT t.truck_id, t.plate_number, t.truck_type, t.carrying_capacity, t.status
                FROM trucks t
                WHERE t.status = 'available'
                  AND t.carrying_capacity >= ?";
        $types = "d";
        $params = [$minCapacityTons];

        if ($truckType !== null) {
            $sql .= " AND t.truck_type = ?";
            $types .= "s";
            $params[] = $truckType;
        }

        $sql .= " AND t.truck_id NOT IN (
                     SELECT s.truck_id FROM hauling_schedules s
                     WHERE s.truck_id IS NOT NULL
                       AND s.status NOT IN ('cancelled','completed')
                       AND s.scheduled_date = ? AND s.scheduled_time = ?)
                  ORDER BY t.carrying_capacity ASC";
        $types .= "ss";
        $params[] = $date;
        $params[] = $time;

        return $this->all($sql, $types, $params);
    }

    /**
     * Return a list of human-readable conflict warnings (empty = safe).
     */
    public function detectConflicts($driverId, $truckId, $date, $time)
    {
        $warnings = [];

        $clash = $this->all(
            "SELECT driver_id, truck_id, schedule_id
             FROM hauling_schedules
             WHERE status NOT IN ('cancelled','completed')
               AND scheduled_date = ? AND scheduled_time = ?
               AND (driver_id = ? OR truck_id = ?)",
            "ssii",
            [$date, $time, $driverId, $truckId]
        );
        foreach ($clash as $c) {
            if ((int) $c['driver_id'] === (int) $driverId) {
                $warnings[] = "Driver #$driverId already has schedule #{$c['schedule_id']} at this date and time.";
            }
            if ((int) $c['truck_id'] === (int) $truckId) {
                $warnings[] = "Truck #$truckId already has schedule #{$c['schedule_id']} at this date and time.";
            }
        }
        return $warnings;
    }

    /**
     * Validate resource compatibility + conflicts, then create the schedule
     * and its delivery in one transaction.
     *
     * @return array ['ok'=>bool,'error'=>?,'warnings'=>[],'schedule_id'=>?, 'delivery_id'=>?]
     */
    public function assignAndSchedule($requestId, $driverId, $truckId, $date, $time, $adminUserId)
    {
        $result = ['ok' => false, 'error' => null, 'warnings' => [], 'schedule_id' => null, 'delivery_id' => null];

        $req = $this->one("SELECT * FROM hauling_requests WHERE request_id = ?", "i", [$requestId]);
        if (!$req) {
            $result['error'] = 'Request not found.';
            return $result;
        }
        if (!in_array($req['status'], ['approved', 'pending'], true)) {
            $result['error'] = 'Only an approved (or pending) request can be scheduled.';
            return $result;
        }

        // Reject if this request already has an active schedule — never overwrite.
        $existing = $this->one(
            "SELECT schedule_id FROM hauling_schedules
             WHERE request_id = ? AND status NOT IN ('cancelled')",
            "i",
            [$requestId]
        );
        if ($existing) {
            $result['error'] = "This request already has schedule #{$existing['schedule_id']}. Cancel it before rescheduling.";
            return $result;
        }

        // Resource existence + status.
        $driver = $this->one("SELECT * FROM drivers WHERE driver_id = ?", "i", [$driverId]);
        $truck  = $this->one("SELECT * FROM trucks WHERE truck_id = ?", "i", [$truckId]);
        if (!$driver || $driver['status'] !== 'active') {
            $result['error'] = 'Selected driver is not available.';
            return $result;
        }
        if (!$truck || $truck['status'] !== 'available') {
            $result['error'] = 'Selected truck is not available.';
            return $result;
        }

        // Capacity check (tons).
        if ((float) $truck['carrying_capacity'] < (float) $req['estimated_fresh_weight']) {
            $result['error'] = sprintf(
                'Capacity violation: truck carries %s t but load is %s t.',
                $truck['carrying_capacity'],
                $req['estimated_fresh_weight']
            );
            return $result;
        }

        // Truck-type compatibility.
        if ($truck['truck_type'] !== $req['requested_truck_type']) {
            $result['error'] = sprintf(
                'Incompatible truck type: request needs "%s", truck is "%s".',
                $req['requested_truck_type'],
                $truck['truck_type']
            );
            return $result;
        }

        // Conflict check.
        $warnings = $this->detectConflicts($driverId, $truckId, $date, $time);
        $result['warnings'] = $warnings;
        if ($warnings) {
            $result['error'] = 'Schedule conflict: ' . implode(' ', $warnings);
            return $result;
        }

        // Create schedule + delivery atomically.
        $this->conn->begin_transaction();
        try {
            $this->run(
                "INSERT INTO hauling_schedules
                 (request_id, farmer_id, driver_id, truck_id, scheduled_date, scheduled_time, status, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, 'scheduled', ?)",
                "iiiissi",
                [$requestId, $req['farmer_id'], $driverId, $truckId, $date, $time, $adminUserId]
            );
            $scheduleId = $this->insertId();

            $this->run(
                "INSERT INTO deliveries (schedule_id, driver_id, truck_id, status)
                 VALUES (?, ?, ?, 'scheduled')",
                "iii",
                [$scheduleId, $driverId, $truckId]
            );
            $deliveryId = $this->insertId();

            $this->run(
                "UPDATE hauling_requests SET status = 'scheduled' WHERE request_id = ?",
                "i",
                [$requestId]
            );

            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollback();
            $result['error'] = 'Could not create schedule: ' . $e->getMessage();
            return $result;
        }

        // Notifications (§5 step 9).
        $farmerUserId = $this->notifier->userIdForFarmer($req['farmer_id']);
        if ($farmerUserId) {
            $this->notifier->send(
                $farmerUserId,
                'Hauling scheduled',
                "Request #$requestId is scheduled for $date $time.",
                'success'
            );
        }
        $driverUserId = $this->notifier->userIdForDriver($driverId);
        if ($driverUserId) {
            $this->notifier->send(
                $driverUserId,
                'New assignment',
                "You are assigned to schedule #$scheduleId on $date at $time.",
                'info'
            );
        }

        $result['ok'] = true;
        $result['schedule_id'] = $scheduleId;
        $result['delivery_id'] = $deliveryId;
        return $result;
    }

    public function getSchedulesForRequest($requestId)
    {
        return $this->all(
            "SELECT s.*, d.fullname AS driver_name, t.plate_number
             FROM hauling_schedules s
             LEFT JOIN drivers d ON d.driver_id = s.driver_id
             LEFT JOIN trucks t ON t.truck_id = s.truck_id
             WHERE s.request_id = ?
             ORDER BY s.created_at DESC",
            "i",
            [$requestId]
        );
    }
}
