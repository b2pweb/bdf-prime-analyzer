<?php

namespace AnalyzerTest;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Builder\IndexBuilder;
use Bdf\Prime\Mapper\Mapper;

class TestEntityMapper extends Mapper
{
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'test_entity',
        ];
    }

    public function buildFields($builder): void
    {
        $builder
            ->bigint('id')->autoincrement()
            ->string('key')->alias('_key')
            ->json('value')->alias('_value')
            ->embedded('embeddedRelation', RelationEntity::class, function (FieldBuilder $builder) {
                $builder->string('key')->alias('embedded_key')->nillable();
            })
        ;
    }

    public function buildIndexes(IndexBuilder $builder): void
    {
        $builder->add()->on('key');
    }

    public function buildRelations($builder): void
    {
        $builder
            ->on('relationEntity')
            ->belongsTo(RelationEntity::class.'::key', 'key')
        ;

        $builder
            ->on('embeddedRelation')
            ->belongsTo(RelationEntity::class.'::key', 'embeddedRelation.key')
        ;
    }
}
