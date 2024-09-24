<?php

namespace App\Helpers\IngestRuleOperations;

use App\Models\File;
use App\Interfaces\IngestRuleOperation;

class SaveOperation implements IngestRuleOperation{
    public function handle(File $file, $criteria, $opts): bool|string
    {
        return $criteria;
    }

}
