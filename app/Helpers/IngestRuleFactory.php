<?php

namespace App\Helpers;

use App\Exceptions\IngestRuleCriteriaException;
use App\Exceptions\InvalidIngestOperationException;
use App\Helpers\IngestRuleOperations\FilenameContainsOperation;
use App\Helpers\IngestRuleOperations\MimetypeIsOperation;
use App\Helpers\IngestRuleOperations\SaveOperation;
use App\Helpers\IngestRule;
use App\Interfaces\IngestRuleOperation;
use GuzzleHttp\Utils;
use Illuminate\Support\Str;

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

        // Next rules are actually a single rule
        if (isset($rules['operation'])) {
            $rules = [$rules];
        }

        foreach ($rules as $rule) {
            $operationString = Str::of($rule['operation'])->trim();
            $criteriaString = Str::of($rule['criteria'])->trim();
            $nextRules = $rule['next'] ?? [];
            $opts = $rule['opts'] ?? [];

            if ($criteriaString->isEmpty()) {
                $msg = 'Invalid ingest rule criteria.';
                throw new IngestRuleCriteriaException($msg);
            }

            $ingestOperation = IngestRuleFactory::processIngestRuleOperation($operationString->toString());

            array_push($arrayOfRuleObjects, new IngestRule(
                $ingestOperation,
                $criteriaString->toString(),
                IngestRuleFactory::create($nextRules),
                $opts
            ));
        }

        return $arrayOfRuleObjects;
    }

    private static function processIngestRuleOperation(string $operation): IngestRuleOperation
    {

        return match ($operation) {
            'filenameContains' => new FilenameContainsOperation(),
            'mimetypeIs' => new MimetypeIsOperation(),
            'save' => new SaveOperation(),
            default => throw new InvalidIngestOperationException("Invalid operation: " . $operation)
        };
    }
}
