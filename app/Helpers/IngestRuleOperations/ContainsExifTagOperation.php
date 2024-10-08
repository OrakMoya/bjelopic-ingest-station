<?php

namespace App\Helpers\IngestRuleOperations;

use App\Interfaces\IngestRuleOperation;
use App\Models\File;
use GuzzleHttp\Utils;
use Illuminate\Support\Facades\Log;

class ContainsExifTagOperation implements IngestRuleOperation{
    public function handle(File $file, $criteria, $opts): bool|string
    {
        $exif = Utils::jsonDecode($file->exif, true);
        return array_key_exists($criteria, $exif['raw']);
    }

}
