<?php

namespace Bdf\Prime\Analyzer;

use Bdf\Collection\HashSet;
use Bdf\Collection\SetInterface;
use Bdf\Prime\Analyzer\Metadata\AnalyzerMetadata;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Factory\DefaultQueryFactory;

use RuntimeException;

use function get_class;

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
        private AnalyzerMetadata $metadata,
        private AnalyzerConfig $config,

        /**
         * Map a query class name to the corresponding analyzer
         *
         * @var array<class-string<\Bdf\Prime\Query\CompilableClause&\Bdf\Prime\Query\Contract\Compilable&\Bdf\Prime\Query\CommandInterface>, AnalyzerInterface>
         */
        private array $analyzerByQuery,
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
                $savedReport->addError(AnalysisTypes::N_PLUS_1, 'Suspicious N+1 or loop query');
            }

            $report = $savedReport;
        } else {
            $this->reports->add($report);
        }

        foreach ($report->errorsByType() as $type => $errors) {
            if ($this->config->isErrorAnalysis($type)) {
                throw new RuntimeException('Query analysis error: '.implode(', ', $errors));
            }
        }
    }

    /**
     * Perform analysis on a query
     * Note: the result will not be push()ed into the service
     *
     * @param \Bdf\Prime\Query\CompilableClause&\Bdf\Prime\Query\Contract\Compilable&\Bdf\Prime\Query\CommandInterface $query
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
     * @deprecated Use AnalyzerConfig::addIgnoredPath()
     */
    public function addIgnoredPath(string $path): void
    {
        $this->config->addIgnoredPath($path);
    }

    /**
     * Ignore an analysis
     *
     * @param string $analysis
     * @deprecated Use AnalyzerConfig::addIgnoredAnalysis()
     */
    public function addIgnoredAnalysis(string $analysis): void
    {
        $this->config->addIgnoredAnalysis($analysis);
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

        if ($this->config->isIgnoredPath($report->file())) {
            return null;
        }

        foreach ($this->config->ignoredAnalysis() as $analysis) {
            $report->ignore($analysis);
        }

        foreach ($this->metadata->ignoredAnalysis($report) as $analysis) {
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
