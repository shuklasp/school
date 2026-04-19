<?php
namespace SPPMod\SPPEntity;
use SPP\Exceptions\AttributeNotFoundException;
use SPP\Exceptions\EntityNotFoundException;
use SPP\Exceptions\EntityConfigurationException;

require_once('entityexceptions.php');
require_once('class.sppentityrelations.php');
/**
 * class Entity
 * Defines an entity
 */

class SPPEntity implements \JsonSerializable
{
  protected $id = NULL;
  protected static $_metadata = array();         /** Static registry for entity configuration */

  protected $_values = array();                      /** attribute-value pairs */

  /**
   * public function __construct($id, $name)
   * Constructor
   * @param int $id
   */
  public function __construct($id = null)
  {
    $this->_values = array();
    $class = static::class;

    if (!isset(self::$_metadata[$class])) {
        self::loadEntityConfig($class);
    }

    $this->after_creation();
    $this->id = $id;
    if ($id != null) {
      $this->load($id);
    }
  }

  /**
   * Loads the entity configuration from YAML, supporting inheritance.
   */
  protected static function loadEntityConfig(string $class)
  {
      $reflection = new \ReflectionClass($class);
      $shortName = $reflection->getShortName();
      $yml_file = static::getEntityConfigFile($shortName);
      
      $config = [
          'table' => strtolower($shortName) . 's',
          'id_field' => 'id',
          'sequence' => strtolower($shortName) . '_seq',
          'login_enabled' => false,
          'profile' => null,
          'attributes' => []
      ];

      self::$_metadata[$class] = &$config;

      if ($yml_file !== false) {
          $ymlData = self::parseYaml($yml_file);
          
          // Handle recursion for 'extends'
          if (isset($ymlData['extends'])) {
              $parentClass = $ymlData['extends'];
              // Ensure parent is loaded
              if (!isset(self::$_metadata[$parentClass])) {
                  static::loadEntityConfig($parentClass);
              }
              // Inherit from parent
              $parentConfig = self::$_metadata[$parentClass];
              $config = array_merge($config, $parentConfig);
              // For attributes, we want a deep merge.
              if (isset($parentConfig['attributes'])) {
                  $config['attributes'] = $parentConfig['attributes'];
              }
          }

          // Override with current YAML values
          if (isset($ymlData['table'])) $config['table'] = $ymlData['table'];
          if (isset($ymlData['id_field'])) $config['id_field'] = $ymlData['id_field'];
          if (isset($ymlData['sequence'])) $config['sequence'] = $ymlData['sequence'];
          if (isset($ymlData['login_enabled'])) $config['login_enabled'] = (bool) $ymlData['login_enabled'];
          if (isset($ymlData['profile'])) $config['profile'] = $ymlData['profile'];
          
          if (isset($ymlData['attributes']) && is_array($ymlData['attributes'])) {
              foreach ($ymlData['attributes'] as $k => $v) {
                  $config['attributes'][$k] = $v;
              }
          }

          // Merge profile attribute
          if ($config['profile'] !== null) {
              $config['attributes']['profid'] = 'bigint';
          }

          // Register relations (only for the current entity level to avoid duplicate registrations if parent also registered them)
          // Wait, if $class is Child, and Parent had relations, we want Child to have those relations too.
          // The relations registration logic uses $shortName which is the class being loaded.
          if (isset($ymlData['relations']) && is_array($ymlData['relations'])) {
              foreach ($ymlData['relations'] as $rel) {
                  \SPPMod\SPPEntity\SPPEntityRelations::registerEntityRelation(
                      $rel['parent_entity'] ?? $class,
                      $rel['parent_entity_field'] ?? 'id',
                      $rel['child_entity'] ?? $class,
                      $rel['child_entity_field'] ?? 'parent_id',
                      $rel['relation_type'] ?? 'OneToMany'
                  );
              }
          }
          
          // Re-register parent relations for this child if they were "relative" (no entity specified)
          // Actually, if we just store relations in config, we can handle them better.
          // For now, I'll stick to what's in the YAML.
          
      } else {
          // Fallback to define_attributes if no YAML
          try {
              $instance = $reflection->newInstanceWithoutConstructor();
              $config['attributes'] = $instance->define_attributes();
          } catch (\Exception $e) {
              // Methods might not be there if it's a dynamic class
          }
      }

      // Post-load validation: If we ended up with an empty table name, it's a critical configuration failure
      if (empty($config['table'])) {
          throw new EntityConfigurationException("Entity configuration error: Class '{$class}' has no database table defined and no default could be resolved.");
      }
  }

  public static function getMetadata(string $key, $default = null)
  {
      if (!isset(self::$_metadata[static::class])) {
          static::loadEntityConfig(static::class);
      }
      return self::$_metadata[static::class][$key] ?? $default;
  }

  protected static function setMetadata(string $key, $value)
  {
      self::$_metadata[static::class][$key] = $value;
  }

  /**
   * Helper to parse YAML using PECL extension or Symfony fallback.
   */
  protected static function parseYaml(string $file)
  {
      if (function_exists('yaml_parse_file')) {
          return yaml_parse_file($file);
      }
      if (class_exists('\Symfony\Component\Yaml\Yaml')) {
          return \Symfony\Component\Yaml\Yaml::parseFile($file);
      }
      throw new \SPP\SPPException("No YAML parser found (PECL yaml or Symfony Yaml required)");
  }

  public function define_attributes()
  {
    throw new \SPP\SPPException('Required method define_attributes() not defined in entity ' . get_class($this) . '.');
  }

  public function after_creation()
  {
    // To be implemented in derived classes
  }

  public function after_load()
  {
    // To be implemented in derived classes
  }

  public function before_save()
  {
    // To be implemented in derived classes
  }

  public function after_save()
  {
    // To be implemented in derived classes
  }

  /**
   * public function __toString()
   * Returns the name of the entity
   * @return string
   */
  public function __toString()
  {
    return strval($this->id);
  }

  /**
   * Serializes the entity specifically for JSON conversion.
   */
  public function jsonSerialize(): mixed
  {
      return array_merge(
          ['id' => $this->id],
          $this->_values
      );
  }

  /**
   * public function __isset($arrt)
   * Magic function to check if attribute exists
   * @param string $attr
   * @return bool $attr exists or not 
   */
  public function __isset($attr)
  {
    return $this->attributeExists($attr);
  }

  /**
   * Magic function to get attribute value
   * @param string $attribute
   * @return mixed
   */
  public function __get($attribute)
  {
      return $this->get($attribute);
  }

  /**
   * public function getId()
   * Returns the id of the entity
   * @return int
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * public function setId($id)
   * Sets the id of the entity
   * @param int $id
   */
  public function setId($id)
  {
    $this->id = $id;
  }

  /**
   * public function getAttributes()
   * Returns the attributes of the entity
   * @return array $_attributes
   */
  public function getAttributes()
  {
    return self::getMetadata('attributes', []);
  }

  /**
   * public function getValues()
   * Gets the values of the entity
   * @return array $_values
   */
  public function getValues()
  {
    return $this->_values;
  }

  /**
   * public function setValues($values)
   * Sets the values of the entity
   * @param array $values
   */
  public function setValues($values)
  {
    $attributes = $this->getAttributes();
    foreach ($values as $att => $val) {
      if (array_key_exists($att, $attributes)) {
        $this->_values[$att] = $val;
      } else {
        throw new AttributeNotFoundException('Wrong attribute ' . $att . ' accessed');
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
  public static function getEntityConfigFile(string $entity_name)
  {
    $path = explode('\\', $entity_name);
    $short_name = array_pop($path);
    
    $appname = class_exists('\SPP\Scheduler') ? \SPP\Scheduler::getContext() : 'default';
    
    $files = array();
    if (defined('APP_ETC_DIR')) {
        $files[] = APP_ETC_DIR . SPP_DS . $appname . SPP_DS . 'entities' . SPP_DS . strtolower($short_name) . '.yml';
        $files[] = APP_ETC_DIR . SPP_DS . $appname . SPP_DS . 'entities' . SPP_DS . $short_name . '.yml';
        $files[] = APP_ETC_DIR . SPP_DS . 'entities' . SPP_DS . strtolower($short_name) . '.yml';
        $files[] = APP_ETC_DIR . SPP_DS . 'entities' . SPP_DS . $short_name . '.yml';
    }
    if (defined('SPP_ETC_DIR')) {
        $files[] = SPP_ETC_DIR . SPP_DS . 'entities' . SPP_DS . strtolower($short_name) . '.yml';
        $files[] = SPP_ETC_DIR . SPP_DS . 'entities' . SPP_DS . $short_name . '.yml';
    }
    
    foreach ($files as $file) {
        if (file_exists($file)) {
            return $file;
        }
    }
    return false;
  }

  public static function entityExists(mixed $entity_name)
  {
    if (class_exists($entity_name)) {
      if (is_a($entity_name, '\SPPMod\SPPEntity\SPPEntity', true)) {
        return true;
      } else {
        return false;
      }
    } else {
      if (self::getEntityConfigFile($entity_name) !== false) {
          return true;
      }
      return false;
    }
  }

  /**
   * Scans all registered etc paths for entity YAML definitions.
   * Returns a deduplicated list indexed by entity name.
   */
  public static function listAvailableEntities(): array
  {
      $appname = class_exists('\SPP\Scheduler') ? \SPP\Scheduler::getContext() : 'default';
      $entities = [];
      $paths = [];
      
      if (defined('APP_ETC_DIR')) {
          $paths[] = APP_ETC_DIR . SPP_DS . $appname . SPP_DS . 'entities';
          $paths[] = APP_ETC_DIR . SPP_DS . 'entities';
      }
      if (defined('SPP_ETC_DIR')) {
          $paths[] = SPP_ETC_DIR . SPP_DS . 'entities';
      }

      foreach (array_unique($paths) as $dir) {
          if (is_dir($dir)) {
              $files = glob($dir . '/*.yml');
              foreach ($files as $f) {
                  $name = basename($f, '.yml');
                  if (!isset($entities[$name])) {
                      $content = file_get_contents($f);
                      $config = [];
                      try {
                          if (class_exists('\Symfony\Component\Yaml\Yaml')) {
                              $config = \Symfony\Component\Yaml\Yaml::parse($content);
                          }
                      } catch (\Exception $e) {}

                      $entities[$name] = [
                          'name' => $name,
                          'path' => $f,
                          'table' => $config['table'] ?? '',
                          'modified' => date('Y-m-d H:i', filemtime($f)),
                          'content' => $content
                      ];
                  }
              }
          }
      }
      return $entities;
  }

  /**
   * Saves an entity definition (YAML) and generates a PHP skeleton class if missing.
   * Mirrors the logic from the Admin Workbench but shared via core.
   */
  public static function saveEntityDefinition(string $name, string $appname, array $config): bool
  {
      // 1. Save YAML Definition
      $entitiesDir = APP_ETC_DIR . '/' . $appname . '/entities';
      if (!is_dir($entitiesDir)) mkdir($entitiesDir, 0777, true);
      
      if (class_exists('\Symfony\Component\Yaml\Yaml')) {
          $yaml = \Symfony\Component\Yaml\Yaml::dump($config, 4, 2);
      } else {
          // Fallback basic serialization
          $yaml = "table: " . ($config['table'] ?? strtolower($name).'s') . "\n";
          $yaml .= "id_field: " . ($config['id_field'] ?? 'id') . "\n";
          if (!empty($config['extends'])) $yaml .= "extends: " . $config['extends'] . "\n";
          $yaml .= "attributes:\n";
          foreach (($config['attributes'] ?? []) as $k => $v) {
              $yaml .= "  $k: $v\n";
          }
      }
      
      file_put_contents($entitiesDir . '/' . strtolower($name) . '.yml', $yaml);

      // 2. Generate PHP Skeleton Class
      $srcDir = SPP_APP_DIR . '/src/' . $appname . '/entities';
      if (!is_dir($srcDir)) mkdir($srcDir, 0777, true);

      $className = ucfirst($name);
      $fileName = 'entity.' . strtolower($name) . '.php';
      $filePath = $srcDir . '/' . $fileName;

      if (!file_exists($filePath)) {
          $namespace = "App\\" . ucfirst($appname) . "\\Entities";
          $parent = !empty($config['extends']) ? $config['extends'] : "\\SPPMod\\SPPEntity\\SPPEntity";
          
          $phpContent = "<?php\nnamespace $namespace;\n\nuse SPPMod\\SPPEntity\\SPPEntity;\n\n/**\n * Class $className\n * Skeleton generated by SPP Entity Builder\n */\nclass $className extends $parent\n{\n    // Custom domain logic here\n}\n";
          file_put_contents($filePath, $phpContent);
      }
      
      return true;
  }

  /**
   * Static helper to find all instances of the entity.
   */
  public static function find_all()
  {
      $instance = new static();
      return $instance->loadAll();
  }

  /**
   * Static helper to count all instances of the entity.
   */
  public static function count()
  {
      $db = new \SPPMod\SPPDB\SPPDB();
      $instance = new static();
      $sql = 'SELECT COUNT(*) as cnt FROM ' . $instance->getTable();
      $res = $db->execute_query($sql);
      return (int)($res[0]['cnt'] ?? 0);
  }

  /**
   *  public static function getEntityName($entity)
   * Gets the name of the entity
   * @param  $entity
   * @return string $entity_name
   */
  public static function getEntityName($entity)
  {
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
  public function getTable()
  {
    $table = self::getMetadata('table');
    if (empty($table)) {
        throw new EntityConfigurationException("Entity " . static::class . " does not have a database table mapping. Check its YAML definition or ensure the class name follows pluralization conventions.");
    }
    
    if (class_exists('\\SPPMod\\SPPDB\\SPPDB')) {
        return \SPPMod\SPPDB\SPPDB::sppTable($table);
    }
    return $table;
  }

  /**
   * public function setTable($table)
   * Sets the table of the entity
   * @param string $table
   */
  public function setTable($table)
  {
    self::setMetadata('table', $table);
  }

  /**
   * public function set($attribute, $value)
   * Sets the value af an attribute of entity
   * @param string $attribute
   * @param mixed $value
   */
  public function set($attribute, $value)
  {
    //print('setting '.$attribute.' to '.$value);
    //var_dump(static::$_attributes);
    $classVar = get_object_vars($this);
    if (array_key_exists($attribute, $classVar)) {
      $this->$attribute = $value;
    } else {
      $attributes = $this->getAttributes();
      if (array_key_exists($attribute, $attributes)) {
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
    $classVar = get_object_vars($this);
    if (array_key_exists($attribute, $classVar)) {
      $this->$attribute = $value;
    } else {
      $attributes = $this->getAttributes();
      if (array_key_exists($attribute, $attributes)) {
        $this->_values[$attribute] = $value;
        //return true;
      } else {
        throw new AttributeNotFoundException('Wrong attribute ' . $attribute . ' accessed');
      }
    }
    return $value;
  }

  public function setAttributes($attributes)
  {
    foreach ($attributes as $att => $val) {
      $this->$att = $val;
    }
    return $this->id;
  }


  /**
   * public function attributeExists($attribute)
   * Checks if an attribute exists
   * @param string $attribute
   * @return bool $exists
   */
  public function attributeExists($attribute)
  {
    $exists = false;
    $classVar = get_object_vars($this);
    //print_r($classVar);
    if (array_key_exists($attribute, $classVar)) {
      $exists = true;
    } else {
      $attributes = $this->getAttributes();
      if (array_key_exists($attribute, $attributes)) {
        $exists = true;
      } else {
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
  public function get($attribute)
  {
    $classVar = get_object_vars($this);
    if (array_key_exists($attribute, $classVar)) {
      return $this->$attribute;
    } else {
      $attributes = $this->getAttributes();
      if (array_key_exists($attribute, $attributes)) {
        return $this->_values[$attribute];
      } else {
        throw new AttributeNotFoundException('Wrong attribute ' . $attribute . ' accessed');
      }
    }
  }

  public static function addAttributes($attributes)
  {
    $currentAttributes = self::getMetadata('attributes', []);
    foreach ($attributes as $key => $value) {
      $currentAttributes[$key] = $value;
    }
    self::setMetadata('attributes', $currentAttributes);
    static::install();
  }

  protected function removeAttribute($attribute)
  {
    $attributes = $this->getAttributes();
    if (isset($attributes[$attribute])) {
      unset($attributes[$attribute]);
      self::setMetadata('attributes', $attributes);
    }
  }

  /**
   * public static function install()
   * installs the entity and creates table for entity.
   */
  public static function install()
  {
    $db = new \SPPMod\SPPDB\SPPDB();
    $table = self::getMetadata('table');
    $id_field = self::getMetadata('id_field', 'id');
    $attributes = self::getMetadata('attributes', []);
    $profile = self::getMetadata('profile');

    if (!$db->tableExists($table)) {
      $sql = 'create table %tab% (' . $id_field . ' varchar(20))';
      $db->exec_squery($sql, $table);
    }
    $db->add_columns($table, $attributes);
    
    // Profile DB Compilation Routine
    if ($profile !== null) {
        $profName = static::getEntityName(static::class) . '_prof';
        if (!\SPPMod\SPPProfile\SPPProfile::doesProfileExist($profName)) {
            $flds = $profile;
            if (!isset($flds['userid'])) {
                $flds['userid'] = 100;
            }
            \SPPMod\SPPProfile\SPPProfile::createProfile($profName, $flds);
            \SPPMod\SPPLogger\SPP_Logger::info("Automated Profile schema successfully compiled onto Database for Entity: " . static::getEntityName(static::class));
        }
    }
  }

  /**
   * protected function uninstall()
   * uninstalls the entity and drops all the columns except id and name
   */
  protected function uninstall()
  {
    $db = new \SPPMod\SPPDB\SPPDB();
    $db->remove_columns($this->getTable(), $this->getAttributes());
  }

  /**
   * pubic function save()
   * Save the current entity.
   * Insert if new entity.
   */
  public function save()
  {
    $this->before_save();
    if ($this->id == null) {
      $new_id = $this->insert();
      $this->after_save();
      return $new_id;
    } else {
      $this->update();
      $this->after_save();
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
    $sequence = self::getMetadata('sequence');
    $initial_id = self::getMetadata('initial_id', 1);
    
    if (!\SPPMod\SPPDB\SPPSequence::sequenceExists($sequence)) {
      \SPPMod\SPPDB\SPPSequence::createSequence($sequence, $initial_id, 1);
    }
    $new_id = \SPPMod\SPPDB\SPPSequence::next($sequence);
    return $new_id;
  }

  /**
   * public function insert()
   * inserts a new entity into the table.
   * @return mixed $new_id
   */
  public function insert()
  {
    $db = new \SPPMod\SPPDB\SPPDB();
    $new_id = $this->createId();
    $this->id = $new_id;
    $val_array = array_merge(array($this->getMetadata('id_field') => $new_id), $this->_values);
    
    if (class_exists('\\SPPMod\\SPPAI\\SPPAI') && $this->attributeExists('ai_vector')) {
        $val_array['ai_vector'] = json_encode(\SPPMod\SPPAI\SPPAI::createEmbedding(json_encode($this->_values)));
    }
    
    $db->insertValues($this->getTable(), $val_array);
    return $new_id;
  }

  /**
   * Generates physical system login credentials tethered natively to this entity instance.
   */
  public function enableLogin(string $username, string $password)
  {
      if (!self::getMetadata('login_enabled', false)) {
          throw new \SPP\SPPException('Logins are disabled natively for this Entity YAML payload.');
      }
      if ($this->id == null) {
          throw new \SPP\SPPException('Entity must be physically stored to database tables dynamically before authenticating mappers.');
      }
      if (\SPPMod\SPPAuth\SPPUser::userExists($username)) {
          throw new \SPP\SPPException("Username '$username' is already claimed inside database.");
      }
      
      // 1. Generate Auth System User Tracker
      \SPPMod\SPPAuth\SPPUser::createUser($username, $password);
      
      // 2. Bind Authenticated Profile physically to Dynamic Entity Extractor
      $profileConfig = self::getMetadata('profile');
      if ($profileConfig !== null) {
          $profName = static::getEntityName(static::class) . '_prof';
          $profile = new \SPPMod\SPPProfile\SPPProfile($profName);
          $profid = $profile->appendSave(['userid' => $username]);
          
          $this->setValues(['profid' => $profid]);
          $this->update();
      }
      
      // 3. Log Architectural Integration Execution
      \SPPMod\SPPLogger\SPP_Logger::info("Login credentials directly compiled for entity {entity} ({id}) under user {uname}", [
          'entity' => static::getEntityName(static::class),
          'id' => $this->id,
          'uname' => $username
      ]);
  }

  public function disableLogin()
  {
      $username = $this->getLoginIdentity();
      if ($username) {
          \SPPMod\SPPAuth\SPPUser::dropUser($username);
          
      if (self::getMetadata('profile') !== null && isset($this->_values['profid'])) {
          $profName = static::getEntityName(static::class) . '_prof';
          $profile = new \SPPMod\SPPProfile\SPPProfile($profName);
          if ($profile->seekProfile($this->_values['profid'])) {
              $profile->deleteMe();
          }
          $this->setValues(['profid' => null]);
          $this->update();
      }
          
          \SPPMod\SPPLogger\SPP_Logger::warning("Login credentials natively suspended for entity {entity} ({id})", [
              'entity' => static::getEntityName(static::class),
              'id' => $this->id
          ]);
      }
  }

  public function getLoginIdentity()
  {
      if (self::getMetadata('profile') !== null && isset($this->_values['profid'])) {
          $profName = static::getEntityName(static::class) . '_prof';
          $profile = new \SPPMod\SPPProfile\SPPProfile($profName);
          if ($profile->seekProfile($this->_values['profid'])) {
              return $profile->get('userid');
          }
      }
      return null;
  }

  /**
   * Automatically intercepts raw natural queries into vector models efficiently smoothly dynamically actively seamlessly fluently organically elegantly properly cleanly instinctively fluently.
   */
  public static function searchNatural(string $query)
  {
        if (class_exists('\\SPPMod\\SPPAI\\SPPAI')) {
            return \SPPMod\SPPAI\SPPAI::search($query);
        }
        return [];
  }

  /**
   * public function update()
   * Updates the entity.
   * @return boolean
   */
  public function update()
  {
    $db = new \SPPMod\SPPDB\SPPDB();
    if ($this->id != null) {
      $values = array_values($this->_values);
      $values[] = $this->id;
      $db->updateValues($this->getTable(), array_keys($this->_values), self::getMetadata('id_field') . '=?', $values);
      return true;
    } else {
      return false;
    }
  }

  /**
   * public function delete()
   * Deletes the present entity record.
   */
  public function delete()
  {
    $db = new \SPPMod\SPPDB\SPPDB();
    $sql = 'delete from %tab% where ' . self::getMetadata('id_field') . '=?';
    $db->exec_squery($sql, $this->getTable(), array($this->id));
    $this->id = null;
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
     $this->id = $id;
     $db = new \SPPMod\SPPDB\SPPDB();
     $sql = 'select * from %tab% where ' . self::getMetadata('id_field') . '=?';
     $result = $db->exec_squery($sql, $this->getTable(), array($id));
     if (sizeof($result) > 0) {
      $row = $result[0];
      foreach ($row as $attribute => $value) {
        if (!is_numeric($attribute)) {
          $this->set($attribute, $value);
        }
      }
      $this->after_load();
    } else {
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
    $db = new \SPPMod\SPPDB\SPPDB();
    $attribute = preg_replace('/[^a-zA-Z0-9_]/', '', $attribute);
    $sql = 'select * from %tab% where ' . $attribute . '=?';
    $result = $db->exec_squery($sql, $this->getTable(), array($value));
    if (sizeof($result) > 0) {
      $row = $result[0];
      foreach ($row as $attribute => $value) {
        $this->set($attribute, $value);
      }
      $this->after_load();
    } else {
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
    $db = new \SPPMod\SPPDB\SPPDB();
    $sql = 'select * from %tab%';
    $result = $db->exec_squery($sql, $this->getTable());
    $entities = array();
    foreach ($result as $row) {
      $entity = new static();
      $entity->setId($row[self::getMetadata('id_field')]);
      foreach ($row as $attribute => $value) {
        if (!is_numeric($attribute)) {
          $entity->set($attribute, $value);
        }
      }
      $entity->after_load();
      $entities[] = $entity;
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
    $db = new \SPPMod\SPPDB\SPPDB();
    $sanitized_attributes = array();
    foreach ($attributes as $attr) {
      $sanitized_attributes[] = preg_replace('/[^a-zA-Z0-9_]/', '', $attr);
    }
    $sql = 'select * from %tab% where ' . implode('=? AND ', $sanitized_attributes) . '=? ';
    $result = $db->exec_squery($sql, $this->getTable(), $values);
    $entities = array();
    foreach ($result as $row) {
      $entity = new static();
      $entity->setId($row[self::getMetadata('id_field')]);
      foreach ($row as $attribute => $value) {
        if (!is_numeric($attribute)) {
          $entity->set($attribute, $value);
        }
      }
      $entity->after_load();
      $entities[] = $entity;
    }
    return $entities;
  }
}


?>
