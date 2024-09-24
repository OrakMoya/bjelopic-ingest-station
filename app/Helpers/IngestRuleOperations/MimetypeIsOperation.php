<?php
namespace App\Helpers\IngestRuleOperations;

use App\Interfaces\IngestRuleOperation;
use App\Models\File;
use Illuminate\Support\Str;

class MimetypeIsOperation implements IngestRuleOperation{
    public function handle(File $file, $criteria, $opts): bool|string
    {
        if($file->mimetype == $criteria){
            return true;
        }

        $parts = explode('/', $criteria);
        if(Str::startsWith($file->mimetype, $parts[0])){
            if(!isset($parts[1]) || $parts[1] == '*'){
                return true;
            }
        }
        return false;
    }

}
