<?php
namespace SPP\Core;

/**
 * Class Container
 * A lightweight Dependency Injection container for the SPP framework.
 */
class Container
{
    protected array $bindings = [];
    protected array $instances = [];

    /**
     * Register a binding in the container.
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false)
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared
        ];
    }

    /**
     * Register a shared binding (singleton) in the container.
     */
    public function singleton(string $abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Resolve the given type from the container.
     */
    public function make(string $abstract, array $parameters = [])
    {
        // If the type is already instantiated as a singleton, return it
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->getConcrete($abstract);

        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete, $parameters);
        } else {
            $object = $this->make($concrete, $parameters);
        }

        // If it's a shared binding, cache the instance
        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    protected function getConcrete($abstract)
    {
        return $this->bindings[$abstract]['concrete'] ?? $abstract;
    }

    protected function isShared($abstract)
    {
        return $this->bindings[$abstract]['shared'] ?? false;
    }

    protected function isBuildable($concrete, $abstract)
    {
        return $concrete === $abstract || $concrete instanceof \Closure;
    }

    /**
     * Instantiate a concrete instance of the given type.
     */
    public function build($concrete, array $parameters = [])
    {
        if ($concrete instanceof \Closure) {
            return $concrete($this, $parameters);
        }

        $reflector = new \ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new \Exception("Class {$concrete} is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete();
        }

        $dependencies = $constructor->getParameters();
        $instances = $this->resolveDependencies($dependencies, $parameters);

        return $reflector->newInstanceArgs($instances);
    }

    protected function resolveDependencies(array $dependencies, array $parameters)
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            $name = $dependency->getName();

            // If a parameter is explicitly passed, use it
            if (isset($parameters[$name])) {
                $results[] = $parameters[$name];
                continue;
            }

            $type = $dependency->getType();

            if (!$type || $type->isBuiltin()) {
                if ($dependency->isDefaultValueAvailable()) {
                    $results[] = $dependency->getDefaultValue();
                } else {
                    throw new \Exception("Unresolvable dependency [{$dependency}] in class {$dependency->getDeclaringClass()->getName()}");
                }
            } else {
                $results[] = $this->make($type->getName());
            }
        }

        return $results;
    }
}
