<?php

namespace Bdf\Prime\Analyzer;

use Bdf\Collection\HashSet;
use Bdf\Collection\SetInterface;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Query\Factory\DefaultQueryFactory;

/**
 * Class AnalyzerService
 */
class AnalyzerService
{
    /**
     * @var AnalyzerInterface[]
     */
    private $analyzerByQuery;

    /**
     * @var string[]
     */
    private $ignoredPath;

    /**
     * @var string[]
     */
    private $ignoredAnalysis;

    /**
     * @var Report[]|SetInterface
     */
    private $reports;


    /**
     * AnalyzerService constructor.
     *
     * @param AnalyzerInterface[] $analyzerByQuery
     * @param string[] $ignoredPath
     * @param string[] $ignoredAnalysis
     */
    public function __construct(array $analyzerByQuery, array $ignoredPath = [], array $ignoredAnalysis = [])
    {
        $this->analyzerByQuery = $analyzerByQuery;
        $this->ignoredPath = $ignoredPath;
        $this->ignoredAnalysis = $ignoredAnalysis;
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
        /** @var Report $savedReport */
        if ($savedReport = $this->reports->lookup($report)->get()) {
            $savedReport->merge($report); // @todo N+1 check

            // Ignore N+1 caused by with : they are either false positive or caused by a real N+1 already reported
            if (!$report->isIgnored('n+1') && !$report->isWith()) {
                $savedReport->addError('Suspicious N+1 or loop query');
            }
        } else {
            $this->reports->add($report);
        }
    }

    /**
     * Get all generated reports
     *
     * @return Report[]
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
     * @param string|null $entity The related entity
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
            if (strpos($report->file(), $path) === 0) {
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
