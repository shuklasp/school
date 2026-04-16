<?php
namespace SPPMod\SPPView;
use Symfony\Component\Yaml\Yaml;

/**
 * class Pages
 *
 * Resolves page routes from YAML and/or database with configurable priority
 * and automatic fallback. Database source is only active when the sppdb
 * module is enabled. Tables are created automatically on first use.
 *
 * Priority is configured in modsconf/sppview/config.yml:
 *   page_source_primary:  yaml | db
 *   page_source_fallback: yaml | db | none
 *
 * @author Satya Prakash Shukla
 */
class Pages extends \SPP\SPPObject
{
    /** @var array<string,mixed>|null In-memory YAML cache */
    private static ?array $yamlCache = null;

    /** @var array<string,mixed>|null Resolved DB pages cache */
    private static ?array $dbCache = null;

    /** @var string[]|null Resolved [primary, fallback] sources */
    private static ?array $sources = null;

    /**
     * Whitelisted methods callable via 'specials' routing in pages.yml / DB.
     * Add new router methods here to keep call_user_func tightly controlled.
     */
    private static array $allowedSpecialMethods = [
        'getResource',
        'getFile',
    ];

    // -------------------------------------------------------------------------
    // Source resolution
    // -------------------------------------------------------------------------

    /**
     * Returns [primary, fallback] source names, respecting sppdb availability.
     * @return string[]
     */
    private static function getSources(): array
    {
        if (self::$sources !== null) {
            return self::$sources;
        }

        $dbAvailable = \SPP\Module::isEnabled('sppdb');

        $primary  = \SPP\Module::getConfig('page_source_primary',  'sppview') ?: 'yaml';
        $fallback = \SPP\Module::getConfig('page_source_fallback', 'sppview') ?: 'none';

        // Silently demote DB to 'none' when sppdb is not loaded
        if (!$dbAvailable) {
            if ($primary  === 'db') $primary  = 'yaml';
            if ($fallback === 'db') $fallback = 'none';
        }

        self::$sources = [$primary, $fallback];
        return self::$sources;
    }

    // -------------------------------------------------------------------------
    // YAML driver
    // -------------------------------------------------------------------------

    private static function getYaml(): array
    {
        if (self::$yamlCache === null) {
            $appname = \SPP\Scheduler::getContext();
            $file = APP_ETC_DIR . SPP_DS . $appname . SPP_DS . 'pages.yml';
            
            if (!file_exists($file)) {
                // Fallback to legacy location (APP_ETC_DIR/pages.yml)
                $legacyFile = APP_ETC_DIR . SPP_DS . 'pages.yml';
                if (file_exists($legacyFile)) {
                    $file = $legacyFile;
                } else {
                    throw new \SPP\SPPException('Pages configuration file not found in app context (' . $appname . ') or legacy location.');
                }
            }
            try {
                self::$yamlCache = Yaml::parseFile($file);
            } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
                throw new \SPP\SPPException('Failed to parse pages.yml: ' . $e->getMessage(), 1000, $e);
            }
        }
        return self::$yamlCache;
    }

    // -------------------------------------------------------------------------
    // Database driver
    // -------------------------------------------------------------------------

    /**
     * Ensures all three routing tables exist, creating them if absent.
     */
    public static function ensureDbSchema(): void
    {
        $db = new \SPPMod\SPPDB\SPPDB();

        $db->execute_query('CREATE TABLE IF NOT EXISTS ' . \SPPMod\SPPDB\SPPDB::sppTable('sppview_pages') . ' (
            id    INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name  VARCHAR(255) NOT NULL UNIQUE,
            url   VARCHAR(500) NOT NULL
        )');

        $db->execute_query('CREATE TABLE IF NOT EXISTS ' . \SPPMod\SPPDB\SPPDB::sppTable('sppview_defaults') . ' (
            id     INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
            defkey VARCHAR(100) NOT NULL UNIQUE,
            defval VARCHAR(500) NOT NULL
        )');

        $db->execute_query('CREATE TABLE IF NOT EXISTS ' . \SPPMod\SPPDB\SPPDB::sppTable('sppview_specials') . ' (
            id     INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name   VARCHAR(100) NOT NULL UNIQUE,
            method VARCHAR(100) NOT NULL
        )');
    }

    /**
     * Returns the full DB routing dataset, mirroring the YAML structure.
     * @return array{pages: array, defaults: array, specials: array}
     */
    private static function getDb(): array
    {
        if (self::$dbCache !== null) {
            return self::$dbCache;
        }

        self::ensureDbSchema();
        $db = new \SPPMod\SPPDB\SPPDB();

        $pages    = $db->execute_query('SELECT name, url FROM '    . \SPPMod\SPPDB\SPPDB::sppTable('sppview_pages'));
        $defaults = $db->execute_query('SELECT defkey, defval FROM ' . \SPPMod\SPPDB\SPPDB::sppTable('sppview_defaults'));
        $specials = $db->execute_query('SELECT name, method FROM ' . \SPPMod\SPPDB\SPPDB::sppTable('sppview_specials'));

        // Normalise into the same shape getYaml() returns
        $defaultsMap = [];
        foreach ($defaults as $row) {
            $defaultsMap[$row['defkey']] = $row['defval'];
        }

        self::$dbCache = [
            'pages'    => $pages,
            'defaults' => $defaultsMap,
            'specials' => $specials,
        ];

        return self::$dbCache;
    }

    // -------------------------------------------------------------------------
    // Internal helpers — single-source lookups
    // -------------------------------------------------------------------------

    /** @return array|null Matched page array or null */
    private static function findPageInYaml(string $q): ?array
    {
        $yaml = self::getYaml();
        foreach ($yaml['pages'] as $routeConfig) {
            if (substr_compare(trim($routeConfig['name']), $q, 0, strlen($routeConfig['name'])) === 0) {
                return self::buildPage($routeConfig['name'], $routeConfig['url'], $q);
            }
        }
        return null;
    }

    /** @return array|null Matched page array or null */
    private static function findPageInDb(string $q): ?array
    {
        $data = self::getDb();
        foreach ($data['pages'] as $row) {
            if (substr_compare(trim($row['name']), $q, 0, strlen($row['name'])) === 0) {
                return self::buildPage($row['name'], $row['url'], $q);
            }
        }
        return null;
    }

    /** Builds the standard page result array used throughout the framework */
    private static function buildPage(string $name, string $url, string $q): array
    {
        $url = ltrim($url, '/');
        $pg  = ['url' => $url, 'name' => $name, 'special' => 0];

        if ($name !== $q) {
            $pos = strpos($q, $name);
            $pr  = ($pos !== false) ? substr_replace($q, '', $pos, strlen($name)) : '';
            $pr  = ltrim($pr, '/');
            $pg['params'] = explode('/', $pr);
        } else {
            $pg['params'] = [];
        }

        $pg['named_params'] = [];
        foreach ($_GET as $parm => $value) {
            if ($parm === 'q') continue;
            $pg['named_params'][$parm] = $value;
        }

        return $pg;
    }

    /** @return string|null Special route URL or null */
    private static function findSpecialInYaml(string $spl, string $q): ?array
    {
        $yaml = self::getYaml();
        foreach ($yaml['specials'] ?? [] as $special) {
            if ($special['name'] === $spl) {
                return self::dispatchSpecial($special['method'], $q);
            }
        }
        return null;
    }

    /** @return array|null Special route result or null */
    private static function findSpecialInDb(string $spl, string $q): ?array
    {
        $data = self::getDb();
        foreach ($data['specials'] as $row) {
            if ($row['name'] === $spl) {
                return self::dispatchSpecial($row['method'], $q);
            }
        }
        return null;
    }

    /** Validates and dispatches a special method, returning the route result */
    private static function dispatchSpecial(string $method, string $q): array
    {
        if (!in_array($method, self::$allowedSpecialMethods, true)) {
            throw new \SPP\SPPException('Disallowed special route method: ' . $method);
        }
        return ['url' => call_user_func([__CLASS__, $method], $q), 'special' => 1];
    }

    /** @return string|null Default value from YAML or null */
    private static function findDefaultInYaml(string $def): ?string
    {
        $yaml = self::getYaml();
        return $yaml['defaults'][$def] ?? null;
    }

    /** @return string|null Default value from DB or null */
    private static function findDefaultInDb(string $def): ?string
    {
        $data = self::getDb();
        return $data['defaults'][$def] ?? null;
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Resolves a page route using the configured source priority.
     */
    public static function getPage($page = null): array
    {
        $q = (isset($_GET['q']) && $_GET['q'] != null) ? $_GET['q'] : $page;
        $q = ($page === null) ? $q : $page;

        [$primary, $fallback] = self::getSources();
        $spl = explode('/', $q)[0];

        // --- Specials ---
        $result = self::trySourceSpecial($primary, $spl, $q)
               ?? self::trySourceSpecial($fallback, $spl, $q);
        if ($result !== null) {
            return $result;
        }

        // --- Regular pages ---
        $result = self::trySourcePage($primary, $q)
               ?? self::trySourcePage($fallback, $q);
        if ($result !== null) {
            return $result;
        }

        // --- Not found ---
        $arr = ['page' => $q];
        \SPP\SPPEvent::fireEvent('PageNotFound', $arr, function () {
            throw new \SPP\SPPException('Page not found');
        });
        return ['url' => '', 'params' => [], 'named_params' => [], 'special' => 0];
    }

    private static function trySourceSpecial(string $source, string $spl, string $q): ?array
    {
        if ($source === 'yaml') return self::findSpecialInYaml($spl, $q);
        if ($source === 'db')   return self::findSpecialInDb($spl, $q);
        return null;
    }

    private static function trySourcePage(string $source, string $q): ?array
    {
        if ($source === 'yaml') return self::findPageInYaml($q);
        if ($source === 'db')   return self::findPageInDb($q);
        return null;
    }

    /**
     * Resolves a default value using the configured source priority.
     */
    public static function getDefault($def): mixed
    {
        [$primary, $fallback] = self::getSources();

        $val = self::trySourceDefault($primary, $def)
            ?? self::trySourceDefault($fallback, $def);

        if ($val !== null) {
            return $val;
        }

        $arr = ['def' => $def];
        \SPP\SPPEvent::fireEvent('DefaultNotFound', $arr, function (&$arr) {
            throw new \SPP\SPPException('Default ' . $arr['def'] . ' not found');
        });
        return false;
    }

    private static function trySourceDefault(string $source, string $def): ?string
    {
        if ($source === 'yaml') return self::findDefaultInYaml($def);
        if ($source === 'db')   return self::findDefaultInDb($def);
        return null;
    }

    /**
     * Returns the physical filesystem path to a resource URL.
     */
    public static function getResource($url): string
    {
        $dir = self::getDefault('resdir');
        return self::stripAndJoin($dir, $url);
    }

    /**
     * Returns the physical filesystem path to a file URL.
     */
    public static function getFile($url): string
    {
        $dir = self::getDefault('filesdir');
        return self::stripAndJoin($dir, $url);
    }

    private static function stripAndJoin(string $dir, string $url): string
    {
        $dir = ltrim($dir, '/');
        $url = ltrim($url, '/');
        $spl = explode('/', $url)[0];
        $url = substr($url, strlen($spl));
        return $dir . $url;
    }

    // -------------------------------------------------------------------------
    // Migration utility
    // -------------------------------------------------------------------------

    /**
     * One-time utility: imports all data from pages.yml into the DB tables.
     * Safe to call multiple times — existing rows are skipped via INSERT IGNORE.
     *
     * @return array{pages: int, defaults: int, specials: int} Counts of inserted rows
     */
    public static function importYamlToDb(): array
    {
        self::ensureDbSchema();
        $db   = new \SPPMod\SPPDB\SPPDB();
        $yaml = self::getYaml();
        $counts = ['pages' => 0, 'defaults' => 0, 'specials' => 0];

        foreach ($yaml['pages'] ?? [] as $page) {
            $db->execute_query(
                'INSERT IGNORE INTO ' . \SPPMod\SPPDB\SPPDB::sppTable('sppview_pages') . ' (name, url) VALUES (?, ?)',
                [$page['name'], $page['url']]
            );
            $counts['pages']++;
        }

        foreach ($yaml['defaults'] ?? [] as $key => $val) {
            $db->execute_query(
                'INSERT IGNORE INTO ' . \SPPMod\SPPDB\SPPDB::sppTable('sppview_defaults') . ' (defkey, defval) VALUES (?, ?)',
                [$key, $val]
            );
            $counts['defaults']++;
        }

        foreach ($yaml['specials'] ?? [] as $special) {
            $db->execute_query(
                'INSERT IGNORE INTO ' . \SPPMod\SPPDB\SPPDB::sppTable('sppview_specials') . ' (name, method) VALUES (?, ?)',
                [$special['name'], $special['method']]
            );
            $counts['specials']++;
        }

        return $counts;
    }

    // -------------------------------------------------------------------------
    // Cache management
    // -------------------------------------------------------------------------

    /**
     * Clears all in-memory caches. Call after modifying routes at runtime.
     */
    public static function clearCache(): void
    {
        self::$yamlCache = null;
        self::$dbCache   = null;
        self::$sources   = null;
    }

    /**
     * Returns the array of registered pages from both YAML and DB.
     */
    public static function listPages(): array
    {
        $yaml = self::getYaml();
        $ymlPages = $yaml['pages'] ?? [];
        foreach ($ymlPages as &$p) {
            $p['source'] = 'yaml';
        }

        $dbPages = [];
        if (\SPP\Module::isEnabled('sppdb')) {
            $data = self::getDb();
            $dbPages = $data['pages'] ?? [];
            foreach ($dbPages as &$p) {
                $p['source'] = 'db';
            }
        }

        return array_merge($ymlPages, $dbPages);
    }

    /**
     * Saves (Add or Update) a page route in either pages.yml or the database.
     */
    public static function savePage(string $name, string $url, string $source = 'yaml'): bool
    {
        if ($source === 'yaml') {
            $appname = \SPP\Scheduler::getContext();
            $file = APP_ETC_DIR . SPP_DS . $appname . SPP_DS . 'pages.yml';
            
            $yaml = file_exists($file) ? Yaml::parseFile($file) : ['pages' => [], 'defaults' => [], 'specials' => []];
            if (!isset($yaml['pages'])) $yaml['pages'] = [];

            $updated = false;
            foreach ($yaml['pages'] as &$p) {
                if ($p['name'] === $name) {
                    $p['url'] = $url;
                    $updated = true;
                    break;
                }
            }
            
            if (!$updated) {
                $yaml['pages'][] = ['name' => $name, 'url' => $url];
            }
            
            file_put_contents($file, Yaml::dump($yaml, 4, 2));
        } else if ($source === 'db') {
            self::ensureDbSchema();
            $db = new \SPPMod\SPPDB\SPPDB();
            $db->execute_query(
                'REPLACE INTO ' . \SPPMod\SPPDB\SPPDB::sppTable('sppview_pages') . ' (name, url) VALUES (?, ?)',
                [$name, $url]
            );
        }
        
        self::clearCache();
        return true;
    }

    /**
     * Removes a page route from either pages.yml or the database by name.
     */
    public static function removePage(string $name, string $source = 'yaml'): bool
    {
        if ($source === 'yaml') {
            $appname = \SPP\Scheduler::getContext();
            $file = APP_ETC_DIR . SPP_DS . $appname . SPP_DS . 'pages.yml';
            if (!file_exists($file)) return false;

            $yaml = Yaml::parseFile($file);
            if (!isset($yaml['pages'])) return false;

            $oldCount = count($yaml['pages']);
            $yaml['pages'] = array_values(array_filter($yaml['pages'], fn($p) => ($p['name'] ?? '') !== $name));
            
            if (count($yaml['pages']) === $oldCount) return false;

            file_put_contents($file, Yaml::dump($yaml, 4, 2));
        } else if ($source === 'db') {
            self::ensureDbSchema();
            $db = new \SPPMod\SPPDB\SPPDB();
            $db->execute_query('DELETE FROM ' . \SPPMod\SPPDB\SPPDB::sppTable('sppview_pages') . ' WHERE name=?', [$name]);
        }

        self::clearCache();
        return true;
    }
}
