<?php

namespace Bdf\Prime\Analyzer\Metadata;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\RelationEntity;
use AnalyzerTest\TestEntity;
use AnalyzerTest\TestEntityWithAnalysisOptions;
use Bdf\Prime\Analyzer\AnalyzerService;
use Bdf\Prime\Analyzer\Metadata\Attribute\AnalysisOptions;
use Bdf\Prime\Analyzer\Metadata\Attribute\IgnoreAnalysis;
use Bdf\Prime\Analyzer\Query\SqlQueryAnalyzer;
use Bdf\Prime\Analyzer\Report;
use Bdf\Prime\Query\Query;

class AnalyzerMetadataTest extends AnalyzerTestCase
{
    private $metadata;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metadata = new AnalyzerMetadata($this->prime);
        $this->service = new AnalyzerService($this->metadata, [Query::class => new SqlQueryAnalyzer($this->prime, $this->metadata)]);
        $this->service->configure($this->prime->connection('test'));
    }

    public function test_without_ignored_analysis_orm()
    {
        try {
            TestEntity::first();
        } catch (\Exception $e) {
        }

        $this->assertSame([], $this->metadata->analysisOptions($this->service->reports()[0]));
        $this->assertSame([], $this->metadata->ignoredAnalysis($this->service->reports()[0]));
    }

    public function test_without_ignored_analysis_dbal()
    {
        try {
            $this->prime->connection('test')->builder()->from('foo')->first();
        } catch (\Exception $e) {
        }

        $this->assertSame([], $this->metadata->analysisOptions($this->service->reports()[0]));
        $this->assertSame([], $this->metadata->ignoredAnalysis($this->service->reports()[0]));
    }

    #[IgnoreAnalysis('foo', 'bar')]
    public function test_with_IgnoreAnalysis_attribute_on_method()
    {
        try {
            TestEntity::first();
        } catch (\Exception $e) {
        }

        $this->assertEquals([
            'foo' => new AnalysisOptions('foo', ignore: true),
            'bar' => new AnalysisOptions('bar', ignore: true),
        ], $this->metadata->analysisOptions($this->service->reports()[0]));
        $this->assertSame(['foo', 'bar'], $this->metadata->ignoredAnalysis($this->service->reports()[0]));
    }

    public function test_with_ignore_on_comment()
    {
        try {
            TestEntity::first(); // @prime-analyzer-ignore aaa bbb
        } catch (\Exception $e) {
        }

        $this->assertEquals([
            'aaa' => new AnalysisOptions('aaa', ignore: true),
            'bbb' => new AnalysisOptions('bbb', ignore: true),
        ], $this->metadata->analysisOptions($this->service->reports()[0]));
        $this->assertSame(['aaa', 'bbb'], $this->metadata->ignoredAnalysis($this->service->reports()[0]));
    }

    public function test_with_analysis_options_on_mapper()
    {
        try {
            TestEntityWithAnalysisOptions::first();
        } catch (\Exception $e) {
        }

        $this->assertEquals([
            'foo' => new AnalysisOptions('foo', ['aaa', 'bbb'], entity: TestEntityWithAnalysisOptions::class),
            'bar' => new AnalysisOptions('bar', ['ccc'], entity: TestEntityWithAnalysisOptions::class),
        ], $this->metadata->analysisOptions($this->service->reports()[0]));
        $this->assertSame([], $this->metadata->ignoredAnalysis($this->service->reports()[0]));
    }

    public function test_with_IgnoreAnalysis_attribute_on_service_class()
    {
        (new MyService())->action();

        $this->assertEquals([
            'foo' => new AnalysisOptions('foo', ignore: true),
        ], $this->metadata->analysisOptions($this->service->reports()[0]));
        $this->assertSame(['foo'], $this->metadata->ignoredAnalysis($this->service->reports()[0]));
    }

    public function test_should_merge_service_class_method_line_and_entity_ignore()
    {
        (new MyService())->actionWithIgnore();

        $this->assertEquals([
            'foo' => new AnalysisOptions('foo', ['aaa', 'bbb'], ignore: true, entity: TestEntityWithAnalysisOptions::class),
            'bar' => new AnalysisOptions('bar', ['ccc'], entity: TestEntityWithAnalysisOptions::class),
            'baz' => new AnalysisOptions('baz', ignore: true),
            'rab' => new AnalysisOptions('rab', ignore: true),
        ], $this->metadata->analysisOptions($this->service->reports()[0]));
        $this->assertSame(['foo', 'baz', 'rab'], $this->metadata->ignoredAnalysis($this->service->reports()[0]));
    }

    #[
        AnalysisOptions('foo', entity: TestEntity::class, ignore: true),
        AnalysisOptions('bar', entity: RelationEntity::class, ignore: true),
        AnalysisOptions('baz', ignore: true),
    ]
    public function test_analysis_options_should_filter_the_entity()
    {
        try {
            TestEntity::first();
        } catch (\Exception $e) {
        }

        $this->assertEquals([
            'foo' => new AnalysisOptions('foo', ignore: true, entity: TestEntity::class),
            'baz' => new AnalysisOptions('baz', ignore: true),
        ], $this->metadata->analysisOptions($this->service->reports()[0]));
        $this->assertSame(['foo', 'baz'], $this->metadata->ignoredAnalysis($this->service->reports()[0]));
    }

    #[
        AnalysisOptions('foo', entity: TestEntity::class, ignore: true),
        AnalysisOptions('bar', entity: RelationEntity::class, ignore: true),
        AnalysisOptions('baz', ignore: true),
    ]
    public function test_analysis_options_should_only_use_global_on_dbal_query()
    {
        try {
            $this->prime->connection('test')->builder()->from('foo')->first();
        } catch (\Exception $e) {
        }

        $this->assertEquals([
            'baz' => new AnalysisOptions('baz', ignore: true),
        ], $this->metadata->analysisOptions($this->service->reports()[0]));
        $this->assertSame(['baz'], $this->metadata->ignoredAnalysis($this->service->reports()[0]));
    }

    #[
        AnalysisOptions('foo', options: ['aqw'], entity: TestEntityWithAnalysisOptions::class),
        AnalysisOptions('bar', options: ['aqw']),
    ]
    public function test_analysis_option_should_be_merged()
    {
        try {
            TestEntityWithAnalysisOptions::first();
        } catch (\Exception $e) {
        }

        $this->assertEquals([
            'foo' => new AnalysisOptions('foo', entity: TestEntityWithAnalysisOptions::class, options: ['aaa', 'bbb', 'aqw']),
            'bar' => new AnalysisOptions('bar', entity: TestEntityWithAnalysisOptions::class, options: ['ccc', 'aqw']),
        ], $this->metadata->analysisOptions($this->service->reports()[0]));
        $this->assertSame([], $this->metadata->ignoredAnalysis($this->service->reports()[0]));
    }

    public function test_analysis_option_from_function()
    {
        call_prime();

        $this->assertEquals([
            'foo' => new AnalysisOptions('foo', ignore: true),
            'bar' => new AnalysisOptions('bar', ignore: true),
        ], $this->metadata->analysisOptions($this->service->reports()[0]));
        $this->assertSame(['foo', 'bar'], $this->metadata->ignoredAnalysis($this->service->reports()[0]));
    }

    public function test_analysisOptionsForEntity()
    {
        $this->assertEquals([], $this->metadata->analysisOptionsForEntity(TestEntity::class));
        $this->assertEquals([
            new AnalysisOptions('foo', ['aaa', 'bbb'], entity: TestEntityWithAnalysisOptions::class),
            new AnalysisOptions('bar', ['ccc'], entity: TestEntityWithAnalysisOptions::class),
        ], $this->metadata->analysisOptionsForEntity(TestEntityWithAnalysisOptions::class));
    }

    public function test_analysisOptionsForMethod()
    {
        $this->assertEquals([], $this->metadata->analysisOptionsForMethod(self::class, 'test_analysisOptionsForMethod'));
        $this->assertEquals([
            new AnalysisOptions('foo', ignore: true),
        ], $this->metadata->analysisOptionsForMethod(MyService::class, 'action'));
        $this->assertEquals([
            new AnalysisOptions('foo', ignore: true),
            new AnalysisOptions('baz', ignore: true),
        ], $this->metadata->analysisOptionsForMethod(MyService::class, 'actionWithIgnore'));
        $this->assertEquals([
            new AnalysisOptions('foo', ignore: true),
            new AnalysisOptions('baz', ignore: true),
        ], $this->metadata->analysisOptionsForMethod(new MyService(), 'actionWithIgnore'));
    }

    public function test_analysis_without_trace()
    {
        $this->assertEquals([], $this->metadata->analysisOptions(new Report(null, false)));
        $this->assertEquals([], $this->metadata->analysisOptions(new Report(TestEntity::class, false)));
        $this->assertEquals([
            'foo' => new AnalysisOptions('foo', ['aaa', 'bbb'], entity: TestEntityWithAnalysisOptions::class),
            'bar' => new AnalysisOptions('bar', ['ccc'], entity: TestEntityWithAnalysisOptions::class),
        ], $this->metadata->analysisOptions(new Report(TestEntityWithAnalysisOptions::class, false)));
    }

}

#[IgnoreAnalysis('foo')]
class MyService
{
    public function action(): void
    {
        try {
            TestEntity::first();
        } catch (\Exception $e) {
        }
    }

    #[IgnoreAnalysis('baz')]
    public function actionWithIgnore(): void
    {
        try {
            TestEntityWithAnalysisOptions::first(); // @prime-analyzer-ignore rab
        } catch (\Exception $e) {
        }
    }
}

#[IgnoreAnalysis('foo', 'bar')]
function call_prime()
{
    try {
        TestEntity::first();
    } catch (\Exception $e) {
    }
}