<?php
declare(strict_types=1);

namespace Atlas\Platform\Core\Container;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use Throwable;

final class Container
{
    /** @var array<string, array{concrete: callable|string, shared: bool}> */
    private array $bindings = [];
    /** @var array<string, object> */
    private array $instances = [];
    /** @var list<string> */
    private array $resolving = [];

    public function bind(string $id, callable|string|null $concrete = null, bool $shared = false): void
    {
        $this->bindings[$id] = ['concrete' => $concrete ?? $id, 'shared' => $shared];
    }

    public function singleton(string $id, callable|string|null $concrete = null): void
    {
        $this->bind($id, $concrete, true);
    }

    public function instance(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->bindings[$id]) || class_exists($id);
    }

    public function get(string $id): object
    {
        return $this->make($id);
    }

    public function make(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }
        if (in_array($id, $this->resolving, true)) {
            throw new ContainerException('Circular dependency detected: ' . implode(' -> ', [...$this->resolving, $id]));
        }

        $binding = $this->bindings[$id] ?? ['concrete' => $id, 'shared' => false];
        $this->resolving[] = $id;
        try {
            $object = is_callable($binding['concrete'])
                ? ($binding['concrete'])($this)
                : $this->build($binding['concrete']);
        } catch (ContainerException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new ContainerException(sprintf('Unable to resolve "%s": %s', $id, $exception->getMessage()), 0, $exception);
        } finally {
            array_pop($this->resolving);
        }
        if (! is_object($object)) {
            throw new ContainerException(sprintf('Binding for "%s" did not return an object.', $id));
        }
        if ($binding['shared']) {
            $this->instances[$id] = $object;
        }
        return $object;
    }

    private function build(string $class): object
    {
        if (! class_exists($class)) {
            throw new ContainerException(sprintf('No binding or resolvable class exists for "%s".', $class));
        }
        $reflection = new ReflectionClass($class);
        if (! $reflection->isInstantiable()) {
            throw new ContainerException(sprintf('Class "%s" is not instantiable.', $class));
        }
        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return $reflection->newInstance();
        }
        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();
                    continue;
                }
                throw new ContainerException(sprintf('Parameter $%s on "%s" requires an explicit binding.', $parameter->getName(), $class));
            }
            $arguments[] = $this->make($type->getName());
        }
        return $reflection->newInstanceArgs($arguments);
    }
}
