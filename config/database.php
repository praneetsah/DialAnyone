<?php
/**
 * Database Configuration
 * 
 * This file contains database connection settings
 */

// Database credentials - Change these to match your database settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name'); // Your database name
define('DB_USER', 'your_database_user'); // Your database username
define('DB_PASS', 'your_database_password'); // Your database password
define('DB_CHARSET', 'utf8mb4');

// Global PDO connection
$pdo = null;

/**
 * Get database connection
 * 
 * @return PDO Database connection object
 */
function getDbConnection() {
    global $pdo;
    
    // Return existing connection if available
    if ($pdo instanceof PDO) {
        // Check if connection is still alive
        try {
            $pdo->query('SELECT 1');
            return $pdo;
        } catch (\PDOException $e) {
            // Connection is stale, create a new one
            $pdo = null;
        }
    }

    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5, // 5 seconds timeout
            PDO::MYSQL_ATTR_FOUND_ROWS   => true,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // Log successful connection
        file_put_contents(__DIR__ . '/../logs/database-log.txt', 
            date('Y-m-d H:i:s') . ' - Database connection established successfully' . "\n", 
            FILE_APPEND
        );
        
        return $pdo;
    } catch (\PDOException $e) {
        // Log error
        $errorMessage = date('Y-m-d H:i:s') . ' - Database connection error: ' . $e->getMessage() . "\n";
        file_put_contents(__DIR__ . '/../logs/database-log.txt', $errorMessage, FILE_APPEND);
        
        // Return null to indicate connection failure
        return null;
    }
}

// Establish initial connection
if (!isset($pdo) || $pdo === null) {
    $pdo = getDbConnection();
}

/**
 * Execute a query and return the result
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @return mixed Query result or false on failure
 */
function dbQuery($sql, $params = []) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (\PDOException $e) {
        // Log error
        file_put_contents(__DIR__ . '/../logs/database-log.txt', 
            date('Y-m-d H:i:s') . ' - Database query error: ' . $e->getMessage() . "\n", 
            FILE_APPEND
        );
        return false;
    }
}

/**
 * Get a single row from the database
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @return array|false Row data or false on failure
 */
function dbFetchRow($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    if (!$stmt) {
        return false;
    }
    
    return $stmt->fetch();
}

/**
 * Get multiple rows from the database
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @return array|false Rows data or false on failure
 */
function dbFetchAll($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    if (!$stmt) {
        return false;
    }
    
    return $stmt->fetchAll();
}

/**
 * Insert data into the database
 * 
 * @param string $table Table name
 * @param array $data Data to insert (column => value)
 * @return int|false Last insert ID or false on failure
 */
function dbInsert($table, $data) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $pdo->lastInsertId();
    } catch (\PDOException $e) {
        // Log error
        file_put_contents(__DIR__ . '/../logs/database-log.txt', 
            date('Y-m-d H:i:s') . ' - Database insert error: ' . $e->getMessage() . "\n", 
            FILE_APPEND
        );
        return false;
    }
}

/**
 * Update data in the database
 * 
 * @param string $table Table name
 * @param array $data Data to update (column => value)
 * @param string $where Where clause
 * @param array $whereParams Parameters for where clause
 * @return bool Success or failure
 */
function dbUpdate($table, $data, $where, $whereParams = []) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $set = [];
        foreach ($data as $column => $value) {
            $set[] = "$column = ?";
        }
        
        $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE $where";
        
        $stmt = $pdo->prepare($sql);
        $params = array_merge(array_values($data), $whereParams);
        $stmt->execute($params);
        
        return $stmt->rowCount() > 0;
    } catch (\PDOException $e) {
        // Log error
        file_put_contents(__DIR__ . '/../logs/database-log.txt', 
            date('Y-m-d H:i:s') . ' - Database update error: ' . $e->getMessage() . "\n", 
            FILE_APPEND
        );
        return false;
    }
}

/**
 * Delete data from the database
 * 
 * @param string $table Table name
 * @param string $where Where clause
 * @param array $params Parameters for where clause
 * @return bool Success or failure
 */
function dbDelete($table, $where, $params = []) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $sql = "DELETE FROM $table WHERE $where";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount() > 0;
    } catch (\PDOException $e) {
        // Log error
        file_put_contents(__DIR__ . '/../logs/database-log.txt', 
            date('Y-m-d H:i:s') . ' - Database delete error: ' . $e->getMessage() . "\n", 
            FILE_APPEND
        );
        return false;
    }
} 