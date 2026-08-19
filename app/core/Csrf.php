<?php
/**
 * CSRF token generation + verification. A single token per session.
 */

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    /** Hidden input for forms. */
    public static function field(): string
    {
        $t = self::token();
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($t, ENT_QUOTES) . '">';
    }

    /** Verify the posted token; abort with 419 on mismatch. */
    public static function verify(): void
    {
        $sent = $_POST['_csrf'] ?? '';
        if (!is_string($sent) || empty($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], $sent)) {
            http_response_code(419);
            echo 'CSRF token mismatch. Go back, refresh the page and try again.';
            exit;
        }
    }
}
