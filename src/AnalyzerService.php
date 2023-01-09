<?php

namespace Bdf\Prime\Analyzer;

use Bdf\Collection\HashSet;
use Bdf\Collection\SetInterface;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Factory\DefaultQueryFactory;

use function get_class;
use function str_starts_with;

/**
 * Class AnalyzerService
 */
class AnalyzerService
{
    /**
     * Store all generated reports.
     */
    private SetInterface $reports;

    public function __construct(
        /**
         * Map a query class name to the corresponding analyzer
         *
         * @var array<class-string<\Bdf\Prime\Query\CompilableClause&\Bdf\Prime\Query\Contract\Compilable&\Bdf\Prime\Query\CommandInterface>, AnalyzerInterface>
         */
        private array $analyzerByQuery,

        /**
         * Paths to ignore
         * This path must be relative to the project root
         *
         * @var string[]
         */
        private array $ignoredPath = [],

        /**
         * List of analysis to ignore
         *
         * @var string[]
         *
         * @see AnalysisTypes
         */
        private array $ignoredAnalysis = [],
    ) {
        $this->reports = new HashSet();
    }

    /**
     * Configure the given connection to intercept the executed queries for analysis
     *
     * @param ConnectionInterface $connection
     */
    public function configure(ConnectionInterface $connection): void
    {
        $factory = $connection->factory();

        if (!$factory instanceof DefaultQueryFactory) {
            return;
        }

        foreach ($this->analyzerByQuery as $query => $analyzer) {
            $compiler = $factory->compiler($query);

            if ($compiler instanceof AnalyzerCompilerAdapter) {
                continue;
            }

            /** @psalm-suppress InvalidArgument */
            $factory->register($query, new AnalyzerCompilerAdapter($this, $compiler, $analyzer));
        }
    }

    /**
     * Add a new analysis report
     *
     * @param Report $report
     */
    public function push(Report $report): void
    {
        /** @psalm-suppress MixedAssignment */
        if ($savedReport = $this->reports->lookup($report)->get()) {
            /** @var Report $savedReport */
            $savedReport->merge($report); // @todo N+1 check

            // Ignore N+1 caused by with : they are either false positive or caused by a real N+1 already reported
            if (!$report->isIgnored(AnalysisTypes::N_PLUS_1) && !$report->isWith()) {
                $savedReport->addError('Suspicious N+1 or loop query');
            }
        } else {
            $this->reports->add($report);
        }
    }

    /**
     * Perform analysis on a query
     * Note: the result will not be push()ed into the service
     *
     * @param CompilableClause $query
     *
     * @return Report|null
     */
    public function analyze(CompilableClause $query): ?Report
    {
        $analyzer = $this->analyzerByQuery[get_class($query)] ?? null;

        if (!$analyzer) {
            return null;
        }

        $report = new Report($analyzer->entity($query), false);

        $analyzer->analyze($report, $query);

        return $report;
    }

    /**
     * Get all generated reports
     *
     * @return Report[]
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public function reports(): array
    {
        return $this->reports->toArray();
    }

    /**
     * Add a new path to ignore
     *
     * @param string $path
     */
    public function addIgnoredPath(string $path): void
    {
        $this->ignoredPath[$path] = $path;
    }

    /**
     * Ignore an analysis
     *
     * @param string $analysis
     */
    public function addIgnoredAnalysis(string $analysis): void
    {
        $this->ignoredAnalysis[$analysis] = $analysis;
    }

    /**
     * Create a report for the current query
     *
     * @param class-string|null $entity The related entity
     * @return Report|null
     *
     * @internal
     */
    public function createReport(?string $entity): ?Report
    {
        try {
            $report = new Report($entity);
        } catch (\LogicException $e) {
            // Ignore logic exception : raised when toSql() is called
            return null;
        }

        foreach ($this->ignoredPath as $path) {
            // The path is ignored
            if (str_starts_with($report->file(), $path)) {
                return null;
            }
        }

        foreach ($this->ignoredAnalysis as $analysis) {
            $report->ignore($analysis);
        }

        return $report;
    }

    /**
     * Clear all reports
     */
    public function reset(): void
    {
        $this->reports->clear();
    }
}
