<?php

namespace Bdf\Prime\Analyzer\Query;

use Bdf\Prime\Analyzer\Repository\RepositoryQueryErrorAnalyzerInterface;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Analyze use of not declared attributes
 */
final class NotDeclaredAttributesAnalyzer implements RepositoryQueryErrorAnalyzerInterface
{
    /**
     * {@inheritdoc}
     */
    public function analyze(RepositoryInterface $repository, CompilableClause $query, array $parameters = []): array
    {
        return array_map(
            function (string $attribute) { return 'Use of undeclared attribute "'.$attribute.'".'; },
            array_values($this->listNotDeclaredAttributes($repository->metadata(), $query->statements['where'], $parameters))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return 'not_declared';
    }

    /**
     * List the not declared attributes
     *
     * @param Metadata $metadata The repository metadata
     * @param array $clauses Query clause du analyze
     * @param array $parameters
     *
     * @return string[]
     *
     * @todo check embedded and relations
     */
    private function listNotDeclaredAttributes(Metadata $metadata, array $clauses, array $parameters): array
    {
        $attributes = [];

        foreach ($clauses as $condition) {
            if (isset($condition['column']) && strpos($condition['column'], '.') === false && !$metadata->attributeExists($condition['column']) && !in_array($condition['column'], $parameters)) {
                $attributes[$condition['column']] = $condition['column'];
            } elseif (isset($condition['nested'])) {
                $attributes += $this->listNotDeclaredAttributes($metadata, $condition['nested'], $parameters);
            }
        }

        return $attributes;
    }
}
