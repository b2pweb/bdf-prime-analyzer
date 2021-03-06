<?php

namespace Bdf\Prime\Analyzer;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\CompilerInterface;

/**
 * Adapt for CompilerInterface for perform query analysis
 *
 * @template Q as CompilableClause&\Bdf\Prime\Query\Contract\Compilable
 * @implements CompilerInterface<Q>
 */
final class AnalyzerCompilerAdapter implements CompilerInterface
{
    /**
     * @var AnalyzerService
     */
    private $service;

    /**
     * @var CompilerInterface<Q>
     */
    private $compiler;

    /**
     * @var AnalyzerInterface
     */
    private $analyzer;

    /**
     * AnalyzerCompilerAdapter constructor.
     *
     * @param AnalyzerService $service
     * @param CompilerInterface<Q> $compiler
     * @param AnalyzerInterface $analyzer
     */
    public function __construct(AnalyzerService $service, CompilerInterface $compiler, AnalyzerInterface $analyzer)
    {
        $this->service = $service;
        $this->compiler = $compiler;
        $this->analyzer = $analyzer;
    }

    /**
     * {@inheritdoc}
     */
    public function compileInsert(CompilableClause $query)
    {
        $this->analyze($query);

        return $this->compiler->compileInsert($query);
    }

    /**
     * {@inheritdoc}
     */
    public function compileUpdate(CompilableClause $query)
    {
        $this->analyze($query);

        return $this->compiler->compileUpdate($query);
    }

    /**
     * {@inheritdoc}
     */
    public function compileDelete(CompilableClause $query)
    {
        $this->analyze($query);

        return $this->compiler->compileDelete($query);
    }

    /**
     * {@inheritdoc}
     */
    public function compileSelect(CompilableClause $query)
    {
        $this->analyze($query);

        return $this->compiler->compileSelect($query);
    }

    /**
     * {@inheritdoc}
     */
    public function platform(): PlatformInterface
    {
        return $this->compiler->platform();
    }

    /**
     * {@inheritdoc}
     */
    public function quoteIdentifier(CompilableClause $query, $column): string
    {
        return $this->compiler->quoteIdentifier($query, $column);
    }

    /**
     * {@inheritdoc}
     */
    public function getBindings(CompilableClause $query): array
    {
        return $this->compiler->getBindings($query);
    }

    private function analyze(CompilableClause $query): void
    {
        if ($report = $this->service->createReport($this->analyzer->entity($query))) {
            $this->analyzer->analyze($report, $query);
            $this->service->push($report);
        }
    }
}
