<?php

namespace Bdf\Prime\Analyzer\Query;

use Bdf\Prime\Analyzer\AnalysisTypes;
use Bdf\Prime\Analyzer\Repository\RepositoryQueryErrorAnalyzerInterface;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Relations\Exceptions\RelationNotFoundException;
use Bdf\Prime\Relations\Relation;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Analyze the user of relation's distant key, instead of the local key, which cause a join
 *
 * @implements RepositoryQueryErrorAnalyzerInterface<\Bdf\Prime\Query\Query>
 */
final class RelationDistantKeyAnalyzer implements RepositoryQueryErrorAnalyzerInterface
{
    /**
     * {@inheritdoc}
     */
    public function analyze(RepositoryInterface $repository, CompilableClause $query, array $parameters = []): array
    {
        /** @var string[] */
        return RecursiveClauseIterator::where($query)->stream()
            ->filter(function ($condition) { return isset($condition['column']); })
            ->mapKey(function ($condition): string { return $condition['column']; })
            ->map(function ($condition) use($repository) { return $this->relationLocalKey($repository, $condition['column']); })
            ->filter(function ($localKey) { return !empty($localKey); })
            ->map(function($local, $distant) { return 'Use of relation distant key "'.$distant.'" which can cause an unnecessary join. Prefer use the local key "'.$local.'"'; })
            ->toArray(false)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return AnalysisTypes::RELATION_DISTANT_KEY;
    }

    /**
     * Get the related local key
     *
     * @param RepositoryInterface $repository
     * @param string $fieldName The filter to check
     *
     * @return string|null The related local key if applicable
     *
     * @psalm-suppress InvalidArrayAccess
     */
    private function relationLocalKey(RepositoryInterface $repository, string $fieldName): ?string
    {
        // Local field
        if ($repository->metadata()->attributeExists($fieldName)) {
            return null;
        }

        $parts = explode('.', $fieldName, 2);

        // Not a relation field
        if (count($parts) !== 2) {
            return null;
        }

        try {
            /** @psalm-suppress ArgumentTypeCoercion */
            $relation = $repository->mapper()->relation($parts[0]);
        } catch (RelationNotFoundException $e) {
            return null;
        }

        // Ignore other relations types
        if (!in_array($relation['type'], [Relation::BELONGS_TO, Relation::HAS_ONE, Relation::HAS_MANY])) {
            return null;
        }

        if (isset($relation['distantKey']) && $relation['distantKey'] === $parts[1]) {
            return $relation['localKey'];
        }

        return null;
    }
}
