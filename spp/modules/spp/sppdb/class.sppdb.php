<?php

namespace SPPMod\SPPDB;
/*require_once("class.sppconfig.php");
require_once 'class.sppobject.php';*/
//\SPP\Module::initWS('sppdb');
/**
 * class SPPDB
 * Handles database transations in the system.
 *
 * @author Satya Prakash Shukla
 */

class SPPDB
{
    /** @var array<\PDO> Shared connections pool indexed by connection hash */
    private static array $sharedConnections = [];

    /**
     * Resolves a table name with current context's prefix.
     *
     * @param string $tname
     * @return string
     */
    public static function sppTable(string $tname): string
    {
        $prefix = \SPP\Module::getConfig('table_prefix', 'sppdb');
        
        // If no prefix configured for current context, fallback to default context
        if ($prefix === false && \SPP\Scheduler::getContext() !== 'default') {
            $prefix = \SPP\Module::getConfig('table_prefix', 'sppdb', 'default');
        }
        
        return ($prefix ?: '') . $tname;
    }

    /** @var \PDO The internal PDO instance */
    private \PDO $pdo;
    
    private $numrows;

    /**
     * public function __construct
     * 
     * Creates or reuses a database connection.
     *
     * @param string|null $dburl
     * @param string|null $dbuser
     * @param string|null $dbpasswd
     * @param array|null $options
     * @param bool $shared Whether to use the shared connection pool (default: true)
     * @return void
     */
    public function __construct($dburl = null, $dbuser = null, $dbpasswd = null, $options = null, bool $shared = true)
    {
        try {
            $url = null;
            if ($dburl == null) {
                $dbtype = \SPP\Module::getConfig('dbtype', 'sppdb');
                $dbhost = \SPP\Module::getConfig('dbhost', 'sppdb');
                $dbname = \SPP\Module::getConfig('dbname', 'sppdb');
                $url = $dbtype . ':host=' . $dbhost . ';dbname=' . $dbname;
            } else {
                $url = $dburl;
            }
            $dbuser = ($dbuser == null) ? \SPP\Module::getConfig('dbuser', 'sppdb') : $dbuser;
            $dbpasswd = ($dbpasswd == null) ? \SPP\Module::getConfig('dbpasswd', 'sppdb') : $dbpasswd;

            // Generate a unique key for the connection parameters if sharing is enabled
            $key = null;
            if ($shared) {
                $key = md5(serialize([$url, $dbuser, $dbpasswd, $options]));
                if (isset(self::$sharedConnections[$key])) {
                    $this->pdo = self::$sharedConnections[$key];
                    return;
                }
            }

            // Create new connection if not found in pool or sharing is disabled
            if ($dbuser == null && $dbpasswd == null && $options == null) {
                $this->pdo = new \PDO($url);
            } elseif ($options == null) {
                $this->pdo = new \PDO($url, $dbuser, $dbpasswd);
            } else {
                $this->pdo = new \PDO($url, $dbuser, $dbpasswd, $options);
            }

            // Ensure PDO throws exceptions for consistency with existing error handling
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            if ($shared && $key) {
                self::$sharedConnections[$key] = $this->pdo;
            }
        } catch (\PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new \SPP\SPPException("Database Connection Error: " . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Proxy unknown method calls to the underlying PDO instance.
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->pdo, $name], $arguments);
    }

    /**
     * Proxy prepare to internal PDO
     */
    public function prepare(string $query, array $options = [])
    {
        return $this->pdo->prepare($query, $options);
    }

    /**
     * Proxy query to internal PDO
     */
    public function query(string $query, ?int $fetchMode = null, ...$fetchModeArgs)
    {
        return $this->pdo->query($query, $fetchMode, ...$fetchModeArgs);
    }

    /**
     * Proxy exec to internal PDO
     */
    public function exec(string $statement)
    {
        return $this->pdo->exec($statement);
    }

    /**
     * public function build_query(string $sql,string $tabname)
     * 
     * Builds the query with the table names
     *
     * @param string $sql
     * @param mixed $tabname
     * @return string
     */
    private function build_query($sql, $tabname)
    {
        $result = $sql;
        if (is_array($tabname)) {
            foreach ($tabname as $tab) {
                $result = \SPP\SPPUtils::str_replace_count('%tab%', $tab, $result, 1);
            }
        } else {
            $result = \SPP\SPPUtils::str_replace_count('%tab%', $tabname, $result, 1);
        }
        return $result;
    }

    /**
     * public function exec_squery
     * executes a query securely and returns the result
     *
     * @param string $sql
     * @param string $tabname
     * @param array $values
     * @return array
     */
    public function exec_squery($sql, $tabname, $values = array())
    {
        $qry = $this->build_query($sql, $tabname);
        return $this->execute_query($qry, $values);
    }


    /**
     * public function execute_query
     * 
     * Executes the query and returns the result
     *
     * @param [type] $sql
     * @param array $values
     * @return array
     */
    public function execute_query($sql, $values = array())
    {
        $result = array();
        try {
            if (sizeof($values) > 0) {
                $stmt = $this->prepare($sql);
                $stmt->execute($values);
                $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->query($sql);
                $result = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : array();
            }
            $this->numrows = count($result);
        } catch (\PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            throw new \SPP\SPPException("Database Error: " . $e->getMessage(), (int) $e->getCode(), $e);
        }
        return $result;
    }

    /**
     * public function add_columns
     * 
     * Adds columns to the table
     *
     * @param [type] $table
     * @param array $cols
     * @return void
     */
    public function add_columns($table, $cols = array())
    {
        foreach ($cols as $col => $type) {
            $col = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
            $type = preg_replace('/[^a-zA-Z0-9_\(\)\s,]/', '', $type);
            if (!$this->columnExists($table, $col)) {
                $sql = 'alter table %tab% add ' . $col . ' ' . $type;
                $this->exec_squery($sql, $table);
            }
        }
    }

    /**
     * public function remove_columns
     * 
     * Removes columns from the table
     *
     * @param [type] $table
     * @param array $cols
     */
    public function remove_columns($table, $cols = array())
    {
        foreach ($cols as $col) {
            $col = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
            if ($this->columnExists($table, $col)) {
                $sql = 'alter table %tab% drop column ' . $col;
                $this->exec_squery($sql, $table);
            }
        }
    }

    /**
     * Check if a table exists in the current database.
     *
     * @param string $table Table to search for.
     * @return bool TRUE if table exists, FALSE if no table found.
     */
    public function tableExists($table)
    {
        try {
            // Using parameterized string filtering since SHOW TABLES LIKE does not support standard binding
            $safe_table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
            $result = $this->query("SHOW TABLES LIKE '{$safe_table}'");
            return $result && $result->rowCount() > 0;
        } catch (\Exception $e) {
            return FALSE;
        }
    }

    /**
     * public function columnExists
     * 
     * Returns true if the column exists in the table
     *
     * @param [type] $table
     * @param [type] $col
     * @return bool
     */
    public function columnExists($table, $col)
    {
        $query = "select " . $col . " from {$table} limit 1";
        if ($this->tableExists($table)) {
            try {
                $result = $this->query($query);
            } catch (\Exception $e) {
                return false;
            }
        } else {
            throw new \SPP\SPPException('Table not found');
        }
        return true;
    }

    /**
     * public function insertValues
     * 
     * Inserts values into the table
     *
     * @param [type] $table
     * @param array $columns
     * @param array $values
     *  */
    public function insertValues(string $table, array $columns, array $values = array())
    {
        $cols = array();
        if (sizeof($values) == 0) {
            foreach ($columns as $key => $value) {
                $cols[] = $key;
                $values[] = $value;
            }
        } else {
            $cols = $columns;
        }
        $sql = ') values (';
        for ($i = 0; $i < sizeof($values); $i++) {
            $sql .= '?';
            if ($i < sizeof($values) - 1) {
                $sql .= ',';
            }
        }
        $sql .= ')';
        $sql = 'insert into %tab% (' . implode(', ', $cols) . $sql;
        $this->exec_squery($sql, $table, $values);
    }

    /**
     * public function updateValues(string $table, array $columns, string $where, array $values=array())
     * 
     * Updates values in the table
     *
     * @param string $table
     * @param array $columns
     * @param string $where
     * @param array $values
     */
    public function updateValues(string $table, array $columns, string $where, array $values = array())
    {
        $sql = 'update %tab% set ';
        
        // Properly identify if the provided columns array is an associative mapping
        $is_assoc = array_keys($columns) !== range(0, count($columns) - 1);
        
        if ($is_assoc) {
            $bind_values = [];
            $sql_cols = [];
            foreach ($columns as $col => $val) {
                $sql_cols[] = $col . '=?';
                $bind_values[] = $val;
            }
            $sql .= implode(', ', $sql_cols);
            // Append explicit WHERE bindings provided in the $values array fallback reliably
            $values = array_merge($bind_values, $values);
        } else {
            // Standard indexed fallback expecting all bindings neatly passed inside $values
            $sql_cols = [];
            foreach ($columns as $col) {
                $sql_cols[] = $col . '=?';
            }
            $sql .= implode(', ', $sql_cols);
        }
        
        $sql .= ' where ' . $where;
        $this->exec_squery($sql, $table, $values);
    }

    /**
     * public function createTableIncremental(string $tableName, array $columns)
     * 
     * Creates a table if missing and adds missing columns incrementally.
     * Non-destructive.
     */
    public function createTableIncremental(string $tableName, array $columns)
    {
        if (!$this->tableExists($tableName)) {
            // Create base table with the first column
            $firstCol = array_key_first($columns);
            $firstType = $columns[$firstCol];
            
            // Clean names for raw SQL
            $tableNameSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
            $firstColSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $firstCol);
            
            $sql = "CREATE TABLE {$tableNameSafe} ({$firstColSafe} {$firstType})";
            $this->exec($sql);
        }
        
        // Use existing add_columns to fill in the rest
        $this->add_columns($tableName, $columns);
    }

    /**
     * public function safeInsert(string $tableName, array $data, string $identityField)
     * 
     * Inserts a record only if the identity field value is not already present.
     */
    public function safeInsert(string $tableName, array $data, string $identityField)
    {
        if (!isset($data[$identityField])) {
            throw new \SPP\SPPException("Identity field '{$identityField}' missing in seed data.");
        }

        $tableNameSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
        $identityFieldSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $identityField);

        $checkSql = "SELECT count(*) as cnt FROM {$tableNameSafe} WHERE {$identityFieldSafe} = ?";
        $res = $this->execute_query($checkSql, [$data[$identityField]]);
        
        if ((int)$res[0]['cnt'] === 0) {
            $this->insertValues($tableName, $data);
            return true;
        }
        return false;
    }
}
//\SPP\Module::endWS();
?>
