<?php
/**
 * Admin — the administrator's data access layer for the whole operation
 * (§32-49): dashboard counters, master-data CRUD for farmers / drivers /
 * trucks / users, rate configuration, activity logs and printable reports.
 *
 * Every mutation is recorded in activity_logs. Financial workflow rules are
 * NOT reimplemented here — status transitions still go through Delivery and
 * scheduling conflicts through Scheduling, so this class stays a thin,
 * validated facade over the same tables the other portals use.
 */
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Notification.php';

class Admin extends Model
{
    const DRIVER_AVAILABILITY = ['available', 'on_trip', 'unavailable'];
    const DRIVER_STATUS        = ['active', 'inactive'];
    const TRUCK_TYPES          = ['tipper', 'flatbed', 'container', 'tractor'];
    const TRUCK_STATUS         = ['available', 'in_use', 'maintenance', 'retired'];
    const USER_ROLES           = ['admin', 'farmer', 'driver', 'weighbridge'];
    const USER_STATUS          = ['active', 'inactive', 'suspended'];

    private $notifier;

    public function __construct()
    {
        parent::__construct();
        $this->notifier = new Notification();
    }

    // -----------------------------------------------------------------
    // Dashboard (feature §38)
    // -----------------------------------------------------------------

    /** All headline counters in a single round-trip. */
    public function overview()
    {
        $counts = $this->one(
            "SELECT
                (SELECT COUNT(*) FROM farmers)                                   AS farmers,
                (SELECT COUNT(*) FROM drivers)                                   AS drivers,
                (SELECT COUNT(*) FROM drivers WHERE status='active'
                    AND availability='available')                                AS drivers_available,
                (SELECT COUNT(*) FROM trucks)                                    AS trucks,
                (SELECT COUNT(*) FROM trucks WHERE status='available')           AS trucks_available,
                (SELECT COUNT(*) FROM hauling_requests)                          AS requests,
                (SELECT COUNT(*) FROM hauling_requests WHERE status='pending')   AS pending_requests,
                (SELECT COUNT(*) FROM hauling_requests WHERE status='approved')  AS approved_requests,
                (SELECT COUNT(*) FROM deliveries
                    WHERE status IN ('scheduled','in_transit'))                  AS scheduled_trips,
                (SELECT COUNT(*) FROM deliveries WHERE status='completed')       AS completed_deliveries,
                (SELECT COUNT(*) FROM users WHERE role='admin')                  AS admins"
        );

        $month = $this->one(
            "SELECT
                COUNT(*)                                     AS month_trips,
                COALESCE(SUM(st.net_weight), 0) / 1000        AS month_tons,
                COALESCE(SUM(pc.net_payable), 0)             AS month_payable
             FROM deliveries dv
             JOIN hauling_schedules s ON s.schedule_id = dv.schedule_id
             LEFT JOIN scale_tickets st        ON st.delivery_id = dv.delivery_id
             LEFT JOIN payment_computations pc ON pc.ticket_id   = st.ticket_id
             WHERE dv.status = 'completed'
               AND YEAR(s.scheduled_date) = YEAR(CURDATE())
               AND MONTH(s.scheduled_date) = MONTH(CURDATE())"
        );

        return [
            'farmers'             => (int) $counts['farmers'],
            'drivers'             => (int) $counts['drivers'],
            'drivers_available'   => (int) $counts['drivers_available'],
            'trucks'              => (int) $counts['trucks'],
            'trucks_available'    => (int) $counts['trucks_available'],
            'requests'            => (int) $counts['requests'],
            'pending_requests'    => (int) $counts['pending_requests'],
            'approved_requests'   => (int) $counts['approved_requests'],
            'scheduled_trips'     => (int) $counts['scheduled_trips'],
            'completed_deliveries'=> (int) $counts['completed_deliveries'],
            'admins'              => (int) $counts['admins'],
            'month_trips'         => (int) ($month['month_trips'] ?? 0),
            'month_tons'          => round((float) ($month['month_tons'] ?? 0), 2),
            'month_payable'       => round((float) ($month['month_payable'] ?? 0), 2),
        ];
    }

    /** Recent requests + upcoming schedules for the dashboard tables. */
    public function recentRequests($limit = 8)
    {
        return $this->all(
            "SELECT r.*, f.fullname AS farmer_name
             FROM hauling_requests r
             JOIN farmers f ON f.farmer_id = r.farmer_id
             ORDER BY r.created_at DESC LIMIT " . (int) $limit
        );
    }

    public function upcomingSchedules($limit = 8)
    {
        return $this->all(
            "SELECT s.*, f.fullname AS farmer_name, d.fullname AS driver_name, t.plate_number
             FROM hauling_schedules s
             LEFT JOIN farmers f ON f.farmer_id = s.farmer_id
             LEFT JOIN drivers d ON d.driver_id = s.driver_id
             LEFT JOIN trucks  t ON t.truck_id  = s.truck_id
             WHERE s.status NOT IN ('completed','cancelled')
             ORDER BY s.scheduled_date ASC, s.scheduled_time ASC LIMIT " . (int) $limit
        );
    }

    // -----------------------------------------------------------------
    // Farmers (feature §39)
    // -----------------------------------------------------------------

    public function listFarmers($search = null)
    {
        $sql = "SELECT f.*, u.email, u.status AS account_status,
                       (SELECT COUNT(*) FROM deliveries dv
                        JOIN hauling_schedules s ON s.schedule_id = dv.schedule_id
                        WHERE s.farmer_id = f.farmer_id AND dv.status='completed') AS completed_hauls
                FROM farmers f
                LEFT JOIN users u ON u.user_id = f.user_id";
        $types = '';
        $params = [];
        if ($search !== null && $search !== '') {
            $sql .= " WHERE f.fullname LIKE ? OR f.farm_location LIKE ? OR u.email LIKE ?";
            $types = 'sss';
            $like = '%' . $search . '%';
            $params = [$like, $like, $like];
        }
        $sql .= " ORDER BY f.created_at DESC";
        return $this->all($sql, $types, $params);
    }

    public function createFarmer(array $d)
    {
        $fullname = trim($d['fullname'] ?? '');
        $email    = strtolower(trim($d['email'] ?? ''));
        $password = $d['password'] ?? '';
        if ($fullname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            return ['ok' => false, 'error' => 'Name, a valid email and a password of at least 6 characters are required.'];
        }
        if ($this->emailExists($email)) {
            return ['ok' => false, 'error' => 'That email is already registered.'];
        }

        $this->conn->begin_transaction();
        try {
            $uid = $this->insertUser($fullname, $email, $password, $d['contact_no'] ?? null, 'farmer');
            $this->run(
                "INSERT INTO farmers (user_id, fullname, contact_no, farm_location, estimated_tons, distance_to_mill, status)
                 VALUES (?, ?, ?, ?, ?, ?, 'active')",
                "isssssd",
                [$uid, $fullname, $d['contact_no'] ?? '', $d['farm_location'] ?? '',
                 (float) ($d['estimated_tons'] ?? 0), (float) ($d['distance_to_mill'] ?? 0)]
            );
            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollback();
            return ['ok' => false, 'error' => 'Could not create farmer: ' . $e->getMessage()];
        }
        $this->log($uid, 'create', 'farmers', $uid, "Created farmer $fullname");
        return ['ok' => true, 'error' => null, 'id' => $uid];
    }

    public function updateFarmer($farmerId, array $d)
    {
        $fullname = trim($d['fullname'] ?? '');
        if ($fullname === '') {
            return ['ok' => false, 'error' => 'Full name is required.'];
        }
        $this->run(
            "UPDATE farmers SET fullname=?, contact_no=?, farm_location=?, estimated_tons=?, distance_to_mill=?, updated_at=NOW()
             WHERE farmer_id=?",
            "ssssdi",
            [$fullname, $d['contact_no'] ?? '', $d['farm_location'] ?? '',
             (float) ($d['estimated_tons'] ?? 0), (float) ($d['distance_to_mill'] ?? 0), $farmerId]
        );
        $userId = $this->scalar("SELECT user_id FROM farmers WHERE farmer_id=?", "i", [$farmerId]);
        if ($userId) {
            $this->run("UPDATE users SET full_name=? WHERE user_id=?", "si", [$fullname, $userId]);
        }
        $this->log($d['_actor'] ?? null, 'update', 'farmers', $farmerId, "Updated farmer #$farmerId");
        return ['ok' => true, 'error' => null];
    }

    public function setFarmerStatus($farmerId, $status, $actorUserId = null)
    {
        if (!in_array($status, self::DRIVER_STATUS, true)) {
            return ['ok' => false, 'error' => 'Invalid farmer status.'];
        }
        $this->run("UPDATE farmers SET status=? WHERE farmer_id=?", "si", [$status, $farmerId]);
        $userId = $this->scalar("SELECT user_id FROM farmers WHERE farmer_id=?", "i", [$farmerId]);
        if ($userId) {
            $this->run("UPDATE users SET status=? WHERE user_id=?", "si", [$status, $userId]);
        }
        $this->log($actorUserId, 'status', 'farmers', $farmerId, "Farmer #$farmerId set to $status");
        return ['ok' => true, 'error' => null];
    }

    // -----------------------------------------------------------------
    // Drivers (feature §40)
    // -----------------------------------------------------------------

    public function listDrivers($search = null)
    {
        $sql = "SELECT d.*, u.email, u.status AS account_status,
                       (SELECT COUNT(*) FROM deliveries dv WHERE dv.driver_id=d.driver_id AND dv.status='completed') AS completed_trips
                FROM drivers d
                LEFT JOIN users u ON u.user_id = d.user_id";
        $types = '';
        $params = [];
        if ($search !== null && $search !== '') {
            $sql .= " WHERE d.fullname LIKE ? OR d.contact_no LIKE ? OR u.email LIKE ?";
            $types = 'sss';
            $like = '%' . $search . '%';
            $params = [$like, $like, $like];
        }
        $sql .= " ORDER BY d.created_at DESC";
        return $this->all($sql, $types, $params);
    }

    public function createDriver(array $d)
    {
        $fullname = trim($d['fullname'] ?? '');
        $email    = strtolower(trim($d['email'] ?? ''));
        $password = $d['password'] ?? '';
        if ($fullname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            return ['ok' => false, 'error' => 'Name, a valid email and a password of at least 6 characters are required.'];
        }
        if ($this->emailExists($email)) {
            return ['ok' => false, 'error' => 'That email is already registered.'];
        }
        $this->conn->begin_transaction();
        try {
            $uid = $this->insertUser($fullname, $email, $password, $d['contact_no'] ?? null, 'driver');
            $this->run(
                "INSERT INTO drivers (user_id, fullname, contact_no, license_no, license_expiry, home_base, availability, status)
                 VALUES (?, ?, ?, ?, ?, ?, 'available', 'active')",
                "issssss",
                [$uid, $fullname, $d['contact_no'] ?? '', $d['license_no'] ?? null,
                 $this->nullDate($d['license_expiry'] ?? null), $d['home_base'] ?? null]
            );
            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollback();
            return ['ok' => false, 'error' => 'Could not create driver: ' . $e->getMessage()];
        }
        $this->log($uid, 'create', 'drivers', $uid, "Created driver $fullname");
        return ['ok' => true, 'error' => null, 'id' => $uid];
    }

    public function updateDriver($driverId, array $d)
    {
        $fullname = trim($d['fullname'] ?? '');
        if ($fullname === '') {
            return ['ok' => false, 'error' => 'Full name is required.'];
        }
        $this->run(
            "UPDATE drivers SET fullname=?, contact_no=?, license_no=?, license_expiry=?, home_base=?, updated_at=NOW()
             WHERE driver_id=?",
            "sssssi",
            [$fullname, $d['contact_no'] ?? '', $d['license_no'] ?? null,
             $this->nullDate($d['license_expiry'] ?? null), $d['home_base'] ?? null, $driverId]
        );
        $userId = $this->scalar("SELECT user_id FROM drivers WHERE driver_id=?", "i", [$driverId]);
        if ($userId) {
            $this->run("UPDATE users SET full_name=? WHERE user_id=?", "si", [$fullname, $userId]);
        }
        $this->log($d['_actor'] ?? null, 'update', 'drivers', $driverId, "Updated driver #$driverId");
        return ['ok' => true, 'error' => null];
    }

    public function setDriverAvailability($driverId, $availability, $actorUserId = null)
    {
        if (!in_array($availability, self::DRIVER_AVAILABILITY, true)) {
            return ['ok' => false, 'error' => 'Invalid availability value.'];
        }
        $this->run("UPDATE drivers SET availability=?, updated_at=NOW() WHERE driver_id=?", "si", [$availability, $driverId]);
        $this->log($actorUserId, 'availability', 'drivers', $driverId, "Driver #$driverId availability -> $availability");
        return ['ok' => true, 'error' => null];
    }

    public function setDriverStatus($driverId, $status, $actorUserId = null)
    {
        if (!in_array($status, self::DRIVER_STATUS, true)) {
            return ['ok' => false, 'error' => 'Invalid driver status.'];
        }
        $this->run("UPDATE drivers SET status=? WHERE driver_id=?", "si", [$status, $driverId]);
        $userId = $this->scalar("SELECT user_id FROM drivers WHERE driver_id=?", "i", [$driverId]);
        if ($userId) {
            $this->run("UPDATE users SET status=? WHERE user_id=?", "si", [$status, $userId]);
        }
        $this->log($actorUserId, 'status', 'drivers', $driverId, "Driver #$driverId set to $status");
        return ['ok' => true, 'error' => null];
    }

    public function driverTrips($driverId, $limit = 20)
    {
        return $this->all(
            "SELECT dv.delivery_id, dv.status, dv.departure_time, dv.completion_time,
                    s.scheduled_date, s.scheduled_time, f.fullname AS farmer_name,
                    t.plate_number, st.net_weight
             FROM deliveries dv
             JOIN hauling_schedules s ON s.schedule_id = dv.schedule_id
             LEFT JOIN farmers f ON f.farmer_id = s.farmer_id
             LEFT JOIN trucks  t ON t.truck_id  = dv.truck_id
             LEFT JOIN scale_tickets st ON st.delivery_id = dv.delivery_id
             WHERE dv.driver_id = ?
             ORDER BY s.scheduled_date DESC LIMIT " . (int) $limit,
            "i",
            [$driverId]
        );
    }

    // -----------------------------------------------------------------
    // Trucks (feature §41)
    // -----------------------------------------------------------------

    public function listTrucks($search = null)
    {
        $sql = "SELECT t.*,
                       (SELECT COUNT(*) FROM deliveries dv WHERE dv.truck_id=t.truck_id AND dv.status='completed') AS completed_trips,
                       (SELECT MAX(m.maintenance_date) FROM truck_maintenance m WHERE m.truck_id=t.truck_id) AS last_service
                FROM trucks t";
        $types = '';
        $params = [];
        if ($search !== null && $search !== '') {
            $sql .= " WHERE t.plate_number LIKE ? OR t.truck_type LIKE ?";
            $types = 'ss';
            $like = '%' . $search . '%';
            $params = [$like, $like];
        }
        $sql .= " ORDER BY t.plate_number ASC";
        return $this->all($sql, $types, $params);
    }

    public function createTruck(array $d)
    {
        $plate = strtoupper(trim($d['plate_number'] ?? ''));
        $type  = $d['truck_type'] ?? '';
        $cap   = (float) ($d['carrying_capacity'] ?? 0);
        if ($plate === '' || !in_array($type, self::TRUCK_TYPES, true) || $cap <= 0) {
            return ['ok' => false, 'error' => 'Plate number, a valid truck type and a capacity greater than zero are required.'];
        }
        if ((int) $this->scalar("SELECT COUNT(*) FROM trucks WHERE plate_number=?", "s", [$plate])) {
            return ['ok' => false, 'error' => 'That plate number already exists.'];
        }
        $this->run(
            "INSERT INTO trucks (plate_number, truck_type, carrying_capacity, status, current_location)
             VALUES (?, ?, ?, ?, ?)",
            "ssdss",
            [$plate, $type, $cap, in_array($d['status'] ?? '', self::TRUCK_STATUS, true) ? $d['status'] : 'available', $d['current_location'] ?? null]
        );
        $id = $this->insertId();
        $this->log($d['_actor'] ?? null, 'create', 'trucks', $id, "Created truck $plate");
        return ['ok' => true, 'error' => null, 'id' => $id];
    }

    public function updateTruck($truckId, array $d)
    {
        $plate = strtoupper(trim($d['plate_number'] ?? ''));
        $type  = $d['truck_type'] ?? '';
        $cap   = (float) ($d['carrying_capacity'] ?? 0);
        if ($plate === '' || !in_array($type, self::TRUCK_TYPES, true) || $cap <= 0) {
            return ['ok' => false, 'error' => 'Plate number, a valid truck type and a capacity greater than zero are required.'];
        }
        $this->run(
            "UPDATE trucks SET plate_number=?, truck_type=?, carrying_capacity=?, current_location=?, updated_at=NOW()
             WHERE truck_id=?",
            "ssdsi",
            [$plate, $type, $cap, $d['current_location'] ?? null, $truckId]
        );
        $this->log($d['_actor'] ?? null, 'update', 'trucks', $truckId, "Updated truck #$truckId");
        return ['ok' => true, 'error' => null];
    }

    /**
     * Change a truck's status. When set to 'maintenance' with a description,
     * a truck_maintenance record is logged too (feature §41).
     */
    public function setTruckStatus($truckId, $status, $actorUserId = null, $maintenance = null)
    {
        if (!in_array($status, self::TRUCK_STATUS, true)) {
            return ['ok' => false, 'error' => 'Invalid truck status.'];
        }
        $this->run("UPDATE trucks SET status=?, updated_at=NOW() WHERE truck_id=?", "si", [$status, $truckId]);
        if ($status === 'maintenance' && !empty($maintenance['description'])) {
            $this->run(
                "INSERT INTO truck_maintenance (truck_id, maintenance_date, description, cost, status, performed_by)
                 VALUES (?, CURDATE(), ?, ?, 'in_progress', ?)",
                "sdsi",
                [$truckId, $maintenance['description'], (float) ($maintenance['cost'] ?? 0), $actorUserId]
            );
        }
        $this->log($actorUserId, 'status', 'trucks', $truckId, "Truck #$truckId set to $status");
        return ['ok' => true, 'error' => null];
    }

    public function truckTrips($truckId, $limit = 20)
    {
        return $this->all(
            "SELECT dv.delivery_id, dv.status, s.scheduled_date, s.scheduled_time,
                    f.fullname AS farmer_name, dr.fullname AS driver_name, st.net_weight
             FROM deliveries dv
             JOIN hauling_schedules s ON s.schedule_id = dv.schedule_id
             LEFT JOIN farmers f ON f.farmer_id = s.farmer_id
             LEFT JOIN drivers dr ON dr.driver_id = dv.driver_id
             LEFT JOIN scale_tickets st ON st.delivery_id = dv.delivery_id
             WHERE dv.truck_id = ?
             ORDER BY s.scheduled_date DESC LIMIT " . (int) $limit,
            "i",
            [$truckId]
        );
    }

    // -----------------------------------------------------------------
    // Requests: view / approve / reject / edit / schedule (§42-44)
    // -----------------------------------------------------------------

    public function listRequests($status = null)
    {
        $sql = "SELECT r.*, f.fullname AS farmer_name, f.contact_no,
                       (SELECT s.schedule_id FROM hauling_schedules s
                        WHERE s.request_id = r.request_id AND s.status <> 'cancelled'
                        ORDER BY s.created_at DESC LIMIT 1) AS schedule_id
                FROM hauling_requests r
                JOIN farmers f ON f.farmer_id = r.farmer_id";
        $types = '';
        $params = [];
        if ($status) {
            $sql .= " WHERE r.status = ?";
            $types = 's';
            $params = [$status];
        }
        $sql .= " ORDER BY r.created_at DESC";
        return $this->all($sql, $types, $params);
    }

    public function editRequest($requestId, array $d)
    {
        $req = $this->one("SELECT * FROM hauling_requests WHERE request_id=?", "i", [$requestId]);
        if (!$req) {
            return ['ok' => false, 'error' => 'Request not found.'];
        }
        if (!in_array($req['status'], ['pending', 'approved'], true)) {
            return ['ok' => false, 'error' => 'Only pending or approved requests can be edited.'];
        }
        $this->run(
            "UPDATE hauling_requests SET preferred_date=?, preferred_time=?, farm_location=?,
                    estimated_fresh_weight=?, requested_truck_type=?, distance_to_mill=?, notes=?, updated_at=NOW()
             WHERE request_id=?",
            "sssdsssi",
            [$d['preferred_date'], $d['preferred_time'], $d['farm_location'],
             (float) $d['estimated_fresh_weight'], $d['requested_truck_type'],
             (float) ($d['distance_to_mill'] ?? 0), $d['notes'] ?? null, $requestId]
        );
        $this->log($d['_actor'] ?? null, 'update', 'hauling_requests', $requestId, "Edited request #$requestId");
        return ['ok' => true, 'error' => null];
    }

    // -----------------------------------------------------------------
    // Schedules view (§44-45) — conflict detection reuses Scheduling.
    // -----------------------------------------------------------------

    public function listSchedules($status = null)
    {
        $sql = "SELECT s.*, f.fullname AS farmer_name, r.request_id,
                       d.fullname AS driver_name, t.plate_number, t.truck_type,
                       dv.status AS delivery_status
                FROM hauling_schedules s
                LEFT JOIN farmers f ON f.farmer_id = s.farmer_id
                LEFT JOIN hauling_requests r ON r.request_id = s.request_id
                LEFT JOIN drivers d ON d.driver_id = s.driver_id
                LEFT JOIN trucks  t ON t.truck_id  = s.truck_id
                LEFT JOIN deliveries dv ON dv.schedule_id = s.schedule_id";
        $types = '';
        $params = [];
        if ($status) {
            $sql .= " WHERE s.status = ?";
            $types = 's';
            $params = [$status];
        }
        $sql .= " ORDER BY s.scheduled_date DESC, s.scheduled_time DESC";
        return $this->all($sql, $types, $params);
    }

    public function cancelSchedule($scheduleId, $actorUserId = null)
    {
        $sched = $this->one("SELECT * FROM hauling_schedules WHERE schedule_id=?", "i", [$scheduleId]);
        if (!$sched) {
            return ['ok' => false, 'error' => 'Schedule not found.'];
        }
        if (in_array($sched['status'], ['completed', 'cancelled'], true)) {
            return ['ok' => false, 'error' => 'This schedule is already closed.'];
        }
        $this->conn->begin_transaction();
        try {
            $this->run("UPDATE hauling_schedules SET status='cancelled', updated_at=NOW() WHERE schedule_id=?", "i", [$scheduleId]);
            $deliveryId = $this->scalar("SELECT delivery_id FROM deliveries WHERE schedule_id=?", "i", [$scheduleId]);
            if ($deliveryId) {
                $this->run("UPDATE deliveries SET status='cancelled', updated_at=NOW() WHERE delivery_id=?", "i", [$deliveryId]);
            }
            $this->run("UPDATE hauling_requests SET status='cancelled' WHERE request_id=?", "i", [$sched['request_id']]);
            if ($sched['driver_id']) {
                $this->run("UPDATE drivers SET availability='available' WHERE driver_id=?", "i", [$sched['driver_id']]);
            }
            if ($sched['truck_id']) {
                $this->run("UPDATE trucks SET status='available' WHERE truck_id=?", "i", [$sched['truck_id']]);
            }
            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollback();
            return ['ok' => false, 'error' => 'Could not cancel schedule: ' . $e->getMessage()];
        }
        $this->log($actorUserId, 'cancel', 'hauling_schedules', $scheduleId, "Cancelled schedule #$scheduleId");
        return ['ok' => true, 'error' => null];
    }

    // -----------------------------------------------------------------
    // Delivery progress (§45)
    // -----------------------------------------------------------------

    public function listDeliveries($status = null)
    {
        $sql = "SELECT dv.*, s.schedule_id, s.scheduled_date, s.scheduled_time, s.request_id,
                       f.fullname AS farmer_name, d.fullname AS driver_name,
                       t.plate_number, t.truck_type,
                       st.ticket_number, st.net_weight
                FROM deliveries dv
                JOIN hauling_schedules s ON s.schedule_id = dv.schedule_id
                LEFT JOIN farmers f ON f.farmer_id = s.farmer_id
                LEFT JOIN drivers d ON d.driver_id = dv.driver_id
                LEFT JOIN trucks  t ON t.truck_id  = dv.truck_id
                LEFT JOIN scale_tickets st ON st.delivery_id = dv.delivery_id";
        $types = '';
        $params = [];
        if ($status) {
            $sql .= " WHERE dv.status = ?";
            $types = 's';
            $params = [$status];
        }
        $sql .= " ORDER BY s.scheduled_date DESC, s.scheduled_time DESC";
        return $this->all($sql, $types, $params);
    }

    // -----------------------------------------------------------------
    // Users (feature §49)
    // -----------------------------------------------------------------

    public function listUsers()
    {
        return $this->all(
            "SELECT user_id, full_name, email, phone, role, status, last_login, created_at
             FROM users ORDER BY created_at DESC"
        );
    }

    public function createUser(array $d)
    {
        $fullname = trim($d['full_name'] ?? '');
        $email    = strtolower(trim($d['email'] ?? ''));
        $password = $d['password'] ?? '';
        $role     = $d['role'] ?? '';
        if ($fullname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            return ['ok' => false, 'error' => 'Name, a valid email and a password of at least 6 characters are required.'];
        }
        if (!in_array($role, self::USER_ROLES, true)) {
            return ['ok' => false, 'error' => 'Invalid role.'];
        }
        if ($this->emailExists($email)) {
            return ['ok' => false, 'error' => 'That email is already registered.'];
        }
        $uid = $this->insertUser($fullname, $email, $password, $d['phone'] ?? null, $role);
        $this->log($d['_actor'] ?? null, 'create', 'users', $uid, "Created $role account $email");
        return ['ok' => true, 'error' => null, 'id' => $uid];
    }

    /** Suspend / activate a login. Refuses to disable the last active admin or self. */
    public function setUserStatus($userId, $status, $actorUserId = null)
    {
        if (!in_array($status, self::USER_STATUS, true)) {
            return ['ok' => false, 'error' => 'Invalid status.'];
        }
        if ($actorUserId && (int) $userId === (int) $actorUserId && $status !== 'active') {
            return ['ok' => false, 'error' => 'You cannot deactivate your own account.'];
        }
        $target = $this->one("SELECT role FROM users WHERE user_id=?", "i", [$userId]);
        if (!$target) {
            return ['ok' => false, 'error' => 'User not found.'];
        }
        if ($target['role'] === 'admin' && $status !== 'active') {
            $activeAdmins = (int) $this->scalar("SELECT COUNT(*) FROM users WHERE role='admin' AND status='active'");
            if ($activeAdmins <= 1) {
                return ['ok' => false, 'error' => 'At least one active administrator must remain.'];
            }
        }
        $this->run("UPDATE users SET status=? WHERE user_id=?", "si", [$status, $userId]);
        $this->log($actorUserId, 'status', 'users', $userId, "User #$userId set to $status");
        return ['ok' => true, 'error' => null];
    }

    public function resetUserPassword($userId, $password, $actorUserId = null)
    {
        if (strlen($password) < 6) {
            return ['ok' => false, 'error' => 'Password must be at least 6 characters.'];
        }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $this->run("UPDATE users SET password=? WHERE user_id=?", "si", [$hash, $userId]);
        $this->log($actorUserId, 'reset_password', 'users', $userId, "Reset password for user #$userId");
        return ['ok' => true, 'error' => null];
    }

    // -----------------------------------------------------------------
    // System settings (§49)
    // -----------------------------------------------------------------

    public function listSettings()
    {
        return $this->all("SELECT * FROM system_settings ORDER BY setting_key");
    }

    public function saveSetting($key, $value, $actorUserId = null)
    {
        if ($key === '') {
            return ['ok' => false, 'error' => 'Setting key is required.'];
        }
        $this->run(
            "INSERT INTO system_settings (setting_key, setting_value, updated_by)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)",
            "ssi",
            [$key, $value, $actorUserId]
        );
        $this->log($actorUserId, 'update', 'system_settings', $key, "Setting '$key' updated");
        return ['ok' => true, 'error' => null];
    }

    // -----------------------------------------------------------------
    // Activity logs + notifications (§47)
    // -----------------------------------------------------------------

    public function activityLogs($limit = 100, $action = null)
    {
        $sql = "SELECT al.*, u.full_name AS user_name
                FROM activity_logs al
                LEFT JOIN users u ON u.user_id = al.user_id";
        $types = '';
        $params = [];
        if ($action) {
            $sql .= " WHERE al.action = ?";
            $types = 's';
            $params = [$action];
        }
        $sql .= " ORDER BY al.created_at DESC LIMIT " . (int) $limit;
        return $this->all($sql, $types, $params);
    }

    // -----------------------------------------------------------------
    // Reports (§48)
    // -----------------------------------------------------------------

    public function reportHauling($from = null, $to = null)
    {
        [$where, $types, $params] = $this->dateFilter('s.scheduled_date', $from, $to);
        return $this->all(
            "SELECT s.scheduled_date, s.scheduled_time, f.fullname AS farmer_name,
                    d.fullname AS driver_name, t.plate_number, st.ticket_number,
                    st.net_weight, pc.net_payable, dv.status
             FROM deliveries dv
             JOIN hauling_schedules s ON s.schedule_id = dv.schedule_id
             LEFT JOIN farmers f ON f.farmer_id = s.farmer_id
             LEFT JOIN drivers d ON d.driver_id = dv.driver_id
             LEFT JOIN trucks  t ON t.truck_id  = dv.truck_id
             LEFT JOIN scale_tickets st ON st.delivery_id = dv.delivery_id
             LEFT JOIN payment_computations pc ON pc.ticket_id = st.ticket_id
             $where
             ORDER BY s.scheduled_date DESC",
            $types,
            $params
        );
    }

    public function reportFarmerSummary()
    {
        return $this->all(
            "SELECT f.farmer_id, f.fullname, f.farm_location,
                    COUNT(DISTINCT r.request_id) AS requests,
                    (SELECT COUNT(*) FROM deliveries dv JOIN hauling_schedules s ON s.schedule_id=dv.schedule_id
                        WHERE s.farmer_id=f.farmer_id AND dv.status='completed') AS completed_hauls,
                    COALESCE((SELECT SUM(st.net_weight) FROM payment_computations pc
                        JOIN scale_tickets st ON st.ticket_id=pc.ticket_id WHERE pc.farmer_id=f.farmer_id),0) AS total_kg,
                    COALESCE((SELECT SUM(pc.net_payable) FROM payment_computations pc WHERE pc.farmer_id=f.farmer_id),0) AS total_payable
             FROM farmers f
             LEFT JOIN hauling_requests r ON r.farmer_id = f.farmer_id
             GROUP BY f.farmer_id
             ORDER BY total_payable DESC"
        );
    }

    public function reportDriverSummary()
    {
        return $this->all(
            "SELECT d.driver_id, d.fullname, d.availability,
                    COUNT(dv.delivery_id) AS total_trips,
                    SUM(dv.status='completed') AS completed_trips
             FROM drivers d
             LEFT JOIN deliveries dv ON dv.driver_id = d.driver_id
             GROUP BY d.driver_id
             ORDER BY completed_trips DESC"
        );
    }

    public function reportTruckSummary()
    {
        return $this->all(
            "SELECT t.truck_id, t.plate_number, t.truck_type, t.carrying_capacity, t.status,
                    COUNT(dv.delivery_id) AS total_trips,
                    SUM(dv.status='completed') AS completed_trips,
                    COALESCE(SUM(st.net_weight),0)/1000 AS tons_hauled
             FROM trucks t
             LEFT JOIN deliveries dv ON dv.truck_id = t.truck_id
             LEFT JOIN scale_tickets st ON st.delivery_id = dv.delivery_id
             GROUP BY t.truck_id
             ORDER BY tons_hauled DESC"
        );
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function insertUser($fullName, $email, $password, $phone, $role)
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $this->run(
            "INSERT INTO users (full_name, email, password, phone, role, status)
             VALUES (?, ?, ?, ?, ?, 'active')",
            "sssss",
            [$fullName, $email, $hash, $phone, $role]
        );
        return $this->insertId();
    }

    private function emailExists($email)
    {
        return (bool) $this->scalar("SELECT COUNT(*) FROM users WHERE email=?", "s", [$email]);
    }

    private function nullDate($v)
    {
        return ($v === '' || $v === null) ? null : $v;
    }

    private function dateFilter($column, $from, $to)
    {
        $clauses = [];
        $types = '';
        $params = [];
        if ($from) { $clauses[] = "$column >= ?"; $types .= 's'; $params[] = $from; }
        if ($to)   { $clauses[] = "$column <= ?"; $types .= 's'; $params[] = $to; }
        $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';
        return [$where, $types, $params];
    }

    private function log($userId, $action, $table, $recordId, $details)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $uid = $userId !== null ? (int) $userId : null;
        $this->run(
            "INSERT INTO activity_logs (user_id, action, table_name, record_id, details, ip_address)
             VALUES (?, ?, ?, ?, ?, ?)",
            "isssss",
            [$uid, $action, $table, (string) $recordId, $details, $ip]
        );
    }
}
