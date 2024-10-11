<?php

namespace Bdf\Prime\Analyzer\Query;

use Bdf\Prime\Analyzer\AnalysisTypes;
use Bdf\Prime\Analyzer\Repository\RepositoryQueryErrorAnalyzerInterface;
use Bdf\Prime\Analyzer\Repository\Util\RepositoryUtil;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Analyze use of not declared attributes
 *
 * @implements RepositoryQueryErrorAnalyzerInterface<\Bdf\Prime\Query\Query>
 */
final class NotDeclaredAttributesAnalyzer implements RepositoryQueryErrorAnalyzerInterface
{
    /**
     * {@inheritdoc}
     * @psalm-suppress RiskyTruthyFalsyComparison
     */
    public function analyze(RepositoryInterface $repository, CompilableClause $query, array $parameters = []): array
    {
        // Do not analyse join queries
        if (!empty($query->statements['joins'])) {
            return [];
        }

        $util = new RepositoryUtil($repository);

        return RecursiveClauseIterator::where($query)->stream()
            ->filter(function ($clause) use($util) { return isset($clause['column']) && !$util->hasAttribute($clause['column']); })
            ->map(function ($clause): string { return $clause['column']; })
            ->filter(function ($attribute) use($parameters) { return !in_array($attribute, $parameters); })
            ->distinct()
            ->map(function ($attribute) { return 'Use of undeclared attribute "'.$attribute.'".'; })
            ->toArray(false)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return AnalysisTypes::NOT_DECLARED;
    }
}
