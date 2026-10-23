<?php
/**
 * export.php — CSV export for admin reports (§48). Guarded by the admin role;
 * the same server-side report methods back the on-screen tables, so an export
 * always matches what the admin can see.
 */
require_once __DIR__ . '/../../includes/access_control.php';
require_once __DIR__ . '/../../class/Admin.php';

require_role('admin');

$admin = new Admin();
$type  = $_GET['type'] ?? 'hauling';
$from  = $_GET['from'] ?? null;
$to    = $_GET['to'] ?? null;

switch ($type) {
    case 'farmers':
        $rows = $admin->reportFarmerSummary();
        $head = ['Farmer', 'Location', 'Requests', 'Completed Hauls', 'Total kg', 'Total Payable'];
        $map  = fn($r) => [$r['fullname'], $r['farm_location'], $r['requests'], $r['completed_hauls'], $r['total_kg'], $r['total_payable']];
        break;
    case 'drivers':
        $rows = $admin->reportDriverSummary();
        $head = ['Driver', 'Availability', 'Total Trips', 'Completed Trips'];
        $map  = fn($r) => [$r['fullname'], $r['availability'], $r['total_trips'], $r['completed_trips']];
        break;
    case 'trucks':
        $rows = $admin->reportTruckSummary();
        $head = ['Plate', 'Type', 'Capacity (t)', 'Status', 'Total Trips', 'Completed Trips', 'Tons Hauled'];
        $map  = fn($r) => [$r['plate_number'], $r['truck_type'], $r['carrying_capacity'], $r['status'], $r['total_trips'], $r['completed_trips'], round((float) $r['tons_hauled'], 2)];
        break;
    case 'hauling':
    default:
        $rows = $admin->reportHauling($from, $to);
        $head = ['Date', 'Time', 'Farmer', 'Driver', 'Plate', 'Ticket', 'Net kg', 'Net Payable', 'Status'];
        $map  = fn($r) => [$r['scheduled_date'], $r['scheduled_time'], $r['farmer_name'], $r['driver_name'], $r['plate_number'], $r['ticket_number'], $r['net_weight'], $r['net_payable'], $r['status']];
        break;
}

$filename = "epalmhaul_{$type}_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, $head);
foreach ($rows as $r) {
    fputcsv($out, $map($r));
}
fclose($out);
exit;
