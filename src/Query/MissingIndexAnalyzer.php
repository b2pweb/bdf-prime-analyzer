<?php

namespace Bdf\Prime\Analyzer\Query;

use Bdf\Prime\Analyzer\Repository\RepositoryQueryErrorAnalyzerInterface;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Relations\Exceptions\RelationNotFoundException;
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
        if (!empty($query->statements['joins'])) {
            return [];
        }

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
     *
     * @todo check the relation indexes ?
     */
    private function checkFilterWithIndex(RepositoryInterface $repository, array $clauses)
    {
        foreach ($clauses as $condition) {
            if (isset($condition['column']) && $this->fieldInIndex($repository, $condition['column'])) {
                return true;
            }

            if (isset($condition['nested']) && $this->checkFilterWithIndex($repository, $condition['nested'])) {
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

        try {
            $repository->relation(explode('.', $fieldName, 2)[0]);

            return true; // @todo check indexes from relation
        } catch (RelationNotFoundException $e) {
            return false;
        }
    }
}
