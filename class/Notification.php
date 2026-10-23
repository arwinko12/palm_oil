<?php
/**
 * Notification — create and read user notifications.
 */
require_once __DIR__ . '/Model.php';

class Notification extends Model
{
    /** Queue one notification for a user. */
    public function send($recipientUserId, $title, $message, $type = 'info', $link = null)
    {
        $this->run(
            "INSERT INTO notifications (recipient_user_id, title, message, type, link)
             VALUES (?, ?, ?, ?, ?)",
            "issss",
            [$recipientUserId, $title, $message, $type, $link]
        );
        return $this->insertId();
    }

    /** Notify every active administrator (used when a farmer submits a request). */
    public function notifyAdmins($title, $message, $type = 'info', $link = null)
    {
        $admins = $this->all(
            "SELECT user_id FROM users WHERE role = 'admin' AND status = 'active'"
        );
        foreach ($admins as $a) {
            $this->send($a['user_id'], $title, $message, $type, $link);
        }
        return count($admins);
    }

    /** All notifications for a user, newest first. */
    public function forUser($userId, $unreadOnly = false, $limit = 50)
    {
        $sql = "SELECT notification_id, title, message, type, link, is_read, created_at
                FROM notifications WHERE recipient_user_id = ?";
        if ($unreadOnly) {
            $sql .= " AND is_read = 0";
        }
        $sql .= " ORDER BY created_at DESC LIMIT " . (int) $limit;
        return $this->all($sql, "i", [$userId]);
    }

    public function unreadCount($userId)
    {
        return (int) $this->scalar(
            "SELECT COUNT(*) FROM notifications WHERE recipient_user_id = ? AND is_read = 0",
            "i",
            [$userId]
        );
    }

    public function markRead($notificationId, $userId)
    {
        $this->run(
            "UPDATE notifications SET is_read = 1
             WHERE notification_id = ? AND recipient_user_id = ?",
            "ii",
            [$notificationId, $userId]
        );
    }

    public function markAllRead($userId)
    {
        $this->run(
            "UPDATE notifications SET is_read = 1 WHERE recipient_user_id = ?",
            "i",
            [$userId]
        );
    }

    /** Look up the user_id tied to a farmer_id / driver_id. */
    public function userIdForFarmer($farmerId)
    {
        return $this->scalar("SELECT user_id FROM farmers WHERE farmer_id = ?", "i", [$farmerId]);
    }

    public function userIdForDriver($driverId)
    {
        return $this->scalar("SELECT user_id FROM drivers WHERE driver_id = ?", "i", [$driverId]);
    }
}
