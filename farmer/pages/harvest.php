<?php
require_once __DIR__ . '/../includes/view.php';
require_once __DIR__ . '/../../class/HarvestCycle.php';
require_once __DIR__ . '/../../class/FarmerProfile.php';

$cycle    = new HarvestCycle();
$days     = $cycle->cycleDays();
$nextDate = $cycle->nextHarvestDate($farmerId);
$history  = $cycle->history($farmerId);
$profile  = (new FarmerProfile())->get($farmerId);
$lastDate = $profile['last_harvest_date'] ?? null;

$daysAway = null;
if ($nextDate) {
    $daysAway = (int) round((strtotime($nextDate) - strtotime(date('Y-m-d'))) / 86400);
}
?>
<h4 class="mb-3">HARVEST SCHEDULE</h4>

<div class="row g-3">
    <div class="col-12 col-md-5">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="text-secondary" style="font-size:12px;">Next Expected Harvest</div>
                <div class="my-2" style="font-size:26px;font-weight:800;">
                    <?= $nextDate ? e(fdate($nextDate)) : '—' ?>
                </div>
                <?php if ($nextDate && $daysAway !== null): ?>
                    <span class="badge <?= $daysAway < 0 ? 'text-bg-danger' : ($daysAway <= 3 ? 'text-bg-warning' : 'text-bg-success') ?>"
                          style="font-size:13px;">
                        <?= $daysAway < 0 ? abs($daysAway) . ' day(s) overdue' : $daysAway . ' day(s) to go' ?>
                    </span>
                <?php else: ?>
                    <div class="text-secondary" style="font-size:12px;">
                        No confirmed harvest yet. The <?= (int) $days ?>-day cycle begins after your first completed haul.
                    </div>
                <?php endif; ?>

                <hr>
                <div class="d-flex justify-content-between" style="font-size:13px;">
                    <span class="text-secondary">Cycle length</span><strong><?= (int) $days ?> days</strong>
                </div>
                <div class="d-flex justify-content-between mt-2" style="font-size:13px;">
                    <span class="text-secondary">Last confirmed harvest</span><strong><?= e(fdate($lastDate)) ?></strong>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-7">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="mb-3"><i class="bi bi-clock-history me-1"></i> Harvest Cycle History</h6>
                <div class="table-responsive">
                    <table class="table custom-table">
                        <thead>
                            <tr>
                                <th>Confirmed Harvest</th>
                                <th>Next Expected</th>
                                <th>Cycle (days)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="cycleBody">
                            <?php if (empty($history)): ?>
                                <tr><td colspan="4" class="text-center text-secondary py-4">
                                    No harvest cycles recorded yet.
                                </td></tr>
                            <?php else: foreach ($history as $c): ?>
                                <tr>
                                    <td><?= e(fdate($c['previous_harvest_date'])) ?></td>
                                    <td><?= e(fdate($c['next_harvest_date'])) ?></td>
                                    <td><?= (int) $c['cycle_days'] ?></td>
                                    <td><span class="badge-status badge-completed">Confirmed</span></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-secondary mt-2" style="font-size:11px;">
                    The <?= (int) $days ?>-day cycle is a planning aid — it is only recalculated when an actual
                    harvest is confirmed by a completed delivery, never assumed.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const HS_CSRF = '<?= e($csrf) ?>';
</script>
