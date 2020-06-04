<?php

namespace Bdf\Prime\Analyzer\Query;

use Bdf\Prime\Analyzer\Repository\RepositoryQueryErrorAnalyzerInterface;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Expression\Like;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Analyze the like filters to dump filters without a wildcard (% or _)
 */
final class LikeWithoutWildcardAnalyzer implements RepositoryQueryErrorAnalyzerInterface
{
    /**
     * {@inheritdoc}
     */
    public function analyze(RepositoryInterface $repository, CompilableClause $query, array $parameters = []): array
    {
        return RecursiveClauseIterator::where($query)->stream()
            ->filter([$this, 'checkLikeCondition'])
            ->map(function ($condition) { return $condition['column']; })
            ->distinct()
            ->map(function (string $filter) { return 'Like without wildcard on field "'.$filter.'".'; })
            ->toArray(false)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return 'like';
    }

    /**
     * Check a single like condition
     *
     * @param array $condition
     *
     * @return bool true if invalid (should be returned by invalidLikeFilters)
     */
    public function checkLikeCondition(array $condition): bool
    {
        if (!isset($condition['column'])) {
            return false;
        }

        if ($condition['value'] instanceof Like) {
            $value = $condition['value']->getValue();
        } elseif ($condition['operator'] === ':like') {
            $value = $condition['value'];
        } else {
            return false;
        }

        return $this->checkLikeValue($value);
    }

    /**
     * Check the like value
     *
     * @param mixed $value
     *
     * @return bool true if invalid (should be returned by invalidLikeFilters)
     */
    private function checkLikeValue($value): bool
    {
        if (is_string($value)) {
            return strpos($value, '%') === false && strpos($value, '_') === false;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if (!$this->checkLikeValue($item)) {
                    return false;
                }
            }
        }

        return true;
    }
}
