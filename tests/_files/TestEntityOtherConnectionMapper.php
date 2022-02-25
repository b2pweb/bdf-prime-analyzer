<?php

namespace AnalyzerTest;

use Bdf\Prime\Mapper\Mapper;

class TestEntityOtherConnectionMapper extends Mapper
{
    public function schema(): array
    {
        return [
            'connection' => 'other',
            'table' => 'test_entity',
        ];
    }

    public function buildFields($builder): void
    {
        $builder
            ->bigint('id')->autoincrement()
            ->string('value')
        ;
    }
}
