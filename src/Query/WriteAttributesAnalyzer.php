<?php

namespace Bdf\Prime\Analyzer\Query;

use Bdf\Prime\Analyzer\Repository\AbstractWriteAttributesAnalyzer;
use Bdf\Prime\Query\CompilableClause;

/**
 * Analyze the values to write (i.e. insert or update)
 */
final class WriteAttributesAnalyzer extends AbstractWriteAttributesAnalyzer
{
    /**
     * {@inheritdoc}
     */
    protected function values(CompilableClause $query): array
    {
        return $query->statements['values']['data'] ?? [];
    }
}
