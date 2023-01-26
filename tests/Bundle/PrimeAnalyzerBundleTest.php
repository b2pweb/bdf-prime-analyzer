<?php

namespace AnalyzerTest\Bundle;

use Bdf\Prime\Analyzer\AnalyzerService;
use Bdf\Prime\Analyzer\Storage\FileReportStorage;
use Bdf\Prime\Analyzer\Storage\Instant\DummyInstantFactory;
use Bdf\Prime\Analyzer\Testing\AnalyzerReportDumper;
use Bdf\Prime\Analyzer\Testing\DumpFormat\ConsoleDumpFormat;
use Bdf\Prime\Analyzer\Testing\DumpFormat\DiffDumpFormat;
use Bdf\Prime\Analyzer\Testing\DumpFormat\HtmlDumpFormat;
use Bdf\Prime\Analyzer\Testing\DumpFormat\StorageDumpFormat;
use Bdf\Prime\Analyzer\Testing\DumpFormat\Trace\HtmlTraceDumpFormat;
use Bdf\Prime\Analyzer\Testing\DumpFormat\Trace\JsonTraceDumpFormat;
use PHPUnit\Framework\TestCase;

class PrimeAnalyzerBundleTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset the dumper
        (new AnalyzerReportDumper([]))->register();
    }

    public function test_instances()
    {
        $kernel = new TestKernel('test', true);
        $kernel->boot();

        $this->assertInstanceOf(AnalyzerService::class, $kernel->getContainer()->get(AnalyzerService::class));
        $this->assertInstanceOf(AnalyzerReportDumper::class, $kernel->getContainer()->get(AnalyzerReportDumper::class));
    }

    public function test_config()
    {
        $kernel = new TestKernel('test', true);
        $kernel->boot();

        $this->assertTrue($kernel->getContainer()->getParameter('prime_analyzer.enabled'));
        $this->assertSame(['ignored/path'], $kernel->getContainer()->getParameter('prime_analyzer.ignored_paths'));
        $this->assertSame(['foo'], $kernel->getContainer()->getParameter('prime_analyzer.ignored_analysis'));
        $this->assertSame(['or'], $kernel->getContainer()->getParameter('prime_analyzer.error_analysis'));
    }

    public function test_report_dumper_formats()
    {
        $kernel = new TestKernel('test', true);
        $kernel->boot();

        /** @var AnalyzerReportDumper $dumper */
        $dumper = $kernel->getContainer()->get(AnalyzerReportDumper::class);

        $r = new \ReflectionProperty($dumper, 'formats');
        $r->setAccessible(true);

        $formats = $r->getValue($dumper);

        $baseDir = realpath(__DIR__.'/../../var') . '/prime_analyzer';

        $this->assertEquals([
            new ConsoleDumpFormat(),
            new HtmlDumpFormat($baseDir . '/dump.html'),
            new JsonTraceDumpFormat($baseDir . '/trace.json'),
            new HtmlTraceDumpFormat($baseDir . '/trace.html'),
            new StorageDumpFormat(
                new FileReportStorage($baseDir . '/'),
                new DummyInstantFactory(),
            ),
            new DiffDumpFormat(
                new FileReportStorage($baseDir . '/'),
                new DummyInstantFactory(),
                [new HtmlDumpFormat($baseDir . '/diff.html')]
            ),
        ], $formats);
    }

    public function test_should_report_during_runtime()
    {
        $kernel = new TestKernel('test', true);
        $kernel->boot();

        try {
            TestEntity::where('not_found', 123)->all();
        } catch (\Exception $e) {
        }

        $this->assertEquals([
            'Query without index. Consider adding an index, or filter on an indexed field.',
            'Use of undeclared attribute "not_found".',
        ], $kernel->getContainer()->get(AnalyzerService::class)->reports()[0]->errors());
    }

    public function test_should_save_report_on_shutdown()
    {
        $kernel = new TestKernel('test', true);
        $kernel->boot();

        try {
            TestEntity::where('not_found', 123)->all();
        } catch (\Exception $e) {
        }

        $kernel->shutdown();

        AnalyzerReportDumper::instance()->dump();

        $this->assertFileExists(__DIR__ . '/../../var/prime_analyzer/dump.html');
        $this->assertFileExists(__DIR__ . '/../../var/prime_analyzer/diff.html');
        $this->assertFileExists(__DIR__ . '/../../var/prime_analyzer/dummy/dummy.report');

        $this->assertStringContainsString('Use of undeclared attribute "not_found".', file_get_contents(__DIR__ . '/../../var/prime_analyzer/dump.html'));
        $this->assertStringContainsString('Use of undeclared attribute "not_found".', file_get_contents(__DIR__ . '/../../var/prime_analyzer/dummy/dummy.report'));
        $this->assertStringContainsString('No prime reports', file_get_contents(__DIR__ . '/../../var/prime_analyzer/diff.html'));
    }

    public function test_error_analysis()
    {
        $this->expectExceptionMessage('Query analysis error: OR not nested on field "name". Consider wrap the condition into a nested where : $query->where(function($query) { ... })');

        $kernel = new TestKernel('test', true);
        $kernel->boot();

        TestEntity::orWhere('name', 'foo')->all();
    }
}
