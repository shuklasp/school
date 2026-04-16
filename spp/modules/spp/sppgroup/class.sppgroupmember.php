<?php
namespace SPPMod\SPPGroup;

use SPPMod\SPPEntity\SPPEntity;

/**
 * class SPPGroupMember
 * Polymorphic join table entity for group memberships.
 */
class SPPGroupMember extends SPPEntity {
    protected $table = 'sppgroupmembers';

    public function define_attributes()
    {
        return [
            'groupid' => 'int',
            'member_class' => 'varchar(255)',
            'member_id' => 'varchar(100)',
            'role' => 'varchar(50)',
            'direct' => 'tinyint(1)',
            'added_at' => 'datetime',
            'rights' => 'text'
        ];
    }
}
