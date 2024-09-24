<?php

namespace App\Interfaces;

use App\Models\File;

interface IngestRuleOperation
{
    /**
     * @param mixed $criteria
     * @param array<string, mixed> $opts
     */
    public function handle(File $file, $criteria, $opts): bool|string;
}
