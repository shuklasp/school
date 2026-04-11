<?php
namespace SPPMod\SPPGroup;

require_once('class.sppgroupmember.php');
require_once('class.sppgroup.php');

// Make sure entities exist or fall back intelligently.
if (\SPP\Module::isEnabled('sppentity') && class_exists('\SPPMod\SPPEntity\SPPEntity')) {
    // Relying on autoloader and YAML config for table generation when instantiating groups.
}
