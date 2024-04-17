<?php
require_once('entityexceptions.php');
require_once('class.sppentityrelations.php');
/**
 * class Entity
 * Defines an entity
 */

 abstract class SPP_Entity{
   protected $id=NULL;
   protected static $_id_field='id';
   protected static $_sequence='entities';
   protected static $_initial_id=1;
   protected static $_table='entities';
   protected static $_attributes=array();      /** Name-datatype pairs */
   protected $_values=array();                      /** attribute-value pairs */

   /**
    * public function __construct($id, $name)
    * Constructor
    * @param int $id
    */
   public function __construct($id=null){
    $this->_values=array();
    self::addAttributes($this->define_attributes());
    $this->after_creation();
    $this->id = $id;
    if($id!=null)
    {
      $this->load($id);
    }
   }

   public function define_attributes()
   {
    throw new \SPP\SPP_Exception('Required method define_attributes() not defined in entity '.get_class($this).'.');
   }

   public function after_creation(){
    // To be implemented in derived classes
   }

   /**
    * public function __toString()
    * Returns the name of the entity
    * @return string
    */
   public function __toString(){
     return strval($this->id);
   }
   
   /**
    * public function __isset($arrt)
    * Magic function to check if attribute exists
    * @return bool $attr exists or not
    * @param string $attr
    * @return bool $attr exists or not 
    * @see http://php.net/manual/en/language.oop5.overloading.php#object.isset
    * @see http://php.net/manual/en/language.oop5.magic
    */
   public function __isset($attr)
   {
    return ($this->id==null)?false:true;
   }

   /**
    * public function getId()
    * Returns the id of the entity
    * @return int
    */
   public function getId(){
     return $this->id;
   }
   
      /**
    * public function setId($id)
    * Sets the id of the entity
    * @param int $id
    */
   public function setId($id){
    $this->id=$id;
   }
   
   /**
    * public function getAttributes()
    * Returns the attributes of the entity
    * @return array $_attributes
    */
   public function getAttributes(){
     return self::$_attributes;
   }
   
   /**
    * public function getValues()
    * Gets the values of the entity
    * @return array $_values
    */
   public function getValues(){
     return $this->_values;
   }

   /**
    * public function setValues($values)
    * Sets the values of the entity
    * @param array $values
    */
   public function setValues($values){
    $atts=array();
    foreach(self::$_attributes as $att=>$type)
    {
      $atts[]=$att;
    }
    foreach($values as $att=>$val)
    {
      if(in_array($att,$atts))
      {
        $this->_values[$att]=$val;
      }
      else
      {
        throw new AttributeNotFoundException('Wrong attribute '.$att.' accessed');
      }
    }
   }

   /****************************************************************
    * STATIC METHODS
    ****************************************************************/

   /**
    * public static function getEntityName($entity)
    * Gets the name of the entity
    * @param
    */
   public static function entityExists(mixed $entity_name){
     if(class_exists($entity_name))
     {
       if(is_a($entity_name,'SPP_Entity',true))
       {
         return true;
       }
       else
       {
         return false;
       }
     }
     else
     {
       return false;
     }
   }

   /**
    *  public static function getEntityName($entity)
    * Gets the name of the entity
    * @param  $entity
    * @return string $entity_name
    */
   public static function getEntityName($entity){
     return get_class($entity);
   }


  /*public static function __set_state($properties){
    $atts = array();
    foreach ($this->_attributes as $att => $type) {
      $atts[] = $att;
    }
    foreach ($this->_values as $att => $val) {
      if (in_array($att, $atts)) {
        $this->_values[$att] = $val;
      } else {
        throw new AttributeNotFoundException('Wrong attribute ' . $att . ' accessed');
      }
    }
  }*/

   /**
    * public function getTable()
    * Gets the table of the entity
    * @return string $_table
    */
   public function getTable(){
     return self::$_table;
   }

   /**
    * public function setTable($table)
    * Sets the table of the entity
    * @param string $table
    */
   public function setTable($table){
    self::$_table=$table;
   }

  /**
   * public function set($attribute, $value)
   * Sets the value af an attribute of entity
   * @param string $attribute
   * @param mixed $value
   */
   public function set($attribute, $value)
  {
    $atts = array();
    //print('setting '.$attribute.' to '.$value);
    //var_dump(self::$_attributes);
    $classVar = get_object_vars($this);
    if (array_key_exists($attribute, $classVar)) {
      $this->$attribute = $value;
    } else {
      foreach (self::$_attributes as $att => $type) {
        $atts[] = $att;
      }
      if (in_array($attribute, $atts)) {
        $this->_values[$attribute] = $value;
        return true;
      } else {
        throw new AttributeNotFoundException('Wrong attribute ' . $attribute . ' accessed');
      }
    }
  }


  /**
   * public function __set($attribute, $value)
   * Sets the value af an attribute of entity
   * @param string $attribute
   * @param mixed $value
   */
  public function __set($attribute, $value)
  {
    $atts = array();
    $classVar = get_object_vars($this);
      if (array_key_exists($attribute, $classVar)) {
      $this->$attribute = $value;
    }
    else
    {
      foreach (self::$_attributes as $att => $type) {
        $atts[] = $att;
      }
      if (in_array($attribute, $atts)) {
        $this->_values[$attribute] = $value;
        //return true;
      } else {
        throw new AttributeNotFoundException('Wrong attribute ' . $att . ' accessed');
      }
    }
    return $value;
   }

  public function setAttributes($attributes)
  {
    foreach($attributes as $att=>$val)
    {
      $this->$att=$val;
    }
    return $this->id;
  }


  /**
   * public function attributeExists($attribute)
   * Checks if an attribute exists
   * @param string $attribute
   * @return bool $exists
   */
  public function attributeExists($attribute){
    $atts = array();
    $exists=array();
    $classVar = get_object_vars($this);
    //print_r($classVar);
    if (array_key_exists($attribute, $classVar)) {
      $exists = true;
    } else {
      foreach (self::$_attributes as $att => $type) {
        $atts[] = $att;
      }
      //print_r($atts);
      //echo 'attribute '.$attribute.'<br>';
      if (in_array($attribute, $atts)) {
        //echo 'attribute '.$attribute.' exists<br>';
        $exists = true;
      } else {
        //echo 'attribute '.$attribute.' does not exist<br>';
        $exists = false;
      }
    }
    return $exists;
  }

  /**
   * public function get($attribute)
   * Gets the value of an attribute of entity.
   * @param string $attribute
   * @return mixed
    */ 
  public function get($attribute){
    $atts = array();
    $classVar = get_object_vars($this);
    if (array_key_exists($attribute, $classVar)) {
      return $this->$attribute;
    } else {
      foreach (self::$_attributes as $att => $type) {
        $atts[] = $att;
      }
      if (in_array($attribute, $atts)) {
        return $this->_values[$attribute];
      } else {
        throw new AttributeNotFoundException('Wrong attribute ' . $att . ' accessed');
      }
    }
   }
   
   public static function addAttributes($attributes)
   {
    //echo '<br>adding attribute '.$attributes.' in '.self::$_table.'<br>';
    foreach( $attributes as $key => $value )
    {
      self::$_attributes[$key] = $value;
    }
     //var_dump(self::$_attributes);
     self::install();
   }
   
   protected function removeAttribute($attribute)
   {
     $key = array_search($attribute, $this->_attributes);
     if ($key !== false) {
       unset(self::$_attributes[$key]);
     }
   }

   /**
    * protected function install()
    * installs the entity and creates table for entity.
    */
   protected static function install()
   {
     $db=new \SPP_DB();
     if(!$db->tableExists(self::$_table))
     {
      $sql='create table %tab% ('. self::$_id_field.' varchar(20))';
      $db->exec_squery($sql, self::$_table);
     }
     $db->add_columns(self::$_table, self::$_attributes);
   }
   
   /**
    * protected function uninstall()
    * uninstalls the entity and drops all the columns except id and name
    */
   protected function uninstall(){
     $db=new \SPP_DB();
     $db->remove_columns(self::$_table, $this->_attributes);
   }
   
   /**
    * pubic function save()
    * Save the current entity.
    * Insert if new entity.
    */
   public function save()
   {
  //  print_r($this);
    if($this->id==null)
    {
      return $this->insert();
    }
    else
    {
      $this->update();
      return $this->id;
    }
   }

   /**
    * protected function createId()
    * Creates a new id for the entity.
    * @return int $new_id
    */
   protected function createId()
   {
        if (!SPP_Sequence::sequenceExists(self::$_sequence)) {
            SPP_Sequence::createSequence(self::$_sequence, self::$_initial_id, 1);
        }
        $new_id = SPP_Sequence::next(self::$_sequence);
        return $new_id;
    }
   
   /**
    * public function insert()
    * inserts a new entity into the table.
    * @return mixed $new_id
    */
   public function insert()
   {
     $db=new \SPP_DB();
     $new_id=$this->createId();
     $this->id=$new_id;
     $val_array=array_merge(array(self::$_id_field=>$new_id),$this->_values);
     $db->insertValues(self::$_table,$val_array);
     return $new_id;
   }
   
   /**
    * public function update()
    * Updates the entity.
    * @return boolean
    */
   public function update()
   {
     $db=new \SPP_DB();
     if($this->id!=null)
     {
      $db->updateValues(self::$_table, $this->_values, self::$_id_field . '=' . $this->id);
      return true;
     }
     else
     {
      return false;
     }
   }

   /**
    * public function delete()
    * Deletes the present entity record.
    */
   public function delete()
   {
     $db=new \SPP_DB();
     $sql='delete from %tab% where '. self::$_id_field.'=?';
     $db->exec_squery($sql, self::$_table, array($this->id));
     $this->id=null;
   }

   /**
    * public function load($id)
    * Loads an entity from the table.
    * @param int $id
    * @throws EntityNotFoundException
    * @return mixed $result
    **/
   public function load($id)
   {
     $db=new \SPP_DB();
     $sql='select * from %tab% where '. self::$_id_field.'=?';
     $result=$db->exec_squery($sql, self::$_table,array($id));
     //print_r($result);
     if(sizeof($result)>0)
     {
       $row=$result[0];
       foreach($row as $attribute=>$value)
       {
        if(!is_numeric($attribute))
        {
         $this->set($attribute, $value);
        }
       }
     }
     else{
      throw new EntityNotFoundException('Entity with id ' . $id . ' not found');
     }
   }

   /**
    * public function loadBy($attribute, $value)
    * Loads an entity from the table.
    * @param string $attribute
    * @param mixed $value
    * @throws EntityNotFoundException
    * @return mixed $result
    */
   public function loadBy($attribute, $value)
   {
     $db=new \SPP_DB();
     $sql='select * from %tab% where '.$attribute.'=?';
     $result=$db->exec_squery($sql, self::$_table,$value);
     if(sizeof($result)>0)
     {
       $row=$result[0];
       foreach($row as $attribute=>$value)
       {
         $this->set($attribute, $value);
       }
     }
     else{
      throw new EntityNotFoundException('Entity with ' . $attribute . '=' . $value . ' not found');
     }
   }
   
   /**
    * public function loadAll()
    * Loads all entities from the table.
    * @return mixed $entities
    */
   public function loadAll()
   {
     $db=new \SPP_DB();
     $sql='select * from %tab%';
     $result=$db->exec_squery($sql, self::$_table);
     $entities=array();
     foreach($result as $row)
     {
       $entity=new SPP_Entity($row[self::$_id_field]);
       $entities[]=$entity;
     }
     return $entities;
   }

   /**
    * public function loadMultiple($attributes, $values)
    * Loads multiple entities from the table.
    * @param array $attributes
    * @param array $values
    * @return mixed $entities
    */
   public function loadMultiple(array $attributes, array $values)
   {
     $db = new \SPP_DB();
     $sql = 'select * from %tab% where ' . implode('=?, ', $attributes) . '=? ';
     $result = $db->exec_squery($sql, self::$_table,$values);
     $entities = array();
     foreach ($result as $row) {
       $entity = new SPP_Entity($row[self::$_id_field]);
       $entities[] = $entity;
     }
     return $entities;
   }
}


?>