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
    private $transaction_level = 0; // Depth of nested beginTransaction() calls.

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

        // Connect to server and select databse. The charset goes in the DSN (instead of SET NAMES) so PDO
        // escapes the bound values for the right connection charset.
        $this->db = new PDO("mysql:host=$_host;port=$_db_port;charset=utf8", $_username, $_password)
            or die("cannot connect");
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $_db_name = "`" . str_replace("`", "``", $_db_name) . "`";
        $this->db->query("CREATE DATABASE IF NOT EXISTS $_db_name;");
        $this->db->query("use $_db_name;");
    }

    /**
     * Executes a SQL query as a prepared statement, binding $params to its ? placeholders in order.
     * Throws an exception if the query execution fails.
     *
     * @param string $sql The SQL query to execute.
     * @param array $params Values for the ? placeholders.
     * @return PDOStatement The PDOStatement object resulting from the execution.
     * @throws Exception If the query execution fails.
     */
    public function execute($sql, array $params = array())
    {
        $stmt = $this->db->prepare($sql);
        $position = 1;
        foreach ($params as $value) {
            $this->bindParam($stmt, $position++, $value);
        }
        // si existe un error se levanta una excepci&#65533;n
        if (!$stmt->execute()) {
            throw new Exception($stmt->errorInfo()[0] . ": " . $stmt->errorInfo()[2]);
        }
        return $stmt;
    }

    /**
     * Binds a value by its PHP type: null as NULL, booleans as 1/0, integers as integers and everything else as
     * text, so the column type decides the conversion (a phone like "0123" keeps its leading zero)
     */
    private function bindParam(PDOStatement $stmt, $position, $value)
    {
        if (is_null($value)) {
            $stmt->bindValue($position, null, PDO::PARAM_NULL);
        } elseif (is_bool($value)) {
            $stmt->bindValue($position, $value ? 1 : 0, PDO::PARAM_INT);
        } elseif (is_int($value)) {
            $stmt->bindValue($position, $value, PDO::PARAM_INT);
        } else {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $stmt->bindValue($position, (string)$value, PDO::PARAM_STR);
        }
    }

    /**
     * Quotes and escapes a value for the connection charset (including the surrounding quotes).
     *
     * @param string $value
     * @return string
     */
    public function quote($value)
    {
        return $this->db->quote((string)$value);
    }

    /**
     * Escapes a string literal the way MySQL does for utf8/latin1 connections, for code without a connection.
     *
     * @param string $value
     * @return string the quoted literal
     */
    public static function escapeLiteral($value)
    {
        return "'" . strtr((string)$value, array(
            "\\" => "\\\\",
            "\0" => "\\0",
            "\n" => "\\n",
            "\r" => "\\r",
            "'" => "\\'",
            '"' => '\\"',
            "\x1a" => "\\Z",
        )) . "'";
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
        $param = stripslashes((string)$param);
        $param = $this->db->quote($param);
        return $param;
    }

    /**
     * Begins a transaction. Calls made while a transaction is open are nested with savepoints, so the queries of
     * every model join the outermost transaction and an inner rollback only undoes its own part.
     */
    public function beginTransaction()
    {
        $this->syncTransactionLevel();
        if ($this->transaction_level == 0) {
            $this->db->beginTransaction();
        } else {
            $this->db->exec("SAVEPOINT dbcore_" . $this->transaction_level);
        }
        $this->transaction_level++;
    }

    /**
     * Commits the current (nested) transaction, if there is still one open.
     */
    public function commit()
    {
        if (!$this->syncTransactionLevel()) {
            return;
        }
        $this->transaction_level--;
        if ($this->transaction_level == 0) {
            $this->db->commit();
        } else {
            $this->db->exec("RELEASE SAVEPOINT dbcore_" . $this->transaction_level);
        }
    }

    /**
     * Rolls back the current (nested) transaction, if there is still one open.
     */
    public function rollback()
    {
        if (!$this->syncTransactionLevel()) {
            return;
        }
        $this->transaction_level--;
        if ($this->transaction_level == 0) {
            $this->db->rollBack();
        } else {
            $this->db->exec("ROLLBACK TO SAVEPOINT dbcore_" . $this->transaction_level);
        }
    }

    /**
     * Runs the callback inside a transaction: commits when it returns, rolls back when it returns false or
     * throws (the exception is re-thrown).
     *
     * @param callable $callback
     * @return mixed the value returned by the callback
     */
    public function transaction(callable $callback)
    {
        $this->beginTransaction();
        try {
            $result = $callback();
        } catch (\Throwable $t) {
            $this->rollback();
            throw $t;
        }
        if ($result === false) {
            $this->rollback();
        } else {
            $this->commit();
        }
        return $result;
    }

    /**
     * Since PHP 8.0 pdo_mysql reports the real server transaction state: statements that commit implicitly
     * (CREATE/ALTER TABLE, ...) end the transaction, and PDO::commit()/rollBack() would throw.
     *
     * @return bool whether a transaction is still open
     */
    private function syncTransactionLevel()
    {
        if (!$this->db->inTransaction()) {
            $this->transaction_level = 0;
            return false;
        }
        if ($this->transaction_level == 0) {
            // a transaction opened directly on the PDO connection
            $this->transaction_level = 1;
        }
        return true;
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
