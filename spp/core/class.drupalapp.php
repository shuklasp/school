<?php

namespace SPP;

/**
 * Class DrupalApp
 * Wraps a Drupal installation as an SPP Application.
 */
class DrupalApp extends App
{
    protected string $drupalRoot;

    public function __construct(string $appname, string $drupalRoot)
    {
        $this->drupalRoot = $drupalRoot;
        // Level 2 init: load modules, but skip session/error for now 
        // as Drupal handles its own.
        parent::__construct($appname, false, 2);
    }

    /**
     * Handoff request to Drupal.
     */
    public function handle(\Symfony\Component\HttpFoundation\Request $request): void
    {
        $root = SPP_APP_DIR . '/' . $this->drupalRoot;
        if (!file_exists($root . '/autoload.php')) {
            throw new \SPP\SPPException("Drupal root not found: " . $root);
        }

        // Bootstrap Drupal
        $autoloader = require $root . '/autoload.php';
        $kernel = \Drupal\Core\DrupalKernel::createFromRequest($request, $autoloader, 'prod');
        
        $response = $kernel->handle($request);
        $response->send();
        $kernel->terminate($request, $response);
        exit;
    }
}
