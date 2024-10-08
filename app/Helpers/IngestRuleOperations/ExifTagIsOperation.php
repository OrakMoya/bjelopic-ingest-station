<?php

namespace App\Helpers\IngestRuleOperations;

use App\Interfaces\IngestRuleOperation;
use App\Models\File;
use GuzzleHttp\Utils;

class ExifTagIsOperation implements IngestRuleOperation{
    public function handle(File $file, $criteria, $opts): bool|string
    {
        $exif = Utils::jsonDecode($file->exif, true);
        $tag = $opts['tag'];
        return array_key_exists($opts['tag'], $exif['raw']) && $exif['raw'][$tag] === $criteria;
    }

}
