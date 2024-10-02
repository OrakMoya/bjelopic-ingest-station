<?php

namespace App\Helpers\IngestRuleOperations;

use App\Interfaces\IngestRuleOperation;
use App\Models\File;
use Illuminate\Support\Str;

class FilenameContainsOperation implements IngestRuleOperation{
    public function handle(File $file, $criteria, $opts): bool|string
    {
        $result = Str::contains($file->filename, $criteria);
        return $result;
    }

}
