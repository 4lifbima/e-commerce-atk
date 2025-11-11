<?php
/**
 * Database Configuration & Connection
 * File ini berisi konfigurasi database dan class untuk koneksi
 */

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'toko_atk_fotocopy');

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $conn;
    
    /**
     * Membuat koneksi ke database
     * @return mysqli connection
     */
    public function connect() {
        $this->conn = null;
        
        try {
            $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
            
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            // Set charset ke utf8mb4
            $this->conn->set_charset("utf8mb4");
            
        } catch(Exception $e) {
            die("Error Database: " . $e->getMessage());
        }
        
        return $this->conn;
    }
    
    /**
     * Menutup koneksi database
     */
    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

/**
 * Function helper untuk mendapatkan koneksi database
 * @return mysqli connection
 */
function getDB() {
    $database = new Database();
    return $database->connect();
}

/**
 * Function helper untuk escape string (prevent SQL injection)
 * @param string $string
 * @return string escaped string
 */
function escape($string) {
    $db = getDB();
    return $db->real_escape_string($string);
}
?>