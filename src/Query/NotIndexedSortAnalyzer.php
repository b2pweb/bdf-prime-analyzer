<?php

namespace Bdf\Prime\Analyzer\Query;

use Bdf\Prime\Analyzer\Repository\RepositoryQueryErrorAnalyzerInterface;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Analyze sorted queries without index on the sorted field
 */
final class NotIndexedSortAnalyzer implements RepositoryQueryErrorAnalyzerInterface
{
    /**
     * {@inheritdoc}
     */
    public function analyze(RepositoryInterface $repository, CompilableClause $query, array $parameters = []): array
    {
        if (!empty($query->statements['orders']) && !in_array($query->statements['orders'][0]['sort'], $parameters) && !$this->fieldInIndex($repository->metadata(), $query->statements['orders'][0]['sort'])) {
            return ['Sort without index on field "'.$query->statements['orders'][0]['sort'].'". Consider adding an index, or ignore this error if a small set of records is returned.'];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return 'sort';
    }

    private function fieldInIndex(Metadata $metadata, string $fieldName): bool
    {
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
