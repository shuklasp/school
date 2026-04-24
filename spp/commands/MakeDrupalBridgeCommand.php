<?php

namespace SPP\CLI\Commands;

use SPP\CLI\Command;

/**
 * Class MakeDrupalBridgeCommand
 * Scaffolds a Drupal module that allows Drupal to access SPP resources.
 */
class MakeDrupalBridgeCommand extends BaseMakeCommand
{
    protected string $name = 'make:drupal-bridge';
    protected string $description = 'Scaffold a Drupal module to bridge SPP into Drupal';

    public function execute(array $args): void
    {
        $drupalRootInput = $args[2] ?? null;
        if (!$drupalRootInput) {
            echo "Drupal Root Path (e.g. ../drupal): ";
            $drupalRootInput = trim(fgets(STDIN));
        }
        if (!$drupalRootInput) return;

        $drupalRoot = SPP_APP_DIR . '/' . $drupalRootInput;
        $moduleDir = $drupalRoot . '/modules/custom/spp_bridge';

        if (!is_dir($moduleDir)) mkdir($moduleDir, 0777, true);

        // 1. spp_bridge.info.yml
        $info = "name: SPP Bridge
type: module
description: 'Allows Drupal to access SPP Entities and Services.'
core_version_requirement: ^8 || ^9 || ^10 || ^11
package: Custom
";
        file_put_contents($moduleDir . '/spp_bridge.info.yml', $info);

        // 2. spp_bridge.module (Bootstrapping SPP inside Drupal)
        $sppInitPath = SPP_APP_DIR . '/spp/sppinit.php';
        $module = <<<'PHP'
<?php
/**
 * @file
 * Main module file for SPP Bridge.
 */

// Load SPP Autoloader
$sppInit = '{{SPP_INIT}}';
if (file_exists($sppInit)) {
    require_once $sppInit;
}

/**
 * Implements hook_twig_functions().
 */
function spp_bridge_theme_suggestions_alter(array &$suggestions, array $variables) {
    // Example of using SPP data to influence Drupal theme
    if (class_exists('\\SPPMod\\SPPAuth\\SPPAuth') && \\SPPMod\\SPPAuth\\SPPAuth::isLoggedIn()) {
        $suggestions[] = 'page__spp_logged_in';
    }
}
PHP;
        $module = str_replace('{{SPP_INIT}}', $sppInitPath, $module);
        file_put_contents($moduleDir . '/spp_bridge.module', $module);

        // 3. Twig Extension registration
        $services = "services:
  spp_bridge.twig_extension:
    class: Drupal\spp_bridge\Twig\SPPBridgeExtension
    tags:
      - { name: twig.extension }
";
        file_put_contents($moduleDir . '/spp_bridge.services.yml', $services);

        $extDir = $moduleDir . '/src/Twig';
        if (!is_dir($extDir)) mkdir($extDir, 0777, true);

        $extension = <<<'PHP'
<?php

namespace Drupal\spp_bridge\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SPPBridgeExtension extends AbstractExtension {
    public function getFunctions() {
        return [
            new TwigFunction('spp_entity', [self::class, 'fetchEntity']),
            new TwigFunction('spp_service', [self::class, 'callService']),
        ];
    }

    public static function fetchEntity($entity, $id) {
        return \SPPMod\SPPDrupal\SPPDrupalBridge::fetchEntity($entity, $id);
    }

    public static function callService($service, $method, $params = []) {
        return \SPPMod\SPPDrupal\SPPDrupalBridge::callService($service, $method, $params);
    }
}
PHP;
        file_put_contents($extDir . '/SPPBridgeExtension.php', $extension);

        echo "Success: Drupal bridge module created at {$moduleDir}\n";
        echo "Enable it in Drupal using: drush en spp_bridge\n";
    }
}
