<?php

namespace Bdf\Prime\Analyzer\Query;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Query\QueryInterface;

/**
 * Class RecursiveClauseIteratorTest
 */
class RecursiveClauseIteratorTest extends AnalyzerTestCase
{
    /**
     *
     */
    public function test_stream()
    {
        $query = TestEntity::builder()
            ->where(function (QueryInterface $query) {
                $query
                    ->where('key', 'response')
                    ->orWhere('value', 42)
                ;
            })
            ->where('relationEntity.label', 'label')
        ;

        $this->assertEquals([
            [
                'column' => 'key',
                'operator' => '=',
                'value' => 'response',
                'glue' => 'AND',
            ],
            [
                'column' => 'value',
                'operator' => '=',
                'value' => 42,
                'glue' => 'OR',
            ],
            [
                'column' => 'relationEntity.label',
                'operator' => '=',
                'value' => 'label',
                'glue' => 'AND',
            ]
        ], RecursiveClauseIterator::where($query)->stream()->toArray(false));
    }
}
