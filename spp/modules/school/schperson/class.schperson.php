<?php
require_once('entityexceptions.php');

/**
 * class SPP_Ajax
 * extends \SPP\SPPObject
 * Deals with ajax calls
 */
class SCH_Person extends SPPEntity{
    protected $enttab;
    protected $props=[array('pname','varchar(40)'),
                                    array('dob','date'),
                                    array('sexcode','varchar(10)'),
                                array('father','varchar(20)'),
                                array('mother', 'varchar(20)')];
    public function __construct($pname)
    {
        parent::__construct();
        $this->enttab='sch_person_'.$pname;
    }

    public function getTable()
    {
        return $this->enttab;
    }

    public function install()
    {
        $db = new SPP_DB();
        if ($db->tableExists($this->enttab)) {
            $query = 'create table '.$this->enttab.'(entid  varchar(20))';
            $db->execute_query($query);
        }
        foreach($this->props as $prop)
        {
            if(!$db->columnExists($this->enttab,$prop[0]))
            {
                $query = 'alter table ' . $this->enttab . ' add column '.$prop[0].'  '.$prop[1];
                $db->execute_query($query);
            }
            
        }
    }


}
?>