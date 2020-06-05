<?php

namespace Bdf\Prime\Analyzer\KeyValueQuery;

use Bdf\Collection\Stream\Streams;
use Bdf\Prime\Analyzer\AnalysisTypes;
use Bdf\Prime\Analyzer\Repository\RepositoryQueryErrorAnalyzerInterface;
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
        $metadata = $repository->metadata();

        return Streams::wrap($query->statements['where'])
            ->map(function ($value, $key) { return $key; })
            ->filter(function ($attribute) use($metadata) { return !$metadata->attributeExists($attribute); })
            ->filter(function ($attribute) use ($parameters) { return !in_array($attribute, $parameters); })
            ->map(function (string $attribute) { return 'Use of undeclared attribute "'.$attribute.'".'; })
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
