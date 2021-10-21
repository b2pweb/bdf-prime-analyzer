<?php

namespace Query;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\Query\LikeWithoutWildcardAnalyzer;
use Bdf\Prime\Analyzer\Query\NotIndexedSortAnalyzer;
use Bdf\Prime\Query\Expression\Like;

/**
 * Class NotIndexedSortAnalyzerTest
 */
class NotIndexedSortAnalyzerTest extends AnalyzerTestCase
{
    /**
     * @var NotIndexedSortAnalyzer
     */
    private $analyzer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testPack->declareEntity(TestEntity::class)->initialize();

        $this->analyzer = new NotIndexedSortAnalyzer();
    }

    /**
     *
     */
    public function test_analyze_valid()
    {
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->builder()));
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->builder()->order('key')));
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->builder()->order('id')));
    }

    /**
     *
     */
    public function test_analyze_not_indexed_sort()
    {
        $this->assertEquals(['Sort without index on field "value". Consider adding an index, or ignore this error if a small set of records is returned.'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->order('value')));
    }

    /**
     *
     */
    public function test_analyze_with_ignore()
    {
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::repository()->order('value'), ['value']));
    }
}
