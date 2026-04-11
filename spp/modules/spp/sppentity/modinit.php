<?php
/**
 * modinit.php for SPPEntity Module
 * Initializes interceptor autoloader for Config-Driven YAML entities.
 */

spl_autoload_register(function ($class_name) {
    // If the class is legitimately compiled in memory, leave immediately
    if (class_exists($class_name, false)) {
        return;
    }

    $path = explode('\\', $class_name);
    $short_class = array_pop($path);
    $namespace = implode('\\', $path);
    
    // Validate if the SPPEntity framework is mounted
    if (class_exists('\SPPMod\SPPEntity\SPPEntity', true)) {
        // Query the configuration payload map
        $yml_file = \SPPMod\SPPEntity\SPPEntity::getEntityConfigFile($short_class);
        if ($yml_file !== false) {
            $extends = '\SPPMod\SPPEntity\SPPEntity';
            
            // Check for structural inheritance in the raw YAML stream efficiently
            $content = file_get_contents($yml_file);
            if (preg_match('/^extends:\s*([a-zA-Z0-9_\\\\-]+)/m', $content, $matches)) {
                $extends = trim($matches[1]);
                // Prefix with global namespace if not already qualified or relative
                if (strpos($extends, '\\') === false && !class_exists($extends)) {
                    $extends = (empty($namespace) ? '' : $namespace . '\\') . $extends;
                }
            }

            // Drop a dynamic entity proxy definition into RAM with native class hierarchy
            $classConfig = "";
            if (!empty($namespace)) {
                $classConfig .= "namespace $namespace; ";
            }
            $classConfig .= "class $short_class extends $extends {}";
            
            eval($classConfig);
        }
    }
});
?>
