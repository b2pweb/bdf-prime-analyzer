<?php

namespace Bdf\Prime\Analyzer\Query;

use Bdf\Prime\Analyzer\Repository\RepositoryQueryErrorAnalyzerInterface;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Relations\Exceptions\RelationNotFoundException;
use Bdf\Prime\Relations\Relation;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Analyze the user of relation's distant key, instead of the local key, which cause a join
 */
final class RelationDistantKeyAnalyzer implements RepositoryQueryErrorAnalyzerInterface
{
    /**
     * {@inheritdoc}
     */
    public function analyze(RepositoryInterface $repository, CompilableClause $query, array $parameters = []): array
    {
        $errors = [];

        foreach ($this->listDistantKeys($repository, $query->statements['where']) as $distant => $local) {
            $errors[] = 'Use of relation distant key "'.$distant.'" which can cause an unnecessary join. Prefer use the local key "'.$local.'"';
        }

        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return 'relation_distant_key';
    }

    /**
     * List the distant keys, mapped with the related local key
     *
     * @param RepositoryInterface $repository
     * @param array $clauses
     *
     * @return string[]
     */
    private function listDistantKeys(RepositoryInterface $repository, array $clauses): array
    {
        $filters = [];

        foreach ($clauses as $condition) {
            if (isset($condition['nested'])) {
                $filters += $this->listDistantKeys($repository, $condition['nested']);
            } elseif (isset($condition['column']) && $localKey = $this->relationLocalKey($repository, $condition['column'])) {
                $filters[$condition['column']] = $localKey;
            }
        }

        return $filters;
    }

    /**
     * Get the related local key
     *
     * @param RepositoryInterface $repository
     * @param string $fieldName The filter to check
     *
     * @return string|null The related local key if applicable
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
