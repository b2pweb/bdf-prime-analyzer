<?php

namespace Query;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\Query\LikeWithoutWildcardAnalyzer;
use Bdf\Prime\Analyzer\Query\OrNotNestedAnalyzer;
use Bdf\Prime\Query\Expression\Like;
use Bdf\Prime\Query\QueryInterface;

/**
 * Class OrNotNestedAnalyzerTest
 */
class OrNotNestedAnalyzerTest extends AnalyzerTestCase
{
    /**
     * @var OrNotNestedAnalyzer
     */
    private $analyzer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testPack->declareEntity(TestEntity::class)->initialize();

        $this->analyzer = new OrNotNestedAnalyzer();
    }

    /**
     *
     */
    public function test_analyze_success()
    {
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->builder()));
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('value', 42)));
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where(function (QueryInterface $query) { $query->orWhere('value', '42')->orWhere('key', 'response'); })));
    }

    /**
     *
     */
    public function test_analyze_error()
    {
        $this->assertEquals(['OR not nested on field "key". Consider wrap the condition into a nested where : $query->where(function($query) { ... })'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('value', 42)->orWhere('key', 'response')));
    }
}
