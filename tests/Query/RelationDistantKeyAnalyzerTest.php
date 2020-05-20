<?php

namespace Query;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\Query\LikeWithoutWildcardAnalyzer;
use Bdf\Prime\Analyzer\Query\RelationDistantKeyAnalyzer;
use Bdf\Prime\Query\Expression\Like;
use Bdf\Prime\Query\QueryInterface;

/**
 * Class RelationDistantKeyAnalyzerTest
 */
class RelationDistantKeyAnalyzerTest extends AnalyzerTestCase
{
    /**
     * @var RelationDistantKeyAnalyzer
     */
    private $analyzer;

    protected function setUp()
    {
        parent::setUp();

        $this->testPack->declareEntity(TestEntity::class)->initialize();

        $this->analyzer = new RelationDistantKeyAnalyzer();
    }

    /**
     *
     */
    public function test_analyze_without_relations()
    {
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->builder()));
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('value', 42)));
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('notFound.relation', 42)));
    }

    /**
     *
     */
    public function test_analyse_with_use_of_relation_local_key()
    {
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('embeddedRelation.key', 'my_key')));
    }

    /**
     *
     */
    public function test_analyse_with_use_relation_attribute_outside_key()
    {
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('relationEntity.label', 'My label')));
    }

    /**
     *
     */
    public function test_analyse_with_use_of_relation_distance_key()
    {
        $this->assertEquals(['Use of relation distant key "relationEntity.key" which can cause an unnecessary join. Prefer use the local key "key"'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('relationEntity.key', 'my_key')));
        $this->assertEquals(['Use of relation distant key "relationEntity.key" which can cause an unnecessary join. Prefer use the local key "key"'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where(function (QueryInterface $query) { $query->where('relationEntity.key', 'my_key')->orWhere('value', 42); })));
    }
}
