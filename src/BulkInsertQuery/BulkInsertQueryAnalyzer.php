<?php

namespace Bdf\Prime\Analyzer\BulkInsertQuery;

use Bdf\Prime\Analyzer\Repository\AbstractRepositoryQueryAnalyzer;
use Bdf\Prime\Analyzer\Repository\RepositoryQueryErrorAnalyzerInterface;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Custom\BulkInsert\BulkInsertQuery;
use Bdf\Prime\ServiceLocator;

/**
 * Analyzer for BulkInsertQuery
 *
 * @see BulkInsertQuery
 * @extends AbstractRepositoryQueryAnalyzer<BulkInsertQuery>
 */
final class BulkInsertQueryAnalyzer extends AbstractRepositoryQueryAnalyzer
{
    /**
     * BulkInsertQueryAnalyzer constructor.
     *
     * @param ServiceLocator $serviceLocator
     * @param RepositoryQueryErrorAnalyzerInterface<BulkInsertQuery>[]|null $analyzers
     */
    public function __construct(ServiceLocator $serviceLocator, ?array $analyzers = null)
    {
        parent::__construct($serviceLocator, $analyzers ?? [
            new InsertValuesAnalyzer(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function entity(CompilableClause $query): ?string
    {
        return ($repository = $this->repositoryByTableName($query->connection(), $query->statements['table'])) ? $repository->entityClass() : null;
    }
}
