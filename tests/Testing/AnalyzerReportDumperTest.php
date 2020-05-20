<?php

namespace Bdf\Prime\Analyzer\Testing;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\AnalyzerService;
use Bdf\Prime\Analyzer\Query\SqlQueryAnalyzer;
use Bdf\Prime\Query\Query;

/**
 * Class AnalyzerReportDumperTest
 */
class AnalyzerReportDumperTest extends AnalyzerTestCase
{
    /**
     *
     */
    public function test_instance()
    {
        $this->assertInstanceOf(AnalyzerReportDumper::class, AnalyzerReportDumper::instance());
        $this->assertSame(AnalyzerReportDumper::instance(), AnalyzerReportDumper::instance());
    }

    /**
     *
     */
    public function test_register()
    {
        $dumper = new AnalyzerReportDumper();
        $dumper->register();

        $this->assertSame($dumper, AnalyzerReportDumper::instance());
    }

    /**
     *
     */
    public function test_dump_empty()
    {
        $this->expectOutputRegex("/No prime reports/");

        (new AnalyzerReportDumper())->dump();
    }

    /**
     *
     */
    public function test_dump_functional()
    {
        $this->testPack->declareEntity([TestEntity::class])->initialize();
        $service = new AnalyzerService([Query::class => new SqlQueryAnalyzer($this->prime)]);
        $service->configure($this->prime->connection('test'));

        TestEntity::all();
        TestEntity::where('_value', 2)->first();

        for ($i = 0; $i < 3; ++$i) {
            TestEntity::where('key', 'response')->all();
        }

        $dumper = new AnalyzerReportDumper();
        $dumper->push($service->reports());

        $this->expectOutputRegex('#'.preg_quote('Prime reports (2):').'#');
        $this->expectOutputRegex('#'.preg_quote('Testing/AnalyzerReportDumperTest.php:56 on AnalyzerTest\TestEntity (called 1 times)').'#');
        $this->expectOutputRegex('#'.preg_quote('Query without index. Consider adding an index, or filter on an indexed field.').'#');
        $this->expectOutputRegex('#'.preg_quote('Use of undeclared attribute "_value".').'#');
        $this->expectOutputRegex('#'.preg_quote('Testing/AnalyzerReportDumperTest.php:59 on AnalyzerTest\TestEntity (called 3 times)').'#');
        $this->expectOutputRegex('#'.preg_quote('Suspicious N+1 or loop query').'#');

        $dumper->dump();
    }
}
