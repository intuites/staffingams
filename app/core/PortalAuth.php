<?php
/**
 * Candidate portal authentication. Completely separate from the admin Auth:
 * its own session keys, its own rate limit. After login, every portal query
 * is scoped to the candidate id stored in the session — portal URLs never
 * carry a candidate id, so one candidate can never address another's data.
 */

class PortalAuth
{
    private const MAX_ATTEMPTS = 8;
    private const WINDOW_SECONDS = 900;

    public static function attempt(string $email, string $password): bool
    {
        if (self::tooManyAttempts()) {
            return false;
        }
        $cand = Database::one(
            'SELECT id, first_name, portal_password_hash, portal_enabled
             FROM candidates WHERE email = :e',
            ['e' => strtolower(trim($email))]
        );
        $enabled = $cand && $cand['portal_enabled'] && $cand['portal_enabled'] !== 'f';
        if (!$cand || !$enabled || !$cand['portal_password_hash']
            || !password_verify($password, $cand['portal_password_hash'])) {
            self::recordAttempt();
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['portal_candidate_id'] = (int) $cand['id'];
        $_SESSION['portal_name'] = $cand['first_name'];
        unset($_SESSION['_portal_attempts']);
        Database::q('UPDATE candidates SET portal_last_login_at = NOW() WHERE id = :id', ['id' => $cand['id']]);
        return true;
    }

    public static function check(): bool
    {
        return !empty($_SESSION['portal_candidate_id']);
    }

    /** The logged-in candidate's id — the ONLY id portal queries may use. */
    public static function candidateId(): int
    {
        return (int) ($_SESSION['portal_candidate_id'] ?? 0);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('/portal/login');
        }
        // Re-check the flag each request so disabling access takes effect immediately.
        $enabled = Database::scalar(
            'SELECT portal_enabled FROM candidates WHERE id = :id',
            ['id' => self::candidateId()]
        );
        if (!$enabled || $enabled === 'f') {
            self::logout();
            redirect('/portal/login');
        }
    }

    public static function logout(): void
    {
        unset($_SESSION['portal_candidate_id'], $_SESSION['portal_name']);
    }

    public static function tooManyAttempts(): bool
    {
        $a = $_SESSION['_portal_attempts'] ?? ['n' => 0, 't' => time()];
        if (time() - $a['t'] > self::WINDOW_SECONDS) {
            return false;
        }
        return $a['n'] >= self::MAX_ATTEMPTS;
    }

    private static function recordAttempt(): void
    {
        $a = $_SESSION['_portal_attempts'] ?? ['n' => 0, 't' => time()];
        if (time() - $a['t'] > self::WINDOW_SECONDS) {
            $a = ['n' => 0, 't' => time()];
        }
        $a['n']++;
        $_SESSION['_portal_attempts'] = $a;
    }
}
