<?php

namespace Query;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\Query\MissingIndexAnalyzer;

/**
 * Class MissingIndexAnalyzerTest
 */
class MissingIndexAnalyzerTest extends AnalyzerTestCase
{
    /**
     * @var MissingIndexAnalyzer
     */
    private $analyzer;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzer = new MissingIndexAnalyzer();
    }

    /**
     *
     */
    public function test_empty_query()
    {
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::builder()));
    }

    /**
     *
     */
    public function test_query_without_index()
    {
        $this->assertEquals(['Query without index. Consider adding an index, or filter on an indexed field.'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::where('value', 42)));
        $this->assertEquals(['Query without index. Consider adding an index, or filter on an indexed field.'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::where('relationEntity.label', 'label')));
    }

    /**
     *
     */
    public function test_query_with_index()
    {
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::where('key', 'response')));
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::where('relationEntity.key', 'response')));
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::where(function ($query) {
            $query->orWhere('value', 42)->orWhere('key', 'response');
        })));
    }

    /**
     *
     */
    public function test_query_on_primary_key()
    {
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::where('id', 4)));
    }
}
