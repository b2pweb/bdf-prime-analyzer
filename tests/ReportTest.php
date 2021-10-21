<?php

namespace Bdf\Prime\Analyzer;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\RelationEntity;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\Query\SqlQueryAnalyzer;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;
use Bdf\Prime\Query\Query;

/**
 * Class ReportTest
 */
class ReportTest extends AnalyzerTestCase
{
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

        $this->service = new AnalyzerService([
            Query::class => new SqlQueryAnalyzer($this->prime, []),
            KeyValueQuery::class => new class implements AnalyzerInterface {
                public function entity(CompilableClause $query): ?string { return null; }
                public function analyze(Report $report, CompilableClause $query): void {}
            },
        ]);
        $this->service->configure($this->prime->connection('test'));
        $this->testPack->declareEntity([TestEntity::class, RelationEntity::class])->initialize();
    }

    /**
     *
     */
    public function test_simple_query()
    {
        TestEntity::all();

        $report = $this->service->reports()[0];

        $this->assertEquals(46, $report->line());
        $this->assertEquals(__FILE__, $report->file());
        $this->assertEquals('__callStatic', $report->stackTrace()[0]['function']);
        $this->assertEquals('test_simple_query', $report->stackTrace()[1]['function']);
        $this->assertFalse($report->isLoad());
    }

    /**
     *
     */
    public function test_load_query()
    {
        $entity = new TestEntity(['key' => 'response', 'value' => 42]);
        $entity->load('relationEntity');

        $report = $this->service->reports()[0];

        $this->assertEquals(63, $report->line());
        $this->assertEquals(__FILE__, $report->file());
        $this->assertTrue($report->isLoad());
        $this->assertFalse($report->isWith());
    }

    /**
     *
     */
    public function test_with_query()
    {
        $entity = new TestEntity(['key' => 'response', 'value' => 42]);
        $entity->insert();

        TestEntity::with('relationEntity')->all();

        $report = $this->service->reports()[1];

        $this->assertEquals(81, $report->line());
        $this->assertEquals(__FILE__, $report->file());
        $this->assertTrue($report->isLoad());
        $this->assertTrue($report->isWith());
    }

    /**
     *
     */
    public function test_ignore_tag()
    {
        TestEntity::all(); // @prime-analyzer-ignore aaa bbb

        $report = $this->service->reports()[0];

        $this->assertTrue($report->isIgnored('aaa'));
        $this->assertTrue($report->isIgnored('bbb'));
        $this->assertFalse($report->isIgnored('ccc'));
    }
}
