<?php
/**
 * admin_api.php — the single session-scoped JSON endpoint for the admin portal.
 * Reads are open to any authenticated admin; every mutation enforces POST +
 * CSRF and re-checks the role server-side. Identity (user_id) always comes
 * from the session, never from the request.
 */
require_once __DIR__ . '/../../includes/access_control.php';
require_once __DIR__ . '/../../class/Admin.php';
require_once __DIR__ . '/../../class/HaulingRequest.php';
require_once __DIR__ . '/../../class/Scheduling.php';
require_once __DIR__ . '/../../class/Delivery.php';
require_once __DIR__ . '/../../class/ScaleTicket.php';
require_once __DIR__ . '/../../class/Payment.php';
require_once __DIR__ . '/../../class/Notification.php';

require_role('admin');

$admin    = new Admin();
$schedule = new Scheduling();
$requestS = new HaulingRequest();
$delivery = new Delivery();
$tickets  = new ScaleTicket();
$payment  = new Payment();
$notifier = new Notification();

$userId = (int) Auth::userId();
$action = $_REQUEST['action'] ?? '';

$MUTATIONS = [
    'farmer_create', 'farmer_update', 'farmer_status',
    'driver_create', 'driver_update', 'driver_availability', 'driver_status',
    'truck_create', 'truck_update', 'truck_status',
    'request_review', 'request_edit', 'request_schedule', 'schedule_cancel',
    'delivery_transition', 'pricing_save', 'setting_save',
    'user_create', 'user_status', 'user_reset_pw', 'mark_read', 'mark_all_read',
];
if (in_array($action, $MUTATIONS, true)) {
    require_post_csrf();
}

switch ($action) {

    // ---- Dashboard ---------------------------------------------------
    case 'overview':
        json_success(['overview' => $admin->overview(),
                      'recent'   => $admin->recentRequests(6),
                      'upcoming' => $admin->upcomingSchedules(6)]);

    // ---- Farmers -----------------------------------------------------
    case 'farmers':
        json_success(['farmers' => $admin->listFarmers($_REQUEST['search'] ?? null)]);
    case 'farmer_create':
        $r = $admin->createFarmer($_POST);
        json_out($r + ['farmers' => $admin->listFarmers()]);
    case 'farmer_update':
        $_POST['_actor'] = $userId;
        json_out($admin->updateFarmer((int) $_POST['farmer_id'], $_POST) + ['farmers' => $admin->listFarmers()]);
    case 'farmer_status':
        json_out($admin->setFarmerStatus((int) $_POST['farmer_id'], $_POST['status'] ?? '', $userId) + ['farmers' => $admin->listFarmers()]);

    // ---- Drivers -----------------------------------------------------
    case 'drivers':
        json_success(['drivers' => $admin->listDrivers($_REQUEST['search'] ?? null)]);
    case 'driver_trips':
        json_success(['trips' => $admin->driverTrips((int) $_REQUEST['driver_id'])]);
    case 'driver_create':
        json_out($admin->createDriver($_POST) + ['drivers' => $admin->listDrivers()]);
    case 'driver_update':
        $_POST['_actor'] = $userId;
        json_out($admin->updateDriver((int) $_POST['driver_id'], $_POST) + ['drivers' => $admin->listDrivers()]);
    case 'driver_availability':
        json_out($admin->setDriverAvailability((int) $_POST['driver_id'], $_POST['availability'] ?? '', $userId) + ['drivers' => $admin->listDrivers()]);
    case 'driver_status':
        json_out($admin->setDriverStatus((int) $_POST['driver_id'], $_POST['status'] ?? '', $userId) + ['drivers' => $admin->listDrivers()]);

    // ---- Trucks ------------------------------------------------------
    case 'trucks':
        json_success(['trucks' => $admin->listTrucks($_REQUEST['search'] ?? null)]);
    case 'truck_trips':
        json_success(['trips' => $admin->truckTrips((int) $_REQUEST['truck_id'])]);
    case 'truck_create':
        $_POST['_actor'] = $userId;
        json_out($admin->createTruck($_POST) + ['trucks' => $admin->listTrucks()]);
    case 'truck_update':
        $_POST['_actor'] = $userId;
        json_out($admin->updateTruck((int) $_POST['truck_id'], $_POST) + ['trucks' => $admin->listTrucks()]);
    case 'truck_status':
        $maint = ['description' => $_POST['maintenance_description'] ?? null, 'cost' => $_POST['maintenance_cost'] ?? 0];
        json_out($admin->setTruckStatus((int) $_POST['truck_id'], $_POST['status'] ?? '', $userId, $maint) + ['trucks' => $admin->listTrucks()]);

    // ---- Requests ----------------------------------------------------
    case 'requests':
        json_success(['requests' => $admin->listRequests($_REQUEST['status'] ?? null)]);
    case 'request_review':
        $r = $requestS->review((int) $_POST['request_id'], $_POST['decision'] ?? '', $userId, $_POST['remarks'] ?? null);
        json_out($r + ['requests' => $admin->listRequests($_POST['status_filter'] ?? null)]);
    case 'request_edit':
        $_POST['_actor'] = $userId;
        json_out($admin->editRequest((int) $_POST['request_id'], $_POST) + ['requests' => $admin->listRequests()]);

    // ---- Scheduling (assign driver/truck + conflict detection) -------
    case 'available_drivers':
        json_success(['drivers' => $schedule->availableDrivers($_REQUEST['date'], $_REQUEST['time'])]);
    case 'available_trucks':
        json_success(['trucks' => $schedule->availableTrucks(
            $_REQUEST['date'], $_REQUEST['time'],
            $_REQUEST['truck_type'] ?: null,
            (float) ($_REQUEST['capacity'] ?? 0)
        )]);
    case 'conflict_check':
        json_success(['warnings' => $schedule->detectConflicts(
            (int) $_REQUEST['driver_id'], (int) $_REQUEST['truck_id'], $_REQUEST['date'], $_REQUEST['time']
        )]);
    case 'request_schedule':
        $r = $schedule->assignAndSchedule(
            (int) $_POST['request_id'], (int) $_POST['driver_id'], (int) $_POST['truck_id'],
            $_POST['date'], $_POST['time'], $userId
        );
        json_out($r);
    case 'schedules':
        json_success(['schedules' => $admin->listSchedules($_REQUEST['status'] ?? null)]);
    case 'schedule_cancel':
        json_out($admin->cancelSchedule((int) $_POST['schedule_id'], $userId) + ['schedules' => $admin->listSchedules()]);

    // ---- Deliveries --------------------------------------------------
    case 'deliveries':
        json_success(['deliveries' => $admin->listDeliveries($_REQUEST['status'] ?? null)]);
    case 'delivery_transition':
        json_out($delivery->transition((int) $_POST['delivery_id'], $_POST['to'] ?? '', $userId, 'admin')
                 + ['deliveries' => $admin->listDeliveries()]);

    // ---- Weighbridge tickets ----------------------------------------
    case 'tickets':
        json_success(['tickets' => $tickets->listTickets(300, $_REQUEST['search'] ?? null)]);

    // ---- Pricing -----------------------------------------------------
    case 'pricing':
        json_success(['pricing' => $payment->listPricing(), 'active' => $payment->activePricing()]);
    case 'pricing_save':
        json_out($payment->savePricing($_POST, $userId) + ['pricing' => $payment->listPricing()]);

    // ---- Settings ----------------------------------------------------
    case 'settings':
        json_success(['settings' => $admin->listSettings()]);
    case 'setting_save':
        json_out($admin->saveSetting(trim($_POST['setting_key'] ?? ''), $_POST['setting_value'] ?? '', $userId)
                 + ['settings' => $admin->listSettings()]);

    // ---- Users -------------------------------------------------------
    case 'users':
        json_success(['users' => $admin->listUsers()]);
    case 'user_create':
        $_POST['_actor'] = $userId;
        json_out($admin->createUser($_POST) + ['users' => $admin->listUsers()]);
    case 'user_status':
        json_out($admin->setUserStatus((int) $_POST['user_id'], $_POST['status'] ?? '', $userId) + ['users' => $admin->listUsers()]);
    case 'user_reset_pw':
        json_out($admin->resetUserPassword((int) $_POST['user_id'], $_POST['password'] ?? '', $userId));

    // ---- Activity logs ----------------------------------------------
    case 'activity_logs':
        json_success(['logs' => $admin->activityLogs(150, $_REQUEST['action_filter'] ?? null)]);

    // ---- Notifications ----------------------------------------------
    case 'notifications':
        json_success(['notifications' => $notifier->forUser($userId, false, 100),
                      'unread' => $notifier->unreadCount($userId)]);
    case 'mark_read':
        $notifier->markRead((int) $_POST['notification_id'], $userId);
        json_success(['unread' => $notifier->unreadCount($userId)]);
    case 'mark_all_read':
        $notifier->markAllRead($userId);
        json_success(['unread' => 0]);

    default:
        json_fail('Unknown action.');
}
