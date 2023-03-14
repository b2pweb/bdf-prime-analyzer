<?php

namespace Bdf\Prime\Analyzer;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\RelationEntity;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\Metadata\AnalyzerMetadata;
use Bdf\Prime\Analyzer\Query\SqlQueryAnalyzer;
use Bdf\Prime\Analyzer\Testing\AnalyzerReportDumper;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use Bdf\Prime\Query\Query;
use RuntimeException;

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

    public static function setUpBeforeClass(): void
    {
        if (!self::$reportDumper) {
            self::$reportDumper = new AnalyzerReportDumper();
            self::$reportDumper->register();
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AnalyzerService($meta = new AnalyzerMetadata($this->prime), new AnalyzerConfig(), [
            Query::class => new SqlQueryAnalyzer($this->prime, $meta),
        ]);

        $this->testPack->declareEntity([TestEntity::class, RelationEntity::class])->initialize();
    }

    protected function tearDown(): void
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

        $this->assertSame($r1, $this->service->push($r1));
        $this->assertSame($r2, $this->service->push($r2));

        $this->assertEquals([$r1, $r2], $this->service->reports());
    }

    /**
     *
     */
    public function test_push_with_analysis_configred_as_error_should_raise_exception()
    {
        $this->service = new AnalyzerService($meta = new AnalyzerMetadata($this->prime), new AnalyzerConfig(errorAnalysis: ['type']), [
            Query::class => new SqlQueryAnalyzer($this->prime, $meta),
        ]);

        $r1 = $this->createReport(__FILE__, 12, ['stack1']);
        $r1->addError('type', 'error 1');
        $r1->addError('type', 'error 2');

        try {
            $this->service->push($r1);
            $this->fail('Should raise exception');
        } catch (RuntimeException $e) {
            $this->assertEquals('Query analysis error: error 1, error 2', $e->getMessage());
        }

        $this->assertEquals([$r1], $this->service->reports());
        $this->assertEquals(['error 1', 'error 2'], $r1->errors());
        $this->assertEquals(['type'], $r1->errorsTypes());
    }

    /**
     *
     */
    public function test_push_with_same_stackTrace_should_merge_reports()
    {
        $r1 = $this->createReport(__FILE__, 12, ['stack1']);
        $r1->addError('type', 'error 1');

        $r2 = $this->createReport(__FILE__, 12, ['stack1']);
        $r2->addError('type', 'error 2');

        $this->assertSame($r1, $this->service->push($r1));
        $this->assertSame($r1, $this->service->push($r2));

        $this->assertEquals([$r1], $this->service->reports());
        $this->assertEquals(['error 1', 'error 2', 'Suspicious N+1 or loop query'], $r1->errors());
        $this->assertEquals(['type', 'n+1'], $r1->errorsTypes());
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
     *
     */
    public function test_analyze()
    {
        $this->assertNull($this->service->analyze(new CompilableClause($this->createMock(PreprocessorInterface::class))));

        $report = $this->service->analyze(TestEntity::where('foo', 'bar'));

        $this->assertEquals(TestEntity::class, $report->entity());
        $this->assertEquals(0, $report->line());
        $this->assertEquals('', $report->file());
        $this->assertEquals(['Query without index. Consider adding an index, or filter on an indexed field.', 'Use of undeclared attribute "foo".'], $report->errors());
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

        $rentity = $r->getProperty('entity');
        $rentity->setAccessible(true);
        $rentity->setValue($report, null);

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
