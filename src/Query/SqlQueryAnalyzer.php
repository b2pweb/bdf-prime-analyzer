<?php

namespace Bdf\Prime\Analyzer\Query;

use Bdf\Prime\Analyzer\Repository\AbstractRepositoryQueryAnalyzer;
use Bdf\Prime\Analyzer\Repository\RepositoryQueryErrorAnalyzerInterface;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Query;
use Bdf\Prime\ServiceLocator;

/**
 * Analyser for simple sql Query
 *
 * @see Query
 * @extends AbstractRepositoryQueryAnalyzer<Query>
 */
final class SqlQueryAnalyzer extends AbstractRepositoryQueryAnalyzer
{
    /**
     * SqlQueryAnalyzer constructor.
     * @param ServiceLocator $serviceLocator
     * @param RepositoryQueryErrorAnalyzerInterface<Query>[]|null $analyzers
     */
    public function __construct(ServiceLocator $serviceLocator, ?array $analyzers = null)
    {
        parent::__construct($serviceLocator, $analyzers ?? [
            new OrNotNestedAnalyzer(), new MissingIndexAnalyzer(), new NotDeclaredAttributesAnalyzer(),
            new NotIndexedSortAnalyzer(), new RelationDistantKeyAnalyzer(), new LikeWithoutWildcardAnalyzer(),
            new QueryOptimisationAnalyser(), new WriteAttributesAnalyzer(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function entity(CompilableClause $query): ?string
    {
        return ($repository = $this->repositoryByTableName($query->connection(), $query->statements['tables'][0]['table'])) ? $repository->entityClass() : null;
    }
}
