<?php
namespace SPP;

/**
 * class PolyglotBridge
 * 
 * Orchestrates cross-language resource sharing and remote routine execution.
 * Supports Python, Perl, and C++ (MSVC) through a JSON-based protocol.
 */
class PolyglotBridge extends \SPP\SPPObject
{
    private static array $runtimes = [];

    /**
     * static function setup()
     * 
     * Performs runtime discovery and initializes the shared bridge environment.
     */
    public static function setup(): array
    {
        $log = [];
        $log[] = "Initializing Generic Polyglot Bridge Setup...";

        // 1. Discovery
        $runtimes = self::discoverRuntimes();
        $log[] = "Discovery complete: Found " . count(array_filter($runtimes, fn($v) => !empty($v['path']))) . " active runtimes.";

        // 2. Directory Management
        $sharedDir = \SPP\Module::getConfig('shared_dir', 'bridge') ?: 'var/shared';
        if (!str_starts_with($sharedDir, '/') && !str_contains($sharedDir, ':')) {
            $sharedDir = SPP_BASE_DIR . SPP_DS . '..' . SPP_DS . $sharedDir;
        }
        $sharedDir = realpath($sharedDir) ?: $sharedDir;

        $bridgeDir = $sharedDir . SPP_DS . 'bridge';
        if (!is_dir($bridgeDir)) {
            mkdir($bridgeDir, 0777, true);
            $log[] = "Created bridge directory: " . $bridgeDir;
        }

        // 3. Generate Dispatchers
        self::generateDispatchers($bridgeDir);
        $log[] = "Language dispatchers updated.";

        // 4. Export Configuration
        $config = self::exportConfig($sharedDir, $runtimes);
        $log[] = "Bridge configuration exported to " . $sharedDir . SPP_DS . 'bridge_config.json';

        return ['success' => true, 'log' => $log, 'config' => $config];
    }

    /**
     * static function discoverRuntimes()
     * 
     * Scans the system for Python, Perl, and C++ compilers.
     */
    public static function discoverRuntimes(): array
    {
        $runtimes = [
            'python'   => ['name' => 'Python', 'path' => '', 'version' => ''],
            'perl'     => ['name' => 'Perl',   'path' => '', 'version' => ''],
            'java'     => ['name' => 'Java',   'path' => '', 'version' => ''],
            'dotnet'   => ['name' => '.NET',   'path' => '', 'version' => ''],
            'go'       => ['name' => 'Go',     'path' => '', 'version' => ''],
            'compiler' => ['name' => 'C++ Compiler', 'path' => '', 'version' => '']
        ];

        $isWindows = PHP_OS_FAMILY === 'Windows';

        // Python (Prefer python3 on Linux/Unix)
        $pyBinaries = $isWindows ? ['python'] : ['python3', 'python'];
        foreach ($pyBinaries as $bin) {
            $path = self::findBinary($bin);
            if ($path) {
                $runtimes['python']['path'] = $path;
                $runtimes['python']['version'] = trim(@shell_exec("\"$path\" --version 2>&1") ?: 'Unknown');
                break;
            }
        }

        // Perl
        $perlPath = self::findBinary('perl');
        if ($perlPath) {
            $runtimes['perl']['path'] = $perlPath;
            $ver = @shell_exec("\"$perlPath\" -v 2>&1");
            if (preg_match('/v(\d+\.\d+\.\d+)/', $ver, $m)) $runtimes['perl']['version'] = $m[1];
        }

        // Java
        $javaPath = self::findBinary('java');
        if ($javaPath) {
            $runtimes['java']['path'] = $javaPath;
            $ver = @shell_exec("\"$javaPath\" -version 2>&1");
            if (preg_match('/version "([^"]+)"/i', $ver, $m)) {
                $runtimes['java']['version'] = $m[1];
            } elseif (preg_match('/openjdk version "([^"]+)"/i', $ver, $m)) {
                $runtimes['java']['version'] = $m[1];
            }
        }

        // .NET
        $dotnetPath = self::findBinary('dotnet');
        if ($dotnetPath) {
            $runtimes['dotnet']['path'] = $dotnetPath;
            $ver = @shell_exec("\"$dotnetPath\" --version 2>&1");
            if ($ver && !str_contains($ver, 'is not recognized')) {
                $runtimes['dotnet']['version'] = trim($ver);
            }
        }

        // Go
        $goPath = self::findBinary('go');
        if ($goPath) {
            $runtimes['go']['path'] = $goPath;
            $ver = @shell_exec("\"$goPath\" version 2>&1");
            if (preg_match('/go(\d+\.\d+\.\d+)/', $ver, $m)) $runtimes['go']['version'] = $m[1];
        }

        // C++ Compiler
        if ($isWindows) {
            $clPath = self::findBinary('cl');
            if ($clPath) {
                $runtimes['compiler']['path'] = $clPath;
                $runtimes['compiler']['name'] = 'MSVC';
            } else {
                // Fallback scan for MSVC
                $vsPaths = [
                    'C:\Program Files (x86)\Microsoft Visual Studio\2019\Community\VC\Tools\MSVC',
                    'C:\Program Files (x86)\Microsoft Visual Studio\2017\Community\VC\Tools\MSVC'
                ];
                foreach ($vsPaths as $base) {
                    if (is_dir($base)) {
                        $tools = glob($base . '\*', GLOB_ONLYDIR);
                        if (!empty($tools)) {
                            $latest = end($tools);
                            $bin = $latest . '\bin\Hostx64\x64\cl.exe';
                            if (file_exists($bin)) {
                                $runtimes['compiler']['path'] = $bin;
                                $runtimes['compiler']['name'] = 'MSVC';
                                break;
                            }
                        }
                    }
                }
            }
        } else {
            // Linux/Unix - Detect GCC or Clang
            foreach (['gcc', 'clang'] as $bin) {
                $path = self::findBinary($bin);
                if ($path) {
                    $runtimes['compiler']['path'] = $path;
                    $runtimes['compiler']['name'] = strtoupper($bin);
                    break;
                }
            }
        }

        if ($runtimes['compiler']['path']) {
             $id = @shell_exec("\"{$runtimes['compiler']['path']}\"" . ($isWindows ? " 2>&1" : " --version 2>&1"));
             if (preg_match('/(Version|(\d+\.\d+\.\d+)) ([\d\.]+)/', $id, $m)) {
                 $runtimes['compiler']['version'] = $m[3] ?? $m[2] ?? 'Unknown';
             } elseif (preg_match('/(\d+\.\d+\.\d+)/', $id, $m)) {
                 $runtimes['compiler']['version'] = $m[1];
             }
        }

        return $runtimes;
    }

    /**
     * private static function findBinary(string $name)
     * 
     * Handles OS-specific binary discovery (which/where).
     */
    private static function findBinary(string $name): string
    {
        $cmd = PHP_OS_FAMILY === 'Windows' ? "where {$name} 2>&1" : "which {$name} 2>&1";
        $out = @shell_exec($cmd);
        if ($out && !str_contains($out, "not found") && !str_contains($out, "Could not find")) {
            return trim(explode("\n", $out)[0]);
        }
        return '';
    }

    /**
     * static function call()
     * 
     * Direct invocation of external routines.
     */
    public static function call(string $lang, string $module, string $func, array $args = []): array
    {
        $lang = strtolower($lang);
        $sharedDir = \SPP\Module::getConfig('shared_dir', 'bridge') ?: 'var/shared';
        if (!str_starts_with($sharedDir, '/') && !str_contains($sharedDir, ':')) {
            $sharedDir = SPP_BASE_DIR . SPP_DS . '..' . SPP_DS . $sharedDir;
        }
        $sharedDir = realpath($sharedDir);
        
        $dispatchScript = $sharedDir . SPP_DS . 'bridge' . SPP_DS . 'dispatch.' . ($lang === 'python' ? 'py' : ($lang === 'perl' ? 'pl' : ''));
        
        if (!file_exists($dispatchScript)) {
            return ['success' => false, 'error' => "Dispatcher script for {$lang} not found."];
        }

        $runtimes = self::discoverRuntimes();
        $binary = $runtimes[$lang]['path'] ?? $lang;

        if ($lang === 'java') {
            // Java expects: java -cp <classpath> <Class> <args>
            // We assume the module name is the class name
            $command = "\"{$binary}\" \"{$module}\"";
        } elseif ($lang === 'dotnet') {
            // .NET expects: dotnet <DLL> <args>
            $command = "\"{$binary}\" \"{$module}\"";
        } elseif ($lang === 'go') {
            // Go expects: go run <file> <args>
            // For now we assume the module is a .go file path
            $command = "\"{$binary}\" run \"{$module}\" \"{$func}\"";
        } else {
            $dispatchScript = $sharedDir . SPP_DS . 'bridge' . SPP_DS . 'dispatch.' . ($lang === 'python' ? 'py' : ($lang === 'perl' ? 'pl' : ''));
            if (!file_exists($dispatchScript)) {
                return ['success' => false, 'error' => "Dispatcher script for {$lang} not found."];
            }
            $command = "\"{$binary}\" \"{$dispatchScript}\" \"{$module}\" \"{$func}\"";
        }
        
        $descriptors = [
            0 => ["pipe", "r"], // stdin
            1 => ["pipe", "w"], // stdout
            2 => ["pipe", "w"]  // stderr
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (is_resource($process)) {
            fwrite($pipes[0], json_encode($args));
            fclose($pipes[0]);

            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $status = proc_close($process);

            if ($status !== 0) {
                return ['success' => false, 'error' => "Runtime Error [{$status}]: " . $stderr];
            }

            $result = json_decode($stdout, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['success' => false, 'error' => "JSON Parse Error: " . $stdout . " | " . $stderr];
            }

            return ['success' => true, 'data' => $result];
        }

        return ['success' => false, 'error' => "Failed to spawn process."];
    }

    private static function generateDispatchers(string $bridgeDir): void
    {
        // Python Dispatcher
        $py = '
import sys, json, importlib, os

def main():
    try:
        module_name = sys.argv[1]
        func_name = sys.argv[2]
        
        # Add bridge directory to path
        bridge_dir = os.path.dirname(os.path.abspath(__file__))
        if bridge_dir not in sys.path:
            sys.path.insert(0, bridge_dir)

        args_raw = sys.stdin.read()
        args = json.loads(args_raw) if args_raw else []

        module = importlib.import_module(module_name)
        func = getattr(module, func_name)
        
        if isinstance(args, list):
            result = func(*args)
        elif isinstance(args, dict):
            result = func(**args)
        else:
            result = func()

        print(json.dumps(result))
    except Exception as e:
        sys.stderr.write(str(e))
        sys.exit(1)

if __name__ == "__main__":
    main()
';
        file_put_contents($bridgeDir . SPP_DS . 'dispatch.py', trim($py));

        // Perl Dispatcher
        $pl = '
use strict;
use warnings;
use JSON;
use File::Basename;
use lib dirname(__FILE__);

my $module = $ARGV[0];
my $func = $ARGV[1];
my $args_raw = do { local $/; <STDIN> };
my $args = $args_raw ? decode_json($args_raw) : [];

eval "require $module";
if ($@) { die $@; }

my $result;
if (ref($args) eq "ARRAY") {
    no strict "refs";
    $result = &{"${module}::${func}"}(@$args);
} else {
    no strict "refs";
    $result = &{"${module}::${func}"}($args);
}

print encode_json($result);
';
        file_put_contents($bridgeDir . SPP_DS . 'dispatch.pl', trim($pl));
    }

    private static function exportConfig(string $sharedDir, array $runtimes): array
    {
        $dbConfig = [
            'dbtype' => \SPP\Module::getConfig('dbtype', 'sppdb'),
            'dbhost' => \SPP\Module::getConfig('dbhost', 'sppdb'),
            'dbname' => \SPP\Module::getConfig('dbname', 'sppdb'),
            'dbuser' => \SPP\Module::getConfig('dbuser', 'sppdb'),
            'dbpasswd' => \SPP\Module::getConfig('dbpasswd', 'sppdb'),
        ];

        $bridgeData = [
            'timestamp' => time(),
            'spp_version' => defined('SPP_VER') ? SPP_VER : '0.5',
            'base_dir' => SPP_BASE_DIR,
            'database' => $dbConfig,
            'modules' => \SPP\Registry::get('__mods=>') ?: [],
            'runtimes' => $runtimes,
            'bridge_settings' => [
                'shared_dir' => $sharedDir,
            ]
        ];

        file_put_contents($sharedDir . SPP_DS . 'bridge_config.json', json_encode($bridgeData, JSON_PRETTY_PRINT));
        return $bridgeData;
    }
}
