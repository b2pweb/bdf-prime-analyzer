<?php

namespace Bdf\Prime\Analyzer;

use Bdf\Prime\Query\CompilableClause;

/**
 * Interface AnalyzerInterface
 * @package Bdf\Prime\Analyzer
 */
interface AnalyzerInterface
{
    /**
     * Extract the entity class from the query
     *
     * @param CompilableClause $query
     *
     * @return string|null The entity class, or null if cannot be resolved
     */
    public function entity(CompilableClause $query): ?string;

    /**
     * @param Report $report
     * @param CompilableClause $query
     *
     * @return void
     */
    public function analyze(Report $report, CompilableClause $query): void;
}
