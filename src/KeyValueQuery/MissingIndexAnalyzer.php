<?php

namespace Bdf\Prime\Analyzer\KeyValueQuery;

use Bdf\Prime\Analyzer\Repository\RepositoryQueryErrorAnalyzerInterface;
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
        if (!$this->checkFilterWithIndex($repository, $query->statements['where']) && !empty($query->statements['where'])) {
            return ['Query without index. Consider adding an index, or filter on an indexed field.'];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return 'index';
    }

    /**
     * @param RepositoryInterface $repository
     * @param array $clauses
     * @return bool
     */
    private function checkFilterWithIndex(RepositoryInterface $repository, array $clauses)
    {
        foreach ($clauses as $column => $value) {
            if ($this->fieldInIndex($repository, $column)) {
                return true;
            }
        }

        return false;
    }

    private function fieldInIndex(RepositoryInterface $repository, string $fieldName): bool
    {
        $metadata = $repository->metadata();

        if ($metadata->isPrimary($fieldName)) {
            return true;
        }

        if ($metadata->attributeExists($fieldName)) {
            $fieldName = $metadata->fieldFrom($fieldName);
        }

        foreach ($metadata->indexes() as $index) {
            if (isset($index['fields'][$fieldName])) {
                return true;
            }
        }

        return false;
    }
}
