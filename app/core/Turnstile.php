<?php
/**
 * Cloudflare Turnstile verification for login forms.
 * Configured via TURNSTILE_SITE_KEY / TURNSTILE_SECRET_KEY in .env.
 * When no keys are configured (e.g. local dev without internet), the
 * widget is not rendered and verification is skipped.
 */

class Turnstile
{
    public static function enabled(): bool
    {
        return env('TURNSTILE_SITE_KEY') !== null && env('TURNSTILE_SECRET_KEY') !== null;
    }

    /** Widget markup + script for a login form. */
    public static function widget(): string
    {
        if (!self::enabled()) {
            return '';
        }
        $key = e(env('TURNSTILE_SITE_KEY'));
        return '<div class="cf-turnstile" data-sitekey="' . $key . '" style="margin-bottom:16px"></div>'
             . '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
    }

    /**
     * Server-side verification of the posted token.
     * Returns true when disabled (not configured). Fails closed on a missing
     * or invalid token; fails closed on verification-API errors too.
     */
    public static function verify(): bool
    {
        if (!self::enabled()) {
            return true;
        }
        $token = $_POST['cf-turnstile-response'] ?? '';
        if (!is_string($token) || $token === '') {
            return false;
        }
        try {
            $client = new \GuzzleHttp\Client(['timeout' => 10]);
            $res = $client->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'form_params' => [
                    'secret'   => env('TURNSTILE_SECRET_KEY'),
                    'response' => $token,
                    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
                ],
            ]);
            $data = json_decode((string) $res->getBody(), true);
            return !empty($data['success']);
        } catch (\Throwable) {
            return false;
        }
    }
}
