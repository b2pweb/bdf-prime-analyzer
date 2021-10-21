<?php

namespace Bdf\Prime\Analyzer\BulkInsertQuery;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\RelationEntity;
use AnalyzerTest\TestEntity;
use AnalyzerTest\TestEntityOtherConnection;
use Bdf\Prime\Analyzer\AnalyzerService;
use Bdf\Prime\Analyzer\Report;
use Bdf\Prime\Query\Custom\BulkInsert\BulkInsertQuery;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;

/**
 * Class BulkInsertQueryAnalyzerTest
 */
class BulkInsertQueryAnalyzerTest extends AnalyzerTestCase
{
    /**
     * @var BulkInsertQueryAnalyzer
     */
    private $analyzer;

    /**
     * @var AnalyzerService
     */
    private $service;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzer = new BulkInsertQueryAnalyzer($this->prime);
        $this->service = new AnalyzerService([BulkInsertQuery::class => $this->analyzer]);
        $this->service->configure($this->prime->connection('test'));
        $this->testPack->declareEntity([TestEntity::class, RelationEntity::class])->initialize();
    }

    /**
     *
     */
    public function test_entity()
    {
        $this->assertSame(TestEntity::class, $this->analyzer->entity(TestEntity::queries()->make(BulkInsertQuery::class)));
        $this->assertSame(TestEntityOtherConnection::class, $this->analyzer->entity(TestEntityOtherConnection::queries()->make(BulkInsertQuery::class)));
    }

    /**
     *
     */
    public function test_success()
    {
        (new TestEntity(['key' => 'response', 'value' => 42]))->insert();

        $report = $this->service->reports()[0];

        $this->assertInstanceOf(Report::class, $report);
        $this->assertEquals(__FILE__, $report->file());
        $this->assertEquals(56, $report->line());
        $this->assertEmpty($report->errors());
        $this->assertEquals(1, $report->calls());
        $this->assertEquals(TestEntity::class, $report->entity());
    }

    /**
     *
     */
    public function test_query_with_error()
    {
        TestEntity::repository()->make(BulkInsertQuery::class)->values(['_key' => 'response', '_value' => 42])->execute();

        $report = $this->service->reports()[0];

        $this->assertEquals(['Write on undeclared attribute "_key".', 'Write on undeclared attribute "_value".'], $report->errors());
    }
}
