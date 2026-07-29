<?php
declare(strict_types=1);

namespace Atlas\Platform\Preview;

interface PreviewResourceRepository
{
    /** @return list<array<string, string>> */
    public function search(string $query = ''): array;
}
