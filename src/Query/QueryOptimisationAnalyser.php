<?php

namespace Bdf\Prime\Analyzer\Query;

use Bdf\Prime\Analyzer\AnalysisTypes;
use Bdf\Prime\Analyzer\Repository\RepositoryQueryErrorAnalyzerInterface;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Query\AbstractReadCommand;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Contract\Compilable;
use Bdf\Prime\Query\QueryRepositoryExtension;
use Bdf\Prime\Repository\RepositoryInterface;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use ReflectionProperty;

/**
 * Analyse query which can be optimized using key value query or findById()
 *
 * @implements RepositoryQueryErrorAnalyzerInterface<\Bdf\Prime\Query\Query>
 */
final class QueryOptimisationAnalyser implements RepositoryQueryErrorAnalyzerInterface
{
    /**
     * @var ReflectionProperty|null
     */
    private $extensionProperty = null;

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
        if ($query->type() !== Compilable::TYPE_SELECT || $this->hasStatements($query, ['joins', 'distinct', 'groups', 'having', 'lock', 'orders'])) {
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
        return AnalysisTypes::OPTIMISATION;
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

    private function isKeyValueQuery(Metadata $metadata, CompilableClause $query): bool
    {
        foreach ($this->getStatements($query) as $filter) {
            // Supports only key value filters (ignore nested, or, operators and relations)
            if (
                !isset($filter['column'])
                || $filter['glue'] !== CompositeExpression::TYPE_AND
                || !in_array($filter['operator'], ['=', ':eq'])
                || is_array($filter['value'])
                || $filter['value'] === null
                || !$metadata->attributeExists($filter['column'])
            ) {
                return false;
            }
        }

        return true;
    }

    private function isPrimaryKeyQuery(RepositoryInterface $repository, CompilableClause $query): bool
    {
        // Ignore aggregate or projection queries (ex: count or inRow)
        if ($this->hasStatements($query, ['aggregate', 'columns'])) {
            return false;
        }

        $statements = $this->getStatements($query);

        // Check the columns
        if (count($statements) !== count($repository->metadata()->primary['attributes'])) {
            return false;
        }

        foreach ($statements as $filter) {
            if (!in_array($filter['column'], $repository->metadata()->primary['attributes'])) {
                return false;
            }
        }

        if (!$this->extensionProperty) {
            $this->extensionProperty = new ReflectionProperty(AbstractReadCommand::class, 'extension');
            $this->extensionProperty->setAccessible(true);
        }

        /** @var object $extension */
        $extension = $this->extensionProperty->getValue($query);

        // The query extension must be empty
        return $extension == new QueryRepositoryExtension($repository);
    }

    /**
     * Get the where statements
     * This method handle query with a single nested statement (i.e. when call where() with an array)
     *
     * @param CompilableClause $query
     * @return array
     */
    private function getStatements(CompilableClause $query): array
    {
        return count($query->statements['where']) === 1 && isset($query->statements['where'][0]['nested'])
            ? $query->statements['where'][0]['nested']
            : $query->statements['where']
        ;
    }
}
