<?php

namespace Bdf\Prime\Analyzer\Query;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Query\Query;

/**
 * Class QueryOptimisationAnalyser
 */
class QueryOptimisationAnalyserTest extends AnalyzerTestCase
{
    /**
     * @var QueryOptimisationAnalyser
     */
    private $analyzer;

    protected function setUp()
    {
        parent::setUp();

        $this->testPack->declareEntity(TestEntity::class)->initialize();

        $this->analyzer = new QueryOptimisationAnalyser();
    }

    /**
     *
     */
    public function test_analyze_findById()
    {
        $this->assertEquals(['Optimisation: use AnalyzerTest\TestEntity::findById() instead'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('id', 5)));
    }

    /**
     *
     */
    public function test_analyze_keyValue()
    {
        $this->assertEquals(['Optimisation: use AnalyzerTest\TestEntity::keyValue() instead'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('value', 5)));
        $this->assertEquals(['Optimisation: use AnalyzerTest\TestEntity::keyValue() instead'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('value', 5)->where('id', 5)));
        $this->assertEquals(['Optimisation: use AnalyzerTest\TestEntity::keyValue() instead'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('id', 5)->with('relationEntity')));
        $this->assertEquals(['Optimisation: use AnalyzerTest\TestEntity::keyValue() instead'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where(['id' => 5, 'value' => 'foo'])));

        $countQuery = TestEntity::repository()->where('id', 5);
        $countQuery->statements['aggregate'] = ['count', '*'];
        $this->assertEquals(['Optimisation: use AnalyzerTest\TestEntity::keyValue() instead'], $this->analyzer->analyze(TestEntity::repository(), $countQuery));
    }

    /**
     *
     */
    public function test_analyze_ignore()
    {
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('id', 5)->where(function (Query $query) { $query->where('value', 5); })));
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('id', '>', 5)));
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('id', [5, 6])));
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('id', 5)->join('relation', 'key')));
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('id', null)));
    }
}
