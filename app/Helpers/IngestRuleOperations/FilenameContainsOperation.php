<?php

namespace App\Helpers\IngestRuleOperations;

use App\Interfaces\IngestRuleOperation;
use App\Models\File;
use Illuminate\Support\Str;

class FilenameContainsOperation implements IngestRuleOperation{
    public function handle(File $file, $criteria, $opts): bool|string
    {
        return Str::contains($file->filename, $criteria);
    }

}
