<?php

namespace Bdf\Prime\Analyzer\KeyValueQuery;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\RelationEntity;
use AnalyzerTest\TestEntity;
use AnalyzerTest\TestEntityOtherConnection;
use Bdf\Prime\Analyzer\AnalyzerService;
use Bdf\Prime\Analyzer\Metadata\AnalyzerMetadata;
use Bdf\Prime\Analyzer\Report;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;

/**
 * Class SqlQueryAnalyzerTest
 */
class KeyValueQueryAnalyzerTest extends AnalyzerTestCase
{
    /**
     * @var KeyValueQueryAnalyzer
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

        $this->analyzer = new KeyValueQueryAnalyzer($this->prime, $meta = new AnalyzerMetadata($this->prime));
        $this->service = new AnalyzerService($meta, [KeyValueQuery::class => $this->analyzer]);
        $this->service->configure($this->prime->connection('test'));
        $this->testPack->declareEntity([TestEntity::class, RelationEntity::class])->initialize();
    }

    /**
     *
     */
    public function test_entity()
    {
        $this->assertSame(TestEntity::class, $this->analyzer->entity(TestEntity::keyValue()));
        $this->assertSame(TestEntityOtherConnection::class, $this->analyzer->entity(TestEntityOtherConnection::keyValue()));
    }

    /**
     *
     */
    public function test_success()
    {
        TestEntity::findById(5);

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
    public function test_query_with_undeclared_attributes()
    {
        TestEntity::repository()->keyValue()
            ->where('_value', 42)
            ->where('_key', 42)
            ->execute()
        ;

        $report = $this->service->reports()[0];

        $this->assertEquals(['Use of undeclared attribute "_value".', 'Use of undeclared attribute "_key".'], $report->errors());
    }

    /**
     *
     */
    public function test_query_without_repository()
    {
        $this->prime->connection('test')->make(KeyValueQuery::class)->from('test_entity')->all();

        $report = $this->service->reports()[0];
        $this->assertEmpty($report->errors());
    }
}
