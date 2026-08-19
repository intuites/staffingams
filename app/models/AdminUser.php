<?php

class AdminUser
{
    /**
     * Email addresses by role. When asking for 'admin' and none exist,
     * falls back to super admins so notifications never vanish.
     */
    public static function emailsByRole(string $role): array
    {
        $emails = array_column(
            Database::all('SELECT email FROM admin_users WHERE role = :r', ['r' => $role]),
            'email'
        );
        if (!$emails && $role === 'admin') {
            $emails = array_column(
                Database::all("SELECT email FROM admin_users WHERE role = 'super_admin'"),
                'email'
            );
        }
        return $emails;
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::one('SELECT * FROM admin_users WHERE email = :e', ['e' => strtolower(trim($email))]);
    }

    public static function create(string $email, string $password, string $name): void
    {
        Database::q(
            'INSERT INTO admin_users (email, password_hash, name) VALUES (:e, :h, :n)',
            ['e' => strtolower(trim($email)), 'h' => password_hash($password, PASSWORD_BCRYPT), 'n' => $name]
        );
    }

    public static function updatePassword(int $id, string $password): void
    {
        Database::q(
            'UPDATE admin_users SET password_hash = :h, updated_at = NOW() WHERE id = :id',
            ['h' => password_hash($password, PASSWORD_BCRYPT), 'id' => $id]
        );
    }
}
