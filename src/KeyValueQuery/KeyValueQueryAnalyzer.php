<?php

namespace Bdf\Prime\Analyzer\KeyValueQuery;

use Bdf\Prime\Analyzer\Metadata\AnalyzerMetadata;
use Bdf\Prime\Analyzer\Repository\AbstractRepositoryQueryAnalyzer;
use Bdf\Prime\Analyzer\Repository\RepositoryQueryErrorAnalyzerInterface;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;
use Bdf\Prime\ServiceLocator;

/**
 * Analyzer for a key value query
 *
 * @see KeyValueQuery
 * @extends AbstractRepositoryQueryAnalyzer<KeyValueQuery>
 */
final class KeyValueQueryAnalyzer extends AbstractRepositoryQueryAnalyzer
{
    /**
     * KeyValueQueryAnalyzer constructor.
     *
     * @param ServiceLocator $serviceLocator
     * @param AnalyzerMetadata $metadata
     * @param RepositoryQueryErrorAnalyzerInterface<KeyValueQuery>[]|null $analyzers
     */
    public function __construct(ServiceLocator $serviceLocator, AnalyzerMetadata $metadata, ?array $analyzers = null)
    {
        parent::__construct($serviceLocator, $metadata, $analyzers ?? [
            new NotDeclaredAttributesAnalyzer(), new MissingIndexAnalyzer(), new UpdateValuesAnalyzer(),
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
