<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('vendor/autoload.php');
require_once('spp/sppinit.php');

// 1. Context Discovery: If 'q' matches a registered app, switch context
$q = $_GET['q'] ?? '';
$parts = explode('/', trim($q, '/'));
$potentialApp = $parts[0];
$settings = \SPP\App::getGlobalSettings();

// Case-insensitive app discovery
$appKeys = array_keys($settings['apps']);
$appMap = array_combine(array_map('strtolower', $appKeys), $appKeys);
$lowPotential = strtolower($potentialApp);

if (isset($appMap[$lowPotential])) {
    $realAppName = $appMap[$lowPotential];
    \SPP\App::getApp($realAppName);
    array_shift($parts);
    $_GET['q'] = implode('/', $parts);
}

require_once('global.php');

\SPP\Core\MiddlewareKernel::run(function($request) {
    $appBaseUri = defined('APP_BASE_URI') ? APP_BASE_URI : '';
    $appAsset = function (string $path) use ($appBaseUri): string {
        return rtrim($appBaseUri, '/') . '/' . ltrim($path, '/');
    };

    // Register core scripts INSIDE the kernel to prevent them from being lost during bootstrap
    \SPPMod\SPPView\ViewPage::addCssIncludeFile($appAsset('res/spp/css/spp.css'));
    \SPPMod\SPPView\ViewPage::addJsIncludeFile($appAsset('res/spp/js/spp.js'));
    \SPPMod\SPPView\ViewPage::addJsIncludeFile($appAsset('res/spp/js/spp-router.js'));
    \SPPMod\SPPView\ViewPage::addJsIncludeFile($appAsset('res/spp/js/sppvalidations.js'));
    \SPPMod\SPPView\ViewPage::addJsIncludeFile($appAsset('res/spp/js/spp-autoinit.js'));

    if (\SPP\Module::isEnabled('sppux') && class_exists('\SPPMod\SPPUX\SPPUX')) {
        \SPPMod\SPPUX\SPPUX::boot();
    }

    if (\SPP\Module::isEnabled('sppapi') && class_exists('\SPPMod\SPPAPI\SPPAPI') && \SPPMod\SPPAPI\SPPAPI::isApiRequest()) {
        \SPPMod\SPPAPI\SPPAPI::handle();
        return;
    }

    if (\SPP\Module::isEnabled('sppajax') && \SPPMod\SPPAjax\SPPAjax::isAjaxRequest()) {
        \SPPMod\SPPAjax\SPPAjax::handle();
        return;
    }

    \SPPMod\SPPView\ViewPage::processForms();
    
    $activeProc = \SPP\Scheduler::getActiveProc();
    if (method_exists($activeProc, 'handle')) {
        $activeProc->handle($request);
        return;
    }

    \SPPMod\SPPView\ViewPage::showPage();
});
