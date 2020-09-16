<?php

namespace Bdf\Prime\Analyzer\Repository;

use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Analyzer for errors on queries related to a repository
 *
 * @template T of CompilableClause
 */
interface RepositoryQueryErrorAnalyzerInterface
{
    /**
     * Analyze the query and return the errors
     *
     * @param RepositoryInterface $repository The related repository
     * @param T $query The query to analyze
     * @param array $parameters Analysis parameter, like fields to ignore
     *
     * @return string[]
     */
    public function analyze(RepositoryInterface $repository, CompilableClause $query, array $parameters = []): array;

    /**
     * The analyzer type name
     *
     * @return string
     */
    public function type(): string;
}
