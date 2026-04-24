<?php

namespace SPPMod\SPPDrupal;

/**
 * Class SPPDrupalBridge
 * Provides Twig extensions and service integration for the Drupal side.
 */
class SPPDrupalBridge
{
    /**
     * This method would be called by a Drupal module to register SPP functions in Twig.
     * It allows Drupal to do: {{ spp_entity('Student', 1).name }}
     */
    public static function getTwigExtensions(): array
    {
        return [
            new \Twig\TwigFunction('spp_entity', [self::class, 'fetchEntity']),
            new \Twig\TwigFunction('spp_service', [self::class, 'callService']),
        ];
    }

    public static function fetchEntity(string $entityName, $id)
    {
        $class = "\\SPPMod\\SPPEntity\\{$entityName}";
        if (class_exists($class)) {
            return $class::find($id);
        }
        return null;
    }

    public static function callService(string $serviceName, string $method, array $params = [])
    {
        $service = \SPP\App::getApp()->make($serviceName);
        if ($service && method_exists($service, $method)) {
            return call_user_func_array([$service, $method], $params);
        }
        return null;
    }
}
