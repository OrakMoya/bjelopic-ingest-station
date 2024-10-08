<?php

namespace App\Helpers;

use App\Exceptions\IngestRuleCriteriaException;
use App\Exceptions\InvalidIngestOperationException;
use App\Helpers\IngestRuleOperations\ExifTagIsOperation;
use App\Helpers\IngestRuleOperations\FilenameContainsOperation;
use App\Helpers\IngestRuleOperations\ContainsExifTagOperation;
use App\Helpers\IngestRuleOperations\MimetypeIsOperation;
use App\Helpers\IngestRuleOperations\SaveOperation;
use App\Helpers\IngestRule;
use App\Interfaces\IngestRuleOperation;
use GuzzleHttp\Utils;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

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
            $labelString = Str::of($rule['label'] ?? '')->trim();
            $nextRules = $rule['next'] ?? [];
            $opts = $rule['opts'] ?? [];

            if ($criteriaString->isEmpty()) {
                $msg = Str::of($labelString . ' ' . 'Invalid ingest rule criteria.')->trim();
                throw new IngestRuleCriteriaException($msg);
            }

            try {
            IngestRuleFactory::checkOptions($operationString, $opts);
            } catch (InvalidIngestOperationException $th) {
                throw new InvalidIngestOperationException($labelString . ' ' . $th->getMessage());
            }

            try {

                $ingestOperation = IngestRuleFactory::processIngestRuleOperation($operationString->toString());
            } catch (InvalidIngestOperationException $th) {
                $msg = Str::of($labelString . ' ' . $th->getMessage())->trim();
                throw new InvalidIngestOperationException($msg);
            }

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
            'containsExifTag' => new ContainsExifTagOperation(),
            'exifTagIs' => new ExifTagIsOperation(),
            'save' => new SaveOperation(),
            default => throw new InvalidIngestOperationException("Invalid operation: " . $operation)
        };
    }

    private static function checkOptions(string $operation, $opts): void
    {
        switch ($operation) {
            case "exifTagIs":
                if (
                    isset($opts['tag'])
                ) {
                    if (
                        gettype($opts['tag']) === 'string' &&
                        Str::of($opts['tag'])->isNotEmpty()
                    ) {
                        break;
                    }
                    throw new InvalidIngestOperationException("Invalid tag: " . $opts['tag']);
                }
                throw new InvalidIngestOperationException("Missing tag.");
            default:
                break;
        }
    }
}
