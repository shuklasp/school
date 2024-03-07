<?php
require_once 'class.sppdatabase.php';
require_once 'class.sppentity.php';
/**
 * class SPP_DB_Entity
 *
 * Defines a SPP database entity.
 *
 * @author Satya Prakash Shukla
 */
abstract class SPP_DB_Entity extends SPP_Entity{

    /**
     * Constructor
     * Load attribute values here.
     */
    public function  __construct() {
        parent::__construct();
    }

    /**
     * function isNew()
     *
     * Return true if entity is new. Else return false.
     * Needs to be imlemented in the child class.
     *
     * @return bool
     *
     */
    protected abstract function isNew();


    /**
     * getProperty()
     * To be implemented only if extra properties are required.
     *
     * @param mixed $propname
     * @return property value. false if property not found.
     */
    protected function getProperty($propname)
    {
        return false;
    }
    
    /**
     * getProperty()
     * To be implemented only if extra properties are required.
     *
     * @param mixed $propname
     * @return property value. false if property not found.
     */
    protected function setProperty($propname,$propval)
    {
        return false;
    }

    /**
     * function insertNew()
     *
     * For inserting new entity in the database.
     */
    protected abstract function insertNew();

    /**
     * function updateEntity()
     *
     * For updating existing entity in the database.
     */
    protected abstract function updateEntity();


    /*
     * function saveToDB()
     *
     * Saves the property to database.
     */
    public function saveToDB()
    {
        if($this->isNew())
        {
            insertNew();
        }
        else
        {
            updateEntity();
        }
    }
}
?>