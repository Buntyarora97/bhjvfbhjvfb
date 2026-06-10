<?php
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $db_url = getenv('DATABASE_URL');
            if ($db_url) {
                // Normalize postgres:// -> postgresql://
                $db_url = str_replace('postgres://', 'postgresql://', $db_url);
                $db_parts = parse_url($db_url);
                $host = $db_parts['host'];
                $port = $db_parts['port'] ?? 5432;
                $dbname = ltrim($db_parts['path'], '/');
                $username = urldecode($db_parts['user']);
                $password = urldecode($db_parts['pass']);
                $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
                $driver_options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
            } else {
                // Fallback: use individual PG env vars if available
                $host = getenv('PGHOST') ?: 'localhost';
                $port = getenv('PGPORT') ?: 5432;
                $dbname = getenv('PGDATABASE') ?: 'u446139296_livvra';
                $username = getenv('PGUSER') ?: 'u446139296_livvra';
                $password = getenv('PGPASSWORD') ?: '';
                $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
                $driver_options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
            }
            $this->pdo = new PDO($dsn, $username, $password, $driver_options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) { self::$instance = new self(); }
        return self::$instance;
    }

    public function getConnection() { return $this->pdo; }
}

function db() { return Database::getInstance()->getConnection(); }
?>
