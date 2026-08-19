<?php
/**
 * CLI: create (or update the password of) an admin user.
 *
 * Usage: php scripts/make_admin.php email@example.com 'SecurePass123!' 'Admin Name' [super_admin|admin]
 */

require __DIR__ . '/_cli_bootstrap.php';

[$self, $email, $password, $name, $role] = array_pad($argv, 5, null);
if (!$email || !$password) {
    echo "Usage: php scripts/make_admin.php <email> <password> [name]\n";
    exit(1);
}
$name = $name ?: 'Admin';
$role = in_array($role, ['super_admin', 'admin'], true) ? $role : 'admin';
$hash = password_hash($password, PASSWORD_BCRYPT);

Database::q(
    'INSERT INTO admin_users (email, password_hash, name, role)
     VALUES (:e, :h, :n, :r)
     ON CONFLICT (email) DO UPDATE SET password_hash = :h, name = :n, role = :r, updated_at = NOW()',
    ['e' => strtolower(trim($email)), 'h' => $hash, 'n' => $name, 'r' => $role]
);
echo "Admin user '{$email}' ({$role}) created/updated.\n";
