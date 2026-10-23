<?php
/**
 * Standalone printable scale ticket for the admin (§48-49 review + print).
 * Guarded by the admin role — separate from the weighbridge operator print
 * screen so each portal stays within its own access boundary.
 */
require_once __DIR__ . '/../../includes/access_control.php';
require_once __DIR__ . '/../../class/ScaleTicket.php';
require_once __DIR__ . '/../includes/view.php';

require_role('admin');

$bundle = (new ScaleTicket())->ticketBundle((int) ($_GET['ticket_id'] ?? 0));
if (!$bundle) {
    echo '<div style="font-family:Arial;padding:40px;">Ticket not found.</div>';
    exit;
}
$pay = $bundle['payment'] ?? [];
$companyName = 'ePalmHaul';
$db  = new Database();
$cs  = $db->connect()->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'company_name' LIMIT 1");
$cs->execute();
$cr = $cs->get_result()->fetch_assoc();
if ($cr) { $companyName = $cr['setting_value']; }
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Scale Ticket <?= e($bundle['ticket_number']) ?></title>
<style>
    body { font-family: Arial, sans-serif; color: #222; margin: 30px; }
    h2 { margin: 0 0 4px; } .muted { color: #666; font-size: 12px; }
    .box { border: 1px solid #ccc; border-radius: 8px; padding: 18px 22px; max-width: 640px; margin-top: 16px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    td { padding: 6px 4px; border-bottom: 1px solid #eee; font-size: 14px; }
    td.k { color: #666; width: 45%; } .total { font-weight: 800; font-size: 16px; }
    .actions { margin: 16px 0; } .actions button { padding: 8px 14px; font-size: 13px; cursor: pointer; }
    @media print { .actions { display: none; } }
</style></head>
<body>
    <div class="actions"><button onclick="window.print()">Print</button> <button onclick="window.close()">Close</button></div>
    <h2><?= e($companyName) ?></h2>
    <div class="muted">Scale Ticket &nbsp;•&nbsp; <?= e($bundle['ticket_number']) ?></div>

    <div class="box">
        <table>
            <tr><td class="k">Farmer</td><td><?= e($bundle['farmer_name']) ?></td></tr>
            <tr><td class="k">Contact</td><td><?= e($bundle['contact_no'] ?: '—') ?></td></tr>
            <tr><td class="k">Truck / Plate</td><td><?= e($bundle['truck_type'] ?? '') ?> — <?= e($bundle['plate_number'] ?: '—') ?></td></tr>
            <tr><td class="k">Farm location</td><td><?= e($bundle['farm_location'] ?: '—') ?></td></tr>
            <tr><td class="k">Harvest date</td><td><?= fdate($bundle['harvest_date']) ?></td></tr>
            <tr><td class="k">Fruit grade</td><td><?= e($bundle['fruit_grade'] ?: '—') ?></td></tr>
            <tr><td class="k">Registered at</td><td><?= fdtime($bundle['registered_at']) ?></td></tr>
            <tr><td class="k">Weighed by</td><td><?= e($bundle['weighed_by_name'] ?: '—') ?></td></tr>
        </table>

        <table>
            <tr><td class="k">Gross weight</td><td><?= kg($bundle['gross_weight']) ?></td></tr>
            <tr><td class="k">Tare weight</td><td><?= kg($bundle['tare_weight']) ?></td></tr>
            <tr><td class="k total">Net weight</td><td class="total"><?= kg($bundle['net_weight']) ?></td></tr>
            <tr><td class="k">Gross payment</td><td><?= money($pay['gross_payment'] ?? 0) ?></td></tr>
            <tr><td class="k">Incentive</td><td><?= money($pay['incentive_amount'] ?? 0) ?></td></tr>
            <tr><td class="k">Quality bonus</td><td><?= money($pay['quality_bonus'] ?? 0) ?></td></tr>
            <tr><td class="k">Haulage deduction</td><td>- <?= money($pay['haulage_deduction'] ?? 0) ?></td></tr>
            <tr><td class="k total">Net payable</td><td class="total"><?= money($pay['net_payable'] ?? 0) ?></td></tr>
        </table>
    </div>
    <div class="muted" style="margin-top:18px;">This is a system-generated record from ePalmHaul.</div>
    <script>window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 300); });</script>
</body></html>
