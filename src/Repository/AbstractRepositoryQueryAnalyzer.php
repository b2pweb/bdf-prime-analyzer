<?php

namespace Bdf\Prime\Analyzer\Repository;

use Bdf\Prime\Analyzer\AnalyzerInterface;
use Bdf\Prime\Analyzer\Report;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Prime\ServiceLocator;

/**
 * Base query analyser type
 */
abstract class AbstractRepositoryQueryAnalyzer implements AnalyzerInterface
{
    /**
     * @var ServiceLocator
     */
    private $serviceLocator;

    /**
     * @var RepositoryQueryErrorAnalyzerInterface[]
     */
    private $analyzers;

    /**
     * SqlQueryAnalyzer constructor.
     *
     * @param ServiceLocator $serviceLocator
     * @param RepositoryQueryErrorAnalyzerInterface[] $analyzers
     */
    public function __construct(ServiceLocator $serviceLocator, array $analyzers = [])
    {
        $this->serviceLocator = $serviceLocator;
        $this->analyzers = $analyzers;
    }

    /**
     * {@inheritdoc}
     */
    final public function analyze(Report $report, CompilableClause $query): void
    {
        if (!$report->entity() || (!$repository = $this->serviceLocator->repository($report->entity()))) {
            return;
        }

        $parameters = method_exists($repository->mapper(), 'primeAnalyzerParameters')
            ? $repository->mapper()->primeAnalyzerParameters()
            : []
        ;

        foreach ($this->analyzers as $analyzer) {
            if ($report->isIgnored($analyzer->type())) {
                continue;
            }

            $analyzerParameters = $parameters[$analyzer->type()] ?? [];

            if ($analyzerParameters === false) {
                continue;
            }

            foreach ($analyzer->analyze($repository, $query, $analyzerParameters) as $error) {
                $report->addError($error);
            }
        }
    }

    /**
     * Resolve a repository by the class name
     * Requires that the repository has been loaded before analysis
     *
     * @param string $tableName The table name
     *
     * @return RepositoryInterface|null
     */
    final protected function repositoryByTableName(string $tableName): ?RepositoryInterface
    {
        foreach ($this->serviceLocator->repositoryNames() as $name) {
            $repository = $this->serviceLocator->repository($name);

            if ($repository->metadata()->table() === $tableName) {
                return $repository;
            }
        }

        return null;
    }
}
