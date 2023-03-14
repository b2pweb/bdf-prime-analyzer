<?php

namespace Testing\DumpFormat;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;
use AnalyzerTest\TestEntityWithAnalysisOptions;
use Bdf\Prime\Analyzer\AnalyzerConfig;
use Bdf\Prime\Analyzer\AnalyzerService;
use Bdf\Prime\Analyzer\Metadata\AnalyzerMetadata;
use Bdf\Prime\Analyzer\Query\SqlQueryAnalyzer;
use Bdf\Prime\Analyzer\Testing\DumpFormat\Trace\Trace;
use Bdf\Prime\Query\Query;

class TraceTest extends AnalyzerTestCase
{
    private $metadata;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metadata = new AnalyzerMetadata($this->prime);
        $this->service = new AnalyzerService($this->metadata, new AnalyzerConfig(), [Query::class => new SqlQueryAnalyzer($this->prime, $this->metadata)]);
        $this->service->configure($this->prime->connection('test'));
    }

    public function test_functional()
    {
        $service = new MyService();
        $service->action();
        $service->action2();

        try {
            $this->prime->connection('test')->from('foo')->all();
        } catch (\Exception $e) {
        }

        $trace = new Trace();

        foreach ($this->service->reports() as $report) {
            $trace->push($report);
        }

        $this->assertSame(4, $trace->calls());
        $this->assertSame([
            TestEntity::class => 1,
            TestEntityWithAnalysisOptions::class => 2,
        ], $trace->callsByEntity());
        $this->assertSame([
            'SELECT t0.* FROM test_entity t0 LIMIT 1',
            'SELECT t0.* FROM TestEntityWithAnalysisOptions t0 LIMIT 1',
            'SELECT * FROM foo',
        ], $trace->queries());

        $this->assertSame('include', $trace->calling()[0]->function());
        $this->assertSame('PHPUnit\TextUI\Command::main', $trace->calling()[0]->calling()[0]->function());

        $currentMethodTrace = $this->searchFunctionRecursive($trace, self::class.'->test_functional');

        $this->assertSame(4, $currentMethodTrace->calls());
        $this->assertSame([
            TestEntity::class => 1,
            TestEntityWithAnalysisOptions::class => 2,
        ], $currentMethodTrace->callsByEntity());
        $this->assertSame([
            'SELECT t0.* FROM test_entity t0 LIMIT 1',
            'SELECT t0.* FROM TestEntityWithAnalysisOptions t0 LIMIT 1',
            'SELECT * FROM foo',
        ], $currentMethodTrace->queries());

        $serviceActionTrace = $this->searchFunctionRecursive($currentMethodTrace, MyService::class.'->action');

        $this->assertSame(2, $serviceActionTrace->calls());
        $this->assertSame([
            TestEntity::class => 1,
            TestEntityWithAnalysisOptions::class => 1,
        ], $serviceActionTrace->callsByEntity());
        $this->assertSame([
            'SELECT t0.* FROM test_entity t0 LIMIT 1',
            'SELECT t0.* FROM TestEntityWithAnalysisOptions t0 LIMIT 1',
        ], $serviceActionTrace->queries());

        $serviceAction2Trace = $this->searchFunctionRecursive($currentMethodTrace, MyService::class.'->action2');

        $this->assertSame(1, $serviceAction2Trace->calls());
        $this->assertSame([
            TestEntityWithAnalysisOptions::class => 1,
        ], $serviceAction2Trace->callsByEntity());
        $this->assertSame([
            'SELECT t0.* FROM TestEntityWithAnalysisOptions t0 LIMIT 1',
        ], $serviceAction2Trace->queries());

        $this->assertEquals(<<<'JSON'
{
    "function": "Testing\\DumpFormat\\TraceTest->test_functional",
    "calls": 4,
    "callsByEntity": {
        "AnalyzerTest\\TestEntity": 1,
        "AnalyzerTest\\TestEntityWithAnalysisOptions": 2
    },
    "queries": [
        "SELECT t0.* FROM test_entity t0 LIMIT 1",
        "SELECT t0.* FROM TestEntityWithAnalysisOptions t0 LIMIT 1",
        "SELECT * FROM foo"
    ],
    "calling": [
        {
            "function": "Testing\\DumpFormat\\MyService->action",
            "calls": 2,
            "callsByEntity": {
                "AnalyzerTest\\TestEntity": 1,
                "AnalyzerTest\\TestEntityWithAnalysisOptions": 1
            },
            "queries": [
                "SELECT t0.* FROM test_entity t0 LIMIT 1",
                "SELECT t0.* FROM TestEntityWithAnalysisOptions t0 LIMIT 1"
            ],
            "calling": [
                {
                    "function": "Bdf\\Prime\\Entity\\Model::__callStatic",
                    "calls": 1,
                    "callsByEntity": {
                        "AnalyzerTest\\TestEntity": 1
                    },
                    "queries": [
                        "SELECT t0.* FROM test_entity t0 LIMIT 1"
                    ],
                    "calling": []
                },
                {
                    "function": "Testing\\DumpFormat\\MyService->action2",
                    "calls": 1,
                    "callsByEntity": {
                        "AnalyzerTest\\TestEntityWithAnalysisOptions": 1
                    },
                    "queries": [
                        "SELECT t0.* FROM TestEntityWithAnalysisOptions t0 LIMIT 1"
                    ],
                    "calling": [
                        {
                            "function": "Bdf\\Prime\\Entity\\Model::__callStatic",
                            "calls": 1,
                            "callsByEntity": {
                                "AnalyzerTest\\TestEntityWithAnalysisOptions": 1
                            },
                            "queries": [
                                "SELECT t0.* FROM TestEntityWithAnalysisOptions t0 LIMIT 1"
                            ],
                            "calling": []
                        }
                    ]
                }
            ]
        },
        {
            "function": "Testing\\DumpFormat\\MyService->action2",
            "calls": 1,
            "callsByEntity": {
                "AnalyzerTest\\TestEntityWithAnalysisOptions": 1
            },
            "queries": [
                "SELECT t0.* FROM TestEntityWithAnalysisOptions t0 LIMIT 1"
            ],
            "calling": [
                {
                    "function": "Bdf\\Prime\\Entity\\Model::__callStatic",
                    "calls": 1,
                    "callsByEntity": {
                        "AnalyzerTest\\TestEntityWithAnalysisOptions": 1
                    },
                    "queries": [
                        "SELECT t0.* FROM TestEntityWithAnalysisOptions t0 LIMIT 1"
                    ],
                    "calling": []
                }
            ]
        },
        {
            "function": "Bdf\\Prime\\Query\\AbstractReadCommand->all",
            "calls": 1,
            "callsByEntity": [],
            "queries": [
                "SELECT * FROM foo"
            ],
            "calling": []
        }
    ]
}
JSON, json_encode($currentMethodTrace, JSON_PRETTY_PRINT)

);
    }

    public function searchFunctionRecursive(Trace $trace, string $function): ?Trace
    {
        if ($trace->function() === $function) {
            return $trace;
        }

        foreach ($trace->calling() as $calling) {
            $result = $this->searchFunctionRecursive($calling, $function);

            if ($result) {
                return $result;
            }
        }

        return null;
    }
}

class MyService
{
    public function action(): void
    {
        try {
            TestEntity::first();
        } catch (\Exception $e) {
        }

        $this->action2();
    }

    public function action2(): void
    {
        try {
            TestEntityWithAnalysisOptions::first();
        } catch (\Exception $e) {
        }
    }
}
