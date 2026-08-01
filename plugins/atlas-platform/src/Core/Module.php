<?php

declare(strict_types=1);

namespace Atlas\Platform\Core;

interface Module
{
    public function register(): void;
}
