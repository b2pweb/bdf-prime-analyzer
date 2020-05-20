<?php

namespace Bdf\Prime\Analyzer\Query;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\RelationEntity;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\AnalyzerService;
use Bdf\Prime\Analyzer\Report;
use Bdf\Prime\Query\Query;

/**
 * Class SqlQueryAnalyzerTest
 */
class SqlQueryAnalyzerTest extends AnalyzerTestCase
{
    /**
     * @var SqlQueryAnalyzer
     */
    private $analyzer;

    /**
     * @var AnalyzerService
     */
    private $service;

    /**
     *
     */
    protected function setUp()
    {
        parent::setUp();

        $this->analyzer = new SqlQueryAnalyzer($this->prime);
        $this->service = new AnalyzerService([Query::class => $this->analyzer]);
        $this->service->configure($this->prime->connection('test'));
        $this->testPack->declareEntity([TestEntity::class, RelationEntity::class])->initialize();
    }

    /**
     *
     */
    public function test_success()
    {
        TestEntity::get('foo');

        $report = $this->service->reports()[0];

        $this->assertInstanceOf(Report::class, $report);
        $this->assertEquals(__FILE__, $report->file());
        $this->assertEquals(45, $report->line());
        $this->assertEmpty($report->errors());
        $this->assertEquals(1, $report->calls());
        $this->assertEquals(TestEntity::class, $report->entity());
    }

    /**
     *
     */
    public function test_or_not_nested()
    {
        TestEntity::repository()->builder()->orWhere('value', 42)->orWhere('key', 42)->execute();

        $report = $this->service->reports()[0];

        $this->assertEquals(['OR not nested on field "value". Consider wrap the condition into a nested where : $query->where(function($query) { ... })'], $report->errors());
    }

    /**
     *
     */
    public function test_query_without_index()
    {
        TestEntity::repository()->builder()->where('value', 42)->execute();

        $report = $this->service->reports()[0];

        $this->assertEquals(['Query without index. Consider adding an index, or filter on an indexed field.'], $report->errors());
    }

    /**
     *
     */
    public function test_query_with_undeclared_attributes()
    {
        TestEntity::repository()->builder()
            ->where('_value', 42)
            ->where(function ($query) {
                $query
                    ->orWhere('value', 42)
                    ->orWhere('_key', 42)
                ;
            })
            ->execute()
        ;

        $report = $this->service->reports()[0];

        $this->assertEquals(['Use of undeclared attribute "_value".', 'Use of undeclared attribute "_key".'], $report->errors());
    }

    /**
     *
     */
    public function test_query_with_not_indexed_sort()
    {
        TestEntity::repository()->builder()
            ->order('value')
            ->execute()
        ;

        $report = $this->service->reports()[0];

        $this->assertEquals(['Sort without index on field "value". Consider adding an index, or ignore this error if a small set of records is returned.'], $report->errors());
    }

    /**
     *
     */
    public function test_query_with_relation_distant_key()
    {
        TestEntity::repository()->builder()
            ->where('relationEntity.key', 42)
            ->execute()
        ;

        $report = $this->service->reports()[0];

        $this->assertContains('Use of relation distant key "relationEntity.key" which can cause an unnecessary join. Prefer use the local key "key"', $report->errors());
    }

    /**
     *
     */
    public function test_query_with_ignore()
    {
        TestEntity::repository()->builder()
            ->where('_value', 42)
            ->execute() // @analyzer-ignore not_declared
        ;

        $report = $this->service->reports()[0];

        $this->assertEquals(['Query without index. Consider adding an index, or filter on an indexed field.'], $report->errors());
    }

    /**
     *
     */
    public function test_query_with_ignore_by_mapper_method()
    {
        TestEntity::repository()->mapper()->primeAnalyzerParameters['not_declared'] = false;

        TestEntity::repository()->builder()
            ->where('_value', 42)
            ->execute()
        ;

        $report = $this->service->reports()[0];

        $this->assertEquals(['Query without index. Consider adding an index, or filter on an indexed field.'], $report->errors());
        TestEntity::repository()->mapper()->primeAnalyzerParameters = [];
    }
}
