<?php

namespace App\Helpers;

use App\Interfaces\IngestRuleOperation;
use App\Models\File;

class IngestRule
{
    /**
     * @param mixed $criteria
     * @param mixed $opts
     * @param array<int,IngestRule> $nextRules
     */
    public function __construct(
        private IngestRuleOperation $operation,
        private $criteria,
        private array $nextRules,
        private $opts
    ) {}

    public function handle(File $file): string|bool
    {
        $result = $this->operation->handle($file, $this->criteria, $this->opts);

        if (is_bool($result) && $result) {
            foreach ($this->nextRules as $nextRule) {
                return $nextRule->handle($file);
            }
        }
        return $result;
    }
}