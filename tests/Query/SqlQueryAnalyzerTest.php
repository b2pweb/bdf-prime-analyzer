<?php

namespace Bdf\Prime\Analyzer\Query;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\RelationEntity;
use AnalyzerTest\TestEntity;
use AnalyzerTest\TestEntityMapper;
use AnalyzerTest\TestEntityOtherConnection;
use Bdf\Prime\Analyzer\AnalysisTypes;
use Bdf\Prime\Analyzer\AnalyzerConfig;
use Bdf\Prime\Analyzer\AnalyzerService;
use Bdf\Prime\Analyzer\Metadata\AnalyzerMetadata;
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
    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzer = new SqlQueryAnalyzer($this->prime, $meta = new AnalyzerMetadata($this->prime));
        $this->service = new AnalyzerService($meta, new AnalyzerConfig(), [Query::class => $this->analyzer]);
        $this->service->configure($this->prime->connection('test'));
        $this->service->addIgnoredAnalysis('optimisation');
        $this->testPack->declareEntity([TestEntity::class, RelationEntity::class, TestEntityWithIgnore::class])->initialize();
    }

    /**
     *
     */
    public function test_entity()
    {
        $this->assertSame(TestEntity::class, $this->analyzer->entity(TestEntity::builder()));
        $this->assertSame(TestEntityOtherConnection::class, $this->analyzer->entity(TestEntityOtherConnection::builder()));
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
        $this->assertEquals(60, $report->line());
        $this->assertEmpty($report->errors());
        $this->assertEmpty($report->errorsTypes());
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
        $this->assertEquals([AnalysisTypes::OR], $report->errorsTypes());
    }

    /**
     *
     */
    public function test_query_without_index()
    {
        TestEntity::repository()->builder()->where('value', 42)->execute();

        $report = $this->service->reports()[0];

        $this->assertEquals(['Query without index. Consider adding an index, or filter on an indexed field.'], $report->errors());
        $this->assertEquals([AnalysisTypes::INDEX], $report->errorsTypes());
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
        $this->assertEquals([AnalysisTypes::NOT_DECLARED], $report->errorsTypes());
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
        $this->assertEquals([AnalysisTypes::SORT], $report->errorsTypes());
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
        $this->assertEquals([AnalysisTypes::RELATION_DISTANT_KEY], $report->errorsTypes());
    }

    /**
     *
     */
    public function test_query_with_ignore()
    {
        TestEntity::repository()->builder()
            ->where('_value', 42)
            ->execute() // @prime-analyzer-ignore not_declared
        ;

        $report = $this->service->reports()[0];

        $this->assertEquals(['Query without index. Consider adding an index, or filter on an indexed field.'], $report->errors());
        $this->assertEquals([AnalysisTypes::INDEX], $report->errorsTypes());
    }

    /**
     *
     */
    public function test_query_with_ignore_by_mapper_method()
    {
        TestEntityWithIgnore::repository()->builder()
            ->where('_value', 42)
            ->execute()
        ;

        $report = $this->service->reports()[0];

        $this->assertEquals(['Query without index. Consider adding an index, or filter on an indexed field.'], $report->errors());
        $this->assertEquals([AnalysisTypes::INDEX], $report->errorsTypes());
    }

    /**
     *
     */
    public function test_query_without_repository()
    {
        $this->prime->connection('test')->from('test_entity')->all();

        $report = $this->service->reports()[0];
        $this->assertEmpty($report->errors());
        $this->assertEmpty($report->errorsTypes());
    }

    /**
     *
     */
    public function test_update_query()
    {
        TestEntity::repository()->where('id', 5)->update(['value' => 42]);

        $this->assertCount(1, $this->service->reports());
        $this->assertEmpty($this->service->reports()[0]->errors());

        TestEntity::repository()->where('_key', 5)->update(['value' => 42]);

        $this->assertCount(2, $this->service->reports());
        $this->assertEquals(['Use of undeclared attribute "_key".'], $this->service->reports()[1]->errors());
        $this->assertEquals([AnalysisTypes::NOT_DECLARED], $this->service->reports()[1]->errorsTypes());
    }

    /**
     *
     */
    public function test_delete_query()
    {
        TestEntity::repository()->where('id', 5)->delete();

        $this->assertCount(1, $this->service->reports());
        $this->assertEmpty($this->service->reports()[0]->errors());

        TestEntity::repository()->where('_key', 5)->delete();

        $this->assertCount(2, $this->service->reports());
        $this->assertEquals(['Use of undeclared attribute "_key".'], $this->service->reports()[1]->errors());
        $this->assertEquals([AnalysisTypes::NOT_DECLARED], $this->service->reports()[1]->errorsTypes());
    }

    /**
     *
     */
    public function test_insert_query()
    {
        TestEntity::repository()->builder()->insert([
            'key' => 'response',
            'value' => 42,
        ]);

        $this->assertCount(1, $this->service->reports());
        $this->assertEmpty($this->service->reports()[0]->errors());

        TestEntity::repository()->builder()->insert([
            '_key' => 'response',
            'value' => 42,
        ]);

        $this->assertCount(2, $this->service->reports());
        $this->assertEquals(['Write on undeclared attribute "_key".'], $this->service->reports()[1]->errors());
        $this->assertEquals([AnalysisTypes::WRITE], $this->service->reports()[1]->errorsTypes());
    }
}

class TestEntityWithIgnore extends TestEntity
{

}

/**
 * @prime-analyzer-ignore not_declared
 */
class TestEntityWithIgnoreMapper extends TestEntityMapper
{
    public function schema(): array
    {
        return ['table' => 'with_ignore'] + parent::schema();
    }
}
