<?php
namespace SPP\Core;

/**
 * Class MiddlewareKernel
 * Manages the registration and execution of the SPP Middleware Pipeline.
 */
class MiddlewareKernel
{
    protected static $middleware = [];
    protected static $isInitialized = false;

    /**
     * Initialize the kernel by loading the middleware stack from YAML.
     */
    public static function boot()
    {
        if (self::$isInitialized) return;

        $baseDir = defined('SPP_BASE_DIR') ? SPP_BASE_DIR : dirname(__DIR__, 2);
        
        // 1. Load Global Middleware
        $globalPath = $baseDir . '/spp/etc/middleware.yml';
        if (file_exists($globalPath) && class_exists('\Symfony\Component\Yaml\Yaml')) {
            try {
                $config = \Symfony\Component\Yaml\Yaml::parseFile($globalPath);
                self::$middleware = $config['global'] ?? [];
            } catch (\Exception $e) {}
        }
        
        // 2. Load App-Level Middleware
        if (class_exists('\SPP\Scheduler')) {
            $context = \SPP\Scheduler::getContext();
            if ($context && $context !== 'default') {
                $appPath = $baseDir . '/etc/apps/' . $context . '/middleware.yml';
                if (file_exists($appPath) && class_exists('\Symfony\Component\Yaml\Yaml')) {
                    try {
                        $appConfig = \Symfony\Component\Yaml\Yaml::parseFile($appPath);
                        $appMiddleware = $appConfig['global'] ?? $appConfig['middleware'] ?? [];
                        self::$middleware = array_merge(self::$middleware, $appMiddleware);
                    } catch (\Exception $e) {}
                }
            }
        }
        
        self::$isInitialized = true;
    }

    /**
     * Executes the middleware pipeline for the current request.
     *
     * @param \Closure $destination The final request handler (core logic)
     * @return mixed
     */
    public static function run(\Closure $destination)
    {
        self::boot();

        $pipeline = array_reduce(
            array_reverse(self::$middleware),
            function ($next, $middlewareClass) {
                return function ($request) use ($next, $middlewareClass) {
                    if (class_exists($middlewareClass)) {
                        $middleware = new $middlewareClass();
                        if ($middleware instanceof MiddlewareInterface) {
                            return $middleware->handle($request, $next);
                        }
                    }
                    // If middleware fails or doesn't exist, skip it
                    return $next($request);
                };
            },
            function ($request) use ($destination) {
                return $destination($request);
            }
        );

        return $pipeline($_REQUEST);
    }
}
