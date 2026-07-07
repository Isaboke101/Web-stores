<?php
/**
 * mailer.php — Email notification service (PHPMailer + Gmail SMTP)
 *
 * Wraps PHPMailer to send transactional emails for:
 *   - Order confirmation to customer
 *   - New order alert to Clarice
 *   - Order status update to customer
 *
 * Requires PHPMailer 6.x files in /lib/PHPMailer/.
 * Install: download from github.com/PHPMailer/PHPMailer/releases
 * and place PHPMailer.php, SMTP.php, Exception.php in lib/PHPMailer/.
 *
 * Gmail setup: enable 2FA → generate App Password at
 * myaccount.google.com/apppasswords → paste into .env
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/PHPMailer/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

class MailerService
{
    /**
     * Creates and configures a PHPMailer instance with Gmail SMTP settings.
     * All credential values come from config constants (loaded from .env).
     *
     * @return PHPMailer  Ready-to-use mailer (recipient and body still need setting)
     */
    private static function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true); // true = throw exceptions on error

        /* Use SMTP transport instead of PHP's mail() function.
           mail() requires a local MTA which XAMPP doesn't have by default. */
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // port 587
        $mail->Port       = (int) MAIL_PORT;

        /* Brand sender identity */
        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        return $mail;
    }

    /**
     * Sends an order confirmation email to the customer.
     *
     * Called automatically after a successful M-Pesa payment callback
     * or when Clarice manually marks an order as paid.
     *
     * @param array $order  Full order row from the database
     * @param array $items  Array of order_items rows for this order
     * @return bool         true on success, false on failure
     */
    public static function sendOrderConfirmation(array $order, array $items): bool
    {
        try {
            $mail = self::createMailer();
            $mail->addAddress($order['customer_email'], $order['customer_name']);
            $mail->Subject = 'Your Injili Apparel Order — ' . $order['order_number'];
            $mail->Body    = self::buildConfirmationEmail($order, $items);
            $mail->AltBody = self::buildConfirmationEmailPlainText($order, $items);
            $mail->send();
            return true;
        } catch (MailException $e) {
            error_log('[Mailer] Order confirmation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Sends a new-order alert to Clarice with full order details.
     *
     * Clarice receives this immediately when a new order is placed,
     * so she can begin production promptly.
     *
     * @param array $order  Full order row from the database
     * @param array $items  Array of order_items rows for this order
     * @return bool
     */
    public static function sendAdminNewOrderAlert(array $order, array $items): bool
    {
        try {
            $mail = self::createMailer();
            $mail->addAddress(ADMIN_EMAIL, 'Clarice — Injili Apparel');
            $mail->Subject = '🛍 New Order: ' . $order['order_number'] . ' — KSh ' . number_format($order['total_ksh']);
            $mail->Body    = self::buildAdminAlertEmail($order, $items);
            $mail->AltBody = 'New order: ' . $order['order_number'] . ' from ' . $order['customer_name'];
            $mail->send();
            return true;
        } catch (MailException $e) {
            error_log('[Mailer] Admin alert failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Sends an order status update email to the customer.
     *
     * Called when Clarice updates an order's status in the admin dashboard.
     * Not all status changes trigger a customer email — only the milestones
     * defined in notifier.php trigger this.
     *
     * @param array  $order   Full order row
     * @param string $status  The new status string
     * @param string $note    Optional note from Clarice (e.g. tracking number)
     * @return bool
     */
    public static function sendStatusUpdate(array $order, string $status, string $note = ''): bool
    {
        $labels = [
            'received'           => 'Order Received',
            'in_production'      => 'In Production',
            'ready_for_dispatch' => 'Ready for Dispatch',
            'handed_to_pm'       => 'Handed to Pickup Mtaani',
            'in_transit'         => 'In Transit',
            'out_for_delivery'   => 'Out for Delivery',
            'delivered'          => 'Delivered',
        ];
        $label = $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));

        try {
            $mail = self::createMailer();
            $mail->addAddress($order['customer_email'], $order['customer_name']);
            $mail->Subject = 'Order Update: ' . $label . ' — ' . $order['order_number'];
            $mail->Body    = self::buildStatusEmail($order, $label, $note);
            $mail->AltBody = "Your order {$order['order_number']} is now: {$label}.";
            $mail->send();
            return true;
        } catch (MailException $e) {
            error_log('[Mailer] Status update email failed: ' . $e->getMessage());
            return false;
        }
    }

    /* ── Email HTML templates ────────────────────────────────────────
       Each template function returns a full HTML email string.
       The design mirrors the brand palette: navy, gold, cream.
    ─────────────────────────────────────────────────────────────────── */

    /**
     * Builds the HTML body for the customer order confirmation email.
     */
    private static function buildConfirmationEmail(array $order, array $items): string
    {
        $itemsHtml = '';
        foreach ($items as $item) {
            $itemsHtml .= "
            <tr>
              <td style='padding:8px 0;border-bottom:1px solid #EDEDE9;font-size:14px;color:#1C2329'>
                {$item['product_name']} — {$item['color_name']} / {$item['size']} × {$item['quantity']}
              </td>
              <td style='padding:8px 0;border-bottom:1px solid #EDEDE9;font-size:14px;color:#1C2329;text-align:right'>
                KSh " . number_format($item['line_total_ksh']) . "
              </td>
            </tr>";
        }

        $trackUrl = APP_URL . '/track.php?order=' . urlencode($order['order_number']);
        $firstName = explode(' ', $order['customer_name'])[0];

        return "<!DOCTYPE html><html><body style='margin:0;padding:0;background:#F8F5F0;font-family:DM Sans,Arial,sans-serif'>
        <table width='100%' cellpadding='0' cellspacing='0' style='background:#F8F5F0;padding:40px 0'>
          <tr><td align='center'>
            <table width='560' cellpadding='0' cellspacing='0' style='background:#FFFFFF;border-radius:4px;overflow:hidden'>
              <tr><td style='background:#1C2329;padding:28px 36px;text-align:center'>
                <span style='font-family:Georgia,serif;font-size:22px;color:#FFFFFF;letter-spacing:2px'>injili</span>
                <span style='display:block;font-size:9px;color:#C98A41;letter-spacing:5px;text-transform:uppercase;margin-top:2px'>Apparel</span>
              </td></tr>
              <tr><td style='padding:36px'>
                <h1 style='font-family:Georgia,serif;font-weight:300;font-size:28px;color:#1C2329;margin:0 0 8px'>Order Confirmed.</h1>
                <p style='font-size:13px;color:#888;margin:0 0 24px'>Thank you, {$firstName}. We've received your order.</p>
                <table width='100%' cellpadding='0' cellspacing='0'>{$itemsHtml}</table>
                <table width='100%' cellpadding='0' cellspacing='0' style='margin-top:12px'>
                  <tr>
                    <td style='font-size:13px;color:#888'>Delivery</td>
                    <td style='font-size:13px;color:#1C2329;text-align:right'>KSh " . number_format($order['delivery_fee_ksh']) . "</td>
                  </tr>
                  <tr>
                    <td style='font-size:15px;font-weight:600;color:#1C2329;padding-top:10px;border-top:1px solid #EDEDE9'>Total</td>
                    <td style='font-size:15px;font-weight:600;color:#1C2329;text-align:right;padding-top:10px;border-top:1px solid #EDEDE9'>KSh " . number_format($order['total_ksh']) . "</td>
                  </tr>
                </table>
                <p style='font-size:13px;color:#888;margin:24px 0 8px'>Order Reference: <strong style='color:#1C2329'>{$order['order_number']}</strong></p>
                <p style='font-size:13px;color:#888;margin:0 0 24px'>We'll notify you when your item is in production and when it's on its way.</p>
                <a href='{$trackUrl}' style='display:inline-block;background:#1C2329;color:#FFFFFF;text-decoration:none;padding:12px 28px;font-size:12px;letter-spacing:2px;text-transform:uppercase;border-radius:2px'>Track My Order</a>
              </td></tr>
              <tr><td style='background:#F8F5F0;padding:20px 36px;text-align:center'>
                <p style='font-size:11px;color:#aaa;margin:0'>© 2025 Injili Apparel · Nairobi, Kenya</p>
              </td></tr>
            </table>
          </td></tr>
        </table></body></html>";
    }

    /**
     * Plain text fallback for the confirmation email.
     */
    private static function buildConfirmationEmailPlainText(array $order, array $items): string
    {
        $lines  = ["ORDER CONFIRMED — {$order['order_number']}", ""];
        foreach ($items as $item) {
            $lines[] = "{$item['product_name']} ({$item['color_name']}/{$item['size']}) x{$item['quantity']} — KSh " . number_format($item['line_total_ksh']);
        }
        $lines[] = "";
        $lines[] = "Total: KSh " . number_format($order['total_ksh']);
        $lines[] = "";
        $lines[] = "Track your order: " . APP_URL . '/track.php?order=' . urlencode($order['order_number']);
        return implode("\n", $lines);
    }

    /**
     * Builds the new-order alert email for Clarice.
     * Contains all production-relevant details in one view.
     */
    private static function buildAdminAlertEmail(array $order, array $items): string
    {
        $itemsHtml = '';
        foreach ($items as $item) {
            $itemsHtml .= "<tr>
              <td style='padding:6px 0;border-bottom:1px solid #eee;font-size:13px'>
                <strong>{$item['product_name']}</strong><br>
                <span style='color:#888'>{$item['color_name']} / Size {$item['size']} × {$item['quantity']}</span>
              </td>
              <td style='padding:6px 0;border-bottom:1px solid #eee;font-size:13px;text-align:right'>
                KSh " . number_format($item['line_total_ksh']) . "
              </td>
            </tr>";
        }

        $adminUrl = APP_URL . '/admin/order_detail.php?id=' . $order['id'];

        return "<!DOCTYPE html><html><body style='font-family:Arial,sans-serif;background:#f4f4f4;padding:20px'>
        <table width='560' style='background:#fff;padding:28px;border-radius:4px;margin:0 auto'>
          <tr><td style='border-bottom:3px solid #C98A41;padding-bottom:16px;margin-bottom:16px'>
            <h2 style='margin:0;color:#1C2329;font-size:18px'>🛍 New Order — {$order['order_number']}</h2>
          </td></tr>
          <tr><td style='padding-top:16px'>
            <p style='margin:0 0 4px;font-size:13px'><strong>Customer:</strong> {$order['customer_name']}</p>
            <p style='margin:0 0 4px;font-size:13px'><strong>Email:</strong> {$order['customer_email']}</p>
            <p style='margin:0 0 4px;font-size:13px'><strong>Phone:</strong> {$order['customer_phone']}</p>
            <p style='margin:0 0 16px;font-size:13px'><strong>Delivery:</strong> " . ucfirst(str_replace('_', ' ', $order['delivery_mode'])) . "</p>
            <table width='100%'>{$itemsHtml}</table>
            <p style='margin:16px 0 0;font-size:15px;font-weight:bold'>Total: KSh " . number_format($order['total_ksh']) . "</p>
            <a href='{$adminUrl}' style='display:inline-block;margin-top:20px;background:#C98A41;color:#fff;text-decoration:none;padding:10px 24px;font-size:12px;letter-spacing:1px;text-transform:uppercase;border-radius:2px'>View in Dashboard</a>
          </td></tr>
        </table></body></html>";
    }

    /**
     * Builds the status update email sent to the customer on key milestones.
     */
    private static function buildStatusEmail(array $order, string $statusLabel, string $note): string
    {
        $trackUrl  = APP_URL . '/track.php?order=' . urlencode($order['order_number']);
        $firstName = explode(' ', $order['customer_name'])[0];
        $noteHtml  = $note ? "<p style='font-size:13px;color:#555;margin:16px 0 0;padding:12px;background:#F8F5F0;border-left:3px solid #C98A41'>{$note}</p>" : '';

        return "<!DOCTYPE html><html><body style='margin:0;padding:0;background:#F8F5F0;font-family:Arial,sans-serif'>
        <table width='100%' style='padding:40px 0'><tr><td align='center'>
          <table width='520' style='background:#fff;border-radius:4px;overflow:hidden'>
            <tr><td style='background:#1C2329;padding:24px;text-align:center'>
              <span style='font-family:Georgia,serif;font-size:20px;color:#fff'>injili</span>
              <span style='display:block;font-size:8px;color:#C98A41;letter-spacing:5px;text-transform:uppercase;margin-top:2px'>Apparel</span>
            </td></tr>
            <tr><td style='padding:32px'>
              <p style='font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#C98A41;margin:0 0 8px'>Order Update</p>
              <h1 style='font-family:Georgia,serif;font-weight:300;font-size:26px;color:#1C2329;margin:0 0 8px'>{$statusLabel}</h1>
              <p style='font-size:13px;color:#888;margin:0'>Hi {$firstName} — your order <strong>{$order['order_number']}</strong> has been updated.</p>
              {$noteHtml}
              <a href='{$trackUrl}' style='display:inline-block;margin-top:24px;background:#1C2329;color:#fff;text-decoration:none;padding:12px 28px;font-size:11px;letter-spacing:2px;text-transform:uppercase;border-radius:2px'>Track My Order</a>
            </td></tr>
            <tr><td style='background:#F8F5F0;padding:16px;text-align:center'>
              <p style='font-size:11px;color:#aaa;margin:0'>© 2025 Injili Apparel · Nairobi, Kenya</p>
            </td></tr>
          </table>
        </td></tr></table></body></html>";
    }
}
