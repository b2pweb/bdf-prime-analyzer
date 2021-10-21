<?php

namespace Bdf\Prime\Analyzer;

use Bdf\Prime\Query\CompilableClause;

/**
 * Base type for analyze query
 *
 * @template T of CompilableClause
 */
interface AnalyzerInterface
{
    /**
     * Extract the entity class from the query
     *
     * @param T $query
     *
     * @return class-string|null The entity class, or null if cannot be resolved
     */
    public function entity(CompilableClause $query): ?string;

    /**
     * @param Report $report
     * @param T $query
     *
     * @return void
     */
    public function analyze(Report $report, CompilableClause $query): void;
}
