<?php require_once __DIR__ . '/../includes/view.php';
$payment = new Payment();
$pricing = $payment->listPricing();
$active  = $payment->activePricing();
?>
<div class="mb-3">
    <h4 class="page-title">Pricing &amp; Rates</h4>
    <div class="page-subtitle">Configure the FFB price, incentives, quality bonus and haulage deduction used for every payment.</div>
</div>

<div class="row">
    <div class="col-12 col-xl-5 mb-3">
        <div class="panel">
            <div class="panel-header"><div><div class="panel-title">New Pricing Period</div><div class="panel-subtitle">Takes effect on the chosen date and supersedes the current rate.</div></div></div>
            <div class="p-3">
                <form id="pricingForm" onsubmit="return savePricing(event)">
                    <div class="form-group"><label>FFB price per kg</label><input type="number" step="0.0001" min="0" class="form-control" name="ffb_price_per_kg" id="p_ffb" required></div>
                    <div class="form-group"><label>Haulage rate per ton</label><input type="number" step="0.0001" min="0" class="form-control" name="haulage_rate_ton" id="p_haul" required></div>
                    <div class="row">
                        <div class="col-6 form-group"><label>Incentive rate (fraction)</label><input type="number" step="0.0001" min="0" class="form-control" name="incentive_rate" id="p_inc" placeholder="0.05" required><small class="text-muted">0.05 = 5%</small></div>
                        <div class="col-6 form-group"><label>Quality bonus (fraction)</label><input type="number" step="0.0001" min="0" class="form-control" name="quality_bonus_rate" id="p_qual" placeholder="0.03" required><small class="text-muted">0.03 = 3%</small></div>
                    </div>
                    <div class="form-group"><label>Effective from</label><input type="date" class="form-control" name="effective_from" id="p_from" value="<?= date('Y-m-d') ?>" required></div>
                    <button type="submit" class="btn btn-success btn-block">Save Pricing</button>
                </form>
                <div class="mt-3 p-2 rounded" style="background:#f4f8f5;">
                    <div class="small text-muted">Current formula</div>
                    <div class="small">Net = Gross − Tare · Payable = (kg × price) + incentive + quality − (tons × haulage)</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-7 mb-3">
        <div class="panel">
            <div class="panel-header"><div><div class="panel-title">Pricing History</div><div class="panel-subtitle">Active: <?= $active ? ('#' . (int) $active['pricing_id'] . ' from ' . fdate($active['effective_from'])) : 'none' ?></div></div></div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>ID</th><th>Price/kg</th><th>Haulage/ton</th><th>Incentive</th><th>Quality</th><th>Effective</th><th></th></tr></thead>
                    <tbody>
                    <?php if (!$pricing): ?><tr><td colspan="7" class="text-muted">No pricing configured.</td></tr><?php endif; ?>
                    <?php foreach ($pricing as $p): ?>
                        <tr class="<?= $active && $active['pricing_id'] === $p['pricing_id'] ? 'table-success' : '' ?>">
                            <td>#<?= (int) $p['pricing_id'] ?></td>
                            <td><?= number_format((float) $p['ffb_price_per_kg'], 4) ?></td>
                            <td><?= number_format((float) $p['haulage_rate_ton'], 4) ?></td>
                            <td><?= number_format((float) $p['incentive_rate'] * 100, 2) ?>%</td>
                            <td><?= number_format((float) $p['quality_bonus_rate'] * 100, 2) ?>%</td>
                            <td><?= fdate($p['effective_from']) ?> → <?= $p['effective_to'] ? fdate($p['effective_to']) : 'open' ?></td>
                            <td><?= $active && $active['pricing_id'] === $p['pricing_id'] ? '<span class="status completed">Active</span>' : '' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function savePricing(e) {
    e.preventDefault();
    apiPost('pricing_save', $('#pricingForm').serialize(), function (res) {
        if (res.ok) { flash('Pricing saved.', true); load_pages('pricing.php'); } else flash(res.error || 'Save failed.', false);
    });
    return false;
}
</script>
