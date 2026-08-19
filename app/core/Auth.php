<?php
/**
 * Custom session-based admin auth with bcrypt passwords and
 * a simple DB-backed login rate limit.
 */

class Auth
{
    private const MAX_ATTEMPTS = 8;      // per window
    private const WINDOW_SECONDS = 900;  // 15 minutes

    public static function attempt(string $email, string $password): bool
    {
        if (self::tooManyAttempts()) {
            return false;
        }
        $user = Database::one('SELECT * FROM admin_users WHERE email = :e', ['e' => strtolower(trim($email))]);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            self::recordAttempt();
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['admin_id']    = (int) $user['id'];
        $_SESSION['admin_name']  = $user['name'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['admin_role']  = $user['role'] ?? 'admin';
        unset($_SESSION['_login_attempts']);
        return true;
    }

    public static function role(): string
    {
        return $_SESSION['admin_role'] ?? 'admin';
    }

    public static function isSuper(): bool
    {
        return self::role() === 'super_admin';
    }

    /** Abort with a flash + redirect when the action needs super admin. */
    public static function requireSuper(): void
    {
        self::requireLogin();
        if (!self::isSuper()) {
            flash('error', 'Only a super admin can do that.');
            redirect('/');
        }
    }

    public static function check(): bool
    {
        return !empty($_SESSION['admin_id']);
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return [
            'id'    => $_SESSION['admin_id'],
            'name'  => $_SESSION['admin_name'] ?? '',
            'email' => $_SESSION['admin_email'] ?? '',
            'role'  => $_SESSION['admin_role'] ?? 'admin',
        ];
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('/login');
        }
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    public static function tooManyAttempts(): bool
    {
        $a = $_SESSION['_login_attempts'] ?? ['n' => 0, 't' => time()];
        if (time() - $a['t'] > self::WINDOW_SECONDS) {
            return false;
        }
        return $a['n'] >= self::MAX_ATTEMPTS;
    }

    private static function recordAttempt(): void
    {
        $a = $_SESSION['_login_attempts'] ?? ['n' => 0, 't' => time()];
        if (time() - $a['t'] > self::WINDOW_SECONDS) {
            $a = ['n' => 0, 't' => time()];
        }
        $a['n']++;
        $_SESSION['_login_attempts'] = $a;
    }
}
