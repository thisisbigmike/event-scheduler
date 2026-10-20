<?php
// Database connection + small helpers for prepared statements.
// Every query that uses user input goes through these helpers, so values
// are always bound with prepared statements (protection against SQL injection).

if (!file_exists(__DIR__ . '/../config.php')) {
    die('config.php not found. Run: cp config.sample.php config.php');
}
$config = require __DIR__ . '/../config.php';

date_default_timezone_set($config['timezone']);

// Make mysqli throw exceptions instead of failing silently.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
    $conn->set_charset('utf8mb4');
    // Keep MySQL's clock in the same timezone as PHP.
    $conn->query("SET time_zone = '" . date('P') . "'");
} catch (mysqli_sql_exception $ex) {
    http_response_code(503); // "service unavailable", so hosting health checks notice
    die('Could not connect to the database. Is MariaDB running and was database/eventdb.sql imported? (' . $ex->getMessage() . ')');
}

// Run a query with bound parameters and return the statement.
// $types is the mysqli type string: i = integer, s = string, d = decimal.
function db_query($sql, $types = '', $params = [])
{
    global $conn;
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

// Return all rows as an array of associative arrays.
function db_all($sql, $types = '', $params = [])
{
    $result = db_query($sql, $types, $params)->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Return the first row, or null if there is none.
function db_one($sql, $types = '', $params = [])
{
    $rows = db_all($sql, $types, $params);
    return $rows ? $rows[0] : null;
}

// Run INSERT / UPDATE / DELETE. Returns the new id for INSERTs.
function db_exec($sql, $types = '', $params = [])
{
    global $conn;
    db_query($sql, $types, $params);
    return $conn->insert_id;
}
