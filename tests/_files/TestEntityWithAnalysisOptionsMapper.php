<?php

namespace AnalyzerTest;

use Bdf\Prime\Analyzer\Metadata\Attribute\AnalysisOptions;

#[AnalysisOptions('foo', ['aaa', 'bbb'])]
#[AnalysisOptions('bar', ['ccc'])]
class TestEntityWithAnalysisOptionsMapper extends TestEntityMapper
{
    public function schema(): array
    {
        return ['table' => 'TestEntityWithAnalysisOptions'] + parent::schema();
    }
}
