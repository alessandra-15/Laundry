<?php
/**
 * notifications_helper.php
 * WashFlow — Customer Notification Helper
 * Shared functions for creating + reading notifications
 */
if (!defined('APP_STARTED')) {
    die('Direct access not permitted');
}

class CustomerNotify
{
    /**
     * Create a notification for a customer
     */
    public static function create($conn, $customer_id, $title, $message, $type = 'system', $icon = 'fas fa-bell', $booking_id = null, $link = null)
    {
        $customer_id = (int)$customer_id;
        $booking_id = $booking_id !== null ? (int)$booking_id : null;

        $sql = "INSERT INTO notifications_user 
                (user_id, title, type, icon, message, link, booking_id, status, is_read, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 0, NOW())";

        $stmt = $conn->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param('isssssi',
            $customer_id, $title, $type, $icon, $message, $link, $booking_id
        );

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Get unread count
     */
    public static function unreadCount($conn, $customer_id)
    {
        $customer_id = (int)$customer_id;
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM notifications_user WHERE user_id = ? AND is_read = 0");
        if (!$stmt) return 0;
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $c = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
        $stmt->close();
        return $c;
    }

    /**
     * Get recent notifications
     */
    public static function recent($conn, $customer_id, $limit = 10)
    {
        $customer_id = (int)$customer_id;
        $limit = (int)$limit;
        $stmt = $conn->prepare("
            SELECT notif_id, title, message, type, icon, link, booking_id, is_read, created_at
            FROM notifications_user
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        if (!$stmt) return [];
        $stmt->bind_param('ii', $customer_id, $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Human-readable time ago
     */
    public static function timeAgo($datetime)
    {
        $ts = strtotime($datetime);
        if (!$ts) return '';
        $diff = time() - $ts;

        if ($diff < 60)         return 'Just now';
        if ($diff < 3600)       return floor($diff / 60) . 'm ago';
        if ($diff < 86400)      return floor($diff / 3600) . 'h ago';
        if ($diff < 604800)     return floor($diff / 86400) . 'd ago';
        return date('M j, Y', $ts);
    }

    /**
     * Icon color class per type
     */
    public static function colorClass($type)
    {
        return match ($type) {
            'booking' => 'notif-blue',
            'status'  => 'notif-green',
            'payment' => 'notif-yellow',
            'promo'   => 'notif-red',
            'system'  => 'notif-dark',
            default   => 'notif-dark',
        };
    }
}