<?php
/**
 * whatsapp.php — WhatsApp notification service via CallMeBot
 *
 * CallMeBot is a free WhatsApp API service for personal use.
 * It doesn't require a WhatsApp Business account — ideal for a
 * solo founder prototype.
 *
 * Setup (one-time):
 *   1. Save the number +34 644 39 84 06 in your phone as "CallMeBot"
 *   2. Send this WhatsApp message to that number:
 *      "I allow callmebot to send messages to my phone"
 *   3. You'll receive an API key in reply
 *   4. Put the key and your number in .env as CALLMEBOT_API_KEY / CALLMEBOT_PHONE
 *
 * Limitation: Only sends to Clarice's WhatsApp (admin notifications only).
 * Customer WhatsApp would require WhatsApp Business API (future phase).
 *
 * CallMeBot docs: callmebot.com/blog/free-api-whatsapp-messages/
 */

require_once __DIR__ . '/../config/config.php';

class WhatsAppService
{
    /* CallMeBot message endpoint */
    private const API_URL = 'https://api.callmebot.com/whatsapp.php';

    /**
     * Sends a WhatsApp message to the configured admin number (Clarice).
     *
     * @param string $message  Plain text message to send
     * @return bool            true if CallMeBot accepted the request
     */
    public static function sendToAdmin(string $message): bool
    {
        if (CALLMEBOT_PHONE === '' || CALLMEBOT_API_KEY === '') {
            error_log('[WhatsApp] CALLMEBOT_PHONE or CALLMEBOT_API_KEY not set in .env');
            return false;
        }

        /* CallMeBot expects the phone in international format without the + sign
           and the message URL-encoded. */
        $phone = ltrim(CALLMEBOT_PHONE, '+');

        $url = self::API_URL . '?' . http_build_query([
            'phone'  => $phone,
            'text'   => $message,
            'apikey' => CALLMEBOT_API_KEY,
        ]);

        /* CallMeBot uses a simple GET request */
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log('[WhatsApp] CallMeBot returned HTTP ' . $httpCode . ': ' . $response);
            return false;
        }

        return true;
    }

    /**
     * Sends a new-order WhatsApp alert to Clarice.
     * Contains the key order details she needs to start production.
     *
     * @param array $order  Order row from database
     * @param array $items  Array of order_items rows
     * @return bool
     */
    public static function sendAdminNewOrderAlert(array $order, array $items): bool
    {
        /* Build a compact summary — WhatsApp limits are generous but keep it readable */
        $itemLines = '';
        foreach ($items as $item) {
            $itemLines .= "• {$item['product_name']} — {$item['color_name']}/{$item['size']} ×{$item['quantity']}\n";
        }

        $message  = "🛍 *NEW INJILI ORDER*\n";
        $message .= "Order: {$order['order_number']}\n";
        $message .= "Customer: {$order['customer_name']}\n";
        $message .= "Phone: {$order['customer_phone']}\n";
        $message .= "Delivery: " . ucfirst(str_replace('_', ' ', $order['delivery_mode'])) . "\n\n";
        $message .= "Items:\n{$itemLines}\n";
        $message .= "Total: KSh " . number_format($order['total_ksh']) . "\n";
        $message .= "Payment: " . strtoupper($order['payment_status']) . "\n\n";
        $message .= "Login to manage: " . APP_URL . "/admin/order_detail.php?id={$order['id']}";

        return self::sendToAdmin($message);
    }
}
