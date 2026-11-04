<?php
/**
 * booking_sync.php
 * WashFlow — keeps bookings, payments and tracking in step.
 *
 * InfinityFree does not allow CREATE TRIGGER, so anything that must happen
 * when a payment or booking changes has to be done here in PHP.
 *
 * @file booking_sync.php
 */

/**
 * SQL condition that skips the empty GCash/Pending row the local
 * after_online_booking_insert trigger adds to every new booking.
 * That row means the customer has not paid anything yet.
 */
function wf_real_online_payment_sql(string $alias = 'p'): string {
    return "NOT ($alias.payment_status = 'Pending' AND $alias.payment_method = 'GCash'
                 AND COALESCE($alias.payment_proof, '') = '')";
}

/**
 * Does this booking have a payment, so it may be marked Completed?
 * Online: paid, or a cash/GCash payment the customer already submitted.
 * Walk-in: paid. A pending walk-in payment means nothing was collected yet.
 */
function wf_booking_has_payment(mysqli $conn, string $type, int $id): bool {
    if ($type === 'walkin') {
        $sql = "SELECT 1 FROM payments p WHERE p.booking_id = ? AND p.payment_status = 'Paid' LIMIT 1";
    } else {
        $sql = "SELECT 1 FROM payments_online p
                WHERE p.booking_id = ? AND p.payment_status IN ('Paid','Pending')
                  AND " . wf_real_online_payment_sql('p') . " LIMIT 1";
    }
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $found = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    return $found;
}

/**
 * Update an online booking after one of its payments was marked Paid.
 */
function wf_settle_online_booking(mysqli $conn, int $booking_id): void {
    $stmt = $conn->prepare("
        UPDATE booking_online b
        SET b.payment_status = IF(
                (SELECT COALESCE(SUM(p.amount), 0) FROM payments_online p
                 WHERE p.booking_id = b.id AND p.payment_status = 'Paid') >= b.total_amount,
                'Paid', 'Partially Paid'),
            b.status = IF(b.status = 'Pending', 'Processing', b.status)
        WHERE b.id = ?
    ");
    $stmt->bind_param('i', $booking_id);
    $stmt->execute();
    $stmt->close();
}

/**
 * Show a completed booking as Completed in the Tracking Monitor.
 * Online bookings without a schedule have no tracking row; the monitor reads
 * those straight from booking_online.
 */
function wf_record_tracking_completed(mysqli $conn, int $customer_id, ?int $schedule_id): void {
    if (empty($schedule_id)) {
        return;
    }
    try {
        $stmt = $conn->prepare("
            SELECT Tracking_ID, laundry_status FROM tracking
            WHERE Schedule_ID = ?
            ORDER BY (laundry_status = 'Completed') DESC, Tracking_ID DESC
            LIMIT 1
        ");
        $stmt->bind_param('i', $schedule_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row && $row['laundry_status'] === 'Completed') {
            return;
        }

        if ($row) {
            $tracking_id = (int)$row['Tracking_ID'];
            $stmt = $conn->prepare("
                UPDATE tracking
                SET laundry_status = 'Completed', Completed_time = CURTIME(),
                    tracking_time = CURTIME(), tracking_date = CURDATE()
                WHERE Tracking_ID = ?
            ");
            $stmt->bind_param('i', $tracking_id);
        } else {
            $stmt = $conn->prepare("
                INSERT INTO tracking
                    (Customer_ID, Schedule_ID, laundry_status, Completed_time, tracking_time, tracking_date)
                VALUES (?, ?, 'Completed', CURTIME(), CURTIME(), CURDATE())
            ");
            $stmt->bind_param('ii', $customer_id, $schedule_id);
        }
        $stmt->execute();
        $stmt->close();
    } catch (Exception $ex) {
        if (class_exists('Logger')) {
            Logger::error('Tracking completion sync failed', ['error' => $ex->getMessage(), 'schedule_id' => $schedule_id]);
        }
    }
}

/**
 * Bookings completed today, walk-in and online.
 * Walk-ins have no completed_at column, so their completion date comes from
 * tracking. Bookings created and completed today count either way.
 */
function wf_completed_today(mysqli $conn): array {
    $out = ['walkin' => 0, 'online' => 0, 'revenue' => 0.0];

    $q = $conn->query("
        SELECT COUNT(DISTINCT b.Booking_ID) c, COALESCE(SUM(b.total_amount), 0) s
        FROM booking b
        WHERE b.status = 'Completed'
          AND (DATE(b.booking_date) = CURDATE() OR EXISTS (
                SELECT 1 FROM tracking t
                WHERE t.Schedule_ID = b.Schedule_ID
                  AND t.laundry_status = 'Completed'
                  AND t.tracking_date = CURDATE()))
    ");
    if ($q) {
        $row = $q->fetch_assoc();
        $out['walkin']  = (int)$row['c'];
        $out['revenue'] = (float)$row['s'];
    }

    $q = $conn->query("
        SELECT COUNT(*) c, COALESCE(SUM(total_amount), 0) s
        FROM booking_online
        WHERE status = 'Completed' AND DATE(COALESCE(completed_at, `timestamp`)) = CURDATE()
    ");
    if ($q) {
        $row = $q->fetch_assoc();
        $out['online']   = (int)$row['c'];
        $out['revenue'] += (float)$row['s'];
    }

    return $out;
}
