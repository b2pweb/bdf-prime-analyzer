<?php

namespace Bdf\Prime\Analyzer;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\RelationEntity;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\KeyValueQuery\KeyValueQueryAnalyzer;
use Bdf\Prime\Analyzer\Metadata\AnalyzerMetadata;
use Bdf\Prime\Analyzer\Query\SqlQueryAnalyzer;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;
use Bdf\Prime\Query\Query;

class FunctionalTest extends AnalyzerTestCase
{
    /**
     * @var AnalyzerService
     */
    private $service;
    private AnalyzerConfig $config;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AnalyzerService($meta = new AnalyzerMetadata($this->prime), $this->config = new AnalyzerConfig(), [
            Query::class => new SqlQueryAnalyzer($this->prime, $meta),
            KeyValueQuery::class => new KeyValueQueryAnalyzer($this->prime, $meta),
        ]);

        $this->testPack->declareEntity([TestEntity::class, RelationEntity::class])->initialize();
        $this->service->configure($this->prime->connection('test'));
    }

    /**
     *
     */
    public function test_n_plus_1()
    {
        for ($i = 1; $i < 4; ++$i) {
            TestEntity::get($i);
        }

        $this->assertCount(1, $this->service->reports());

        $report = $this->service->reports()[0];

        $this->assertEquals(3, $report->calls());
        $this->assertContains('Suspicious N+1 or loop query', $report->errors());
        $this->assertEquals(__FILE__, $report->file());
        $this->assertEquals(44, $report->line());
    }

    /**
     *
     */
    public function test_with_on_same_entity_should_not_raise_n_plus_1()
    {
        $this->testPack->pushEntity(new TestEntity(['id' => 2, 'key' => 'response', 'value' => 42]));
        TestEntity::with(['relationEntity', 'embeddedRelation'])->get(2);

        $this->assertCount(2, $this->service->reports());

        $report = $this->service->reports()[1];

        $this->assertEquals(2, $report->calls());
        $this->assertEmpty($report->errors());
        $this->assertEmpty($report->errorsByType());
        $this->assertEquals(__FILE__, $report->file());
        $this->assertEquals(63, $report->line());
    }

    /**
     *
     */
    public function test_addIgnoredAnalysis()
    {
        $this->service->addIgnoredAnalysis('not_declared');

        TestEntity::where('_value', 42)->first();

        $report = $this->service->reports()[0];

        $this->assertEquals(['Query without index. Consider adding an index, or filter on an indexed field.'], $report->errors());
    }

    /**
     *
     */
    public function test_addIgnoredPath()
    {
        $this->service->addIgnoredPath(__DIR__);

        TestEntity::where('_value', 42)->first();

        $this->assertEmpty($this->service->reports());
    }

    /**
     *
     */
    public function test_with_eval_code()
    {
        eval(TestEntity::class."::where('_value', 42)->first();");

        $report = $this->service->reports()[0];

        $this->assertEquals(['Query without index. Consider adding an index, or filter on an indexed field.', 'Use of undeclared attribute "_value".'], $report->errors());
        $this->assertEquals([
            AnalysisTypes::NOT_DECLARED => ['Use of undeclared attribute "_value".' => 'Use of undeclared attribute "_value".'],
            AnalysisTypes::INDEX => ['Query without index. Consider adding an index, or filter on an indexed field.' => 'Query without index. Consider adding an index, or filter on an indexed field.']
        ], $report->errorsByType());
        $this->assertEquals(1, $report->line());
        $this->assertEquals(__FILE__."(107) : eval()'d code", $report->file());
    }

    /**
     *
     */
    public function test_error_analysis()
    {
        $this->expectExceptionMessage('Query analysis error: Use of undeclared attribute "_value".');
        $this->config->addErrorAnalysis('not_declared');

        TestEntity::where('_value', 42)->first();
    }
}
