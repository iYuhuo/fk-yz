<?php

namespace AuthSystem\Core\Container;


class Container
{
    private array $bindings = [];
    private array $instances = [];


    public function bind(string $abstract, $concrete = null): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = $concrete;
    }


    public function singleton(string $abstract, $concrete = null): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bind($abstract, $concrete);
        $this->instances[$abstract] = null;
    }


    public function make(string $abstract)
    {

        if (isset($this->instances[$abstract]) && $this->instances[$abstract] !== null) {
            return $this->instances[$abstract];
        }

        $concrete = $this->bindings[$abstract] ?? $abstract;


        if ($concrete instanceof \Closure) {
            $instance = $concrete($this);
        } else {

            $instance = $this->build($concrete);
        }


        if (isset($this->instances[$abstract])) {
            $this->instances[$abstract] = $instance;
        }

        return $instance;
    }


    private function build(string $concrete)
    {
        $reflector = new \ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new \Exception("Class {$concrete} is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete;
        }

        $dependencies = $this->resolveDependencies($constructor->getParameters());

        return $reflector->newInstanceArgs($dependencies);
    }


    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getType();

            if ($dependency === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new \Exception("Cannot resolve class dependency {$parameter->getName()}");
                }
            } elseif ($dependency instanceof \ReflectionNamedType && !$dependency->isBuiltin()) {

                $dependencies[] = $this->make($dependency->getName());
            } elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                throw new \Exception("Cannot resolve builtin type dependency {$dependency->getName()} for parameter {$parameter->getName()}");
            }
        }

        return $dependencies;
    }


    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]);
    }


    public function getBindings(): array
    {
        return $this->bindings;
    }
}