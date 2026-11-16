<?php
/**
 * HOME TASTE FOODS - Supabase (PostgreSQL) via PDO
 * mysqli-compatible shim for existing app code
 */

class PgResult {
    private $rows = [];
    private $idx = 0;
    public function __construct(array $rows) { $this->rows = array_values($rows); }
    public function fetch_object() {
        if ($this->idx >= count($this->rows)) return null;
        $r = $this->rows[$this->idx++];
        return is_object($r) ? $r : (object) $r;
    }
    public function fetch_assoc() {
        if ($this->idx >= count($this->rows)) return null;
        $r = $this->rows[$this->idx++];
        return (array) $r;
    }
    public function num_rows() { return count($this->rows); }
}

class PgStmt {
    private $pdo;
    private $sql;
    /** @var PDOStatement */
    private $stmt;
    private $result;
    private $ok = false;
    private $bindRefs = [];
    private $numRows = [];
    private $rowIdx = 0;

    public function __construct(PDO $pdo, string $sql) {
        $this->pdo = $pdo;
        $sql = str_replace('`', '', $sql);
        $this->sql = $sql;
        $this->stmt = $pdo->prepare($this->sql);
    }

    public function bind_param($types, &...$vars) {
        $i = 1;
        foreach ($vars as &$v) {
            $this->stmt->bindValue($i, $v);
            $i++;
        }
        return true;
    }

    public function bind_result(&...$vars) {
        $this->bindRefs = [];
        foreach ($vars as &$v) {
            $this->bindRefs[] = &$v;
        }
        return true;
    }

    public function execute() {
        try {
            $this->ok = $this->stmt->execute();
            $this->rowIdx = 0;
            $this->numRows = [];
            $assoc = [];
            if ($this->ok && $this->stmt->columnCount() > 0) {
                while ($row = $this->stmt->fetch(PDO::FETCH_ASSOC)) {
                    $assoc[] = $row;
                    $this->numRows[] = array_values($row);
                }
            }
            $this->result = new PgResult($assoc);
            return $this->ok;
        } catch (PDOException $e) {
            error_log('SQL: ' . $e->getMessage() . ' | ' . $this->sql);
            $this->ok = false;
            return false;
        }
    }

    public function store_result() { return true; }

    public function fetch() {
        if ($this->rowIdx >= count($this->numRows)) return null;
        $row = $this->numRows[$this->rowIdx++];
        foreach ($this->bindRefs as $i => &$ref) {
            $ref = $row[$i] ?? null;
        }
        return true;
    }

    public function get_result() {
        return $this->result ?: new PgResult([]);
    }

    public function close() { return true; }

    public function __get($name) {
        if ($name === 'affected_rows') {
            return $this->stmt ? $this->stmt->rowCount() : 0;
        }
        return null;
    }
}

class PgMysqliShim {
    public $connect_errno = 0;
    public $connect_error = '';
    public $error = '';
    private $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function prepare($sql) {
        try {
            return new PgStmt($this->pdo, $sql);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function query($sql) {
        try {
            $sql = str_replace('`', '', $sql);
            $res = $this->pdo->query($sql);
            if ($res === false) return false;
            return new PgResult($res->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function set_charset($c) { return true; }
    public function close() { return true; }
}

function htf_pdo(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $url = getenv('DATABASE_URL') ?: getenv('SUPABASE_DB_URL') ?: '';
    if ($url) {
        $p = parse_url($url);
        $host = $p['host'] ?? 'db.supabase.co';
        $port = $p['port'] ?? 5432;
        $user = isset($p['user']) ? urldecode($p['user']) : 'postgres';
        $pass = isset($p['pass']) ? urldecode($p['pass']) : '';
        $db   = isset($p['path']) ? ltrim($p['path'], '/') : 'postgres';
        $dsn  = "pgsql:host={$host};port={$port};dbname={$db};sslmode=require";
    } else {
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '5432';
        $user = getenv('DB_USER') ?: 'postgres';
        $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
        $db   = getenv('DB_NAME') ?: 'postgres';
        $dsn  = "pgsql:host={$host};port={$port};dbname={$db};sslmode=require";
    }

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        if (getenv('APP_ENV') === 'production') {
            http_response_code(503);
            die('Database temporarily unavailable.');
        }
        die('DB connection failed: ' . htmlspecialchars($e->getMessage()));
    }
    return $pdo;
}

$mysqli = new PgMysqliShim(htf_pdo());
