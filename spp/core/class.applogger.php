<?php
namespace SPP\Middleware;

use SPP\Core\MiddlewareInterface;

/**
 * Class AppLogger
 * A middleware that only runs for a specific application.
 */
class AppLogger implements MiddlewareInterface
{
    public function handle($request, \Closure $next)
    {
        error_log("[SPP Middleware] APPLOGGER: Running for specific app context.");
        return $next($request);
    }
}
