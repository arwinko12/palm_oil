<?php
require_once __DIR__ . '/../includes/view.php';
require_once __DIR__ . '/../../class/HaulingRequest.php';

$rows = (new HaulingRequest())->historyForFarmer($farmerId);
?>
<h4 class="mb-3">HAULING RECORDS &amp; PAYMENTS</h4>
<p class="text-secondary" style="font-size:12px;">
    Your historical hauling and delivery transactions, with scale tickets and the final payment breakdown.
</p>

<div class="card mb-4">
    <div class="card-body">
        <h6 class="mb-3"><i class="bi bi-truck me-1"></i> Delivery History</h6>
        <div class="table-responsive">
            <table class="table custom-table">
                <thead>
                    <tr>
                        <th>Ref</th>
                        <th>Preferred</th>
                        <th>Driver</th>
                        <th>Truck</th>
                        <th>Delivery Status</th>
                        <th>Departure</th>
                        <th>Completion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="7" class="text-center text-secondary py-4">No records yet.</td></tr>
                    <?php else: foreach ($rows as $row): ?>
                        <tr>
                            <td class="fw-bold">REQ-<?= (int) $row['request_id'] ?></td>
                            <td><?= e(fdate($row['preferred_date'])) ?></td>
                            <td><?= e($row['driver_name'] ?: '—') ?></td>
                            <td><?= e($row['plate_number'] ?: '—') ?></td>
                            <td><?= status_badge(display_status($row)) ?></td>
                            <td><?= e($row['departure_time'] ?: '—') ?></td>
                            <td><?= e($row['completion_time'] ?: '—') ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h6 class="mb-3"><i class="bi bi-receipt me-1"></i> Scale Tickets &amp; Payments</h6>
        <div class="table-responsive">
            <table class="table custom-table">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Grade</th>
                        <th>Gross (kg)</th>
                        <th>Tare (kg)</th>
                        <th>Net (kg)</th>
                        <th>Gross Pay</th>
                        <th>Incentive</th>
                        <th>Quality Bonus</th>
                        <th>Deductions</th>
                        <th>Net Payable</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $hasTicket = false;
                    foreach ($rows as $row): if (empty($row['ticket_id'])) { continue; } $hasTicket = true; ?>
                        <tr>
                            <td class="fw-bold"><?= e($row['ticket_number']) ?></td>
                            <td><?= e($row['fruit_grade'] ?: '—') ?></td>
                            <td><?= money($row['gross_weight']) ?></td>
                            <td><?= money($row['tare_weight']) ?></td>
                            <td class="fw-bold"><?= money($row['net_weight']) ?></td>
                            <td><?= money($row['gross_payment']) ?></td>
                            <td class="text-success">+<?= money($row['incentive_amount']) ?></td>
                            <td class="text-success">+<?= money($row['quality_bonus']) ?></td>
                            <td class="text-danger">-<?= money($row['total_deductions']) ?></td>
                            <td class="fw-bold text-success"><?= money($row['net_payable']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$hasTicket): ?>
                        <tr><td colspan="10" class="text-center text-secondary py-4">
                            No scale tickets yet. Tickets appear after the weighbridge records your delivery.
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
