<?php
/**
 * pickupmtaani.php — Pickup Mtaani delivery integration
 *
 * Provides a unified interface that works in two modes:
 *
 *   MOCK MODE (PM_USE_MOCK=true in .env):
 *     All responses are simulated locally. No external API call is made.
 *     PM tracking data is stored in the delivery_tracking table.
 *     Status can be advanced via the admin dashboard "Simulate Next Status" button.
 *     This lets the full delivery flow be demonstrated without a real PM account.
 *
 *   LIVE MODE (PM_USE_MOCK=false in .env):
 *     Real HTTP calls to the Pickup Mtaani REST API using PM_BASE_URL and PM_API_KEY.
 *     The method signatures and return shapes are identical to mock mode, so the
 *     rest of the codebase doesn't need to change at all.
 *
 * Status progression (mirrors actual PM flow):
 *   Parcel Created → At Sending Agent → In Warehouse → In Transit
 *   → At Receiving Agent / Out for Doorstep Delivery → Delivered/Collected
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

class PickupMtaaniService
{
    /* Ordered list of status labels that a parcel progresses through.
       The mock's "advance status" feature walks this array in order. */
    public const STATUS_FLOW = [
        'Parcel Created',
        'At Sending Agent',
        'In Warehouse',
        'In Transit',
        'Out for Delivery',
        'Delivered',
    ];

    /* Mock pickup agent data (realistic Nairobi locations) */
    private const MOCK_AGENTS = [
        ['id' => 'AGT001', 'name' => 'PM Westlands Agent', 'location' => 'Sarit Centre, Westlands', 'phone' => '+254 712 000 001'],
        ['id' => 'AGT002', 'name' => 'PM Kilimani Agent',  'location' => 'Yaya Centre, Kilimani',  'phone' => '+254 712 000 002'],
        ['id' => 'AGT003', 'name' => 'PM CBD Agent',       'location' => 'Kencom House, CBD',       'phone' => '+254 712 000 003'],
        ['id' => 'AGT004', 'name' => 'PM Karen Agent',     'location' => 'Karen Shopping Centre',   'phone' => '+254 712 000 004'],
        ['id' => 'AGT005', 'name' => 'PM Ngong Road Agent','location' => 'Junction Mall, Ngong Rd', 'phone' => '+254 712 000 005'],
    ];

    /**
     * Creates a new delivery shipment with Pickup Mtaani.
     *
     * Called when Clarice marks an order as "Ready for Dispatch" in the
     * admin dashboard. Returns a PM reference number used for tracking.
     *
     * @param array $order     Full order row from database
     * @param array $agentId   Optional — PM agent ID for agent_pickup orders
     * @return array           ['pm_reference' => '...', 'status' => '...', 'events' => [...]]
     * @throws RuntimeException on API failure (live mode only)
     */
    public static function createOrder(array $order, string $agentId = ''): array
    {
        if (PM_USE_MOCK) {
            return self::mockCreateOrder($order, $agentId);
        }
        return self::liveCreateOrder($order, $agentId);
    }

    /**
     * Retrieves the current tracking status and event history for a shipment.
     *
     * @param string $pmReference  The PM reference number, e.g. "PM-2025-001234"
     * @return array               ['pm_reference'=>'...', 'pm_status'=>'...', 'pm_events'=>[...]]
     */
    public static function getTrackingStatus(string $pmReference): array
    {
        if (PM_USE_MOCK) {
            return self::mockGetTrackingStatus($pmReference);
        }
        return self::liveGetTrackingStatus($pmReference);
    }

    /**
     * Returns a list of Pickup Mtaani agent locations.
     *
     * Used on the checkout delivery step to let the customer choose
     * their nearest agent for collection.
     *
     * @param string $city  Optional filter (e.g. "Nairobi")
     * @return array        Array of agent objects with id, name, location, phone
     */
    public static function listAgents(string $city = ''): array
    {
        if (PM_USE_MOCK) {
            return self::MOCK_AGENTS;
        }
        return self::liveListAgents($city);
    }

    /**
     * Advances the mock status to the next step in STATUS_FLOW.
     * Only available in mock mode. Called by the admin dashboard's
     * "Simulate Next Status" button.
     *
     * @param int $orderId  Internal order ID
     * @return string       The new status label, or 'Already Delivered'
     */
    public static function mockAdvanceStatus(int $orderId): string
    {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM delivery_tracking WHERE order_id = :oid');
        $stmt->execute([':oid' => $orderId]);
        $row  = $stmt->fetch();

        if (!$row) { return 'No tracking record found'; }

        $currentIdx = array_search($row['pm_status'], self::STATUS_FLOW);

        if ($currentIdx === false || $currentIdx >= count(self::STATUS_FLOW) - 1) {
            return 'Already Delivered';
        }

        $nextStatus = self::STATUS_FLOW[$currentIdx + 1];
        $events     = json_decode($row['pm_events'], true) ?? [];
        $now        = date('Y-m-d H:i:s');

        /* Append new PM event to history */
        $events[] = [
            'status'    => $nextStatus,
            'timestamp' => $now,
            'note'      => 'Simulated by admin',
        ];

        /* Update delivery_tracking with new PM status */
        $db->prepare(
            'UPDATE delivery_tracking
            SET pm_status = :status, pm_events = :events, updated_at = :now
            WHERE order_id = :oid'
        )->execute([
            ':status' => $nextStatus,
            ':events' => json_encode($events),
            ':now'    => $now,
            ':oid'    => $orderId,
        ]);

        /* ── Sync order status with PM status ──────────────────────────
        Map each PM stage to the corresponding order status so the
        customer tracking page and admin order status stay in sync.
        ─────────────────────────────────────────────────────────────── */
        $pmToOrderStatus = [
            'At Sending Agent' => 'in_transit',
            'In Warehouse'     => 'in_transit',
            'In Transit'       => 'in_transit',
            'Out for Delivery' => 'out_for_delivery',
            'Delivered'        => 'delivered',
        ];

        if (isset($pmToOrderStatus[$nextStatus])) {
            $orderStatus = $pmToOrderStatus[$nextStatus];

            /* Update the order's main status column */
            $db->prepare('UPDATE orders SET status = :status WHERE id = :oid')
            ->execute([':status' => $orderStatus, ':oid' => $orderId]);

            /* Append to the immutable status history so the
            customer tracking timeline stays up to date */
            $db->prepare(
                'INSERT INTO order_status_history
                (order_id, status, note, changed_by)
                VALUES
                (:oid, :status, :note, "pickup_mtaani")'
            )->execute([
                ':oid'    => $orderId,
                ':status' => $orderStatus,
                ':note'   => 'Pickup Mtaani update: ' . $nextStatus,
            ]);

            /* Fire customer notification for key milestones only */
            $notifyStatuses = ['in_transit', 'out_for_delivery', 'delivered'];
            if (in_array($orderStatus, $notifyStatuses)) {
                require_once __DIR__ . '/notifier.php';
                Notifier::dispatchForStatus($orderStatus, $orderId,
                    'Pickup Mtaani update: ' . $nextStatus);
            }
        }

        return $nextStatus;
    }

    /* ── MOCK implementations ─────────────────────────────────────── */

    /**
     * Simulates creating a PM order. Generates a reference number,
     * stores the initial tracking record in delivery_tracking.
     */
    private static function mockCreateOrder(array $order, string $agentId): array
    {
        $db  = getDB();
        $ref = 'PM-' . date('Y') . '-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);

        /* Find the agent details if this is an agent_pickup order */
        $agent = null;
        if ($agentId) {
            foreach (self::MOCK_AGENTS as $a) {
                if ($a['id'] === $agentId) {
                    $agent = $a;
                    break;
                }
            }
        }

        /* Use the timestamp from when Clarice marked the order as
        handed_to_pm so the PM "Parcel Created" event time matches
        the order status history entry exactly — fixing the time gap. */
        $tsStmt = $db->prepare(
            'SELECT created_at FROM order_status_history
            WHERE order_id = :oid AND status = "handed_to_pm"
            ORDER BY id DESC LIMIT 1'
        );
        $tsStmt->execute([':oid' => $order['id']]);
        $tsRow     = $tsStmt->fetch();
        $handedAt  = $tsRow ? $tsRow['created_at'] : date('Y-m-d H:i:s');

        $initialEvent = [
            'status'    => 'Parcel Created',
            'timestamp' => $handedAt,
            'note'      => 'Order registered with Pickup Mtaani (mock)',
        ];

        $stmt = $db->prepare(
            'INSERT INTO delivery_tracking
            (order_id, pm_reference, pm_status, pm_events, agent_name, agent_location, agent_phone)
            VALUES
            (:oid, :ref, :status, :events, :aname, :aloc, :aphone)
            ON DUPLICATE KEY UPDATE
            pm_reference = :ref2, pm_status = :status2, pm_events = :events2'
        );
        $stmt->execute([
            ':oid'     => $order['id'],
            ':ref'     => $ref,
            ':status'  => 'Parcel Created',
            ':events'  => json_encode([$initialEvent]),
            ':aname'   => $agent['name']     ?? null,
            ':aloc'    => $agent['location'] ?? null,
            ':aphone'  => $agent['phone']    ?? null,
            ':ref2'    => $ref,
            ':status2' => 'Parcel Created',
            ':events2' => json_encode([$initialEvent]),
        ]);

        return [
            'pm_reference' => $ref,
            'status'       => 'Parcel Created',
            'events'       => [$initialEvent],
        ];
    }

    /**
     * Retrieves tracking data from the delivery_tracking table (mock).
     */
    private static function mockGetTrackingStatus(string $pmReference): array
    {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM delivery_tracking WHERE pm_reference = :ref');
        $stmt->execute([':ref' => $pmReference]);
        $row  = $stmt->fetch();

        if (!$row) {
            return ['error' => 'Tracking reference not found'];
        }

        return [
            'pm_reference' => $row['pm_reference'],
            'pm_status'    => $row['pm_status'],
            'pm_events'    => json_decode($row['pm_events'], true) ?? [],
            'agent_name'   => $row['agent_name'],
            'agent_location' => $row['agent_location'],
        ];
    }

    /* ── LIVE implementations (used when PM_USE_MOCK=false) ─────────── */

    private static function liveCreateOrder(array $order, string $agentId): array
    {
        $payload = [
            'sender_name'    => PM_SENDER_NAME,
            'receiver_name'  => $order['customer_name'],
            'receiver_phone' => $order['customer_phone'],
            'delivery_mode'  => $order['delivery_mode'],
            'address'        => $order['delivery_address'] ?? '',
            'agent_id'       => $agentId ?: null,
            'notes'          => $order['delivery_notes'] ?? '',
        ];
        return self::livePost('/orders', $payload);
    }

    private static function liveGetTrackingStatus(string $pmReference): array
    {
        return self::liveGet('/orders/' . urlencode($pmReference) . '/tracking');
    }

    private static function liveListAgents(string $city): array
    {
        $result = self::liveGet('/agents' . ($city ? '?city=' . urlencode($city) : ''));
        return $result['agents'] ?? [];
    }

    /** Performs a GET request to the live PM API */
    private static function liveGet(string $path): array
    {
        $ch = curl_init(PM_BASE_URL . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . PM_API_KEY, 'Accept: application/json'],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result ?: '{}', true) ?? [];
    }

    /** Performs a POST request to the live PM API */
    private static function livePost(string $path, array $body): array
    {
        $ch = curl_init(PM_BASE_URL . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . PM_API_KEY,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result ?: '{}', true) ?? [];
    }
}
