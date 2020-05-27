<?php

namespace Bdf\Prime\Analyzer\Query;

use Bdf\Prime\Analyzer\Repository\RepositoryQueryErrorAnalyzerInterface;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Query\AbstractReadCommand;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\QueryRepositoryExtension;
use Bdf\Prime\Repository\RepositoryInterface;
use Doctrine\DBAL\Query\Expression\CompositeExpression;

/**
 * Analyse query which can be optimized using key value query or findById()
 */
final class QueryOptimisationAnalyser implements RepositoryQueryErrorAnalyzerInterface
{
    /**
     * @var \ReflectionProperty
     */
    private $extensionProperty;

    /**
     * {@inheritdoc}
     */
    public function analyze(RepositoryInterface $repository, CompilableClause $query, array $parameters = []): array
    {
        // key value query is not supported by the repository
        if ($repository->queries()->keyValue() === null) {
            return [];
        }

        // @todo ignore relation query
        // Ignore unsupported statements
        if ($this->hasStatements($query, ['joins', 'distinct', 'groups', 'having', 'lock', 'orders'])) {
            return [];
        }

        if (!$this->isKeyValueQuery($repository->metadata(), $query)) {
            return [];
        }

        return $this->isPrimaryKeyQuery($repository, $query)
            ? ['Optimisation: use '.$repository->entityClass().'::findById() instead']
            : ['Optimisation: use '.$repository->entityClass().'::keyValue() instead']
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return 'optimisation';
    }

    private function hasStatements(CompilableClause $query, array $statements): bool
    {
        foreach ($statements as $statement) {
            if (!empty($query->statements[$statement])) {
                return true;
            }
        }

        return false;
    }

    private function isKeyValueQuery(Metadata $metadata, CompilableClause $query)
    {
        foreach ($query->statements['where'] as $filter) {
            // Supports only key value filters (ignore nested, or, operators and relations)
            if (
                !isset($filter['column'])
                || $filter['glue'] !== CompositeExpression::TYPE_AND
                || !in_array($filter['operator'], ['=', ':eq'])
                || is_array($filter['value'])
                || !$metadata->attributeExists($filter['column'])
            ) {
                return false;
            }
        }

        return true;
    }

    private function isPrimaryKeyQuery(RepositoryInterface $repository, CompilableClause $query)
    {
        // Ignore aggregate or projection queries (ex: count or inRow)
        if ($this->hasStatements($query, ['aggregate', 'columns'])) {
            return false;
        }

        // Check the columns
        if (count($query->statements['where']) !== count($repository->metadata()->primary['attributes'])) {
            return false;
        }

        foreach ($query->statements['where'] as $filter) {
            if (!in_array($filter['column'], $repository->metadata()->primary['attributes'])) {
                return false;
            }
        }

        if (!$this->extensionProperty) {
            $this->extensionProperty = new \ReflectionProperty(AbstractReadCommand::class, 'extension');
            $this->extensionProperty->setAccessible(true);
        }

        $extension = $this->extensionProperty->getValue($query);

        // The query extension must be empty
        return $extension == new QueryRepositoryExtension($repository);
    }
}
