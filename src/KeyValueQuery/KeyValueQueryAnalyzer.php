<?php

namespace Bdf\Prime\Analyzer\KeyValueQuery;

use Bdf\Prime\Analyzer\Repository\AbstractRepositoryQueryAnalyzer;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;

/**
 * Analyzer for a key value query
 *
 * @see KeyValueQuery
 */
final class KeyValueQueryAnalyzer extends AbstractRepositoryQueryAnalyzer
{
    /**
     * {@inheritdoc}
     */
    public function entity(CompilableClause $query): ?string
    {
        return $this->repositoryByTableName($query->statements['table'])->entityClass();
    }
}
