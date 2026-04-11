<?php
namespace SPPMod\SPPGroup;

use SPPMod\SPPEntity\SPPEntity;
use SPP\SPPException;

/**
 * class SPPGroup
 * Entity class providing group behavior and many-to-many polymorphic relations.
 */
class SPPGroup extends SPPEntity {
    public const ROLE_MEMBER = 'member';
    public const ROLE_ADMIN = 'admin';
    
    /**
     * Add an entity as a member to this group.
     * @param SPPEntity $entity
     * @param string $role
     * @param array|null $rights
     * @return bool
     * @throws SPPException
     */
    public function addMember(SPPEntity $entity, string $role = self::ROLE_MEMBER, ?array $rights = null) {
        if ($this->id == null) {
            throw new SPPException("Group must be saved before adding members.");
        }
        if ($entity->getId() == null) {
            throw new SPPException("Entity must be saved before being added to a group.");
        }
        
        // Cycle detection if entity is also a group
        if ($entity instanceof SPPGroup) {
            if ($this->hasAncestor($entity)) {
                throw new SPPException("Cannot add group to prevent circular nesting.");
            }
        }
        
        if (!$this->isMember($entity)) {
            $member = new SPPGroupMember();
            $member->group_id = $this->id;
            $member->member_entity = get_class($entity);
            $member->member_id = $entity->getId();
            $member->role = $role;
            if ($rights !== null) {
                $member->rights = json_encode($rights);
            }
            $member->save();
            return true;
        }
        return false;
    }
    
    /**
     * Check if a group is an ancestor of this group to prevent cycles.
     * @param SPPGroup $group
     * @return bool
     */
    public function hasAncestor(SPPGroup $group) {
        if ($this->id == $group->getId()) return true;
        
        $parents = $this->getParentGroups();
        foreach ($parents as $parent) {
            if ($parent->hasAncestor($group)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Retrieves all parent groups that contain this group as a member.
     * @return SPPGroup[]
     */
    public function getParentGroups() {
        if ($this->id == null) return [];
        $gm = new SPPGroupMember();
        $records = $gm->loadMultiple(
            ['member_entity', 'member_id'], 
            [static::class, $this->id]
        );
        $parents = [];
        foreach ($records as $record) {
            $parent = new SPPGroup($record->group_id);
            $parents[] = $parent;
        }
        return $parents;
    }
    
    /**
     * Remove an entity from this group.
     * @param SPPEntity $entity
     * @return bool
     */
    public function removeMember(SPPEntity $entity) {
        if ($this->id == null || $entity->getId() == null) return false;
        
        $gm = new SPPGroupMember();
        $records = $gm->loadMultiple(
            ['group_id', 'member_entity', 'member_id'],
            [$this->id, get_class($entity), $entity->getId()]
        );
        
        if (count($records) > 0) {
            foreach ($records as $record) {
                $record->delete();
            }
            return true;
        }
        return false;
    }
    
    /**
     * Check if an entity is a member of this group.
     * @param SPPEntity $entity
     * @return bool
     */
    public function isMember(SPPEntity $entity) {
        if ($this->id == null || $entity->getId() == null) return false;
        $gm = new SPPGroupMember();
        $records = $gm->loadMultiple(
            ['group_id', 'member_entity', 'member_id'],
            [$this->id, get_class($entity), $entity->getId()]
        );
        return count($records) > 0;
    }
    
    /**
     * Retrieve members of this group, optionally filtered by class type.
     * @param ?string $entityClass
     * @return SPPEntity[]
     */
    public function getMembers(?string $entityClass = null) {
        if ($this->id == null) return [];
        
        $gm = new SPPGroupMember();
        if ($entityClass) {
            $records = $gm->loadMultiple(
                ['group_id', 'member_entity'], 
                [$this->id, $entityClass]
            );
        } else {
            $records = $gm->loadMultiple(['group_id'], [$this->id]);
        }
        
        $members = [];
        foreach ($records as $record) {
            $className = $record->member_entity;
            if (class_exists($className)) {
                $members[] = new $className($record->member_id);
            }
        }
        return $members;
    }
    
    /**
     * Update an existing member's role and/or rights.
     * @param SPPEntity $entity
     * @param string $role
     * @param array|null $rights
     * @return bool
     */
    public function updateMember(SPPEntity $entity, string $role, ?array $rights = null) {
        if ($this->id == null || $entity->getId() == null) return false;
        
        $gm = new SPPGroupMember();
        $records = $gm->loadMultiple(
            ['group_id', 'member_entity', 'member_id'],
            [$this->id, get_class($entity), $entity->getId()]
        );
        if (count($records) > 0) {
            $member = $records[0];
            $member->role = $role;
            if ($rights !== null) {
                $member->rights = json_encode($rights);
            } else {
                $member->rights = null;
            }
            $member->save();
            return true;
        }
        return false;
    }

    /**
     * Retrieve the role of a specific member.
     * @param SPPEntity $entity
     * @return ?string
     */
    public function getMemberRole(SPPEntity $entity) {
        if ($this->id == null || $entity->getId() == null) return null;
        $gm = new SPPGroupMember();
        $records = $gm->loadMultiple(
            ['group_id', 'member_entity', 'member_id'],
            [$this->id, get_class($entity), $entity->getId()]
        );
        return count($records) > 0 ? $records[0]->role : null;
    }

    /**
     * Retrieve the rights of a specific member as an array.
     * @param SPPEntity $entity
     * @return ?array
     */
    public function getMemberRights(SPPEntity $entity) {
        if ($this->id == null || $entity->getId() == null) return null;
        $gm = new SPPGroupMember();
        $records = $gm->loadMultiple(
            ['group_id', 'member_entity', 'member_id'],
            [$this->id, get_class($entity), $entity->getId()]
        );
        if (count($records) > 0 && isset($records[0]->rights) && $records[0]->rights) {
            return json_decode($records[0]->rights, true);
        }
        return null;
    }

    /**
     * Determine if a member possesses admin privileges in the group.
     * @param SPPEntity $entity
     * @return bool
     */
    public function isAdmin(SPPEntity $entity) {
        return $this->getMemberRole($entity) === self::ROLE_ADMIN;
    }

    /**
     * Returns an array of group member entities who are admins.
     * @param ?string $entityClass
     * @return SPPEntity[]
     */
    public function getAdmins(?string $entityClass = null) {
        if ($this->id == null) return [];
        
        $gm = new SPPGroupMember();
        if ($entityClass) {
            $records = $gm->loadMultiple(
                ['group_id', 'member_entity', 'role'], 
                [$this->id, $entityClass, self::ROLE_ADMIN]
            );
        } else {
            $records = $gm->loadMultiple(
                ['group_id', 'role'], 
                [$this->id, self::ROLE_ADMIN]
            );
        }
        
        $admins = [];
        foreach ($records as $record) {
            $className = $record->member_entity;
            if (class_exists($className)) {
                $admins[] = new $className($record->member_id);
            }
        }
        return $admins;
    }
}
