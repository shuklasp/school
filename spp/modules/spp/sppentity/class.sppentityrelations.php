<?php
namespace SPPMod\SPPEntity;


/**
 * class SPPEntityRelations
 * Defines the relations between entities
 * @author  Satya Prakash Shukla
 * @version 1.0
 * @since   2015-09-15
 */
class SPPEntityRelations
{
    protected static $parent_to_child = array();
    protected static $child_to_parent = array();

    /**
     * Helper to resolve, validate, and retrieve relation configuration
     * @param string $relation
     * @return array
     * @throws \SPP\SPPException
     */
    protected static function _resolveRelation(string $relation)
    {
        $parts = explode('=>', $relation);
        $parent_entity = trim($parts[0] ?? '');
        $child_entity = trim($parts[1] ?? '');
        
        if (!SPPEntity::entityExists($parent_entity) || !SPPEntity::entityExists($child_entity)) {
            throw new \SPP\SPPException('Invalid entity class in relation: ' . $relation);
        }
        
        if (!isset(self::$parent_to_child[$parent_entity]) || !in_array($child_entity, self::$parent_to_child[$parent_entity])) {
            throw new \SPP\SPPException('No registered sub-relation for ' . $relation . ' found');
        }

        $rel_record = \SPP\Registry::get('EntityRelations');
        if (is_array($rel_record)) {
            foreach ($rel_record as $rel) {
                if ($rel['parent_entity'] == $parent_entity && $rel['child_entity'] == $child_entity) {
                    return $rel;
                }
            }
        }
        
        throw new \SPP\SPPException('No relation schema found for ' . $relation);
    }

    /****
     * public static function registerEntityRelation()
     * Registers the relations between entities
     * 
     * @param string $parent_entity
     * @param string $parent_entity_field
     * @param string $child_entity
     * @param string $child_entity_field
     * @param string $relation_type
     * @throws \SPP\SPPException
     */
    public static function registerEntityRelation(
        string $parent_entity,
        string $parent_entity_field,
        string $child_entity,
        string $child_entity_field,
        string $relation_type
    ) {
        $rel_array = array(
            'parent_entity' => $parent_entity,
            'parent_entity_field' => $parent_entity_field,
            'child_entity' => $child_entity,
            'child_entity_field' => $child_entity_field,
            'relation_type' => $relation_type
        );
        
        if (!SPPEntity::entityExists($parent_entity)) {
            throw new \SPP\SPPException("Invalid parent entity class " . $parent_entity . " found");
        }
        if (!SPPEntity::entityExists($child_entity)) {
            throw new \SPP\SPPException("Invalid child entity class" . $child_entity . " found");
        }
        
        $prev_rel = array();
        if (\SPP\Registry::isRegistered('EntityRelations')) {
            $prev_rel = \SPP\Registry::get('EntityRelations');
            if (is_array($prev_rel) && !in_array($rel_array, $prev_rel)) {
                $prev_rel[] = $rel_array;
            }
        } else {
            $prev_rel[] = $rel_array;
        }
        \SPP\Registry::register('EntityRelations', $prev_rel);
        
        if (!isset(self::$parent_to_child[$parent_entity])) {
            self::$parent_to_child[$parent_entity] = array();
        }
        if (!in_array($child_entity, self::$parent_to_child[$parent_entity])) {
            self::$parent_to_child[$parent_entity][] = $child_entity;
        }
        
        if (!isset(self::$child_to_parent[$child_entity])) {
             self::$child_to_parent[$child_entity] = array();
        }
        if (!in_array($parent_entity, self::$child_to_parent[$child_entity])) {
             self::$child_to_parent[$child_entity][] = $parent_entity;
        }

        $parent = new $parent_entity();
        if (!$parent->attributeExists($parent_entity_field)) {
            $parent::addAttributes(array($parent_entity_field => 'varchar(20)'));
        }
        $child = new $child_entity();
        if (!$child->attributeExists($child_entity_field)) {
            $child::addAttributes(array($child_entity_field => 'varchar(20)'));
        }
    }

    /**
     * public static function getChildren(string $parent_entity)
     * @param string $parent_entity
     * @return array
     * @throws \SPP\SPPException
     */
    public static function getChildren(string $parent_entity)
    {
        return self::$parent_to_child[$parent_entity] ?? array();
    }

    /**
     * public static function getParents(string $child_entity)
     * @param string $child_entity
     * @return array
     * @throws \SPP\SPPException
     */
    public static function getParents(string $child_entity)
    {
        return self::$child_to_parent[$child_entity] ?? array();
    }

    /****
     * public static function getRelations()
     * @return array
     */
    public static function getRelations()
    {
        return self::$parent_to_child;
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
    public static function getRelatedEntitiesByParent(string $relation, $parent_id, $attributes = array(), $values = array())
    {
        $rel = self::_resolveRelation($relation);
        $child_ent = new $rel['child_entity']();
        return $child_ent->loadMultiple(
            array_merge(array($rel['child_entity_field']), $attributes), 
            array_merge(array($parent_id), $values)
        );
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
    public static function getRelatedEntitiesByChild(string $relation, $child_id, $attributes = array(), $values = array())
    {
        $rel = self::_resolveRelation($relation);
        
        $child_ent = new $rel['child_entity']($child_id);
        $mapped_id = $child_ent->get($rel['child_entity_field']);
        
        if (empty($mapped_id)) {
            return array();
        }

        $parent_ent = new $rel['parent_entity']();
        return $parent_ent->loadMultiple(
            array_merge(array($rel['parent_entity_field']), $attributes), 
            array_merge(array($mapped_id), $values)
        );
    }

    /****
     * public static function relateEntities()
     * Relate two existing entity objects already registered in related entities array
     * 
     * @param string $relation
     * @param $parent_id
     * @param $child_id
     * @return boolean
     * @throws \SPP\SPPException
     */
    public static function relateEntities(string $relation, $parent_id, $child_id)
    {
        $rel = self::_resolveRelation($relation);
        $field = $rel['child_entity_field'];
        
        $child_ent = new $rel['child_entity']($child_id);
        $child_ent->$field = $parent_id;
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
     * @throws \SPP\SPPException
     * 
     *  */
    public static function unrelateEntities(string $relation, $parent_id, $child_id)
    {
        $rel = self::_resolveRelation($relation);
        $field = $rel['child_entity_field'];
        
        $child_ent = new $rel['child_entity']($child_id);
        $child_ent->$field = null;
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
     * @throws \SPP\SPPException
     * 
     */
    public static function addChildEntity(string $relation, $parent_id, $attributes)
    {
        $rel = self::_resolveRelation($relation);
        $field = $rel['child_entity_field'];
        
        $child_ent = new $rel['child_entity']();
        $child_ent->$field = $parent_id;
        $child_ent->setAttributes($attributes);
        return $child_ent->save();
    }
    
    // --- HIERARCHY HYBRID LOGIC METHODS ---
    
    /**
     * public static function getAncestors()
     * Crawl up the hierarchy tree.
     */
    public static function getAncestors(string $relation, $id, int $limit = 0)
    {
        $rel = self::_resolveRelation($relation);
        if ($rel['parent_entity'] !== $rel['child_entity']) {
            throw new \SPP\SPPException("Relation must be self-referential (Parent=>Parent) for hierarchy ancestors.");
        }
        
        $ancestors = array();
        $current_id = $id;
        $depth = 0;
        
        while ($current_id !== null && ($limit === 0 || $depth < $limit)) {
            $child = new $rel['child_entity']($current_id);
            $parent_id = $child->get($rel['child_entity_field']);
            
            if (empty($parent_id) || $parent_id == $current_id) { // Prevent infinite self-loops
                break;
            }
            
            $parent = new $rel['parent_entity']($parent_id);
            $ancestors[] = $parent;
            $current_id = $parent_id;
            $depth++;
        }
        return $ancestors;
    }
    
    /**
     * public static function yieldDescendants()
     * Yield descendants safely using memory-efficient Generators. 
     */
    public static function yieldDescendants(string $relation, $id, int $max_depth = 0)
    {
        $rel = self::_resolveRelation($relation);
        if ($rel['parent_entity'] !== $rel['child_entity']) {
            throw new \SPP\SPPException("Relation must be self-referential for hierarchy descendants.");
        }
        
        $queue = array(array('id' => $id, 'depth' => 1));
        $visited = array($id => true);
        
        while (!empty($queue)) {
            $current = array_shift($queue);
            $current_id = $current['id'];
            $depth = $current['depth'];
            
            if ($max_depth > 0 && $depth > $max_depth) {
                continue;
            }
            
            $node_shell = new $rel['child_entity']();
            $children = $node_shell->loadMultiple(array($rel['child_entity_field']), array($current_id));
            
            foreach ($children as $child) {
                $c_id = $child->getId();
                if (!isset($visited[$c_id])) {
                    $visited[$c_id] = true;
                    yield $child;
                    $queue[] = array('id' => $c_id, 'depth' => $depth + 1);
                }
            }
        }
    }

    /**
     * public static function getDescendants()
     * Retrieve all descendants using hybrid flat or tree array configurations.
     */
    public static function getDescendants(string $relation, $id, string $format = 'flat', int $max_depth = 0)
    {
        if ($format === 'flat') {
            $results = array();
            // PHP 7+ generator extraction
            foreach (self::yieldDescendants($relation, $id, $max_depth) as $descendant) {
                $results[] = $descendant;
            }
            return $results;
        } else if ($format === 'tree') {
            $rel = self::_resolveRelation($relation);
            return self::_buildTreeBranch($rel, $id, 1, $max_depth);
        }
        
        throw new \SPP\SPPException("Unknown format " . $format);
    }
    
    protected static function _buildTreeBranch($rel, $current_id, $depth, $max_depth)
    {
        if ($max_depth > 0 && $depth > $max_depth) {
            return array();
        }
        
        $branch = array();
        $node_shell = new $rel['child_entity']();
        $children = $node_shell->loadMultiple(array($rel['child_entity_field']), array($current_id));
        
        foreach ($children as $child) {
            $node = array('entity' => $child);
            $nested = self::_buildTreeBranch($rel, $child->getId(), $depth + 1, $max_depth);
            if (!empty($nested)) {
                $node['children'] = $nested;
            }
            $branch[] = $node;
        }
        return $branch;
    }
}
?>