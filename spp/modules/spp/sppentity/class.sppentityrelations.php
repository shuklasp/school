<?php

/**
 * class SPP_EntityRelations
 * Defines the relations between entities
 * @author  Satya Prakash Shukla
 * @version 1.0
 * @since   2015-09-15
 */
class SPP_EntityRelations
{
    protected static $related_entities=array();

    /****
     * public static function registerEntityRelation()
     * Registers the relations between entities
     * 
     * @param string $parent_entity
     * @param string $parent_entity_field
     * @param string $child_entity
     * @param string $child_entity_field
     * @param string $relation_type
     * @throws \SPP\SPP_Exception
     */
    public static function registerEntityRelation(
        string $parent_entity,
        string $parent_entity_field,
        string $child_entity,
        string $child_entity_field,
        string $relation_type
    ) {
        $rel_array = array();
        $rel_array['parent_entity'] = $parent_entity;
        $rel_array['parent_entity_field'] = $parent_entity_field;
        $rel_array['child_entity'] = $child_entity;
        $rel_array['child_entity_field'] = $child_entity_field;
        $rel_array['relation_type'] = $relation_type;
        if (!SPP_Entity::entityExists($parent_entity)) {
            throw new \SPP\SPP_Exception("Invalid parent entity class");
        }
        if (!SPP_Entity::entityExists($child_entity)) {
            throw new \SPP\SPP_Exception("Invalid child entity class");
        }
        $prev_rel = array();
        if (\SPP\Registry::isRegistered('EntityRelations')) {
            $prev_rel = \SPP\Registry::get('EntityRelations');
            $prev_rel[] = $rel_array;
        } else {
            $prev_rel[] = $rel_array;
        }
        \SPP\Registry::register('EntityRelations', $prev_rel);
        if(!isset(self::$related_entities[$parent_entity]))
        {
            self::$related_entities[$parent_entity]=array();
        }
        self::$related_entities[$parent_entity][] = $child_entity;
        if(!isset(self::$related_entities['_'.$child_entity]))   // Preceed child entity with '_'
        {
            self::$related_entities['_'.$child_entity]=array();
        }
        self::$related_entities['_'.$child_entity][] = $parent_entity;
        
        $parent=new $parent_entity();
        if(!$parent->attributeExists($parent_entity_field)){
            $parent::addAttributes(array($parent_entity_field => 'varchar(20)'));
        }
        $child=new $child_entity();
        if(!$child->attributeExists($child_entity_field)){
            $child::addAttributes(array($child_entity_field => 'varchar(20)'));
        }

    }

    /**
     * public static function getChilren(string $parent_entity)
     * @param string $parent_entity
     * @return array
     * @throws \SPP\SPP_Exception
     */
    public static function getChildren(string $parent_entity)
    {
        return self::$related_entities[$parent_entity];
    }

    /**
     * public static function getParents(string $child_entity)
     * @param string $child_entity
     * @return array
     * @throws \SPP\SPP_Exception
     */
    public static function getParents(string $child_entity)
    {
        return self::$related_entities['_'.$child_entity];
    }

    /****
     * public static function getRelations()
     * @return array
     */
    public static function getRelations()
    {
        return self::$related_entities;
    }

    /****
     * public static function getRelatedEntitiesByParent()
     * Returns an array of related entities for a given parent entity
     * 
     * @param string $relation
     * @param $parent_id
     * @param array $attributes
     * @param array $values
     * @return array
     */
    public static function getRelatedEntitiesByParent(string $relation, $parent_id, $attributes=array(), $values=array())
    {
        $parent_entity = trim(strtok(($relation), '=>'));
        $child_entity = trim(strtok('=>'));
        if(!SPP_Entity::entityExists($parent_entity)||!SPP_Entity::entityExists($child_entity))
        {
            throw new \SPP\SPP_Exception('Invalid entity class');
        }
        if (array_key_exists($parent_entity, self::$related_entities)) {
            if (!in_array($child_entity, self::$related_entities[$parent_entity])) {
                throw new \SPP\SPP_Exception('No child entity ' . $child_entity . ' in relation ' . $relation . ' found');
            }
        } else {
            throw new \SPP\SPP_Exception('No parent entity ' . $parent_entity . ' in relation ' . $relation . ' found');
        }
        $rel_record=\SPP\Registry::get('EntityRelations');
        $rel=array();
        foreach($rel_record as $rel) {
            if($rel['parent_entity']==$parent_entity && $rel['child_entity']==$child_entity)
            {
                break;
            }
        }
        if(empty($rel))
        {
            throw new \SPP\SPP_Exception('No relation found for '.$parent_entity.'=>'.$child_entity);
        }
        $child_entity_id_field=$rel['child_entity_field'];
        $child_ent = new $child_entity();
        return $child_ent->loadMultiple(array_merge(array($child_entity_id_field),$attributes), array_merge(array($parent_id),$values));
    }


    /****
     * public static function getRelatedEntitiesByChild()
     * Returns an array of related entities for a given child entity
     * 
     * @param string $relation
     * @param $child_id
     * @param array $attributes
     * @param array $values
     * @return array
     */
    public static function getRelatedEntitiesByChild(string $relation, $child_id, $attributes=array(), $values=array())
    {
        $parent_entity = trim(strtok(($relation), '=>'));
        $child_entity = trim(strtok('=>'));
        if (!SPP_Entity::entityExists($parent_entity) || !SPP_Entity::entityExists($child_entity)) {
            throw new \SPP\SPP_Exception('Invalid entity class');
        }
        if (array_key_exists($parent_entity, self::$related_entities)) {
            if (!in_array($child_entity, self::$related_entities[$parent_entity])) {
                throw new \SPP\SPP_Exception('No child entity ' . $child_entity . ' in relation ' . $relation . ' found');
            }
        } else {
            throw new \SPP\SPP_Exception('No parent entity ' . $parent_entity . ' in relation ' . $relation . ' found');
        }
        $rel_record = \SPP\Registry::get('EntityRelations');
        $rel = array();
        foreach ($rel_record as $rel) {
            if ($rel['parent_entity'] == $parent_entity && $rel['child_entity'] == $child_entity) {
                break;
            }
        }
        if (empty($rel)) {
            throw new \SPP\SPP_Exception('No relation found for ' . $parent_entity . '=>' . $child_entity);
        }
        $parent_entity_id_field = $rel['parent_entity_field'];
        $parent_ent = new $parent_entity();
        return $parent_ent->loadMultiple(array_merge(array($parent_entity_id_field), $attributes), array_merge(array($child_id), $values));
    }


    /****
     * public static function relateEntities()
     * Relate two existing entity objects already registered in related entities array
     * 
     * @param string $relation
     * @param $parent_id
     * @param $child_id
     * @return boolean
     * @throws \SPP\SPP_Exception
     */
    public static function relateEntities(string $relation, $parent_id, $child_id)
    {
        $parent_entity = trim(strtok(($relation), '=>'));
        $child_entity = trim(strtok('=>'));
        if (!SPP_Entity::entityExists($parent_entity) || !SPP_Entity::entityExists($child_entity)) {
            throw new \SPP\SPP_Exception('Invalid entity class');
        }
        //print_r(self::$related_entities);
        //echo "<br>".$parent_entity;
        //echo "<br>" . $child_entity;
        if (array_key_exists($parent_entity, self::$related_entities)) {
            if (!in_array($child_entity, self::$related_entities[$parent_entity])) {
                throw new \SPP\SPP_Exception('No child entity ' . $child_entity . ' in relation ' . $relation . ' found');
            }
        } else {
            throw new \SPP\SPP_Exception('No parent entity ' . $parent_entity . ' in relation ' . $relation . ' found');
        }
        $rel_record = \SPP\Registry::get('EntityRelations');
        $rel = array();
        foreach ($rel_record as $rel) {
            if ($rel['parent_entity'] == $parent_entity && $rel['child_entity'] == $child_entity) {
                break;
            }
        }
        //print_r($rel_record);
        if (empty($rel)) {
            throw new \SPP\SPP_Exception('No relation found for ' . $parent_entity . '=>' . $child_entity);
        }
        $child_entity_id_field = $rel['child_entity_field'];
        $child_ent = new $child_entity($child_id);
        $child_ent->$child_entity_id_field = $parent_id;
        $child_ent->save();
    }


    /****
     * public static function unrelateEntities()
     * Unrelate two entities
     * 
     * @param string $relation
     * @param $parent_id
     * @param $child_id
     * @return boolean
     * @throws \SPP\SPP_Exception
     * 
     *  */
    public static function unrelateEntities(string $relation, $parent_id, $child_id)
    {
        $parent_entity = trim(strtok(($relation), '=>'));
        $child_entity = trim(strtok('=>'));
        if (!SPP_Entity::entityExists($parent_entity) || !SPP_Entity::entityExists($child_entity)) {
            throw new \SPP\SPP_Exception('Invalid entity class');
        }
        if (array_key_exists($parent_entity, self::$related_entities)) {
            if (!in_array($child_entity, self::$related_entities[$parent_entity])) {
                throw new \SPP\SPP_Exception('No child entity ' . $child_entity . ' in relation ' . $relation . ' found');
            }
        } else {
            throw new \SPP\SPP_Exception('No parent entity ' . $parent_entity . ' in relation ' . $relation . ' found');
        }
        $rel_record = \SPP\Registry::get('EntityRelations');
        $rel = array();
        foreach ($rel_record as $rel) {
            if ($rel['parent_entity'] == $parent_entity && $rel['child_entity'] == $child_entity) {
                break;
            }
        }
        if (empty($rel)) {
            throw new \SPP\SPP_Exception('No relation found for ' . $parent_entity . '=>' . $child_entity);
        }
        $child_entity_id_field = $rel['child_entity_field'];
        $child_ent = new $child_entity($child_id);
        $child_ent->$child_entity_id_field = null;
        $child_ent->save();
    }

    /**
     * public static function addChildEntity()
     * Add a child entity to a parent entity
     * 
     * @param string $relation
     * @param $parent_id
     * @param $attributes
     * @return boolean
     * @throws \SPP\SPP_Exception
     * 
     */
    public static function addChildEntity(string $relation, $parent_id, $attributes)
    {
        $parent_entity = trim(strtok(($relation), '=>'));
        $child_entity = trim(strtok('=>'));
        if (!SPP_Entity::entityExists($parent_entity) || !SPP_Entity::entityExists($child_entity)) {
            throw new \SPP\SPP_Exception('Invalid entity class');
        }
        if (array_key_exists($parent_entity, self::$related_entities)) {
            if (!in_array($child_entity, self::$related_entities[$parent_entity])) {
                throw new \SPP\SPP_Exception('No child entity ' . $child_entity . ' in relation ' . $relation . ' found');
            }
        } else {
            throw new \SPP\SPP_Exception('No parent entity ' . $parent_entity . ' in relation ' . $relation . ' found');
        }
        $rel_record = \SPP\Registry::get('EntityRelations');
        $rel = array();
        foreach ($rel_record as $rel) {
            if ($rel['parent_entity'] == $parent_entity && $rel['child_entity'] == $child_entity) {
                break;
            }
        }
        if (empty($rel)) {
            throw new \SPP\SPP_Exception('No relation found for ' . $parent_entity . '=>' . $child_entity);
        }
        $child_entity_id_field = $rel['child_entity_field'];
        $child_ent = new $child_entity();
        $child_ent->$child_entity_id_field = $parent_id;
        $child_ent->setAttributes($attributes);
        return $child_ent->save();
    }

/*     public static function removeChildEntity(string $relation, $parent_id, $child_id)
    {
        // TODO: Implement removeChildEntity() method.
    }
 */



}


?>