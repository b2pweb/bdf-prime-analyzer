<?php

namespace Query;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\Query\LikeWithoutWildcardAnalyzer;
use Bdf\Prime\Query\Expression\Like;

/**
 * Class LikeWithoutWildcardAnalyzerTest
 */
class LikeWithoutWildcardAnalyzerTest extends AnalyzerTestCase
{
    /**
     * @var LikeWithoutWildcardAnalyzer
     */
    private $analyzer;

    protected function setUp()
    {
        parent::setUp();

        $this->testPack->declareEntity(TestEntity::class)->initialize();

        $this->analyzer = new LikeWithoutWildcardAnalyzer();
    }

    /**
     *
     */
    public function test_analyze_without_like()
    {
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('value', 42)));
    }

    /**
     *
     */
    public function test_analyze_with_valid_like()
    {
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('value', ':like', '%j%')));
    }

    /**
     *
     */
    public function test_analyze_with_missing_wildcard()
    {
        $this->assertEquals(['Like without wildcard on field "value".'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('value', ':like', '42')));
        $this->assertEquals(
            ['Like without wildcard on field "value".', 'Like without wildcard on field "key".'],
            $this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where(function ($query) {
            $query
                ->where('value', ':like', '42')
                ->orWhere('key', ':like', 'response')
            ;
        })));
    }

    /**
     *
     */
    public function test_analyze_with_not_string_value()
    {
        $this->assertEquals(['Like without wildcard on field "value".'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('value', ':like', 42)));
        $this->assertEquals(['Like without wildcard on field "value".'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('value', ':like', 4.2)));
        $this->assertEquals(['Like without wildcard on field "value".'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('value', ':like', new \stdClass())));
    }

    /**
     *
     */
    public function test_analyze_with_array_value()
    {
        $this->assertEquals(['Like without wildcard on field "value".'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('value', ':like', ['aaa', 'bbb'])));
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('value', ':like', ['aaa', '%bbb'])));
    }

    /**
     *
     */
    public function test_analyze_with_like_expression()
    {
        $this->assertEquals(['Like without wildcard on field "value".'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('value', new Like('aaa'))));
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->where('value', (new Like('aaa'))->endsWith())));
    }
}
