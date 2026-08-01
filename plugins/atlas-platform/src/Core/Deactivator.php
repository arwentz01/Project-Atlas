<?php

declare(strict_types=1);

namespace Atlas\Platform\Core;

final class Deactivator
{
    public static function deactivate(): void
    {
        flush_rewrite_rules(false);
    }
}
