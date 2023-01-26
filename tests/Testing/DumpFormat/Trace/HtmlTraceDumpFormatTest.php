<?php

namespace Bdf\Prime\Analyzer\Testing\DumpFormat;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\AnalyzerConfig;
use Bdf\Prime\Analyzer\AnalyzerService;
use Bdf\Prime\Analyzer\Metadata\AnalyzerMetadata;
use Bdf\Prime\Analyzer\Query\SqlQueryAnalyzer;
use Bdf\Prime\Analyzer\Testing\DumpFormat\Trace\HtmlTraceDumpFormat;
use Bdf\Prime\Query\Query;

class HtmlTraceDumpFormatTest extends AnalyzerTestCase
{
    /**
     * @var string
     */
    private $file;

    /**
     * @var HtmlTraceDumpFormat()
     */
    private $format;

    protected function setUp(): void
    {
        parent::setUp();

        $this->file = tempnam(sys_get_temp_dir(), 'report_');
        $this->format = new HtmlTraceDumpFormat($this->file);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unlink($this->file);
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
        $this->assertStringContainsString('HtmlTraceDumpFormatTest-&gt;test_dump', strip_tags($content));
        $this->assertStringContainsString('TestEntity (x5)', strip_tags($content));
    }
}
