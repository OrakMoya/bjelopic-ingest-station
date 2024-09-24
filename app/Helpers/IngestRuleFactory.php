<?php

namespace App\Helpers;

use App\Helpers\IngestRuleOperations\FilenameContainsOperation;
use App\Helpers\IngestRuleOperations\MimetypeIsOperation;
use App\Helpers\IngestRuleOperations\SaveOperation;
use App\Helpers\IngestRule;
use Exception;
use GuzzleHttp\Utils;


/*
 * Ingest rule format:
 * [
 *      0 => [
 *          operation => 'xxx',
 *          criteria => 'yyy',
 *          opts => array<string, string>,
 *          next => [
 *              0 => [ operation => 'zzz'...]
 *          ],
 *      ],
 *      1 => [
 *          operation => 'aaa',
 *          criteria = 'bbb',
 *          opts => ...,
 *          next => [
 *              0 => [
 *                  operation => 'save',
 *                  criteria => project relative path,
 *                  opts => ...
 *                  next => [] empty array, is ignored
 *              ]
 *          ]
 *      ]
 *      2 => ...
 * ]
 */

class IngestRuleFactory
{
    /**
     * @param array<int,mixed>|string $rules
     * @return array<int, IngestRule>
     */
    public static function create(array|string $rules): array
    {
        if (gettype($rules) == 'string') {
            $rules = Utils::jsonDecode($rules, true);
        }

        $arrayOfRuleObjects = [];

        foreach ($rules as $rule) {
            $operationString = $rule['operation'];
            $criteriaString = $rule['criteria'];
            $nextRules = $rule['next'];
            if (is_null($nextRules)) {
                $nextRules = [];
            }
            $opts = $rule['opts'];

            $ingestOperation = null;
            switch ($operationString) {
                case 'filenameContains':
                    $ingestOperation = new FilenameContainsOperation();
                    break;
                case 'mimetypeIs':
                    $ingestOperation = new MimetypeIsOperation();
                    break;
                case 'save':
                    $ingestOperation = new SaveOperation();
                    break;
                default:
                    throw new Exception("Invalid operation");
            }

            array_push($arrayOfRuleObjects, new IngestRule(
                $ingestOperation,
                $criteriaString,
                IngestRuleFactory::create($nextRules),
                $opts
            ));
        }
        return $arrayOfRuleObjects;
    }
}
