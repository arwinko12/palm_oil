<?php
/**
 * Farmer view bootstrap — guards the page, resolves the farmer's own context
 * and exposes small presentation helpers. Every farmer page includes this
 * first, so $farmerId / $userId / $csrf are always available and the visitor
 * is guaranteed to be an authenticated farmer viewing only their own data.
 */
require_once __DIR__ . '/../../includes/access_control.php';
require_role('farmer');

$farmerId = current_farmer_id();
$userId   = (int) Auth::userId();
$csrf     = csrf_token();

function e($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function truck_types()
{
    return ['tipper', 'flatbed', 'container', 'tractor'];
}

/** Colour-coded status pill for request / delivery / schedule states. */
function status_badge($status)
{
    $good    = ['completed', 'approved'];
    $warn    = ['pending', 'scheduled', 'in_transit', 'arrived', 'delivered'];
    $bad     = ['rejected', 'cancelled'];

    if (in_array($status, $good, true)) {
        $cls = 'badge-status badge-completed';
    } elseif (in_array($status, $bad, true)) {
        $cls = 'badge-status badge-cancelled';
    } else {
        $cls = 'badge-status badge-pending';
    }
    $label = ucwords(str_replace('_', ' ', (string) $status));
    return '<span class="' . $cls . '">' . e($label) . '</span>';
}

/**
 * A request has no "completed" status of its own — completion lives on the
 * delivery. Derive the single status a farmer should see for a record.
 */
function display_status(array $row)
{
    $dv = $row['delivery_status'] ?? null;
    if ($dv === 'completed') {
        return 'completed';
    }
    if (in_array($dv, ['in_transit', 'arrived', 'delivered'], true)) {
        return $dv;
    }
    if ($dv === 'cancelled') {
        return 'cancelled';
    }
    return $row['request_status'] ?? 'pending';
}

function money($n)
{
    return number_format((float) $n, 2);
}

function fdate($d)
{
    return $d ? date('M d, Y', strtotime($d)) : '—';
}

function ftime($t)
{
    return $t ? date('h:i A', strtotime($t)) : '—';
}
