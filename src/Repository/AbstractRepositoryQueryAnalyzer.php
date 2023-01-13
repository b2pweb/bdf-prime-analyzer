<?php

namespace Bdf\Prime\Analyzer\Repository;

use Bdf\Prime\Analyzer\AnalyzerInterface;
use Bdf\Prime\Analyzer\Metadata\AnalyzerMetadata;
use Bdf\Prime\Analyzer\Report;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Prime\ServiceLocator;

/**
 * Base query analyser type
 *
 * @template T of CompilableClause
 * @implements AnalyzerInterface<T>
 */
abstract class AbstractRepositoryQueryAnalyzer implements AnalyzerInterface
{
    public function __construct(
        private ServiceLocator $serviceLocator,
        private AnalyzerMetadata $metadata,

        /**
         * List of analyzers to apply
         *
         * @var array<RepositoryQueryErrorAnalyzerInterface<T>>
         */
        private array $analyzers = []
    ) {
    }

    /**
     * {@inheritdoc}
     */
    final public function analyze(Report $report, CompilableClause $query): void
    {
        /** @var RepositoryInterface $repository */
        if (!$report->entity() || (!$repository = $this->serviceLocator->repository($report->entity()))) {
            return;
        }

        $parameters = $this->metadata->analysisOptions($report);

        foreach ($this->analyzers as $analyzer) {
            if ($report->isIgnored($analyzer->type())) {
                continue;
            }

            $analyzerParameters = $parameters[$analyzer->type()] ?? null;

            if ($analyzerParameters && $analyzerParameters->ignore()) {
                continue;
            }

            foreach ($analyzer->analyze($repository, $query, $analyzerParameters?->options() ?? []) as $error) {
                $report->addError($error);
            }
        }
    }

    /**
     * Resolve a repository by the class name
     * Requires that the repository has been loaded before analysis
     *
     * @param ConnectionInterface $connection The used connection
     * @param string $tableName The table name
     *
     * @return RepositoryInterface|null
     */
    final protected function repositoryByTableName(ConnectionInterface $connection, string $tableName): ?RepositoryInterface
    {
        foreach ($this->serviceLocator->repositoryNames() as $name) {
            $repository = $this->serviceLocator->repository($name);

            if ($repository->metadata()->connection() === $connection->getName() && $repository->metadata()->table() === $tableName) {
                return $repository;
            }
        }

        return null;
    }
}
