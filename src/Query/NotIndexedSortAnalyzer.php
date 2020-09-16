<?php

namespace Bdf\Prime\Analyzer\Query;

use Bdf\Prime\Analyzer\AnalysisTypes;
use Bdf\Prime\Analyzer\Repository\RepositoryQueryErrorAnalyzerInterface;
use Bdf\Prime\Analyzer\Repository\Util\RepositoryUtil;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Analyze sorted queries without index on the sorted field
 *
 * @implements RepositoryQueryErrorAnalyzerInterface<\Bdf\Prime\Query\Query>
 */
final class NotIndexedSortAnalyzer implements RepositoryQueryErrorAnalyzerInterface
{
    /**
     * {@inheritdoc}
     */
    public function analyze(RepositoryInterface $repository, CompilableClause $query, array $parameters = []): array
    {
        $util = new RepositoryUtil($repository);

        if (!empty($query->statements['orders']) && !in_array($query->statements['orders'][0]['sort'], $parameters) && !$util->isIndexed($query->statements['orders'][0]['sort'])) {
            return ['Sort without index on field "'.$query->statements['orders'][0]['sort'].'". Consider adding an index, or ignore this error if a small set of records is returned.'];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return AnalysisTypes::SORT;
    }
}
