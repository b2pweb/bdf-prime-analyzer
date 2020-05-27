<?php

namespace Bdf\Prime\Analyzer\KeyValueQuery;

use Bdf\Prime\Analyzer\Repository\AbstractRepositoryQueryAnalyzer;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;
use Bdf\Prime\ServiceLocator;

/**
 * Analyzer for a key value query
 *
 * @see KeyValueQuery
 */
final class KeyValueQueryAnalyzer extends AbstractRepositoryQueryAnalyzer
{
    /**
     * KeyValueQueryAnalyzer constructor.
     *
     * @param ServiceLocator $serviceLocator
     * @param array|null $analyzers
     */
    public function __construct(ServiceLocator $serviceLocator, ?array $analyzers = null)
    {
        parent::__construct($serviceLocator, $analyzers ?? [
            new NotDeclaredAttributesAnalyzer(), new MissingIndexAnalyzer(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function entity(CompilableClause $query): ?string
    {
        return ($repository = $this->repositoryByTableName($query->statements['table'])) ? $repository->entityClass() : null;
    }
}
