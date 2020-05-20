<?php

namespace Bdf\Prime\Analyzer;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\RelationEntity;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\Query\SqlQueryAnalyzer;
use Bdf\Prime\Analyzer\Testing\AnalyzerReportDumper;
use Bdf\Prime\Query\Query;

/**
 * Class AnalyzerServiceTest
 */
class AnalyzerServiceTest extends AnalyzerTestCase
{
    /**
     * @var AnalyzerReportDumper
     */
    private static $reportDumper;

    /**
     * @var AnalyzerService
     */
    private $service;

    public static function setUpBeforeClass()
    {
        if (!self::$reportDumper) {
            self::$reportDumper = new AnalyzerReportDumper();
            self::$reportDumper->register();
        }
    }

    protected function setUp()
    {
        parent::setUp();

        $this->service = new AnalyzerService([
            Query::class => new SqlQueryAnalyzer($this->prime),
        ]);

        $this->testPack->declareEntity([TestEntity::class, RelationEntity::class])->initialize();
    }

    protected function tearDown()
    {
        self::$reportDumper->push($this->service->reports());
        parent::tearDown();
    }

    /**
     *
     */
    public function test_configure()
    {
        $connection = $this->prime->connection('test');
        $this->service->configure($connection);

        $compiler = $connection->factory()->compiler(Query::class);
        $this->assertInstanceOf(AnalyzerCompilerAdapter::class, $compiler);

        $this->service->configure($connection);
        $this->assertSame($compiler, $connection->factory()->compiler(Query::class));

        for ($i = 0; $i < 5; ++$i) {
            TestEntity::repository()
                ->where('relationEntity.key', '>', 4)
                ->where(function ($query) {
                    $query
                        ->orWhere('value', ':like', 42)
                        ->orWhere('_value', 5);
                })
                ->order('key')
                ->all()
            ;
        }
    }

    /**
     *
     */
    public function test_push_should_add_report()
    {
        $r1 = $this->createReport(__FILE__, 12, ['stack1']);
        $r2 = $this->createReport('otherfile.php', 12, ['stack2']);

        $this->service->push($r1);
        $this->service->push($r2);

        $this->assertEquals([$r1, $r2], $this->service->reports());
    }

    /**
     *
     */
    public function test_push_with_same_stackTrace_should_merge_reports()
    {
        $r1 = $this->createReport(__FILE__, 12, ['stack1']);
        $r1->addError('error 1');

        $r2 = $this->createReport(__FILE__, 12, ['stack1']);
        $r2->addError('error 2');

        $this->service->push($r1);
        $this->service->push($r2);

        $this->assertEquals([$r1], $this->service->reports());
        $this->assertEquals(['error 1', 'error 2', 'Suspicious N+1 or loop query'], $r1->errors());
        $this->assertEquals(2, $r1->calls());
    }

    /**
     *
     */
    public function test_reset()
    {
        $this->service->push($this->createReport(__FILE__, 12, ['stack1']));
        $this->service->push($this->createReport(__FILE__, 12, ['stack2']));

        $this->service->reset();

        $this->assertEmpty($this->service->reports());
    }

    /**
     * @param string $file
     * @param int $line
     * @param array $stackTrace
     * @return Report
     * @throws \ReflectionException
     */
    private function createReport(string $file, int $line, array $stackTrace): Report
    {
        $r = new \ReflectionClass(Report::class);

        $report = $r->newInstanceWithoutConstructor();

        $rfile = $r->getProperty('file');
        $rfile->setAccessible(true);
        $rfile->setValue($report, $file);

        $rline = $r->getProperty('line');
        $rline->setAccessible(true);
        $rline->setValue($report, $line);

        $rstackTrace = $r->getProperty('stackTrace');
        $rstackTrace->setAccessible(true);
        $rstackTrace->setValue($report, $stackTrace);

        return $report;
    }
}
