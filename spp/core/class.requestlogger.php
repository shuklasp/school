<?php
namespace SPP\Middleware;

use SPP\Core\MiddlewareInterface;

/**
 * Class RequestLogger
 * A simple middleware that logs each request to the framework's log.
 */
class RequestLogger implements MiddlewareInterface
{
    public function handle($request, \Closure $next)
    {
        // Pre-processing
        $method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
        $uri = $_SERVER['REQUEST_URI'] ?? 'UNKNOWN';
        
        // Log the request (MOCKED for now as I don't want to rely on the full logger module)
        // \SPPMod\SPPLogger\SPP_Logger::info("Incoming Request: $method $uri");
        error_log("[SPP Middleware] Incoming Request: $method $uri");

        // Pass to the next middleware
        $response = $next($request);

        // Post-processing
        return $response;
    }
}
