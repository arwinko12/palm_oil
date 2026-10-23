<?php
/**
 * print_report.php — a clean, printable rendering of any admin report (§48).
 * Opened in a new tab from the Reports page; auto-invokes the print dialog.
 */
require_once __DIR__ . '/../../includes/access_control.php';
require_once __DIR__ . '/../includes/view.php';
require_role('admin');

$type = $_GET['type'] ?? 'hauling';
$from = $_GET['from'] ?? '';
$to   = $_GET['to'] ?? '';

$company = 'ePalmHaul';
$db = new Database();
$cs = $db->connect()->prepare("SELECT setting_value FROM system_settings WHERE setting_key='company_name' LIMIT 1");
$cs->execute(); $cr = $cs->get_result()->fetch_assoc(); if ($cr) $company = $cr['setting_value'];

switch ($type) {
    case 'farmers': $rows = $admin->reportFarmerSummary(); $title = 'Farmer Summary Report';
        $cols = ['Farmer','Location','Requests','Completed','Total kg','Total Payable'];
        $cell = fn($r) => [$r['fullname'], $r['farm_location'], $r['requests'], $r['completed_hauls'], number_format((float)$r['total_kg'],1), money($r['total_payable'])]; break;
    case 'drivers': $rows = $admin->reportDriverSummary(); $title = 'Driver Performance Report';
        $cols = ['Driver','Availability','Total Trips','Completed Trips'];
        $cell = fn($r) => [$r['fullname'], $r['availability'], $r['total_trips'], $r['completed_trips']]; break;
    case 'trucks':  $rows = $admin->reportTruckSummary(); $title = 'Truck Utilization Report';
        $cols = ['Plate','Type','Capacity (t)','Status','Total Trips','Completed','Tons Hauled'];
        $cell = fn($r) => [$r['plate_number'], $r['truck_type'], $r['carrying_capacity'], $r['status'], $r['total_trips'], $r['completed_trips'], number_format((float)$r['tons_hauled'],2)]; break;
    default:
        $type = 'hauling'; $rows = $admin->reportHauling($from ?: null, $to ?: null); $title = 'Hauling & Payment Report';
        $range = ($from || $to) ? ('  (' . ($from ?: '…') . ' → ' . ($to ?: '…') . ')') : '';
        $title .= $range;
        $cols = ['Date','Farmer','Driver','Plate','Ticket','Net (kg)','Net Payable','Status'];
        $cell = fn($r) => [fdate($r['scheduled_date']), $r['farmer_name'], $r['driver_name'] ?: '—', $r['plate_number'] ?: '—', $r['ticket_number'] ?: '—', $r['net_weight'] !== null ? number_format((float)$r['net_weight'],2) : '—', isset($r['net_payable']) ? money($r['net_payable']) : '—', $r['status']]; break;
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title><?= e($title) ?></title>
<style>
    body { font-family: Arial, sans-serif; color: #222; margin: 28px; }
    h1 { font-size: 20px; margin: 0; } .sub { color: #666; font-size: 12px; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; font-size: 12px; }
    th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
    th { background: #f0f4f0; } .btn { padding: 8px 14px; margin-bottom: 16px; cursor: pointer; }
    @media print { .btn { display: none; } }
</style></head>
<body>
    <button class="btn" onclick="window.print()">Print</button>
    <h1><?= e($company) ?></h1>
    <div class="sub"><?= e($title) ?> &nbsp;•&nbsp; Generated <?= date('d M Y, h:i A') ?> &nbsp;•&nbsp; <?= count($rows) ?> record(s)</div>
    <table>
        <thead><tr><?php foreach ($cols as $c): ?><th><?= e($c) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="<?= count($cols) ?>">No records.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): $vals = $cell($r); ?>
            <tr><?php foreach ($vals as $v): ?><td><?= e($v) ?></td><?php endforeach; ?></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <script>window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 300); });</script>
</body></html>
