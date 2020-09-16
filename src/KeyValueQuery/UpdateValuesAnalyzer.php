<?php

namespace Bdf\Prime\Analyzer\KeyValueQuery;

use Bdf\Prime\Analyzer\Repository\AbstractWriteAttributesAnalyzer;
use Bdf\Prime\Query\CompilableClause;

/**
 * Analyze the values of update query
 *
 * @extends AbstractWriteAttributesAnalyzer<\Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery>
 */
final class UpdateValuesAnalyzer extends AbstractWriteAttributesAnalyzer
{
    /**
     * {@inheritdoc}
     */
    protected function values(CompilableClause $query): array
    {
        return $query->statements['values']['data'] ?? [];
    }
}
