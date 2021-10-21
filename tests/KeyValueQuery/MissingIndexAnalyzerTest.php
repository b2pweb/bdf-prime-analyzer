<?php

namespace Bdf\Prime\Analyzer\KeyValueQuery;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;

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
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::keyValue()));
    }

    /**
     *
     */
    public function test_query_without_index()
    {
        $this->assertEquals(['Query without index. Consider adding an index, or filter on an indexed field.'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::keyValue('value', 42)));
    }

    /**
     *
     */
    public function test_query_with_index()
    {
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::keyValue(['key' => 'response', 'value' => '42'])));
    }

    /**
     *
     */
    public function test_query_on_primary_key()
    {
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::keyValue('id', 4)));
    }
}
