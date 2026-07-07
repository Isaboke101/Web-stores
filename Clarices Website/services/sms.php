<?php
/**
 * sms.php — SMS notification service via Africa's Talking
 *
 * Africa's Talking (AT) provides a free sandbox environment that works
 * without a real SIM card. Sandbox messages appear in the AT simulator
 * at simulator.africastalking.com (no real SMS sent).
 *
 * To go live: change AT_USERNAME from 'sandbox' to your AT username
 * and ensure your account has a funded SMS bundle. The code doesn't change.
 *
 * AT docs: developers.africastalking.com/docs/sms
 */

require_once __DIR__ . '/../config/config.php';

class SmsService
{
    /* Africa's Talking sandbox vs live SMS endpoint */
    private const SANDBOX_URL = 'https://api.sandbox.africastalking.com/version1/messaging';
    private const LIVE_URL    = 'https://api.africastalking.com/version1/messaging';

    /**
     * Sends an SMS to a single phone number.
     *
     * @param string $phone    Recipient phone in international format, e.g. "+254712345678"
     * @param string $message  Plain text message (keep under 160 chars for single SMS)
     * @return bool            true on success (AT accepted the request), false on failure
     */
    public static function send(string $phone, string $message): bool
    {
        /* Select the correct endpoint based on the AT username.
           If the username is 'sandbox', we use the sandbox URL. */
        $url = (AT_USERNAME === 'sandbox') ? self::SANDBOX_URL : self::LIVE_URL;

        /* AT's SMS API accepts a POST with form-encoded body.
           The Content-Type must be application/x-www-form-urlencoded. */
        $body = http_build_query([
            'username' => AT_USERNAME,
            'to'       => $phone,
            'message'  => $message,
            'from'     => (AT_SENDER_ID !== '') ? AT_SENDER_ID : null,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'apiKey: '    . AT_API_KEY,
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201) {
            error_log('[SMS] Failed to send to ' . $phone . '. HTTP ' . $httpCode . ': ' . $response);
            return false;
        }

        $data = json_decode($response, true);

        /* AT returns a recipients array — check the first recipient's status */
        $recipientStatus = $data['SMSMessageData']['Recipients'][0]['status'] ?? 'Unknown';
        if ($recipientStatus !== 'Success') {
            error_log('[SMS] AT rejected message to ' . $phone . '. Status: ' . $recipientStatus);
            return false;
        }

        return true;
    }

    /**
     * Sends an order confirmation SMS to the customer.
     *
     * @param array $order  Order row from database
     * @return bool
     */
    public static function sendOrderConfirmation(array $order): bool
    {
        $firstName = explode(' ', $order['customer_name'])[0];
        $message   = "Hi {$firstName}! Your Injili Apparel order {$order['order_number']} (KSh " .
                     number_format($order['total_ksh']) . ") is confirmed. " .
                     "We'll SMS you when it's in production. Thank you!";

        return self::send($order['customer_phone'], $message);
    }

    /**
     * Sends a status update SMS to the customer.
     *
     * @param array  $order        Order row
     * @param string $statusLabel  Human-readable status, e.g. "In Production"
     * @param string $note         Optional extra info (e.g. PM tracking reference)
     * @return bool
     */
    public static function sendStatusUpdate(array $order, string $statusLabel, string $note = ''): bool
    {
        $firstName = explode(' ', $order['customer_name'])[0];
        $message   = "Hi {$firstName}! Your order {$order['order_number']} is now: {$statusLabel}.";

        if ($note) {
            $message .= ' ' . $note;
        }

        /* Append tracking URL — SMS is short so use order number for tracking page */
        $trackUrl = APP_URL . '/track.php?order=' . urlencode($order['order_number']);
        $message .= " Track: {$trackUrl}";

        /* Trim to 160 chars to keep it a single SMS */
        $message = substr($message, 0, 160);

        return self::send($order['customer_phone'], $message);
    }

    /**
     * Sends a new-order alert SMS to Clarice (admin).
     *
     * @param array $order  Order row
     * @return bool
     */
    public static function sendAdminNewOrderAlert(array $order): bool
    {
        $message = "New Injili order! {$order['order_number']} — " .
                   $order['customer_name'] . " — KSh " . number_format($order['total_ksh']) .
                   ". Login to dashboard to begin production.";

        return self::send(ADMIN_PHONE, $message);
    }
}
