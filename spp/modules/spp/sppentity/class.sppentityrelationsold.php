<?php

class SPP_EntityRelations
{
    public static $related_entities=array();

    public $relation_type=NULL;
    public SPP_Entity $this_entity;
    public $current_entity_id_field=NULL;
    public $related_entity_id_field=NULL;
    public $related_entity_class;
    public $related_field_info=NULL;
    public static $ER_OneToOne="ER_OneToOne";
    public static $ER_OneToMany="ER_OneToMany";
    public static $ER_ManyToOne="ER_ManyToOne";
    public static $ER_ManyToMany="ER_ManyToMany";
    public static $relations=array();

    /****
     * SPP_EntityRelations constructor.
     * @param $relation_type
     * @param $current_entity_id
     * @param $related_entity_id
     * @throws SPP_Exception
     */
    public function __construct($this_entity, $relation_type, $related_entity_class)
    {
        if(is_null($relation_type) || is_null($related_entity_class) || !is_a($this_entity,'SPP_Entity'))
        {
            throw new SPP_Exception("Invalid parameters for EntityRelations");
        }
        $rel_class=strtolower($related_entity_class);
        $this_class=strtolower(get_class($this_entity));

        if($relation_type=="ER_OneToOne")
        {
            $this->current_entity_id_field='id';
            $this->related_entity_id_field='id';
        }
        else if($relation_type=="ER_OneToMany")
        {
            $this->current_entity_id_field='id';
            $this->related_entity_id_field=$this_class.'_id';
        }
        else if($relation_type=="ER_ManyToOne")
        {
            $this->current_entity_id_field= $rel_class . '_id';
            $this->related_entity_id_field='id';
        }
        else if($relation_type=="ER_ManyToMany")
        {
            $this->current_entity_id_field= $rel_class.'_id';
            $this->related_entity_id_field= $this_class.'_id';
        }
        else
        {
            throw new SPP_Exception("Invalid relation type");
        }
        $this->relation_type = $relation_type;
        $this->related_entity_class = $related_entity_class;
        $this->this_entity = $this_entity;
        if(!SPP_Entity::entityExists($this->related_entity_class))
        {
            throw new SPP_Exception("Invalid related entity class");
        }
        if($this->current_entity_id_field=='id')
        {
            $this->related_field_info = 'This_Entity';
        }
        else if($this->related_entity_id_field=='id') {
            $this->related_field_info = 'Related_Entity';
        }
        else
        {
            throw new SPP_Exception("Invalid field name");
        }
        if($relation_type!="ER_OneToOne" && $relation_type!="ER_OneToMany" && $relation_type != "ER_ManyToOne")
        {
            throw new SPP_Exception("Invalid relation type");
        }

    }


    public static function registerEntityRelation(
        string $parent_entity,
        string $parent_entity_field,
        string $child_entity,
        string $child_entity_field,
        string $relation_type
    ) {
/*         if (is_null($relation_type) || is_null($related_entity_class) || !is_a($this_entity, 'SPP_Entity')) {
            throw new SPP_Exception("Invalid parameters for EntityRelations");
        }
        $rel_class = strtolower($related_entity_class);
        $this_class = strtolower(get_class($this_entity));
 */
        $rel_array = array();
        $rel_array['parent_entity'] = $parent_entity;
        $rel_array['parent_entity_field'] = $parent_entity_field;
        $rel_array['child_entity'] = $child_entity;
        $rel_array['child_entity_field'] = $child_entity_field;
        $rel_array['relation_type'] = $relation_type;
        if (!SPP_Entity::entityExists($parent_entity)) {
            throw new SPP_Exception("Invalid parent entity class");
        }
        if (!SPP_Entity::entityExists($child_entity)) {
            throw new SPP_Exception("Invalid child entity class");
        }
        $prev_rel = array();
        if (SPP_Registry::isRegistered('EntityRelations')) {
            $prev_rel = SPP_Registry::get('EntityRelations');
            $prev_rel[] = $rel_array;
        } else {
            $prev_rel[] = $rel_array;
        }
        SPP_Registry::register('EntityRelations', $prev_rel);
        if(!isset(self::$related_entities[$parent_entity]))
        {
            self::$related_entities[$parent_entity]=array();
        } else {
            self::$related_entities[$parent_entity][] = $child_entity;
        }
        if(!isset(self::$related_entities['_'.$child_entity]))   // Preceed child entity with '_'
        {
            self::$related_entities['_'.$child_entity]=array();
        } else {
            self::$related_entities['_'.$child_entity][] = $parent_entity;
        }
    }


/*     public static function registerEntityRelation(
        string $parent_entity,
        string $parent_entity_field,
        string $child_entity,
        string $child_entity_field,
        string $relation_type
    ) {
        if (is_null($relation_type) || is_null($related_entity_class) || !is_a($this_entity, 'SPP_Entity')) {
            throw new SPP_Exception("Invalid parameters for EntityRelations");
        }
        $rel_class = strtolower($related_entity_class);
        $this_class = strtolower(get_class($this_entity));
        $rel_array = array();
        $rel_array['parent_entity'] = $parent_entity;
        $rel_array['parent_entity_field'] = $parent_entity_field;
        $rel_array['child_entity'] = $child_entity;
        $rel_array['child_entity_field'] = $child_entity_field;
        $rel_array['relation_type'] = $relation_type;
        if (!SPP_Entity::entityExists($parent_entity)) {
            throw new SPP_Exception("Invalid parent entity class");
        }
        if (!SPP_Entity::entityExists($child_entity)) {
            throw new SPP_Exception("Invalid child entity class");
        }
        $prev_rel = array();
        if (SPP_Registry::isRegistered('EntityRelations')) {
            $prev_rel = SPP_Registry::get('EntityRelations');
            $prev_rel[] = $rel_array;
        } else {
            $prev_rel[] = $rel_array;
        }
        SPP_Registry::register('EntityRelations', $prev_rel);
        if(!isset(self::$related_entities[$parent_entity
       )
        self::$related_entities[$parent_entity]=array_merge(self::$related_entities[$parent_entity],array($child_entity));
        // Preceed '_' for child entity to avoid conflict with parent entity
        self::$related_entities['_'.$child_entity]=array_merge(self::$related_entities['_'.$child_entity],array($parent_entity));
    }

 */
/*     public static function registerEntityRelation(SPP_Entity $entity1, string $entity_field1, SPP_Entity $entity2,
    string $entity_field2, string $relation_type, $related_entity_class)
    {
        if (is_null($relation_type) || is_null($related_entity_class) || !is_a($this_entity, 'SPP_Entity')) {
            throw new SPP_Exception("Invalid parameters for EntityRelations");
        }
        $rel_class = strtolower($related_entity_class);
        $this_class = strtolower(get_class($this_entity));
        $rel_array = array();

        if ($relation_type == "ER_OneToOne") {
            $rel_array['current_entity_id_field'] = 'id';
            $rel_array['related_entity_id_field'] = 'id';
        } else if ($relation_type == "ER_OneToMany") {
            $rel_array['current_entity_id_field'] = 'id';
            $rel_array['related_entity_id_field'] = $this_class . '_id';
        } else if ($relation_type == "ER_ManyToOne") {
            $rel_array['current_entity_id_field'] = $rel_class . '_id';
            $rel_array['related_entity_id_field'] = 'id';
        } else if ($relation_type == "ER_ManyToMany") {
            $rel_array['current_entity_id_field'] = $rel_class . '_id ';
            $rel_array['related_entity_id_field'] = $this_class . '_id';
        } else {
            throw new SPP_Exception("Invalid relation type");
        }
        $rel_array['relation_type'] = $relation_type;
        $rel_array['related_entity_class'] = $related_entity_class;
        $rel_array['this_entity'] = $this_entity;
        if (!SPP_Entity::entityExists($rel_array['related_entity_class'])) {
            throw new SPP_Exception("Invalid related entity class");
        }
        if ($rel_array['current_entity_id_field'] == 'id') {
            $rel_array['related_field_info'] = 'This_Entity';
        } else if ($rel_array['related_entity_id_field'] == 'id') {
            $rel_array['related_field_info'] = 'Related_Entity';
        } else {
            throw new SPP_Exception("Invalid field name");
        }
        self::$relations[] = $rel_array;
        $prev_rel=array();
        if(SPP_Registry::isRegistered('EntityRelations'))
        {
            $prev_rel=SPP_Registry::get('EntityRelations');
            $prev_rel[]=$rel_array;
        }
        else
        {
            $prev_rel[]=$rel_array;
        }
        SPP_Registry::register('EntityRelations', $prev_rel);

    }
 */

/*     public function __construct($this_entity, $relation_type, $current_entity_id_field, $related_entity_id_field, $related_entity_class)
    {
        if (is_null($relation_type) || is_null($current_entity_id_field) || is_null($related_entity_id_field) || is_null($related_entity_class) || !is_a($this_entity, 'SPP_Entity')) {
            throw new SPP_Exception("Invalid parameters for EntityRelations");
        }
        $this->relation_type = $relation_type;
        $this->current_entity_id_field = $current_entity_id_field;
        $this->related_entity_id_field = $related_entity_id_field;
        $this->related_entity_class = $related_entity_class;
        $this->this_entity = $this_entity;
        if (!SPP_Entity::entityExists($this->related_entity_class)) {
            throw new SPP_Exception("Invalid related entity class");
        }
        if ($this->current_entity_id_field == 'id') {
            $this->related_field_info = 'This_Entity';
        } else if ($this->related_entity_id_field == 'id') {
            $this->related_field_info = 'Related_Entity';
        } else {
            throw new SPP_Exception("Invalid field name");
        }
        if ($relation_type != "ER_OneToOne" && $relation_type != "ER_OneToMany" && $relation_type != "ER_ManyToOne") {
            throw new SPP_Exception("Invalid relation type");
        }
    }
 */
    /**
     * public function findRelatedEntities()
     * Retrun all related entities for the current entity
     * @return mixed
     */
    public function findRelatedEntities()
    {
        $ent = $this->this_entity;
        $rel_ent = new $this->related_entity_class();
        if(SPP_Entity::entityExists($this->related_entity_class))
        {
            throw new SPP_Exception("Invalid entity class".$this->related_entity_class);
        }
        $rel = $rel_ent->loadMultiple(array($this->related_entity_id_field), array($ent->get($this->related_entity_id_field)));
        return $rel;
/*         if($this->relation_type=="ER_OneToOne")
        {
            $rel=$rel_ent->loadMultiple(array($this->related_entity_id_field),$ent->get($this->related_entity_id_field));
            return $rel;
        }
        if($this->relation_type=="ER_OneToMany"){
            $rel=$rel_ent->loadMultiple(array($this->related_entity_id_field),$ent->get($this->related_entity_id_field));
            return $rel;
        }
        if($this->relation_type=="ER_ManyToOne"){
            $rel=$rel_ent->loadMultiple(array($this->current_entity_id_field),$ent->get($this->current_entity_id_field));
            return $rel;
        }
 *//*         if($this->relation_type=="ER_ManyToMany")
        {
            $db=new SPP_DB();
            $sql="SELECT * FROM ".$ent->get('_table').' as ent,'.$rel_ent->get('_table')." as rel_ent WHERE ent.".$this->current_entity_id_field."=rel_ent.".$this->related_entity_id_field." AND ent.".$this->current_entity_id_field."=".$ent->get($this->current_entity_id_field);
            $result=$db->execute_query($sql);
            return $result;
        }
 *///        $ent = new $this->current_entity_class();
//        $ent->loadMultiple(array($this->current_entity_id_field),$this->this_id);
//        return $ent;
    }

    public function findRelatedEntity()
    {
        // TODO: Implement findRelatedEntity() method.
    }

    /**
     * public function findRelatedEntityById(array $atts, array $values)
     * Find a related entity by attributes and values
     * 
     * @param array $atts
     * @param array $values
     * @return mixed
     */
    public function findRelatedEntityBy(array $atts, array $values)
    {
        // TODO: Implement findRelatedEntityById() method.
        $ent = $this->this_entity;
        $rel_ent = new $this->related_entity_class();
        if (SPP_Entity::entityExists($this->related_entity_class)) {
            throw new SPP_Exception("Invalid entity class" . $this->related_entity_class);
        }
        $rel = $rel_ent->loadMultiple(array_merge(array($this->related_entity_id_field),$atts), array_merge(array($ent->get($this->related_entity_id_field)),$values));
        return $rel;
    }


    /**
     * public function addRelatedEntity($values)
     * Add a new related entity
     * @param $values
     * @return mixed
     */
    public function addRelatedEntity($values)
    {
        $values[$this->current_entity_id_field]=$this->this_entity->get($this->current_entity_id_field);
        $rel_ent = new $this->related_entity_class();
        $rel_ent->setValues($values);
        $rel_ent->save();
        return $rel_ent;
    }

    /**
     * public function removeRelatedEntity($id)
     * Remove a related entity
     * @param $id
     * @return bool
     */
    public function removeRelatedEntity($id)
    {
        $rel_ent = new $this->related_entity_class();
        try{
            $rel_ent->load($id);
            $rel_ent->delete();
        }
        catch(SPP_Exception $e)
        {
            return false;
        }
        return true;
    }


    /**
     * public function updateRelatedEntity($id, $values)
     * Update a related entity
     * @param $id
     * @param $values
     * @return bool
     */
    public function updateRelatedEntity($id, $values)
    {
        try{
            $rel_ent = new $this->related_entity_class();
            $rel_ent->load($id);
            $rel_ent->setValues($values);
            $rel_ent->save();
        }
        catch(SPP_Exception $e)
        {
            return false;
        }
        return true;
    }

    public function getRelatedEntity($id)
    {
        // TODO: Implement getRelatedEntity() method.
    }

}


?>