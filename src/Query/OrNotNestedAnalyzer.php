<?php

namespace Bdf\Prime\Analyzer\Query;

use Bdf\Prime\Analyzer\AnalysisTypes;
use Bdf\Prime\Analyzer\Repository\RepositoryQueryErrorAnalyzerInterface;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Repository\RepositoryInterface;
use Doctrine\DBAL\Query\Expression\CompositeExpression;

/**
 * Analyze the OR conditions at the query root
 */
final class OrNotNestedAnalyzer implements RepositoryQueryErrorAnalyzerInterface
{
    /**
     * {@inheritdoc}
     */
    public function analyze(RepositoryInterface $repository, CompilableClause $query, array $parameters = []): array
    {
        foreach ($query->statements['where'] as $condition) {
            if ($condition['glue'] === CompositeExpression::TYPE_OR) {
                $error = 'OR not nested';

                if (isset($condition['column'])) {
                    $error .= ' on field "'.$condition['column'].'"';
                }

                $error .= '. Consider wrap the condition into a nested where : $query->where(function($query) { ... })';

                return [$error];
            }
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return AnalysisTypes::OR;
    }
}
