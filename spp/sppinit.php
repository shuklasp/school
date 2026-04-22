<?php
/**
 * File sppinit.php
 * Initiates the SPP.
 */
if (!defined('SPP_VER')) {

  /**
   * Store the old working directory.
   */
  define('SPP_VER', '0.5');
  //define('SPP_DS',DIRECTORY_SEPARATOR);
  define('SPP_DS', '/');
  define('SPP_US', '/');
  if (!defined('SPP_BASE_DIR')) {
    define('SPP_BASE_DIR', dirname(__FILE__));
  }
  define('SPP_DOC_ROOT', $_SERVER['DOCUMENT_ROOT']);
  $rstrlen = strlen(SPP_DOC_ROOT);
  $str = substr(SPP_BASE_DIR, $rstrlen);
  $str = str_replace('\\', '/', $str);
  define('SPP_CORE_DIR', SPP_BASE_DIR . SPP_DS . 'core');
  define('SPP_RES_URI', $str . SPP_US . 'res');
  define('SPP_JS_URI', SPP_RES_URI . SPP_US . 'js');
  define('SPP_DOJO_URI', SPP_JS_URI . SPP_US . 'dojotoolkit');
  define('SPP_DEV_DIR', SPP_BASE_DIR . SPP_DS . 'dev');
  define('SPP_CSS_URI', SPP_RES_URI . SPP_US . 'css');
  define('SPP_IMG_URI', SPP_RES_URI . SPP_US . 'images');
  define('SPP_MODULES_DIR', SPP_BASE_DIR . SPP_DS . 'modules');
  define('SPP_ETC_DIR', SPP_BASE_DIR . SPP_DS . 'etc');
  if (!defined('SPP_APP_DIR')) {
    define('SPP_APP_DIR', dirname(__DIR__, 1));
  }
  define('APP_ETC_DIR', SPP_APP_DIR . SPP_DS . 'etc' . SPP_DS . 'apps');
  define('SPP_LOG_DIR', SPP_APP_DIR . SPP_DS . 'var' . SPP_DS . 'logs');

  //echo SPP_APP_DIR;
  //$dirs=scandir(SPP_APP_DIR);
  //var_dump($dirs);
  //define('SPP_APP_ETC',SPP_ETC_DIR.SPP_DS.'apps');
  //define('SPP_MODSCONF_DIR',SPP_ETC_DIR.SPP_DS.'modsconf');
  define('SPP_RC_DIR', SPP_ETC_DIR . SPP_DS . 'rc.d');

// Include Composer autoloader
$composer_autoload = SPP_APP_DIR . SPP_DS . 'vendor' . SPP_DS . 'autoload.php';
if (file_exists($composer_autoload)) {
    require_once $composer_autoload;
}

  /**
   * Include core files.
   */

  spl_autoload_register(function ($class_name) {
    $path = explode('\\', $class_name);
    $class = array_pop($path);
    $search_paths = [
        SPP_CORE_DIR . SPP_DS . 'class.' . strtolower($class) . '.php',
        SPP_CORE_DIR . SPP_DS . 'int.' . strtolower($class) . '.php',
        SPP_CORE_DIR . SPP_DS . 'middleware' . SPP_DS . 'class.' . strtolower($class) . '.php'
    ];
    foreach ($search_paths as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
  });

  spl_autoload_register(function ($class_name) {
    //var_dump( $class_name);
    //var_dump($class_path);
    if (substr($class_name, strlen('Exception') * (-1)) == 'Exception') {
      require_once SPP_CORE_DIR . SPP_DS . 'class.sppexception.php';
      if (!class_exists($class_name)) {
        class_alias('SPP\SPPException', $class_name);
      }
      if (!class_exists('SPPException')) {
        class_alias('SPP\SPPException', 'SPPException');
      }
    }
  });

  spl_autoload_register(function ($class_name) {
    $path = explode('\\', $class_name);
    $class = array_pop($path);
    if (file_exists(SPP_CORE_DIR . SPP_DS . strtolower($class) . '.php')) {
      require_once SPP_CORE_DIR . SPP_DS . strtolower($class) . '.php';
    }
  });

  spl_autoload_register(function ($class_name) {
    if (strpos($class_name, 'SPPMod\\') === 0) {
      $path = explode('\\', strtolower($class_name));
      $class = array_pop($path);
      $mod = array_pop($path);
      $classFile = 'class.' . $class . '.php';
      
      foreach (['spp', 'school'] as $bucket) {
        $file = SPP_MODULES_DIR . SPP_DS . $bucket . SPP_DS . $mod . SPP_DS . $classFile;
        if (file_exists($file)) {
          require_once $file;
          return;
        }
      }
    }
  });

  spl_autoload_register(function ($class_name) {
    if (strpos($class_name, 'App\\') === 0) {
      $parts = explode('\\', $class_name);
      if (count($parts) >= 4) {
        $appName = strtolower($parts[1]);
        $type = strtolower($parts[2]);
        $name = strtolower($parts[3]);
        
        $file = '';
        if ($type === 'entities') {
            $file = SPP_APP_DIR . SPP_DS . 'src' . SPP_DS . $appName . SPP_DS . 'entities' . SPP_DS . 'entity.' . $name . '.php';
        } elseif ($type === 'components') {
            $file = SPP_APP_DIR . SPP_DS . 'src' . SPP_DS . $appName . SPP_DS . 'components' . SPP_DS . $parts[3] . '.php';
        }

        if ($file && file_exists($file)) {
          require_once $file;
        }
      }
    }
  });


  if (php_sapi_name() !== 'cli') {
    if (session_status() === PHP_SESSION_NONE) {
      // 1. Check for Redis Session Driver
      $redisEnabled = \SPP\Module::getConfig('enabled', 'redis');
      if (($redisEnabled === true || $redisEnabled === '1' || $redisEnabled === 'true') && \SPP\RedisCache::isAvailable()) {
          session_set_save_handler(new \SPP\Core\RedisSessionHandler(), true);
      }
      session_start();
    }
  } else {
    // In CLI mode, ensure $_SESSION is at least an empty array to prevent bridge/core failures
    if (!isset($_SESSION)) {
      $_SESSION = [];
    }
  }

  //\SPP\SPPEvent::startEvent('spp_init');
  //\SPP\SPPEvent::endEvent('spp_init');


  /* require_once SPP_CORE_DIR.SPP_DS.'sppfuncs.php';
  require_once SPP_CORE_DIR.SPP_DS.'class.sppobject.php';
  require_once SPP_CORE_DIR.SPP_DS.'class.app.php';
  require_once SPP_CORE_DIR.SPP_DS.'class.scheduler.php';
  require_once SPP_CORE_DIR.SPP_DS.'class.sppglobal.php';
  require_once SPP_CORE_DIR.SPP_DS.'class.sppxml.php';
  require_once SPP_CORE_DIR.SPP_DS.'class.stack.php';
  require_once SPP_CORE_DIR.SPP_DS.'class.settings.php';
  require_once SPP_CORE_DIR.SPP_DS.'class.sppsession.php';
  require_once SPP_CORE_DIR.SPP_DS.'class.sppbase.php';
  require_once SPP_CORE_DIR.SPP_DS.'class.registry.php';
  //require_once SPP_CORE_DIR.SPP_DS.'class.spphtmlelement.php';
  //require_once SPP_CORE_DIR.SPP_DS.'class.spphtmltable.php';
  //require_once SPP_CORE_DIR.SPP_DS.'class.sppformelement.php';
  //require_once SPP_CORE_DIR.SPP_DS.'class.sppvalidator.php';
  //require_once SPP_CORE_DIR.SPP_DS.'class.sppsinglevalidator.php';
  //require_once SPP_CORE_DIR.SPP_DS.'class.sppmultiplevalidator.php';
  //require_once SPP_CORE_DIR.SPP_DS.'classes.sppvalidators.php';
  //require_once SPP_CORE_DIR.SPP_DS.'classes.formelements.php';
  //require_once SPP_CORE_DIR.SPP_DS.'classes.htmlelements.php';
  //require_once SPP_CORE_DIR.SPP_DS.'class.sppform.php';
  //require_once SPP_CORE_DIR.SPP_DS.'class.spphtmlpage.php';
  require_once SPP_CORE_DIR.SPP_DS.'class.spperror.php';
  require_once SPP_CORE_DIR.SPP_DS.'class.sppevent.php';
  require_once SPP_CORE_DIR.SPP_DS.'class.sppexception.php';
  require_once SPP_CORE_DIR.SPP_DS.'class.module.php';
  require_once SPP_CORE_DIR.SPP_DS.'class.spputils.php';
  require_once SPP_CORE_DIR . SPP_DS . 'int.imodule.php';
  require_once SPP_CORE_DIR . SPP_DS . 'int.sppientity.php';
  //require_once SPP_CORE_DIR.SPP_DS.'class.sppentity.php';
  require_once SPP_CORE_DIR.SPP_DS.'class.sppfs.php';
  require_once SPP_CORE_DIR.SPP_DS.'sppsystemexceptions.php';
  //require_once SPP_CORE_DIR.SPP_DS.'class.sppdev.php';
  //require_once SPP_CORE_DIR.SPP_DS.'class.sppbase.php';
*/
  //\SPP\SPPEvent::registerEvent('spp_init');
  //  \SPP\SPPEvent::startEvent('spp_init');
//    define('SPP_SRC_URI',$str.SPP_DS.SPP_Setti)
  /**
   * Initiate SPPSession and SPPError
   */
  \SPP\Scheduler::detectAndEnforceContext();
  $app = new \SPP\App(\SPP\Scheduler::getContext());

  // Redundant call removed here as App::__construct already handles loadAllModules()
  
  // Bridge Configuration Export
  if (defined('SPP_BASE_DIR') && class_exists('\SPP\PolyglotBridge')) {
      \SPP\PolyglotBridge::setup();
  }
}
\SPP\SPPEvent::registerEvent('spp_init');
\SPP\SPPEvent::registerEvent('event_spp_module_install');
//echo SPP_APP_DIR . SPP_DS . 'events';
//\SPP\App::getAppConfDir()
//session_destroy();
//print SPPError::getUlErrors('Line: !linenum!, File: !filename!, Error No.: !errno!, Error message: !errmsg!');
/**
 * Load all active modules.
 */
//require_once('services.php');
//$dirs=\SPP\Registry::getDirs('events');
//var_dump($dirs);
//print_r(\SPP\Registry::$reg);
//print_r(\SPP\Registry::$values);
?>
