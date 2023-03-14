<?php

namespace Bdf\Prime\Analyzer;

use BadMethodCallException;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\DeleteCompilerInterface;
use Bdf\Prime\Query\Compiler\InsertCompilerInterface;
use Bdf\Prime\Query\Compiler\QuoteCompilerInterface;
use Bdf\Prime\Query\Compiler\SelectCompilerInterface;
use Bdf\Prime\Query\Compiler\UpdateCompilerInterface;

use Doctrine\DBAL\Statement;

use function is_string;

/**
 * Adapt for CompilerInterface for perform query analysis
 *
 * @template Q as CompilableClause&\Bdf\Prime\Query\Contract\Compilable
 * @implements CompilerInterface<Q>
 * @implements QuoteCompilerInterface<Q>
 */
final class AnalyzerCompilerAdapter implements CompilerInterface, QuoteCompilerInterface
{
    public function __construct(
        private AnalyzerService $service,
        private object $compiler,
        private AnalyzerInterface $analyzer
    ) {
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

        $report = $this->analyze($query);
        $query = $compiler->compileInsert($query);
        $sql = $this->getSql($query);

        if ($report && $sql) {
            $report->addQuery($sql);
        }

        return $query;
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

        $report = $this->analyze($query);
        $query = $compiler->compileUpdate($query);
        $sql = $this->getSql($query);

        if ($report && $sql) {
            $report->addQuery($sql);
        }

        return $query;
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

        $report = $this->analyze($query);
        $query = $compiler->compileDelete($query);
        $sql = $this->getSql($query);

        if ($report && $sql) {
            $report->addQuery($sql);
        }

        return $query;
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

        $report = $this->analyze($query);
        $query = $compiler->compileSelect($query);
        $sql = $this->getSql($query);

        if ($report && $sql) {
            $report->addQuery($sql);
        }

        return $query;
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

    private function analyze(CompilableClause $query): ?Report
    {
        if ($report = $this->service->createReport($this->analyzer->entity($query))) {
            $this->analyzer->analyze($report, $query);
            return $this->service->push($report);
        }

        return null;
    }

    /**
     * Get the SQL query from a compiled statement
     *
     * @param mixed $query
     *
     * @return string|null
     *
     * @psalm-suppress PossiblyInvalidFunctionCall
     * @psalm-suppress InaccessibleProperty
     * @psalm-suppress PossiblyNullFunctionCall
     */
    private function getSql($query): ?string
    {
        if ($query instanceof Statement) {
            $query = (static fn (): string => $query->sql)->bindTo(null, Statement::class)();
        }

        if (is_string($query)) {
            return $query;
        }

        return null;
    }
}
