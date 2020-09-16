<?php

namespace Bdf\Prime\Analyzer\Repository;

use Bdf\Prime\Analyzer\AnalyzerInterface;
use Bdf\Prime\Analyzer\IgnoreTagParser;
use Bdf\Prime\Analyzer\Report;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Mapper\Mapper;
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
    /**
     * @var ServiceLocator
     */
    private $serviceLocator;

    /**
     * @var RepositoryQueryErrorAnalyzerInterface<T>[]
     */
    private $analyzers;

    /**
     * @var array<string, array<string, string[]|false>>
     */
    private $analyzersParameters = [];

    /**
     * SqlQueryAnalyzer constructor.
     *
     * @param ServiceLocator $serviceLocator
     * @param RepositoryQueryErrorAnalyzerInterface<T>[] $analyzers
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
        /** @var RepositoryInterface $repository */
        if (!$report->entity() || (!$repository = $this->serviceLocator->repository($report->entity()))) {
            return;
        }

        $parameters = $this->analyzersParameters($repository->mapper());

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

    /**
     * Get the analyzer parameters for the given mapper
     *
     * @param Mapper $mapper
     *
     * @return array<string, string[]|false>
     * @throws \ReflectionException
     */
    private function analyzersParameters(Mapper $mapper): array
    {
        if (isset($this->analyzersParameters[$mapper->getEntityClass()])) {
            return $this->analyzersParameters[$mapper->getEntityClass()];
        }

        $reflection = new \ReflectionClass($mapper);
        $docblock = $reflection->getDocComment();
        $parameters = [];

        foreach (IgnoreTagParser::parseDocBlock($docblock) as $tag) {
            $parameters[$tag[0]] = array_slice($tag, 1) ?: false;
        }

        return $this->analyzersParameters[$mapper->getEntityClass()] = $parameters;
    }
}
