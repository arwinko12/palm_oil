<?php
/**
 * Weighbridge view bootstrap — guards the page and exposes presentation
 * helpers. Every weighbridge page includes this first, so $userId / $csrf are
 * available and the visitor is guaranteed to be an authenticated operator.
 */
require_once __DIR__ . '/../../includes/access_control.php';
require_role('weighbridge');

$userId = (int) Auth::userId();
$csrf   = csrf_token();

function e($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/** Standard FFB fruit grades offered on the ticket form. */
function fruit_grades()
{
    return ['FFB Grade A', 'FFB Grade B', 'FFB Grade C', 'Loose', 'Underripe', 'Overripe', 'Empty Bunches'];
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

function money($n)
{
    return number_format((float) $n, 2);
}

function kg($n)
{
    return number_format((float) $n, 2) . ' kg';
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
