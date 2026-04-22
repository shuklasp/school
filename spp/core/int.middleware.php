<?php
namespace SPP\Core;

/**
 * Interface MiddlewareInterface
 * Defines the contract for SPP Middleware components.
 */
interface MiddlewareInterface
{
    /**
     * Handle an incoming request.
     *
     * @param mixed $request  The request context (can be $_REQUEST or a custom object)
     * @param \Closure $next  The next middleware in the pipeline
     * @return mixed          The response
     */
    public function handle($request, \Closure $next);
}
