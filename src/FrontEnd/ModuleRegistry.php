<?php

declare(strict_types=1);

namespace Atlas\FrontEnd;

final class ModuleRegistry
{
    public static function boot(): void
    {
        $files = glob(ATLAS_DIR . 'src/FrontEnd/Modules/*.php') ?: [];
        sort($files);
        foreach ($files as $file) {
            require_once $file;
            $class = 'Atlas\\FrontEnd\\Modules\\' . basename($file, '.php');
            if (class_exists($class) && method_exists($class, 'boot')) {
                $class::boot();
            }
        }
    }
}
