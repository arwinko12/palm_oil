<?php
/**
 * Standalone printable scale ticket (§93). Opened in its own window from the
 * tickets list, so it renders a full HTML document (not an AJAX partial) and
 * triggers the browser print dialog automatically.
 */
require_once __DIR__ . '/../../includes/access_control.php';
require_once __DIR__ . '/../../class/ScaleTicket.php';

require_role('weighbridge');

$ticketId = (int) ($_GET['ticket_id'] ?? 0);
$ticket   = (new ScaleTicket())->ticketBundle($ticketId);

if (!$ticket) {
    http_response_code(404);
    exit('Ticket not found.');
}

// Company header from system settings (best-effort).
$db  = new Database();
$cn  = $db->connect();
$stm = $cn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'company_name' LIMIT 1");
$company = 'ePalmHaul';
if ($stm && $stm->execute()) {
    $r = $stm->get_result()->fetch_assoc();
    if ($r) { $company = $r['setting_value']; }
}

function p($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function pm($v) { return number_format((float) $v, 2); }
$pay = $ticket['payment'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scale Ticket <?= p($ticket['ticket_number']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f1; margin: 0; padding: 20px; color: #19342c; }
        .receipt { width: 340px; margin: auto; background: #fff; border: 1px solid #d7e0da; border-radius: 10px; padding: 20px; }
        h3 { text-align: center; margin: 0 0 2px; font-size: 18px; }
        .sub { text-align: center; color: #7b877f; font-size: 11px; margin-bottom: 14px; }
        .row { display: flex; justify-content: space-between; font-size: 12px; padding: 4px 0; border-bottom: 1px dashed #edf2ef; }
        .row span:first-child { color: #7b877f; }
        .row span:last-child { font-weight: 600; }
        .total { background: #e1f5e8; border-radius: 8px; padding: 10px 12px; margin-top: 12px; display: flex; justify-content: space-between; font-weight: 800; font-size: 15px; color: #16864b; }
        .stamp { text-align: center; color: #b5c0b9; font-size: 10px; margin-top: 16px; }
        .actions { text-align: center; margin-top: 16px; }
        button { background: #16864b; color: #fff; border: none; border-radius: 8px; padding: 10px 18px; font-size: 13px; cursor: pointer; }
        @media print { body { background: #fff; padding: 0; } .actions { display: none; } .receipt { border: none; } }
    </style>
</head>
<body>
    <div class="receipt">
        <h3><?= p($company) ?></h3>
        <div class="sub">Official Scale Ticket<br><?= p($ticket['ticket_number']) ?></div>

        <div class="row"><span>Farmer</span><span><?= p($ticket['farmer_name']) ?></span></div>
        <div class="row"><span>Contact</span><span><?= p($ticket['contact_no'] ?: '—') ?></span></div>
        <div class="row"><span>Truck Plate</span><span><?= p($ticket['plate_number']) ?></span></div>
        <div class="row"><span>Truck Type</span><span><?= p($ticket['truck_type']) ?></span></div>
        <div class="row"><span>Farm Location</span><span><?= p($ticket['farm_location'] ?: '—') ?></span></div>
        <div class="row"><span>Harvest Date</span><span><?= p($ticket['harvest_date'] ? date('M d, Y', strtotime($ticket['harvest_date'])) : '—') ?></span></div>
        <div class="row"><span>Fruit Grade</span><span><?= p($ticket['fruit_grade'] ?: '—') ?></span></div>
        <div class="row"><span>Registered</span><span><?= p(date('M d, Y h:i A', strtotime($ticket['registered_at']))) ?></span></div>

        <div class="row"><span>Gross Weight</span><span><?= pm($ticket['gross_weight']) ?> kg</span></div>
        <div class="row"><span>Tare Weight</span><span><?= pm($ticket['tare_weight']) ?> kg</span></div>
        <div class="row"><span>Net Weight</span><span><?= pm($ticket['net_weight']) ?> kg</span></div>

        <div class="row"><span>Gross Payment</span><span><?= pm($pay['gross_payment'] ?? 0) ?></span></div>
        <div class="row"><span>Incentive</span><span>+<?= pm($pay['incentive_amount'] ?? 0) ?></span></div>
        <div class="row"><span>Quality Bonus</span><span>+<?= pm($pay['quality_bonus'] ?? 0) ?></span></div>
        <div class="row"><span>Haulage Deduction</span><span>-<?= pm($pay['total_deductions'] ?? 0) ?></span></div>

        <div class="total"><span>Net Payable</span><span><?= pm($pay['net_payable'] ?? 0) ?></span></div>

        <div class="stamp">Weighed by <?= p($ticket['weighed_by_name'] ?: 'Weighbridge Operator') ?></div>
        <div class="actions"><button onclick="window.print()">Print</button></div>
    </div>
    <script>window.onload = function () { window.print(); };</script>
</body>
</html>
