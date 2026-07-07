<?php
/**
 * mpesa.php — Safaricom Daraja M-Pesa STK Push service
 *
 * Handles all communication with the Daraja API:
 *   1. OAuth token generation (cached for 55 minutes per Daraja spec)
 *   2. STK Push initiation (sends the payment prompt to customer's phone)
 *
 * Sandbox vs live: controlled entirely by MPESA_ENV in .env.
 * Switching to live requires only updating .env — no code changes.
 *
 * Daraja sandbox docs: developer.safaricom.co.ke
 */

require_once __DIR__ . '/../config/config.php';

class MpesaService
{
    /* Daraja API base URLs — toggled by MPESA_ENV constant */
    private const BASE_SANDBOX = 'https://sandbox.safaricom.co.ke';
    private const BASE_LIVE    = 'https://api.safaricom.co.ke';

    /**
     * Returns the correct base URL for the current environment.
     * All endpoint builds should use this instead of hardcoding.
     */
    private static function baseUrl(): string
    {
        return (MPESA_ENV === 'live') ? self::BASE_LIVE : self::BASE_SANDBOX;
    }

    /**
     * Fetches an OAuth access token from Daraja.
     *
     * Daraja tokens are valid for 3600 seconds (1 hour). We cache the
     * token in a local temp file for 55 minutes to avoid hitting the
     * rate limit. The cache file lives outside the web root is not
     * strictly necessary on XAMPP but is good practice.
     *
     * @return string  The Bearer token string
     * @throws RuntimeException if the token fetch fails
     */
    public static function getAccessToken(): string
    {
        /* Check the cached token first */
        $cacheFile = sys_get_temp_dir() . '/injili_mpesa_token.json';

        if (file_exists($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            /* Use the cached token if it hasn't expired (55-minute window) */
            if (isset($cached['token'], $cached['expires_at'])
                && time() < $cached['expires_at']) {
                return $cached['token'];
            }
        }

        /* Build Basic Auth header from consumer key:secret pair */
        $credentials = base64_encode(MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET);

        $url = self::baseUrl() . '/oauth/v1/generate?grant_type=client_credentials';

        $response = self::curlGet($url, [
            'Authorization: Basic ' . $credentials,
        ]);

        if (!isset($response['access_token'])) {
            error_log('[M-Pesa] Token fetch failed: ' . json_encode($response));
            throw new RuntimeException('M-Pesa token fetch failed. Check your consumer key/secret in .env.');
        }

        $token = $response['access_token'];

        /* Cache the token for 55 minutes (3300 seconds) */
        file_put_contents($cacheFile, json_encode([
            'token'      => $token,
            'expires_at' => time() + 3300,
        ]));

        return $token;
    }

    /**
     * Initiates an M-Pesa STK Push (Lipa na M-Pesa Online).
     *
     * Sends a payment prompt directly to the customer's phone.
     * The customer enters their M-Pesa PIN to approve.
     * Daraja calls our callback URL asynchronously when done.
     *
     * @param string $phone     Customer phone, e.g. "0712345678" or "254712345678"
     * @param int    $amount    Amount in KSh (integer, no decimals)
     * @param string $orderId   Our internal order number, sent as AccountReference
     * @param string $desc      Short description shown on the M-Pesa prompt (max 20 chars)
     *
     * @return array  Daraja response (contains CheckoutRequestID on success)
     * @throws RuntimeException on API failure
     */
    public static function stkPush(string $phone, int $amount, string $orderId, string $desc = 'Injili Apparel'): array
    {
        $token     = self::getAccessToken();
        $timestamp = date('YmdHis');

        /* The Daraja password is: base64(ShortCode + Passkey + Timestamp) */
        $password = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);

        /* Normalise phone to 254XXXXXXXXX format */
        $phone = self::normalisePhone($phone);

        $payload = [
            'BusinessShortCode' => MPESA_SHORTCODE,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => $amount,
            'PartyA'            => $phone,           // customer's phone
            'PartyB'            => MPESA_SHORTCODE,  // business shortcode
            'PhoneNumber'       => $phone,            // phone to receive prompt
            'CallBackURL'       => MPESA_CALLBACK_URL,
            'AccountReference'  => $orderId,
            'TransactionDesc'   => substr($desc, 0, 20), // Daraja max 20 chars
        ];

        $url = self::baseUrl() . '/mpesa/stkpush/v1/processrequest';

        $response = self::curlPost($url, $payload, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ]);

        if (isset($response['errorCode'])) {
            error_log('[M-Pesa STK] Error: ' . json_encode($response));
            throw new RuntimeException('M-Pesa STK Push failed: ' . ($response['errorMessage'] ?? 'Unknown error'));
        }

        return $response;
    }

    /**
     * Normalises a Kenyan phone number to the 254XXXXXXXXX format
     * that Daraja requires.
     *
     * Handles inputs like: 0712345678, +254712345678, 254712345678
     *
     * @param string $phone  Raw phone number from the form
     * @return string        Normalised number, e.g. "254712345678"
     */
    public static function normalisePhone(string $phone): string
    {
        /* Strip all non-digit characters */
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '0')) {
            /* Convert 07XXXXXXXX → 2547XXXXXXXX */
            $phone = '254' . substr($phone, 1);
        } elseif (str_starts_with($phone, '254')) {
            /* Already correct */
        } else {
            /* Assume it's missing the country code */
            $phone = '254' . $phone;
        }

        return $phone;
    }

    /**
     * Performs a cURL GET request and returns the decoded JSON body.
     *
     * @param string $url      Full URL to fetch
     * @param array  $headers  HTTP headers as ["Name: value"] strings
     * @return array           Decoded JSON response
     */
    private static function curlGet(string $url, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => (MPESA_ENV === 'live'), // verify SSL on live only
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result ?: '{}', true) ?? [];
    }

    /**
     * Performs a cURL POST request with a JSON body.
     *
     * @param string $url      Full URL to POST to
     * @param array  $body     Request body (will be JSON-encoded)
     * @param array  $headers  HTTP headers as ["Name: value"] strings
     * @return array           Decoded JSON response
     */
    private static function curlPost(string $url, array $body, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => (MPESA_ENV === 'live'),
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result ?: '{}', true) ?? [];
    }
}
