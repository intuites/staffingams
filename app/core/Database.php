<?php
/**
 * PDO singleton for Supabase Postgres.
 */

class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            // DB_HOST may be a comma-separated list of candidate hosts
            // (e.g. both Supabase pooler clusters + the direct IPv6 host);
            // the first one that accepts the connection wins.
            $hosts = array_filter(array_map('trim', explode(',', env('DB_HOST', '127.0.0.1'))));
            $port = env('DB_PORT', '5432');
            $db   = env('DB_DATABASE', 'postgres');
            $user = env('DB_USERNAME', 'postgres');
            $pass = env('DB_PASSWORD', '');
            $sslmode = env('DB_SSLMODE', 'prefer');

            // Supabase resilience: the exact pooler cluster (aws-0 vs aws-1)
            // varies per project, so when a pooler host is configured also try
            // its sibling cluster, plus the direct host derived from the
            // project ref embedded in the username (postgres.<ref>).
            foreach ($hosts as $h) {
                if (preg_match('/^aws-(\d)-(.+)\.pooler\.supabase\.com$/', $h, $m)) {
                    foreach (['0', '1', '2'] as $n) {
                        $hosts[] = "aws-{$n}-{$m[2]}.pooler.supabase.com";
                    }
                }
            }
            if (preg_match('/^postgres\.([a-z0-9]+)$/', $user, $m)) {
                $hosts[] = "db.{$m[1]}.supabase.co";
            }
            $hosts = array_values(array_unique($hosts));

            $lastEx = null;
            foreach ($hosts as $host) {
                // The pooler wants postgres.<ref>; the direct host wants plain postgres.
                $hostUser = preg_match('/^db\.[a-z0-9]+\.supabase\.co$/', $host)
                    ? preg_replace('/^postgres\.[a-z0-9]+$/', 'postgres', $user)
                    : $user;
                $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=$sslmode;connect_timeout=8";
                try {
                    self::$pdo = new PDO($dsn, $hostUser, $pass, [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]);
                    return self::$pdo;
                } catch (PDOException $ex) {
                    $lastEx = $ex;
                }
            }
            throw $lastEx ?? new PDOException('No database host configured.');
        }
        return self::$pdo;
    }

    /** Prepared query returning the statement. */
    public static function q(string $sql, array $params = []): PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    /** First row or null. */
    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::q($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** All rows. */
    public static function all(string $sql, array $params = []): array
    {
        return self::q($sql, $params)->fetchAll();
    }

    /** Single scalar value. */
    public static function scalar(string $sql, array $params = []): mixed
    {
        return self::q($sql, $params)->fetchColumn();
    }

    public static function begin(): void  { self::pdo()->beginTransaction(); }
    public static function commit(): void { self::pdo()->commit(); }
    public static function rollBack(): void
    {
        if (self::pdo()->inTransaction()) {
            self::pdo()->rollBack();
        }
    }

    public static function lastId(string $seq): int
    {
        return (int) self::pdo()->lastInsertId($seq);
    }
}
