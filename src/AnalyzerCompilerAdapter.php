<?php

namespace Bdf\Prime\Analyzer;

use BadMethodCallException;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\DeleteCompilerInterface;
use Bdf\Prime\Query\Compiler\InsertCompilerInterface;
use Bdf\Prime\Query\Compiler\QuoteCompilerInterface;
use Bdf\Prime\Query\Compiler\SelectCompilerInterface;
use Bdf\Prime\Query\Compiler\UpdateCompilerInterface;

/**
 * Adapt for CompilerInterface for perform query analysis
 *
 * @template Q as CompilableClause&\Bdf\Prime\Query\Contract\Compilable
 * @implements CompilerInterface<Q>
 */
final class AnalyzerCompilerAdapter implements CompilerInterface, QuoteCompilerInterface
{
    /**
     * @var AnalyzerService
     */
    private $service;

    /**
     * @var object
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
     * @param object $compiler
     * @param AnalyzerInterface $analyzer
     */
    public function __construct(AnalyzerService $service, $compiler, AnalyzerInterface $analyzer)
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
        $compiler = $this->compiler;

        if (!$compiler instanceof InsertCompilerInterface) {
            throw new BadMethodCallException();
        }

        $this->analyze($query);

        return $this->compiler->compileInsert($query);
    }

    /**
     * {@inheritdoc}
     */
    public function compileUpdate(CompilableClause $query)
    {
        $compiler = $this->compiler;

        if (!$compiler instanceof UpdateCompilerInterface) {
            throw new BadMethodCallException();
        }

        $this->analyze($query);

        return $this->compiler->compileUpdate($query);
    }

    /**
     * {@inheritdoc}
     */
    public function compileDelete(CompilableClause $query)
    {
        $compiler = $this->compiler;

        if (!$compiler instanceof DeleteCompilerInterface) {
            throw new BadMethodCallException();
        }

        $this->analyze($query);

        return $this->compiler->compileDelete($query);
    }

    /**
     * {@inheritdoc}
     */
    public function compileSelect(CompilableClause $query)
    {
        $compiler = $this->compiler;

        if (!$compiler instanceof SelectCompilerInterface) {
            throw new BadMethodCallException();
        }

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
        $compiler = $this->compiler;

        if (!$compiler instanceof QuoteCompilerInterface) {
            throw new BadMethodCallException();
        }

        return $compiler->quoteIdentifier($query, $column);
    }

    /**
     * {@inheritdoc}
     */
    public function quote($value)
    {
        $compiler = $this->compiler;

        if (!$compiler instanceof QuoteCompilerInterface) {
            throw new BadMethodCallException();
        }

        return $compiler->quote($value);
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
