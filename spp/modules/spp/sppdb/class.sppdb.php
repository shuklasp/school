<?php
/*require_once("class.sppconfig.php");
require_once 'class.sppobject.php';*/
//SPP_Module::initWS('sppdb');
/**
 * class SPP_DB
 * Handles database transations in the system.
 *
 * @author Satya Prakash Shukla
 */

 SPP_Event::registerEvent('spp_db_connection');
class SPP_DB extends PDO {
    private $con,$numrows,$numcols,$insertedid;
    /**
     * public function __construct
     * 
     * Creates a database connection
     *
     * @param string $dburl
     * @param string $dbuser
     * @param string $dbpasswd
     * @param [type] $options
     * @return void
     */
    public function __construct($dburl=null,$dbuser=null,$dbpasswd=null,$options=null) {
        $dbmapper=array();
        try {
            $url='old';
            SPP_Event::startEvent('spp_db_connection');
          //  echo 'Creating connection'.$url.$dbuser;
            if($dburl==null)
            {
                $dbtype=SPP_Module::getConfig('dbtype','sppdb');
                $dbhost=SPP_Module::getConfig('dbhost','sppdb');
                $dbname=SPP_Module::getConfig('dbname','sppdb');
                $url = $dbtype.':host='.$dbhost.';dbname='.$dbname;
            }
            else
            {
                $url=$dburl;
            }
            $dbuser=($dbuser==null)?SPP_Module::getConfig('dbuser','sppdb'):$dbuser;
            $dbpasswd=($dbpasswd==null)?SPP_Module::getConfig('dbpasswd','sppdb'):$dbpasswd;
            if($dbuser==null&&$dbpasswd==null&&$options==null)
            {
                parent::__construct($url);
            }
            elseif($options==null)
            {
                parent::__construct($url,$dbuser,$dbpasswd);
            }
            else
            {
                parent::__construct($url,$dbuser,$dbpasswd,$options);
            }
            //$this->con = new PDO($url,$dbuser,$dbpasswd);
        }
        catch(PDOException $e)
        {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();

        }
        //SPP_Event::endEvent('spp_db_connection');
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
    private function build_query($sql,$tabname){
        $result=$sql;
        if(is_array($tabname)){
            foreach($tabname as $tab){
                $result=SPP_Utils::str_replace_count('%tab%', $tab, $result, 1);
            }
        }
        else{
            $result = SPP_Utils::str_replace_count('%tab%', $tabname, $result, 1);
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
    public function exec_squery($sql, $tabname, $values=array()){
        $qry=$this->build_query($sql, $tabname);
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
    public function execute_query($sql, $values=array()) {
        $result = array();
        try {
            if(sizeof($values) > 0) {
                $stmt=$this->prepare($sql);
                $stmt->execute($values);
                $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            else {
                foreach($this->query($sql) as $row) {
                    $result[]=$row;
                }
            }
            $this->numrows=count($result);
        }
        catch(PDOException $e)
        {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
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
    public function add_columns($table ,$cols=array())
    { 
        foreach($cols as $col=>$type)
        {
            if(!$this->columnExists($table,$col)) {
                $sql = 'alter table %tab% add '.$col.' '.$type;
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
            if (!$this->columnExists($table, $col)) {
                $sql = 'alter table %tab% drop column ' . $col;
                $this->exec_squery($sql, $table);
            }
        }
    }

    /**
     * Check if a table exists in the current database.
     *
     * @param PDO $pdo PDO instance connected to a database.
     * @param string $table Table to search for.
     * @return bool TRUE if table exists, FALSE if no table found.
     */
    public function tableExists($table)
    {

        // Try a select statement against the table
        // Run it in try-catch in case PDO is in ERRMODE_EXCEPTION.
        try {
            $result = $this->query("SELECT 1 FROM {$table} LIMIT 1");
        } catch (Exception $e) {
            // We got an exception (table not found)
            return FALSE;
        }

        // Result is either boolean FALSE (no table found) or PDOStatement Object (table found)
        return $result !== FALSE;
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
        $query = "select ".$col." from {$table} limit 1";
        if($this->tableExists($table))
        {
            try{
            $result = $this->query($query);
            }
            catch (Exception $e) {
                return false;
            }
        }
        else{
            throw new SPP_Exception('Table not found');
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
    public function insertValues(string $table, array $columns, array $values=array())
    {
        $cols=array();
        if(sizeof($values) == 0) {
            foreach($columns as $key=>$value) {
                $cols[]=$key;
                $values[]=$value;
            }
        }
        else {
            $cols=$columns;
        }
        $sql= ') values (';
        for($i=0;$i<sizeof($values);$i++) {
            $sql.='?';
            if($i<sizeof($values)-1) {
                $sql.=',';
            }
        }
        $sql.=')';
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
    public function updateValues(string $table, array $columns, string $where, array $values=array())
    {
        $sql= 'update %tab% set ';
        $cols=array();

        if (sizeof($values) == 0) {
            $i=0;
            foreach ($columns as $key => $value) {
                if ($i !== 0) {
                    $sql .=',';
                }
                $sql .= $key . '=? ';
                $values[]= $value;
                $i++;
            }
        } else {
            $i = 0;
            foreach ($columns as $col) {
                if ($i !== 0) {
                    $sql .= ',';
                }
                $sql .= $col . '=? ';
                $i++;
            }
        }
        $sql.=' where '.$where;
        $this->exec_squery($sql, $table, $values);
    }




    /*public function execute_manip_query($sql, $table, $idfield, array $values=array()) {
        $result = array();
        try {
            $this->con->beginTransaction();
            if(sizeof($values) > 0) {
                $stmt=$this->con->prepare($sql);
                $stmt->execute($values);
            //				print_r($stmt);
            //				print_r($values);
            }
            else {
                $this->con->query($sql);
            }
            $this->numrows=count($result);
            $st=$this->con->prepare('select max('.$idfield.') lst from '.$table);
            $this->insertedid=$this->con->lastInsertId();
            $this->con->commit();
        }
        catch(PDOException $e)
        {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }
        return $result;
    }*/
}
//SPP_Module::endWS();
?>