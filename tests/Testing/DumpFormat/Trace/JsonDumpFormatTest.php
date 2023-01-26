<?php

namespace Bdf\Prime\Analyzer\Testing\DumpFormat;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\AnalyzerConfig;
use Bdf\Prime\Analyzer\AnalyzerService;
use Bdf\Prime\Analyzer\Metadata\AnalyzerMetadata;
use Bdf\Prime\Analyzer\Query\SqlQueryAnalyzer;
use Bdf\Prime\Analyzer\Testing\DumpFormat\Trace\HtmlTraceDumpFormat;
use Bdf\Prime\Analyzer\Testing\DumpFormat\Trace\JsonTraceDumpFormat;
use Bdf\Prime\Analyzer\Testing\DumpFormat\Trace\Trace;
use Bdf\Prime\Query\Query;
use Bdf\Util\Arr;

class JsonDumpFormatTest extends AnalyzerTestCase
{
    /**
     * @var string
     */
    private $file;

    /**
     * @var JsonTraceDumpFormat
     */
    private $format;

    protected function setUp(): void
    {
        parent::setUp();

        $this->file = tempnam(sys_get_temp_dir(), 'report_');
        $this->format = new JsonTraceDumpFormat($this->file);
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
        $data = json_decode($content, true);

        $this->assertSame('{main}', $data['function']);
        $this->assertSame(5, $data['calls']);
        $this->assertSame([TestEntity::class => 5], $data['callsByEntity']);

        $currentTrace = $this->searchFunctionRecursive($data, self::class.'->test_dump');

        $this->assertSame([
            'function' => 'Bdf\\Prime\\Analyzer\\Testing\\DumpFormat\\JsonDumpFormatTest->test_dump',
            'calls' => 5,
            'callsByEntity' => ['AnalyzerTest\\TestEntity' => 5],
            'calling' => [
                [
                    'function' => 'Bdf\\Prime\\Entity\\Model::__callStatic',
                    'calls' => 1,
                    'callsByEntity' => ['AnalyzerTest\\TestEntity' => 1],
                    'calling' => [],
                ],
                [
                    'function' => 'Bdf\\Prime\\Query\\AbstractReadCommand->first',
                    'calls' => 1,
                    'callsByEntity' => ['AnalyzerTest\\TestEntity' => 1],
                    'calling' => [],
                ],
                [
                    'function' => 'Bdf\\Prime\\Query\\AbstractReadCommand->all',
                    'calls' => 3,
                    'callsByEntity' => ['AnalyzerTest\\TestEntity' => 3],
                    'calling' => [],
                ],
            ],
        ], $currentTrace);
    }

    public function searchFunctionRecursive(array $trace, string $function): ?array
    {
        if ($trace['function'] === $function) {
            return $trace;
        }

        foreach ($trace['calling'] as $calling) {
            $result = $this->searchFunctionRecursive($calling, $function);

            if ($result) {
                return $result;
            }
        }

        return null;
    }
}
