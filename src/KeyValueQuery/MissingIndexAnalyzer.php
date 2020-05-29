<?php

namespace Bdf\Prime\Analyzer\KeyValueQuery;

use Bdf\Collection\Stream\Streams;
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
        $indexed = empty($query->statements['where']) || Streams::wrap($query->statements['where'])
            ->map(function ($value, $key) { return $key; })
            ->matchOne([new RepositoryUtil($repository), 'isIndexed'])
        ;

        return $indexed ? [] : ['Query without index. Consider adding an index, or filter on an indexed field.'];
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return 'index';
    }
}
