<?php

namespace Bdf\Prime\Analyzer\Testing\DumpFormat;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\AnalyzerService;
use Bdf\Prime\Analyzer\Query\SqlQueryAnalyzer;
use Bdf\Prime\Query\Query;

/**
 * Class HtmlDumpFormatTest
 */
class HtmlDumpFormatTest extends AnalyzerTestCase
{
    /**
     * @var string
     */
    private $file;

    /**
     * @var HtmlDumpFormat
     */
    private $format;

    protected function setUp()
    {
        parent::setUp();

        $this->file = tempnam(sys_get_temp_dir(), 'report_');
        $this->format = new HtmlDumpFormat($this->file);
    }

    protected function tearDown()
    {
        parent::tearDown();

        unlink($this->file);
    }

    /**
     *
     */
    public function test_dump_empty()
    {
        $this->format->dump([]);

        $content = file_get_contents($this->file);
        $this->assertContains('Prime analyser report', $content);
        $this->assertContains('No prime reports', $content);
    }

    /**
     *
     */
    public function test_dump()
    {
        $this->testPack->declareEntity([TestEntity::class])->initialize();
        $service = new AnalyzerService([Query::class => new SqlQueryAnalyzer($this->prime)]);
        $service->configure($this->prime->connection('test'));

        TestEntity::all();
        TestEntity::where('_value', 2)->first();

        for ($i = 0; $i < 3; ++$i) {
            TestEntity::where('key', 'response')->all();
        }

        $this->format->dump($service->reports());

        $content = file_get_contents($this->file);
        $this->assertContains('Prime analyser report', $content);
        $this->assertContains('Prime reports (3) :', $content);
        $this->assertContains('DumpFormat/HtmlDumpFormatTest.php:63 on AnalyzerTest\TestEntity (called 1 times)', $content);
        $this->assertContains('Query without index. Consider adding an index, or filter on an indexed field.', $content);
        $this->assertContains('Use of undeclared attribute "_value".', $content);
        $this->assertContains('DumpFormat/HtmlDumpFormatTest.php:66 on AnalyzerTest\TestEntity (called 3 times)', $content);
        $this->assertContains('Suspicious N+1 or loop query', $content);
    }
}
