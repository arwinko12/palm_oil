<?php
/**
 * Admin hauling API (workflow §5 steps 4–9): review requests, list free
 * resources for a slot, detect conflicts, and assign driver + truck.
 */
require_once __DIR__ . '/../../includes/access_control.php';
require_once __DIR__ . '/../../class/HaulingRequest.php';
require_once __DIR__ . '/../../class/Scheduling.php';

require_role('admin');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'pending':
        json_success(['requests' => (new HaulingRequest())->listByStatus('pending')]);
        break;

    case 'review':
        require_post_csrf();
        $res = (new HaulingRequest())->review(
            (int) ($_POST['request_id'] ?? 0),
            $_POST['decision'] ?? '',
            (int) Auth::userId(),
            $_POST['remarks'] ?? null
        );
        $res['ok'] ? json_success() : json_fail($res['error']);
        break;

    case 'available_drivers':
        $date = $_GET['date'] ?? date('Y-m-d');
        $time = $_GET['time'] ?? '08:00:00';
        json_success(['drivers' => (new Scheduling())->availableDrivers($date, $time)]);
        break;

    case 'available_trucks':
        $sched  = new Scheduling();
        $date   = $_GET['date'] ?? date('Y-m-d');
        $time   = $_GET['time'] ?? '08:00:00';
        $type   = $_GET['truck_type'] ?? null;
        $minCap = (float) ($_GET['min_capacity'] ?? 0);
        json_success(['trucks' => $sched->availableTrucks($date, $time, $type, $minCap)]);
        break;

    case 'check_conflict':
        $warnings = (new Scheduling())->detectConflicts(
            (int) ($_POST['driver_id'] ?? $_GET['driver_id'] ?? 0),
            (int) ($_POST['truck_id'] ?? $_GET['truck_id'] ?? 0),
            $_POST['date'] ?? $_GET['date'] ?? '',
            $_POST['time'] ?? $_GET['time'] ?? ''
        );
        json_success(['conflict' => !empty($warnings), 'warnings' => $warnings]);
        break;

    case 'assign':
        require_post_csrf();
        $res = (new Scheduling())->assignAndSchedule(
            (int) ($_POST['request_id'] ?? 0),
            (int) ($_POST['driver_id'] ?? 0),
            (int) ($_POST['truck_id'] ?? 0),
            $_POST['scheduled_date'] ?? '',
            $_POST['scheduled_time'] ?? '',
            (int) Auth::userId()
        );
        $res['ok']
            ? json_success(['schedule_id' => $res['schedule_id'], 'delivery_id' => $res['delivery_id']])
            : json_fail($res['error'], ['warnings' => $res['warnings']]);
        break;

    default:
        json_fail('Unknown action.');
}
