<?php

namespace Bdf\Prime\Analyzer\Query;

use Bdf\Prime\Analyzer\Repository\RepositoryQueryErrorAnalyzerInterface;
use Bdf\Prime\Analyzer\Repository\Util\RepositoryUtil;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Analyze requests performed without any indexes
 */
final class MissingIndexAnalyzer implements RepositoryQueryErrorAnalyzerInterface
{
    /**
     * {@inheritdoc}
     */
    public function analyze(RepositoryInterface $repository, CompilableClause $query, array $parameters = []): array
    {
        // The query has join : cannot process indexes here
        if (!empty($query->statements['joins']) || empty($query->statements['where'])) {
            return [];
        }

        $hasIndex = RecursiveClauseIterator::where($query)->stream()
            ->filter(function ($clause) { return isset($clause['column']); })
            ->map(function ($clause) { return $clause['column']; })
            ->matchOne([new RepositoryUtil($repository), 'isIndexed'])
        ;

        return $hasIndex ? [] : ['Query without index. Consider adding an index, or filter on an indexed field.'];
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return 'index';
    }
}
