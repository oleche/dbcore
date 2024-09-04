<?php

/*********************************************
 *
 *  Modificaciones:
 * Diego Vásquez -- levantar excepciones al detectar error de mysql -- 23/10/2009
 * Oscar Leche -- Refactoring -- 16/08/2017
 *
 *********************************************/

namespace Geekcow\Dbcore;

use Exception;
use PDO;
use PDOStatement;

/**
 * The DataBase class provides a singleton pattern implementation for database operations.
 * It encapsulates the PDO instance for database connection and provides methods for executing
 * SQL queries, managing transactions, and handling SQL injection prevention.
 */
class DataBase
{
    private $host; // Host name for the database connection.
    private $username; // Username for the database connection.
    private $password; // Password for the database connection.
    private $db_name; // Name of the database to connect to.
    private $db_port; // Port number for the database connection.
    private $db; // PDO instance for database operations.

    protected static $instance = null;

    /**
     * Returns the singleton instance of the DataBase class.
     * If the instance does not exist, it is created with the configuration parameters.
     *
     * @param string $configfile Path to the configuration file.
     * @return DataBase The singleton instance of the DataBase class.
     */
    final public static function getInstance($configfile = "config.ini")
    {
        $config = parse_ini_file($configfile);

        $server = $config['dbcore.server'] ?? 'localhost';
        $db_user = $config['dbcore.db_user'] ?? 'root';
        $db_pass = $config['dbcore.db_pass'] ?? '123';
        $db_database = $config['dbcore.database'] ?? 'fony';
        $db_port = $config['dbcore.port'] ?? '3306';

        if (!self::$instance) {
            self::$instance = new DataBase($server, $db_user, $db_pass, $db_database, $db_port);
            //INSTANCE CREATED
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct instantiation.
     * Initializes the database connection using PDO.
     *
     * @param string $_host Database host.
     * @param string $_username Database username.
     * @param string $_password Database password.
     * @param string $_db_name Database name.
     * @param string $_db_port Database port.
     */
    private function __construct($_host, $_username, $_password, $_db_name, $_db_port)
    {
        $this->host = $_host;
        $this->username = $_username;
        $this->password = $_password;
        $this->db_name = $_db_name;
        $this->db_port = $_db_port;

        // Connect to server and select databse.
        $this->db = new PDO("mysql:host=$_host;port=$_db_port", $_username, $_password) or die("cannot connect");
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

        $result = $this->db->query("SET NAMES 'utf8';");

        $_db_name = "`" . str_replace("`", "``", $_db_name) . "`";
        $this->db->query("CREATE DATABASE IF NOT EXISTS $_db_name;");
        $this->db->query("use $_db_name;");
    }

    /**
     * Executes a SQL query using the PDO instance.
     * Throws an exception if the query execution fails.
     *
     * @param string $sql The SQL query to execute.
     * @return PDOStatement The PDOStatement object resulting from the execution.
     * @throws Exception If the query execution fails.
     */
    public function execute($sql)
    {
        $stmt = $this->db->prepare($sql);
        // si existe un error se levanta una excepci&#65533;n
        if (!$stmt->execute()) {
            throw new Exception($stmt->errorInfo()[0] . ": " . $stmt->errorInfo()[2]);
        }
        return $stmt;
    }

    /**
     * Retrieves the last inserted ID from the database.
     *
     * @return string The last inserted ID.
     */
    public function lastID()
    {
        return $this->db->lastInsertId();
    }

    /**
     * Validates and sanitizes input to prevent SQL injection.
     *
     * @param string $param The input parameter to sanitize.
     * @return string The sanitized input parameter.
     */
    public function checkSQL($param)
    {
        $param = stripslashes($param);
        $param = $this->db->quote($param);
        return $param;
    }

    /**
     * Begins a transaction by setting the autocommit mode to false and starting the transaction.
     */
    public function beginTransaction()
    {
        $this->db->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
        $this->db->query("START TRANSACTION ISOLATION LEVEL SERIALIZATION;");
        $this->db->beginTransaction();
    }

    /**
     * Commits the current transaction and sets the autocommit mode back to true.
     */
    public function commit()
    {
        $this->db->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
        $this->db->commit();
    }

    /**
     * Rolls back the current transaction and sets the autocommit mode back to true.
     */
    public function rollback()
    {
        $this->db->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
        $this->db->rollBack();
    }

    /**
     * Closes the database connection.
     * Currently, this method is not implemented.
     */
    public function close()
    {
        //mysqli_close($this->db);
    }
}
