<?php


namespace AnalyzerTest;

use Bdf\Prime\Mapper\Mapper;

/**
 * Class RelationEntityMapper
 */
class RelationEntityMapper extends Mapper
{
    /**
     * @return array|void|null
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'relation_entity',
        ];
    }

    public function buildFields($builder): void
    {
        $builder
            ->string('key')->primary()
            ->string('label')
        ;
    }
}
