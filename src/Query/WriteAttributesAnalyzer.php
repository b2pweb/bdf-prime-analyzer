<?php

namespace Bdf\Prime\Analyzer\Query;

use Bdf\Prime\Analyzer\Repository\AbstractWriteAttributesAnalyzer;
use Bdf\Prime\Query\CompilableClause;

use function is_array;

/**
 * Analyze the values to write (i.e. insert or update)
 *
 * @extends AbstractWriteAttributesAnalyzer<\Bdf\Prime\Query\Query>
 */
final class WriteAttributesAnalyzer extends AbstractWriteAttributesAnalyzer
{
    /**
     * {@inheritdoc}
     */
    protected function values(CompilableClause $query): array
    {
        $values = $query->statements['values']['data'] ?? [];

        return is_array($values) ? $values : [];
    }
}
