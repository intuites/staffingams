<?php
/**
 * Notification email sender.
 * Priority: Resend API (RESEND_API_KEY) → SMTP via PHPMailer (MAIL_HOST) → skip + log.
 * Failures never break the request — notifications are best-effort and logged.
 *
 * Resend notes: the "from" address (MAIL_FROM) must be on a domain verified in
 * your Resend dashboard. Until a domain is verified, Resend only delivers from
 * onboarding@resend.dev and only to the Resend account owner's email.
 */

use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    /** @param string[] $to recipient email addresses */
    public static function send(array $to, string $subject, string $htmlBody): void
    {
        $to = array_values(array_unique(array_filter($to)));
        if (!$to) {
            return;
        }
        if (env('RESEND_API_KEY')) {
            self::sendViaResend($to, $subject, $htmlBody);
            return;
        }
        if (env('MAIL_HOST')) {
            self::sendViaSmtp($to, $subject, $htmlBody);
            return;
        }
        error_log('[mailer] skipped (no RESEND_API_KEY or MAIL_HOST configured): ' . $subject . ' -> ' . implode(',', $to));
    }

    private static function sendViaResend(array $to, string $subject, string $htmlBody): void
    {
        try {
            $client = new \GuzzleHttp\Client(['timeout' => 15]);
            $res = $client->post('https://api.resend.com/emails', [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('RESEND_API_KEY'),
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'from'    => env('MAIL_FROM_NAME', 'Staffing Accounting') . ' <' . env('MAIL_FROM', 'onboarding@resend.dev') . '>',
                    'to'      => $to,
                    'subject' => $subject,
                    'html'    => $htmlBody,
                ],
                'http_errors' => false,
            ]);
            $code = $res->getStatusCode();
            if ($code >= 300) {
                error_log('[mailer] resend error ' . $code . ': ' . substr((string) $res->getBody(), 0, 300));
            } else {
                error_log('[mailer] resend sent: ' . $subject . ' -> ' . implode(',', $to));
            }
        } catch (\Throwable $ex) {
            error_log('[mailer] resend failed: ' . $ex->getMessage());
        }
    }

    private static function sendViaSmtp(array $to, string $subject, string $htmlBody): void
    {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = env('MAIL_HOST');
            $mail->Port       = (int) env('MAIL_PORT', '587');
            $mail->SMTPAuth   = env('MAIL_USERNAME') !== null;
            if ($mail->SMTPAuth) {
                $mail->Username = env('MAIL_USERNAME');
                $mail->Password = env('MAIL_PASSWORD', '');
            }
            $mail->SMTPSecure = $mail->Port === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Timeout    = 10;
            $mail->setFrom(env('MAIL_FROM', 'noreply@example.com'), env('MAIL_FROM_NAME', 'Staffing Accounting'));
            foreach ($to as $addr) {
                $mail->addAddress($addr);
            }
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $htmlBody));
            $mail->send();
        } catch (\Throwable $ex) {
            error_log('[mailer] smtp send failed: ' . $ex->getMessage());
        }
    }

    /** Simple branded wrapper for notification bodies. */
    public static function wrap(string $title, string $bodyHtml): string
    {
        $url = rtrim(env('APP_URL', ''), '/');
        return '<div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto">'
            . '<div style="background:#0e2136;color:#fff;padding:14px 20px;border-radius:8px 8px 0 0;font-weight:bold">Staffing Accounting System</div>'
            . '<div style="border:1px solid #dde5ed;border-top:0;padding:20px;border-radius:0 0 8px 8px">'
            . '<h2 style="margin:0 0 12px;font-size:17px;color:#0b1522">' . $title . '</h2>'
            . $bodyHtml
            . ($url ? '<p style="margin-top:18px"><a href="' . $url . '" style="color:#0a93c0">Open the app →</a></p>' : '')
            . '</div></div>';
    }
}
