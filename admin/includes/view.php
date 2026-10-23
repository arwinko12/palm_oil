<?php
/**
 * Admin view bootstrap — included at the top of every admin page partial.
 * Guards the role, exposes $userId / $csrf and the shared Admin service, and
 * defines the small formatting helpers the views render with.
 */
require_once __DIR__ . '/../../includes/access_control.php';
require_once __DIR__ . '/../../class/Admin.php';
require_once __DIR__ . '/../../class/HaulingRequest.php';
require_once __DIR__ . '/../../class/Scheduling.php';
require_once __DIR__ . '/../../class/Delivery.php';
require_once __DIR__ . '/../../class/ScaleTicket.php';
require_once __DIR__ . '/../../class/Payment.php';
require_once __DIR__ . '/../../class/Notification.php';

require_role('admin');

$admin    = new Admin();
$userId   = (int) Auth::userId();
$userName = Auth::name() ?: 'Administrator';
$csrf     = csrf_token();

if (!function_exists('e')) {
    function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('money')) {
    function money($v) {
        static $cur = null;
        if ($cur === null) {
            $db   = new Database();
            $stmt = $db->connect()->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'currency' LIMIT 1");
            $stmt->execute();
            $row  = $stmt->get_result()->fetch_assoc();
            $cur  = $row ? $row['setting_value'] : 'RM';
        }
        return number_format((float) $v, 2) . ' ' . $cur;
    }
}

if (!function_exists('kg')) {
    function kg($v) { return number_format((float) $v, 2) . ' kg'; }
}

if (!function_exists('tons')) {
    function tons($v) { return number_format((float) $v, 2) . ' t'; }
}

if (!function_exists('fdate')) {
    function fdate($v) { return $v ? date('d M Y', strtotime($v)) : '—'; }
}

if (!function_exists('ftime')) {
    function ftime($v) { return $v ? date('h:i A', strtotime($v)) : '—'; }
}

if (!function_exists('fdtime')) {
    function fdtime($v) { return $v ? date('d M Y, h:i A', strtotime($v)) : '—'; }
}

/** Delivery status -> the badge classes defined in main.css. */
if (!function_exists('status_badge')) {
    function status_badge($status) {
        $map = [
            'scheduled'  => 'scheduled',
            'in_transit' => 'progress-status',
            'arrived'    => 'progress-status',
            'delivered'  => 'progress-status',
            'completed'  => 'completed',
            'cancelled'  => 'pending',
        ];
        $cls = $map[$status] ?? 'pending';
        $label = ucfirst(str_replace('_', ' ', (string) $status));
        return '<span class="status ' . $cls . '">' . e($label) . '</span>';
    }
}

if (!function_exists('req_badge')) {
    function req_badge($status) {
        $map = [
            'pending'   => 'pending',
            'approved'  => 'completed',
            'scheduled' => 'scheduled',
            'rejected'  => 'pending',
            'cancelled' => 'pending',
        ];
        $cls = $map[$status] ?? 'pending';
        return '<span class="status ' . $cls . '">' . e(ucfirst((string) $status)) . '</span>';
    }
}

if (!function_exists('truck_badge')) {
    function truck_badge($status) {
        $map = ['available' => 'completed', 'in_use' => 'progress-status',
                'maintenance' => 'pending', 'retired' => 'scheduled'];
        $cls = $map[$status] ?? 'pending';
        return '<span class="status ' . $cls . '">' . e(ucfirst(str_replace('_', ' ', (string) $status))) . '</span>';
    }
}

if (!function_exists('avail_badge')) {
    function avail_badge($availability) {
        $map = ['available' => 'completed', 'on_trip' => 'progress-status', 'unavailable' => 'pending'];
        $cls = $map[$availability] ?? 'pending';
        return '<span class="status ' . $cls . '">' . e(ucfirst(str_replace('_', ' ', (string) $availability))) . '</span>';
    }
}

if (!function_exists('account_badge')) {
    function account_badge($status) {
        $cls = $status === 'active' ? 'completed' : 'pending';
        return '<span class="status ' . $cls . '">' . e(ucfirst((string) $status)) . '</span>';
    }
}
