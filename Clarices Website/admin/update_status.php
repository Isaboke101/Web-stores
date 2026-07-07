<?php
/**
 * admin/update_status.php — Order status update handler
 *
 * POST only. Handles four actions:
 *   'update_status'      — change order status + trigger notifications
 *   'mark_paid'          — manually mark payment as paid
 *   'create_pm_shipment' — create a Pickup Mtaani shipment (mock or live)
 *   'simulate_pm'        — advance mock PM status (demo only)
 *
 * Redirects back to order_detail.php with a flash message.
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/notifier.php';
require_once __DIR__ . '/../services/pickupmtaani.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: orders.php');
    exit;
}

$db      = getDB();
$orderId = (int)($_POST['order_id'] ?? 0);
$action  = $_POST['action'] ?? '';

if (!$orderId) {
    header('Location: orders.php');
    exit;
}

/* Fetch the order to validate it exists */
$orderStmt = $db->prepare('SELECT * FROM orders WHERE id = :id');
$orderStmt->execute([':id' => $orderId]);
$order = $orderStmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

/* Helper: set a flash message in session and redirect back */
function redirectWithFlash(int $orderId, string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header('Location: order_detail.php?id=' . $orderId);
    exit;
}

switch ($action) {

    case 'update_status':
        $newStatus = $_POST['status'] ?? '';
        $note      = trim($_POST['note'] ?? '');

        /* ── Define the strict linear progression ──────────────────
        Admin can only move forward one step at a time.
        After handed_to_pm, Pickup Mtaani controls all updates.
        ─────────────────────────────────────────────────────────── */
        $statusFlow = [
            'received'      => 'in_production',
            'in_production' => 'ready_for_dispatch',
            'ready_for_dispatch' => 'handed_to_pm',
        ];

        /* Statuses that are PM-controlled — admin cannot touch */
        $pmControlled = ['handed_to_pm', 'in_transit', 'out_for_delivery', 'delivered'];

        /* Block if order is already with PM */
        if (in_array($order['status'], $pmControlled)) {
            redirectWithFlash($orderId, 'error',
                'This order is now with Pickup Mtaani. Use the Simulate PM Status button to advance tracking.');
        }

        /* Block if trying to skip ahead or go backwards */
        $allowedNext = $statusFlow[$order['status']] ?? null;
        if (!$allowedNext || $newStatus !== $allowedNext) {
            $label = ucwords(str_replace('_', ' ', $allowedNext ?? 'none'));
            redirectWithFlash($orderId, 'error',
                'Invalid status change. The next required step is: ' . $label);
        }

        /* All checks passed — update the order */
        $db->prepare('UPDATE orders SET status = :status WHERE id = :id')
        ->execute([':status' => $newStatus, ':id' => $orderId]);

        $db->prepare(
            'INSERT INTO order_status_history (order_id, status, note, changed_by)
            VALUES (:oid, :status, :note, "admin")'
        )->execute([':oid' => $orderId, ':status' => $newStatus, ':note' => $note ?: null]);

        /* Auto-create PM shipment when handed over */
        if ($newStatus === 'handed_to_pm') {
            $existing = $db->prepare('SELECT id FROM delivery_tracking WHERE order_id = :oid');
            $existing->execute([':oid' => $orderId]);
            if (!$existing->fetch()) {
                try {
                    PickupMtaaniService::createOrder($order);
                } catch (Exception $e) {
                    error_log('[update_status] PM createOrder failed: ' . $e->getMessage());
                }
            }
        }

        Notifier::dispatchForStatus($newStatus, $orderId, $note);

        redirectWithFlash($orderId, 'success',
            'Status updated to "' . ucwords(str_replace('_', ' ', $newStatus)) . '". Customer notified.');
        break;

    case 'mark_paid':
        $db->prepare(
            'UPDATE orders SET payment_status = "manual" WHERE id = :id AND payment_status != "paid"'
        )->execute([':id' => $orderId]);

        /* Fetch items for the notification */
        $itemStmt = $db->prepare('SELECT * FROM order_items WHERE order_id = :oid');
        $itemStmt->execute([':oid' => $orderId]);

        /* Trigger order_placed notifications now that payment is confirmed */
        Notifier::dispatch('order_placed', $orderId);

        redirectWithFlash($orderId, 'success', 'Order marked as manually paid. Customer notified.');
        break;

    case 'create_pm_shipment':
        try {
            $result = PickupMtaaniService::createOrder($order);
            redirectWithFlash($orderId, 'success',
                'PM shipment created. Reference: ' . $result['pm_reference']);
        } catch (Exception $e) {
            redirectWithFlash($orderId, 'error', 'PM shipment failed: ' . $e->getMessage());
        }
        break;

    case 'simulate_pm':
        $newPmStatus = PickupMtaaniService::mockAdvanceStatus($orderId);
        redirectWithFlash($orderId, 'success', 'PM status advanced to: ' . $newPmStatus);
        break;

    default:
        redirectWithFlash($orderId, 'error', 'Unknown action.');
}
