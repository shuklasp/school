<?php
namespace {
    if (!defined('SPP_DS')) define('SPP_DS', DIRECTORY_SEPARATOR);
    if (!defined('SPP_APP_DIR')) define('SPP_APP_DIR', __DIR__);
    if (!defined('APP_ETC_DIR')) define('APP_ETC_DIR', __DIR__ . '/etc');
}

namespace SPP\Exceptions {
    if (!class_exists('SPP\Exceptions\SPPException')) {
        class SPPException extends \Exception {}
    }
}

namespace SPPMod\SPPEntity {
    // We already modified this file, so it should contain the classes in SPP\Exceptions
    require_once('spp/modules/spp/sppentity/entityexceptions.php');
}

namespace {
    if (!class_exists('Scheduler')) {
        class Scheduler {
            public static function getContext() { return 'default'; }
        }
    }
    
    // Load the modified class
    require_once('spp/modules/spp/sppentity/class.sppentity.php');

    class ManualBrokenEntity extends \SPPMod\SPPEntity\SPPEntity {
        public function __construct() {
            // Manually set empty metadata to simulate a failed configuration
            self::$_metadata[static::class] = [
                'table' => '',
                'attributes' => []
            ];
        }
    }

    echo "Testing Entity Configuration Hardening (Mock Mode)...\n";

    try {
        echo "Attempting to get table for ManualBrokenEntity...\n";
        $entity = new ManualBrokenEntity();
        $table = $entity->getTable();
        echo "FAILED: getTable() returned '{$table}' instead of throwing exception.\n";
    } catch (\SPP\Exceptions\EntityConfigurationException $e) {
        echo "SUCCESS: Caught expected exception: " . $e->getMessage() . "\n";
    } catch (\Throwable $e) {
        echo "FAILED: Caught unexpected exception: " . get_class($e) . " - " . $e->getMessage() . "\n";
    }

    echo "Test complete.\n";
}
