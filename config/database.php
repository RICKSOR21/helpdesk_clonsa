<?php
/**
 * CLASE DE CONEXIÓN A BASE DE DATOS
 * Usando PDO para seguridad y compatibilidad
 */

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;
    private $conn = null;

    /**
     * Obtener conexión a la base de datos
     */
    public function getConnection() {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $this->charset
                ];
                
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
                
                if (DEBUG_MODE) {
                    error_log("Conexión a base de datos establecida correctamente");
                }
            } catch(PDOException $e) {
                if (DEBUG_MODE) {
                    error_log("Error de conexión: " . $e->getMessage());
                }
                throw new Exception("Error de conexión a la base de datos");
            }
        }
        
        return $this->conn;
    }

    /**
     * Cerrar conexión
     */
    public function closeConnection() {
        $this->conn = null;
    }

    /**
     * Iniciar transacción
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    /**
     * Confirmar transacción
     */
    public function commit() {
        return $this->conn->commit();
    }

    /**
     * Revertir transacción
     */
    public function rollBack() {
        return $this->conn->rollBack();
    }

    /**
     * Obtener el último ID insertado
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
}

?>
