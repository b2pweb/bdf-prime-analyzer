<?php

namespace Bdf\Prime\Analyzer\Testing\DumpFormat;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\AnalyzerConfig;
use Bdf\Prime\Analyzer\AnalyzerService;
use Bdf\Prime\Analyzer\Metadata\AnalyzerMetadata;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->file = tempnam(sys_get_temp_dir(), 'report_');
        $this->format = new HtmlDumpFormat($this->file);
    }

    protected function tearDown(): void
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
        $this->assertStringContainsString('Prime analyser report', $content);
        $this->assertStringContainsString('No prime reports', $content);
    }

    /**
     *
     */
    public function test_dump()
    {
        $this->testPack->declareEntity([TestEntity::class])->initialize();
        $service = new AnalyzerService($meta = new AnalyzerMetadata($this->prime), new AnalyzerConfig(), [Query::class => new SqlQueryAnalyzer($this->prime, $meta)]);
        $service->configure($this->prime->connection('test'));

        TestEntity::all();
        TestEntity::where('_value', 2)->first();

        for ($i = 0; $i < 3; ++$i) {
            TestEntity::where('key', 'response')->all();
        }

        $this->format->dump($service->reports());

        $content = file_get_contents($this->file);
        $this->assertStringContainsString('Prime analyser report', $content);
        $this->assertStringContainsString('Prime reports (3) :', $content);
        $this->assertStringContainsString('DumpFormat/HtmlDumpFormatTest.php:65 on AnalyzerTest\TestEntity (called 1 times)', $content);
        $this->assertStringContainsString('Query without index. Consider adding an index, or filter on an indexed field.', $content);
        $this->assertStringContainsString('Use of undeclared attribute "_value".', $content);
        $this->assertStringContainsString('DumpFormat/HtmlDumpFormatTest.php:68 on AnalyzerTest\TestEntity (called 3 times)', $content);
        $this->assertStringContainsString('Suspicious N+1 or loop query', $content);
    }
}
