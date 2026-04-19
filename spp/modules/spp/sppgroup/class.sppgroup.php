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

    protected $source = 'database';
    protected $filePath = null;
    protected $loadedMetadata = [];
    protected $appContext = 'default';

    public function define_attributes()
    {
        return [
            'name' => 'varchar(255)',
            'description' => 'text',
            'created_at' => 'datetime',
            'ai_vector' => 'text',
            'metadata' => 'text',
            'source' => 'varchar(50)',
            'group_context' => 'varchar(100)'
        ];
    }

    public function setSource($source, $appContext = 'default') {
        $this->source = $source;
        $this->appContext = $appContext;
        
        if ($this->source === 'app') {
            $name = $this->_values['name'] ?? 'new-group';
            $slug = $this->id ?: $this->slugify($name);
            $this->filePath = SPPGroupLoader::getAppGroupDir($appContext) . DIRECTORY_SEPARATOR . $slug . ".yml";
            $this->id = $slug;
        } elseif ($this->source === 'global') {
            $name = $this->_values['name'] ?? 'new-group';
            $slug = $this->id ?: $this->slugify($name);
            $this->filePath = SPPGroupLoader::getGlobalGroupDir() . DIRECTORY_SEPARATOR . $slug . ".yml";
            $this->id = $slug;
        }
    }

    protected function slugify($text) {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        return empty($text) ? 'n-a' : $text;
    }

    /**
     * Overrides load to support priority-based resolution.
     */
    public function load($id) {
        // Try resolving as a name first for file-backed groups
        $res = SPPGroupLoader::resolveGroup($id);
        if ($res) {
            $this->source = $res['source'];
            if ($this->source !== 'database') {
                $this->filePath = $res['path'];
                $this->loadFromFile($res['path']);
                return;
            } else {
                // If found in database by name, switch to its numeric ID
                $id = $res['id'];
            }
        }
        
        // Fallback to database
        $this->source = 'database';
        parent::load($id);
    }

    protected function loadFromFile($path) {
        if (!file_exists($path)) return;
        
        $data = null;
        if (function_exists('yaml_parse_file')) {
            $data = yaml_parse_file($path);
        } elseif (class_exists('\Symfony\Component\Yaml\Yaml')) {
            $data = \Symfony\Component\Yaml\Yaml::parseFile($path);
        }

        if (!$data) return;

        $this->id = $data['id'] ?? basename($path, '.yml');
        $this->_values['name'] = $data['name'] ?? $this->id;
        $this->_values['description'] = $data['description'] ?? '';
        $this->loadedMetadata = $data['members'] ?? [];
        
        // Map other attributes if present
        if (isset($data['attributes'])) {
            foreach ($data['attributes'] as $k => $v) {
                $this->_values[$k] = $v;
            }
        }
    }

    /**
     * Override to allow dynamic attributes for custom metadata.
     */
    public function set($attribute, $value) {
        $this->_values[$attribute] = $value;
        return true;
    }

    /**
     * Override to allow dynamic attributes for custom metadata.
     */
    public function get($attribute) {
        if (property_exists($this, $attribute)) return $this->$attribute;
        return $this->_values[$attribute] ?? null;
    }

    /**
     * Override to allow dynamic attributes for custom metadata.
     */
    public function attributeExists($attribute) {
        return true;
    }

    /**
     * Save group. If source is file-backed, write to YAML.
     */
    public function save() {
        if ($this->source === 'database') {
            return parent::save();
        }

        // Auto-pathing if source is file but path is null
        if (empty($this->filePath)) {
            $this->setSource($this->source, $this->appContext);
        }

        // Ensure directory exists
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $data = [
            'id' => $this->id,
            'name' => $this->_values['name'] ?? '',
            'description' => $this->_values['description'] ?? '',
            'attributes' => array_diff_key($this->_values, ['name' => 1, 'description' => 1, 'id' => 1]),
            'members' => $this->loadedMetadata
        ];

        if (function_exists('yaml_emit_file')) {
            $res = yaml_emit_file($this->filePath, $data);
        } elseif (class_exists('\Symfony\Component\Yaml\Yaml')) {
            $res = file_put_contents($this->filePath, \Symfony\Component\Yaml\Yaml::dump($data, 4));
        } else {
            throw new \SPP\SPPException("No YAML emitter found.");
        }
        
        return $this->id;
    }

    /**
     * Recursive membership check.
     */
    public function isMember($entity, bool $recursive = true, array &$seen = []) {
        if ($this->id == null || $entity->getId() == null) return false;
        
        // Prevent infinite recursion
        $uid = $this->source . ':' . $this->id;
        if (isset($seen[$uid])) return false;
        $seen[$uid] = true;

        $directMembers = $this->getDirectMembers();
        foreach ($directMembers as $member) {
            // Check if exact match
            if (get_class($member['entity']) === get_class($entity) && $member['entity']->getId() == $entity->getId()) {
                return true;
            }

            // If recursive, and member is a group, check its kids
            if ($recursive && $member['entity'] instanceof SPPGroup) {
                if ($member['entity']->isMember($entity, true, $seen)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Recursive member collection (Flattened).
     */
    public function getMembers(bool $recursive = true, array &$seen = []) {
        if ($this->id == null) return [];

        $uid = $this->source . ':' . $this->id;
        if (isset($seen[$uid])) return [];
        $seen[$uid] = true;

        $direct = $this->getDirectMembers();
        $all = [];

        foreach ($direct as $m) {
            $memberObj = $m['entity'];
            $memberKey = get_class($memberObj) . ':' . $memberObj->getId();
            
            if (!isset($all[$memberKey])) {
                $all[$memberKey] = [
                    'entity' => $memberObj,
                    'role' => $m['role'],
                    'source_group' => $this->_values['name'] ?? $this->id,
                    'direct' => true
                ];
            }

            if ($recursive && $memberObj instanceof SPPGroup) {
                $subMembers = $memberObj->getMembers(true, $seen);
                foreach ($subMembers as $sm) {
                    $smKey = get_class($sm['entity']) . ':' . $sm['entity']->getId();
                    if (!isset($all[$smKey])) {
                        $all[$smKey] = $sm;
                        $all[$smKey]['direct'] = false;
                        $all[$smKey]['inherited_via'] = $this->_values['name'] ?? $this->id;
                    }
                }
            }
        }

        return array_values($all);
    }

    /**
     * Helper to get direct members from current storage source.
     */
    protected function getDirectMembers() {
        $results = [];
        if ($this->source === 'database') {
            $gm = new SPPGroupMember();
            $records = $gm->loadMultiple(['groupid'], [$this->id]);
            foreach ($records as $record) {
                $class = $record->member_class;
                if (class_exists($class)) {
                    $results[] = [
                        'entity' => new $class($record->member_id),
                        'role' => $record->role
                    ];
                }
            }
        } else {
            foreach ($this->loadedMetadata as $m) {
                $class = $m['entity'];
                if (class_exists($class)) {
                    $results[] = [
                        'entity' => new $class($m['id']),
                        'role' => $m['role'] ?? self::ROLE_MEMBER
                    ];
                }
            }
        }
        return $results;
    }

    /**
     * Add a member to the group (Polymorphic).
     */
    public function addMember($entity, string $role = self::ROLE_MEMBER, ?array $rights = null) {
        if ($this->id == null) throw new SPPException("Group must be loaded/saved.");
        
        // Cycle detection
        if ($entity instanceof SPPGroup) {
            if ($this->hasAncestor($entity)) {
                throw new SPPException("Cycle detected: Cannot add group.");
            }
        }

        if ($this->isMember($entity, false)) return false;

        if ($this->source === 'database') {
            $member = new SPPGroupMember();
            $member->groupid = $this->id;
            $member->member_class = get_class($entity);
            $member->member_id = $entity->getId();
            $member->role = $role;
            if ($rights) $member->rights = json_encode($rights);
            $member->save();
        } else {
            $this->loadedMetadata[] = [
                'entity' => get_class($entity),
                'id' => $entity->getId(),
                'role' => $role,
                'rights' => $rights
            ];
            $this->save();
        }
        return true;
    }

    /**
     * Remove a member from the group.
     */
    public function removeMember($entity) {
        if ($this->source === 'database') {
            $gm = new SPPGroupMember();
            $records = $gm->loadMultiple(
                ['groupid', 'member_class', 'member_id'],
                [$this->id, get_class($entity), $entity->getId()]
            );
            if (empty($records)) return false;
            foreach ($records as $r) $r->delete();
            return true;
        } else {
            $initialCount = count($this->loadedMetadata);
            $this->loadedMetadata = array_filter($this->loadedMetadata, function($m) use ($entity) {
                return !($m['entity'] === get_class($entity) && $m['id'] == $entity->getId());
            });
            if (count($this->loadedMetadata) < $initialCount) {
                $this->save();
                return true;
            }
            return false;
        }
    }

    public function hasAncestor(SPPGroup $group, array &$seen = []) {
        if ($this->id == $group->getId()) return true;
        
        $uid = $this->source . ':' . $this->id;
        if (isset($seen[$uid])) return false;
        $seen[$uid] = true;

        $parents = $this->getParentGroups();
        foreach ($parents as $parent) {
            if ($parent->hasAncestor($group, $seen)) return true;
        }
        return false;
    }

    public function getParentGroups() {
        $parents = [];
        
        // DB lookup
        try {
            $gm = new SPPGroupMember();
            $records = $gm->loadMultiple(['member_class', 'member_id'], [static::class, $this->id]);
            foreach ($records as $r) $parents[] = new SPPGroup($r->groupid);
        } catch (\Exception $e) {}

        // File lookup (Scanning all groups)
        $allGroups = SPPGroupLoader::listAllGroups();
        foreach ($allGroups as $g) {
            if ($g['name'] === $this->id) continue;
            $groupObj = new SPPGroup();
            $groupObj->load($g['name']);
            if ($groupObj->isMember($this, false)) {
                $parents[] = $groupObj;
            }
        }

        return $parents;
    }

    /**
     * Centralized orchestration to create or update a group.
     */
    public static function saveGroupInfo(array $data)
    {
        $id = $data['id'] ?? $data['slug'] ?? null;
        $name = trim($data['name'] ?? '');
        $desc = trim($data['description'] ?? '');
        $source = $data['source'] ?? 'database';
        $appContext = $data['app_context'] ?? 'default';

        $group = new self();
        if (!empty($id)) {
            $group->load($id);
        } else {
            $group->setSource($source, $appContext);
        }

        if (!empty($name)) $group->set('name', $name);
        if (isset($data['description'])) $group->set('description', $desc);
        
        return $group->save();
    }

    /**
     * Shorthand to add a member to a group.
     */
    public static function addMemberToGroup(string $slug, string $class, $mid, $role = self::ROLE_MEMBER)
    {
        $group = new self();
        $group->load($slug);
        if (!$group->id) throw new \Exception("Group '{$slug}' not found.");
        
        if (!class_exists($class)) throw new \Exception("Member class '{$class}' not found.");
        $member = new $class($mid);
        
        return $group->addMember($member, $role);
    }

    /**
     * Shorthand to remove a member from a group.
     */
    public static function removeMemberFromGroup(string $slug, string $class, $mid)
    {
        $group = new self();
        $group->load($slug);
        if (!$group->id) throw new \Exception("Group '{$slug}' not found.");
        
        if (!class_exists($class)) throw new \Exception("Member class '{$class}' not found.");
        $member = new $class($mid);
        
        return $group->removeMember($member);
    }
}
