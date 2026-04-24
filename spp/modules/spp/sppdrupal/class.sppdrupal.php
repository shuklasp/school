<?php

namespace SPPMod\SPPDrupal;

/**
 * Class SPPDrupal
 * Bridge for Drupal content and services.
 */
class SPPDrupal extends \SPP\SPPObject
{
    protected ?string $drupalRoot = null;
    protected bool $booted = false;

    public function __construct()
    {
        $this->drupalRoot = \SPP\Module::getConfig('drupal_root', 'sppdrupal');
    }

    /**
     * Bootstrap Drupal's kernel inside SPP.
     * This allows using \Drupal::* methods directly.
     */
    public function bootstrap(): bool
    {
        if ($this->booted) return true;
        
        $root = SPP_APP_DIR . '/' . $this->drupalRoot;
        if (!file_exists($root . '/core/includes/bootstrap.inc')) {
            return false;
        }

        define('DRUPAL_DIR', $root);
        
        // Drupal needs its own autoloader
        $autoloader = require $root . '/autoload.php';
        
        $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
        $kernel = \Drupal\Core\DrupalKernel::createFromRequest($request, $autoloader, 'prod');
        $kernel->boot();
        $kernel->prepareLegacyRequest($request);
        
        $this->booted = true;
        return true;
    }

    /**
     * Fetch a node from Drupal (Bootstrapped mode).
     */
    public function getNode(int $nid)
    {
        if ($this->bootstrap()) {
            return \Drupal\node\Entity\Node::load($nid);
        }
        return null;
    }

    /**
     * Headless mode: Fetch node via JSON:API.
     */
    public function fetchNodeApi(int $nid)
    {
        $apiUrl = \SPP\Module::getConfig('api_url', 'sppdrupal');
        if (!$apiUrl) return null;

        $json = file_get_contents($apiUrl . "/node/article/" . $nid);
        return json_decode($json, true);
    }
}
