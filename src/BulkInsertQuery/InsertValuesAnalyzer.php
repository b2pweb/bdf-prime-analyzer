<?php

namespace Bdf\Prime\Analyzer\BulkInsertQuery;

use Bdf\Prime\Analyzer\Repository\AbstractWriteAttributesAnalyzer;
use Bdf\Prime\Analyzer\Repository\RepositoryQueryErrorAnalyzerInterface;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Analyse the insert values on a bulk insert query
 */
final class InsertValuesAnalyzer implements RepositoryQueryErrorAnalyzerInterface
{
    /**
     * {@inheritdoc}
     */
    public function analyze(RepositoryInterface $repository, CompilableClause $query, array $parameters = []): array
    {
        $errors = [];

        foreach ($query->statements['values'] as $values) {
            $analyzer = new class($values) extends AbstractWriteAttributesAnalyzer {
                private $values;
                public function __construct(array $values) { $this->values = $values; }
                protected function values(CompilableClause $query): array { return $this->values; }
            };

            $errors = array_merge($errors, $analyzer->analyze($repository, $query, $parameters));
        }

        return array_unique($errors);
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return 'insert';
    }
}
