<?php
/**
 * notifier.php — Notification orchestrator
 *
 * Single point of dispatch for all outgoing notifications.
 * The rest of the backend calls Notifier::dispatch($event, $orderId)
 * and this class decides which channels fire, to whom, and what to say.
 *
 * Every notification attempt is logged in the notifications_log table
 * so Clarice can see failed sends in the admin dashboard and retry.
 *
 * Events and their notification rules:
 *   'order_placed'      → Email + SMS to customer | Email + WhatsApp to admin
 *   'in_production'     → Email + SMS to customer
 *   'handed_to_pm'      → Email + SMS to customer (with PM tracking reference)
 *   'delivered'         → Email + SMS to customer
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/sms.php';
require_once __DIR__ . '/whatsapp.php';

class Notifier
{
    /* Map of status strings (from orders.status) to event names */
    private const STATUS_TO_EVENT = [
        'received'       => 'order_placed',
        'in_production'  => 'in_production',
        'handed_to_pm'   => 'handed_to_pm',
        'delivered'      => 'delivered',
    ];

    /**
     * Main dispatch entry point.
     *
     * Called after a status change is written to the database.
     * Fetches the order and its items, then fires the appropriate
     * notifications based on which event was triggered.
     *
     * @param string $event    One of: 'order_placed', 'in_production', 'handed_to_pm', 'delivered'
     * @param int    $orderId  Internal order ID (pk from orders table)
     * @param string $note     Optional note from Clarice, forwarded to customer emails/SMS
     */
    public static function dispatch(string $event, int $orderId, string $note = ''): void
    {
        /* Fetch the order from the database */
        $db    = getDB();
        $stmt  = $db->prepare('SELECT * FROM orders WHERE id = :id');
        $stmt->execute([':id' => $orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            error_log("[Notifier] Order ID {$orderId} not found");
            return;
        }

        /* Fetch the order line items */
        $itemStmt = $db->prepare(
            'SELECT oi.*, p.name AS product_name
             FROM order_items oi
             JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = :oid'
        );
        $itemStmt->execute([':oid' => $orderId]);
        $items = $itemStmt->fetchAll();

        /* Route to the correct set of notifications */
        switch ($event) {
            case 'order_placed':
                self::fireOrderPlaced($order, $items);
                break;

            case 'in_production':
                self::fireStatusUpdate($order, 'In Production', $note);
                break;

            case 'handed_to_pm':
                self::fireStatusUpdate($order, 'Handed to Pickup Mtaani', $note);
                break;

            case 'delivered':
                self::fireStatusUpdate($order, 'Delivered', $note);
                break;

            default:
                /* Unknown event — log and do nothing */
                error_log("[Notifier] Unknown event '{$event}' for order {$orderId}");
        }
    }

    /**
     * Convenience method: dispatch from an order status string directly.
     * Used by update_status.php so the caller doesn't need to know the event name.
     *
     * @param string $status   Value from orders.status ENUM
     * @param int    $orderId
     * @param string $note
     */
    public static function dispatchForStatus(string $status, int $orderId, string $note = ''): void
    {
        $event = self::STATUS_TO_EVENT[$status] ?? null;
        if ($event) {
            self::dispatch($event, $orderId, $note);
        }
    }

    /* ── Private fire methods ─────────────────────────────────────── */

    /**
     * Fires all notifications for a newly placed order:
     * - Email confirmation to customer
     * - SMS confirmation to customer
     * - Email alert to Clarice
     * - WhatsApp alert to Clarice
     */
    private static function fireOrderPlaced(array $order, array $items): void
    {
        /* Customer: Email */
        $emailSent = MailerService::sendOrderConfirmation($order, $items);
        self::log($order['id'], 'email', 'customer', $order['customer_email'], 'order_placed', $emailSent);

        /* Customer: SMS */
        $smsSent = SmsService::sendOrderConfirmation($order);
        self::log($order['id'], 'sms', 'customer', $order['customer_phone'], 'order_placed', $smsSent);

        /* Admin: Email */
        $adminEmailSent = MailerService::sendAdminNewOrderAlert($order, $items);
        self::log($order['id'], 'email', 'admin', ADMIN_EMAIL, 'order_placed', $adminEmailSent);

        /* Admin: WhatsApp */
        $waSent = WhatsAppService::sendAdminNewOrderAlert($order, $items);
        self::log($order['id'], 'whatsapp', 'admin', CALLMEBOT_PHONE, 'order_placed', $waSent);
    }

    /**
     * Fires status update notifications to the customer only.
     * Admin is not re-notified on status updates (she's the one changing them).
     *
     * @param array  $order        Order row
     * @param string $statusLabel  Human-readable label shown to customer
     * @param string $note         Optional note from Clarice
     */
    private static function fireStatusUpdate(array $order, string $statusLabel, string $note): void
    {
        /* Customer: Email */
        $emailSent = MailerService::sendStatusUpdate($order, $statusLabel, $note);
        self::log($order['id'], 'email', 'customer', $order['customer_email'], $statusLabel, $emailSent);

        /* Customer: SMS */
        $smsSent = SmsService::sendStatusUpdate($order, $statusLabel, $note);
        self::log($order['id'], 'sms', 'customer', $order['customer_phone'], $statusLabel, $smsSent);
    }

    /**
     * Logs every notification attempt to notifications_log.
     *
     * This gives Clarice visibility into what was sent and what failed.
     * A future admin feature can surface failed logs and offer a "resend" button.
     *
     * @param int    $orderId     Order ID
     * @param string $channel     'email' | 'sms' | 'whatsapp'
     * @param string $recipient   'customer' | 'admin'
     * @param string $destination Email address or phone number
     * @param string $event       Event or status label
     * @param bool   $success     Whether the send succeeded
     * @param string $errorMsg    Error detail if failed
     */
    private static function log(
        int    $orderId,
        string $channel,
        string $recipient,
        string $destination,
        string $event,
        bool   $success,
        string $errorMsg = ''
    ): void {
        try {
            $db   = getDB();
            $stmt = $db->prepare(
                'INSERT INTO notifications_log
                   (order_id, channel, recipient, destination, event, status, error_msg)
                 VALUES
                   (:oid, :channel, :recipient, :dest, :event, :status, :error)'
            );
            $stmt->execute([
                ':oid'       => $orderId,
                ':channel'   => $channel,
                ':recipient' => $recipient,
                ':dest'      => $destination,
                ':event'     => $event,
                ':status'    => $success ? 'sent' : 'failed',
                ':error'     => $success ? null : $errorMsg,
            ]);
        } catch (Exception $e) {
            error_log('[Notifier] Failed to log notification: ' . $e->getMessage());
        }
    }
}
