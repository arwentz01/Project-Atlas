<?php
declare(strict_types=1);

namespace Atlas\Platform\Core\Modules;

use Atlas\Platform\Core\Container\Container;
use Atlas\Platform\Core\Logging\Logger;
use RuntimeException;
use Throwable;

final class ModuleRegistry
{
    /** @var array<string, Module> */
    private array $modules = [];
    /** @var array<string, string> */
    private array $statuses = [];

    public function __construct(private Container $container, private Logger $logger)
    {
    }

    public function add(Module $module): void
    {
        $slug = $module->slug();
        if ($slug === '' || isset($this->modules[$slug])) {
            throw new RuntimeException(sprintf('Duplicate or empty module slug "%s".', $slug));
        }
        $this->modules[$slug] = $module;
        $this->statuses[$slug] = 'registered';
        try {
            $module->register($this->container);
        } catch (Throwable $exception) {
            $this->statuses[$slug] = 'failed';
            $this->logger->log('critical', 'module.registration_failed', 'Module failed to register.', ['slug' => $slug], $slug, $exception);
            throw new RuntimeException(sprintf('Module "%s" failed to register.', $slug), 0, $exception);
        }
        $this->logger->log('info', 'module.registered', 'Module registered.', ['slug' => $slug], $slug);
    }

    public function bootAll(): void
    {
        foreach ($this->orderedSlugs() as $slug) {
            try {
                $this->modules[$slug]->boot();
                $this->statuses[$slug] = 'booted';
                $this->logger->log('info', 'module.booted', 'Module booted.', ['slug' => $slug], $slug);
            } catch (Throwable $exception) {
                $this->statuses[$slug] = 'failed';
                $this->logger->log('critical', 'module.boot_failed', 'Module failed to boot.', ['slug' => $slug], $slug, $exception);
                throw new RuntimeException(sprintf('Module "%s" failed to boot.', $slug), 0, $exception);
            }
        }
    }

    /** @return array<string, Module> */
    public function modules(): array { return $this->modules; }
    /** @return array<string, string> */
    public function statuses(): array { return $this->statuses; }

    /** @return list<string> */
    private function orderedSlugs(): array
    {
        $ordered = [];
        $visiting = [];
        $visit = function (string $slug) use (&$visit, &$ordered, &$visiting): void {
            if (in_array($slug, $ordered, true)) { return; }
            if (isset($visiting[$slug])) { throw new RuntimeException('Circular module dependency detected at "' . $slug . '".'); }
            if (! isset($this->modules[$slug])) { throw new RuntimeException('Missing module dependency "' . $slug . '".'); }
            $visiting[$slug] = true;
            foreach ($this->modules[$slug]->dependencies() as $dependency) { $visit($dependency); }
            unset($visiting[$slug]);
            $ordered[] = $slug;
        };
        foreach (array_keys($this->modules) as $slug) { $visit($slug); }
        return $ordered;
    }
}
