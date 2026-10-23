<?php
require_once __DIR__ . '/../includes/view.php';
require_once __DIR__ . '/../../class/FarmerProfile.php';
require_once __DIR__ . '/../../class/HarvestCycle.php';

$profile  = (new FarmerProfile())->get($farmerId);
$nextDate = (new HarvestCycle())->nextHarvestDate($farmerId);
?>
<h4 class="mb-3">MY FARM &amp; PROFILE</h4>

<div class="row g-3">
    <div class="col-12 col-xl-8">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3"><i class="bi bi-person-lines-fill me-1"></i> Profile &amp; Farm Information</h6>

                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="fullname" value="<?= e($profile['fullname'] ?? Auth::name()) ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="contact_no" value="<?= e($profile['contact_no'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Farm Location <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="farm_location" rows="2"><?= e($profile['farm_location'] ?? '') ?></textarea>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Estimated Harvest Volume (tons) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" id="estimated_tons"
                               value="<?= e($profile['estimated_tons'] ?? '0') ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Distance to Mill (km) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" id="distance_to_mill"
                               value="<?= e($profile['distance_to_mill'] ?? '0') ?>">
                    </div>
                </div>

                <div class="text-secondary mt-3" style="font-size:11px;">
                    Your <strong>last harvest date</strong> is confirmed automatically when a haul is completed,
                    so it can't be edited here — it reflects real operational records.
                </div>

                <button class="btn btn-sm btn-success mt-3" onclick="save_profile()">
                    <i class="bi bi-save me-1"></i> Save Changes
                </button>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="mb-3"><i class="bi bi-calendar-heart me-1"></i> Harvest Snapshot</h6>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-secondary">Account status</span>
                    <strong><?= e(ucfirst($profile['status'] ?? 'active')) ?></strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-secondary">Last confirmed harvest</span>
                    <strong><?= e(fdate($profile['last_harvest_date'] ?? null)) ?></strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-secondary">Next expected harvest</span>
                    <strong><?= e(fdate($nextDate)) ?></strong>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-secondary">Member since</span>
                    <strong><?= e(fdate($profile['created_at'] ?? null)) ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const MF_CSRF = '<?= e($csrf) ?>';

function save_profile() {
    const payload = {
        action: 'save_profile',
        csrf_token: MF_CSRF,
        fullname: $('#fullname').val(),
        contact_no: $('#contact_no').val(),
        farm_location: $('#farm_location').val(),
        estimated_tons: $('#estimated_tons').val(),
        distance_to_mill: $('#distance_to_mill').val()
    };

    $.post('query/request_api.php', payload, function (res) {
        if (res.ok) {
            Swal.fire({ icon: 'success', title: 'Saved', text: 'Your profile has been updated.' });
        } else {
            Swal.fire({ icon: 'error', title: 'Not saved', text: res.error || 'Please check your input.' });
        }
    }, 'json').fail(function () {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Save failed. Try again.' });
    });
}
</script>
