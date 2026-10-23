<?php
/**
 * Driver view bootstrap — guards the page, resolves the driver's own context
 * and exposes small presentation helpers. Every driver page includes this
 * first, so $driverId / $userId / $csrf are always available and the visitor
 * is guaranteed to be an authenticated driver viewing only their own
 * assignments (§66-79). Financial data is never surfaced to drivers.
 */
require_once __DIR__ . '/../../includes/access_control.php';
require_role('driver');

$driverId = current_driver_id();
$userId   = (int) Auth::userId();
$csrf     = csrf_token();

if (!$driverId) {
    // Authenticated as a driver role but no drivers profile row exists.
    http_response_code(500);
    exit('No driver profile is linked to this account.');
}

function e($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/** Colour-coded status pill for delivery / schedule states. */
function status_badge($status)
{
    $good = ['completed', 'delivered'];
    $bad  = ['cancelled'];
    $info = ['in_transit', 'arrived'];

    if (in_array($status, $good, true)) {
        $cls = 'badge-status badge-completed';
    } elseif (in_array($status, $bad, true)) {
        $cls = 'badge-status badge-cancelled';
    } elseif (in_array($status, $info, true)) {
        $cls = 'badge-status badge-transit';
    } else {
        $cls = 'badge-status badge-pending';
    }
    $label = ucwords(str_replace('_', ' ', (string) $status));
    return '<span class="' . $cls . '">' . e($label) . '</span>';
}

/** Availability pill (available / on_trip / unavailable). */
function availability_badge($status)
{
    $map = [
        'available'   => 'badge-completed',
        'on_trip'     => 'badge-transit',
        'unavailable' => 'badge-cancelled',
    ];
    $cls   = $map[$status] ?? 'badge-pending';
    $label = ucwords(str_replace('_', ' ', (string) $status));
    return '<span class="badge-status ' . $cls . '">' . e($label) . '</span>';
}

/** Human verb for a driver-permitted target status. */
function action_label($status)
{
    return ['in_transit' => 'Start Trip / Record Departure'][$status]
        ?? ucwords(str_replace('_', ' ', (string) $status));
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

function fdtime($dt)
{
    return $dt ? date('M d, Y h:i A', strtotime($dt)) : '—';
}
