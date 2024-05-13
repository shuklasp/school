<?php
namespace School;

use SPPEntityRelations;

class Person extends \SPPMod\SPPEntity
{
    const TABLENAME = 'person';
    
    //public $children;
    


    /* public function __construct($person_id=null)
    {
        //$attributes = array('name'=>'varchar(255)', 'sex'=>'int(11) default NULL', 'dob'=>'date default NULL', 'address'=>'varchar(255) default NULL', 'phone'=>'varchar(255) default NULL', 'email'=>'varchar(255) default NULL', 'father_id'=>'int(11) default NULL', 'mother_id'=>'int(11) default NULL', 'spouse_id'=>'int(11) default NULL');
        //print_r($this->_attributes);
        //self::addAttributes($attributes);
        //self::$_initial_id = 100001;
        //self::$_sequence = self::TABLENAME;
        //self::$_table=self::TABLENAME;
        //self::$children = array();
        //parent::__construct($person_id);
        //$this->name = 'Shukla';
        //$this->install();
        //$this->save();
        //$this->children=new \SPPEntityRelations($this, 'ER_OneToMany','id', 'person_id', 'Children');
        
    }
 */
    public function after_creation(){
        //self::$children = array();
        //parent::__construct($person_id);
        //var_dump($this->_attributes);
        //self::$children = array();)
        $this->name = 'Shukla';
        $this->install();
        $this->save();
    }

    public function define_attributes()
    {
        self::$_initial_id = 100001;
        self::$_sequence = self::TABLENAME;
        self::$_table = self::TABLENAME;
        //parent::define_attributes();
        return array('name' => 'varchar(255)',
        'sex' => 'int(11) default NULL',
        'dob' => 'date default NULL',
        'address' => 'varchar(255) default NULL',
        'phone' => 'varchar(255) default NULL',
        'email' => 'varchar(255) default NULL',
        'father_id' => 'int(11) default NULL',
        'mother_id' => 'int(11) default NULL',
        'spouse_id' => 'int(11) default NULL') ;
    }
}

\SPPMod\SPPEntityRelations::registerEntityRelation('\School\Person', 'id', '\School\Person', 'person_id', 'ER_OneToOne');
\SPPMod\SPPEntityRelations::relateEntities('\School\Person=>\School\Person','100027','100037');
?>