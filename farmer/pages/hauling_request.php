<?php
require_once __DIR__ . '/../includes/view.php';
require_once __DIR__ . '/../../class/HaulingRequest.php';

$requests = (new HaulingRequest())->historyForFarmer($farmerId);
$defaultLoc = '';
?>
<div class="row">
    <div class="col-sm-12">
        <h4 class="mb-3">HAULING REQUESTS</h4>

        <div class="card">
            <div class="card-body">

                <button type="button" class="btn btn-success btn-sm mb-3" data-bs-toggle="collapse"
                        data-bs-target="#add_hauling_req" aria-expanded="false" aria-controls="add_hauling_req">
                    <i class="bi bi-file-earmark-plus"></i> New Request
                </button>

                <div class="collapse" id="add_hauling_req">
                    <div class="card card-body">
                        <div class="row g-3">
                            <div class="col-sm-4">
                                <label class="form-label">Preferred Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="preferred_date"
                                       min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label">Preferred Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="preferred_time">
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label">Farm Location <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="farm_location" value="<?= e($defaultLoc) ?>">
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label">Estimated FFB (tons) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" class="form-control" id="estimated_fresh_weight">
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label">Requested Truck Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="requested_truck_type">
                                    <?php foreach (truck_types() as $tt): ?>
                                        <option value="<?= e($tt) ?>"><?= e(ucwords($tt)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label">Distance to Mill (km)</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="distance_to_mill" value="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" rows="2" placeholder="Optional instructions"></textarea>
                            </div>
                        </div>
                        <br>
                        <button type="button" onclick="add_new_request()" class="btn btn-sm btn-success">Submit Request</button>
                    </div>
                </div>

                <div class="table-responsive mt-2">
                    <table class="table custom-table">
                        <thead>
                            <tr>
                                <th>Ref</th>
                                <th>Preferred Date</th>
                                <th>Time</th>
                                <th>Location</th>
                                <th>Est. Tons</th>
                                <th>Truck Type</th>
                                <th>Assigned Driver</th>
                                <th>Truck</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="reqBody">
                            <?php if (empty($requests)): ?>
                                <tr><td colspan="9" class="text-center text-secondary py-4">No hauling requests yet.</td></tr>
                            <?php else: foreach ($requests as $row): ?>
                                <tr>
                                    <td class="fw-bold">REQ-<?= (int) $row['request_id'] ?></td>
                                    <td><?= e(fdate($row['preferred_date'])) ?></td>
                                    <td><?= e(ftime($row['preferred_time'])) ?></td>
                                    <td><?= e($row['farm_location']) ?></td>
                                    <td><?= money($row['estimated_fresh_weight']) ?></td>
                                    <td><?= e(ucwords($row['requested_truck_type'])) ?></td>
                                    <td><?= e($row['driver_name'] ?: '—') ?></td>
                                    <td><?= e($row['plate_number'] ? $row['plate_number'] . ' (' . ucwords($row['truck_type']) . ')' : '—') ?></td>
                                    <td><?= status_badge(display_status($row)) ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-secondary mt-2" style="font-size:11px;">
                    Approved requests are scheduled by the administrator, who assigns a driver and truck.
                    You'll be notified once your schedule is confirmed.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const HR_CSRF = '<?= e($csrf) ?>';

function add_new_request() {
    const payload = {
        action: 'submit',
        csrf_token: HR_CSRF,
        preferred_date: $('#preferred_date').val(),
        preferred_time: $('#preferred_time').val(),
        farm_location: $('#farm_location').val(),
        estimated_fresh_weight: $('#estimated_fresh_weight').val(),
        requested_truck_type: $('#requested_truck_type').val(),
        distance_to_mill: $('#distance_to_mill').val() || 0,
        notes: $('#notes').val()
    };

    if (!payload.preferred_date || !payload.preferred_time || !payload.farm_location
        || !payload.estimated_fresh_weight) {
        Swal.fire({ icon: 'error', title: 'Oops...', text: 'Please complete all required fields.' });
        return false;
    }

    $.post('query/request_api.php', payload, function (res) {
        if (res.ok) {
            Swal.fire({ icon: 'success', title: 'Submitted', text: 'Your request has been received and is pending review.' })
                .then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'Not submitted', text: res.error || 'Please check your input.' });
        }
    }, 'json').fail(function () {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Request failed. Try again.' });
    });
}
</script>
