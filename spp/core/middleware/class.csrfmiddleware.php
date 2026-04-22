<?php
namespace SPP\Core\Middleware;

use SPP\SPPSession;
use SPP\Exceptions\SPPException;

/**
 * Class CSRFMiddleware
 * Protects state-changing requests by validating CSRF tokens.
 */
class CSRFMiddleware implements \SPP\Core\MiddlewareInterface
{
    /**
     * Handle the request and validate CSRF token for admin actions.
     */
    public function handle(array $request, \Closure $next)
    {
        // 1. Identify if this is an admin API request
        // In SPP, admin API is usually api.php in the admin folder.
        // We can also check $_SERVER['SCRIPT_NAME']
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        
        if (str_contains($scriptName, '/admin/api.php')) {
            $action = $request['action'] ?? '';
            
            // Skip check for login action if no session exists yet, 
            // but usually check_auth is the first call.
            if ($action !== 'login' && $action !== 'check_auth') {
                $submittedToken = $_REQUEST['csrf_token'] ?? '';
                
                try {
                    $sessionToken = @SPPSession::getCsrfToken();
                    if (!$submittedToken || $submittedToken !== $sessionToken) {
                        // In an enterprise system, we should log this potential attack
                        header('HTTP/1.1 419 Page Expired');
                        throw new SPPException("CSRF Token validation failed. Please refresh the page.", 419);
                    }
                } catch (\Exception $e) {
                    // Session might not be initialized yet
                    if ($action !== 'login') {
                         throw $e;
                    }
                }
            }
        }

        return $next($request);
    }
}
