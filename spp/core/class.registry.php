<?php

namespace SPP;

/**
 * class \SPP\Registry
 *
 * Implements a global registry system for Satya Portal Pack.
 * Provides hierarchical storage for application-level entities,
 * directories, classes, and functions.
 *
 * Backward-compatible modernization for PHP 8+.
 *
 * @author
 *     Satya Prakash Shukla
 * @version
 *     2.1 compatible with legacy SPP 1.x
 */
class Registry extends \SPP\SPPObject
{
    /** @var array<string,mixed> */
    public static array $reg = [];

    /** @var array<int,mixed> */
    public static array $values = [];

    /** @var array<string,mixed> Flat lookup cache for O(1) performance */
    private static array $lookupCache = [];

    /** @var array<string,string> Resolved name cache */
    private static array $resolvedNames = [];

    /** @var string Active context prefix */
    private static string $contextPrefix = '';

    /** @var int */
    private static int $valkey = 0;

    public function __construct()
    {
        // Reserved for future expansion; no initialization required.
    }

    /**
     * Registers an entity and assigns a value.
     *
     * @param string $entity
     * @param mixed  $value
     * @return void
     */
    public static function register(string $entity, mixed $value): void
    {
        $entity = self::resolveEntityName($entity);
        $key = self::getKey($entity);

        if ($key !== false) {
            self::$values[$key] = $value;
            self::$lookupCache[$entity] = $value; // Update cache
            return;
        }

        // Create new hierarchical entry
        $tokens = array_map('trim', explode('=>', $entity));

        self::$values[self::$valkey] = $value;
        $arr = [array_pop($tokens) => self::$valkey];
        self::$valkey++;

        while (!empty($tokens)) {
            $val = array_pop($tokens);
            $arr = [$val => $arr];
        }

        // Merge if existing entry
        $rootKey = key($arr);
        if (array_key_exists($rootKey, self::$reg)) {
            $merged = array_merge_recursive(self::$reg[$rootKey], $arr[$rootKey]);
        } else {
            $merged = $arr[$rootKey];
        }

        self::$reg[$rootKey] = $merged;
    }

    /**
     * Registers a directory for a given category.
     */
    public static function registerDir(string $category, string|array $dir): void
    {
        $dir = str_replace('\\', '/', $dir);
        $existing = self::get('__dirs=>' . $category);
        $dirs = is_array($existing) ? $existing : [];
        $dirs = array_merge($dirs, (array) $dir);

        self::register('__dirs=>' . $category, $dirs);
    }

    /**
     * Registers a class for a given category.
     */
    public static function registerClass(string $category, string $class): void
    {
        $classes = self::get('__classes=>' . $category);
        $classes = is_array($classes) ? $classes : [];
        $classes[] = $class;

        self::register('__classes=>' . $category, $classes);
    }

    /**
     * Registers a function for a given category.
     */
    public static function registerFunction(string $category, string $function): void
    {
        $functions = self::get('__functions=>' . $category);
        $functions = is_array($functions) ? $functions : [];
        $functions[] = $function;

        self::register('__functions=>' . $category, $functions);
    }

    /**
     * Retrieves directories for a category.
     */
    public static function getDirs(string $category): array|false
    {
        return self::get('__dirs=>' . $category);
    }

    /**
     * Retrieves value of a registered entity.
     */
    public static function getValue(string $entity): mixed
    {
        $key = self::getKey($entity);
        return is_int($key) ? self::$values[$key] : false;
    }

    /**
     * Retrieves the value of a registered entity.
     * Returns false if entity is not registered.
     */
    public static function get(string $entity): mixed
    {
        $entity = self::resolveEntityName($entity);
        
        // O(1) Flat Cache Hit
        if (array_key_exists($entity, self::$lookupCache)) {
            return self::$lookupCache[$entity];
        }

        $key = self::getKey($entity);
        
        if (is_int($key)) {
            $value = self::$values[$key];
            self::$lookupCache[$entity] = $value; // Memoize for future
            return $value;
        }

        if (is_array($key)) {
            // It's a non-leaf node, attempt to resolve all children recursively
            return self::resolveValueMap($key);
        }

        return false;
    }

    /**
     * Recursively resolves an array of registry indices to their values.
     */
    private static function resolveValueMap(array $map): array
    {
        $result = [];
        foreach ($map as $k => $v) {
            if (is_int($v)) {
                $result[$k] = self::$values[$v] ?? false;
            } elseif (is_array($v)) {
                $result[$k] = self::resolveValueMap($v);
            } else {
                $result[$k] = $v;
            }
        }
        return $result;
    }

    /**
     * Checks if an entity is registered.
     */
    public static function isRegistered(string $entity): bool
    {
        $entity = self::resolveEntityName($entity);

        if (array_key_exists($entity, self::$lookupCache)) {
            return true;
        }

        return self::getKey($entity) !== false;
    }

    /**
     * Gets the registry key (internal helper).
     *
     * @param string $entity
     * @return array|int|false
     */
    private static function getKey(string $entity): array|int|false
    {
        // Internal check: if we already have the integer key mapping
        // but this is deeper than we usually cache.
        
        $tokens = array_map('trim', explode('=>', $entity));
        $arr = self::$reg;

        foreach ($tokens as $token) {
            if (!is_array($arr) || !array_key_exists($token, $arr)) {
                return false;
            }
            $arr = $arr[$token];
        }

        return $arr;
    }

    /**
     * Resolves the entity name with context and memoizes it.
     */
    private static function resolveEntityName(string $entity): string
    {
        // System-level global keys (starting with __) should not be prefixed with application context
        if (strpos($entity, '__') === 0) {
            return $entity;
        }

        $ctx = \SPP\Scheduler::getContext();
        if ($ctx === '' || $ctx === 'default') return $entity;

        $cacheKey = $ctx . '::' . $entity;
        if (isset(self::$resolvedNames[$cacheKey])) {
            return self::$resolvedNames[$cacheKey];
        }

        $resolved = '__apps=>' . $ctx . '=>' . $entity;
        self::$resolvedNames[$cacheKey] = $resolved;
        return $resolved;
    }
}
