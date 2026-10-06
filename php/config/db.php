<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Central Database Connection Module using PHP Data Objects (PDO)
 * Target Environment: XAMPP (Apache + MySQL/MariaDB)
 */

// Strict error reporting for robust debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide raw errors from public JSON output; log instead

class Database {
    private static ?PDO $instance = null;

    // Database configuration settings (Default XAMPP setup)
    private const DB_HOST = 'localhost';
    private const DB_PORT = '3306';
    private const DB_NAME = 'government_scheme_tracker';
    private const DB_USER = 'root';
    private const DB_PASS = '';
    private const DB_CHARSET = 'utf8mb4';

    /**
     * Get singleton PDO connection instance with prepared statement emulation disabled.
     *
     * @return PDO
     * @throws PDOException
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: self::DB_HOST;
            $port = getenv('DB_PORT') ?: self::DB_PORT;
            $dbname = getenv('DB_NAME') ?: self::DB_NAME;
            $user = getenv('DB_USER') ?: self::DB_USER;
            $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : self::DB_PASS;

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=" . self::DB_CHARSET;

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on SQL errors
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return associative arrays
                PDO::ATTR_EMULATE_PREPARES   => false,                  // Native database prepared statements
                PDO::ATTR_PERSISTENT         => false                   // Clean connection per request
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                // Return a structured JSON response if database is unreachable
                if (!headers_sent()) {
                    header('Content-Type: application/json; charset=utf-8');
                    http_response_code(500);
                }
                echo json_encode([
                    'success' => false,
                    'message' => 'Database connection error: Unable to connect to MySQL database "' . htmlspecialchars($dbname) . '". Please ensure MySQL is started in XAMPP and schema.sql has been imported.',
                    'error_code' => $e->getCode()
                ]);
                exit;
            }
        }

        return self::$instance;
    }
}

/**
 * Procedural helper to retrieve database connection cleanly
 * 
 * @return PDO
 */
function getDB(): PDO {
    return Database::getConnection();
}
