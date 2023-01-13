<?php

namespace Testing\DumpFormat;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\AnalyzerConfig;
use Bdf\Prime\Analyzer\AnalyzerService;
use Bdf\Prime\Analyzer\Metadata\AnalyzerMetadata;
use Bdf\Prime\Analyzer\Query\SqlQueryAnalyzer;
use Bdf\Prime\Analyzer\Report;
use Bdf\Prime\Analyzer\Storage\FileReportStorage;
use Bdf\Prime\Analyzer\Storage\Instant\IncrementInstantFactory;
use Bdf\Prime\Analyzer\Testing\DumpFormat\DiffDumpFormat;
use Bdf\Prime\Analyzer\Testing\DumpFormat\DumpFormatInterface;
use Bdf\Prime\Query\Query;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class DiffDumpFormatTest
 */
class DiffDumpFormatTest extends AnalyzerTestCase
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @var DiffDumpFormat
     */
    private $diff;

    /**
     * @var DumpFormatInterface
     */
    private $innerFormat;

    /**
     * @var FileReportStorage
     */
    private $storage;

    /**
     * @var IncrementInstantFactory
     */
    private $instantFactory;

    /**
     * @var AnalyzerService
     */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = '/tmp/test_diff_dump_'.bin2hex(random_bytes(12));
        $this->clear();

        $this->storage = new FileReportStorage($this->directory);
        $this->instantFactory = new IncrementInstantFactory();

        $this->innerFormat = new class implements DumpFormatInterface {
            public $reports;
            public function dump(array $reports): void { $this->reports = $reports; }
        };

        $this->diff = new DiffDumpFormat(
            $this->storage,
            $this->instantFactory,
            [$this->innerFormat]
        );

        $this->testPack->declareEntity([TestEntity::class])->initialize();
        $this->service = new AnalyzerService($meta = new AnalyzerMetadata($this->prime), new AnalyzerConfig(), [Query::class => new SqlQueryAnalyzer($this->prime, $meta)]);
        $this->service->configure($this->prime->connection('test'));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->clear();
    }

    /**
     *
     */
    public function test_dump()
    {
        $this->query1();

        $this->diff->dump($this->service->reports());
        $this->storage->push($this->instantFactory->next($this->storage), $this->service->reports());

        $this->assertEquals($this->service->reports(), $this->innerFormat->reports);

        $this->query2();

        $this->diff->dump($this->service->reports());
        $this->storage->push($this->instantFactory->next($this->storage), $this->service->reports());

        $this->assertEquals([$this->service->reports()[1]], $this->innerFormat->reports);

        $this->service->reset();
        $this->query1();
        $this->query2();

        $this->diff->dump($this->service->reports());
        $this->assertEmpty($this->innerFormat->reports);
    }

    /**
     *
     */
    public function test_dump_with_root_changed()
    {
        $this->query1();

        $this->diff->dump($this->service->reports());
        $this->storage->push($this->instantFactory->next($this->storage), $this->changeRoot($this->service->reports()));

        $this->assertEquals($this->service->reports(), $this->innerFormat->reports);

        $this->query2();

        $this->diff->dump($this->service->reports());
        $this->storage->push($this->instantFactory->next($this->storage), $this->changeRoot($this->service->reports()));

        $this->assertEquals([$this->service->reports()[1]], $this->innerFormat->reports);

        $this->service->reset();
        $this->query1();
        $this->query2();

        $this->diff->dump($this->service->reports());
        $this->assertEmpty($this->innerFormat->reports);
    }

    /**
     * @param Report[] $reports
     * @throws \ReflectionException
     */
    private function changeRoot(array $reports): array
    {
        $newReports = [];

        $currentRoot = realpath(__DIR__.'/../../..');
        $newRoot = '/test/new/root';

        $r = new \ReflectionClass(Report::class);
        $property = $r->getProperty('stackTrace');
        $property->setAccessible(true);

        foreach ($reports as $report) {
            $newReport = clone $report;
            $stackTrace = $newReport->stackTrace();

            foreach ($stackTrace as &$item) {
                if (isset($item['file'])) {
                    $item['file'] = str_replace($currentRoot, $newRoot, $item['file']);
                }
            }

            $property->setValue($newReport, $stackTrace);
            $newReports[] = $newReport;
        }

        return $newReports;
    }

    private function query1()
    {
        TestEntity::where('_value', 2)->first();
    }

    private function query2()
    {
        for ($i = 0; $i < 3; ++$i) {
            TestEntity::where('key', 'response')->all();
        }
    }

    private function clear(): void
    {
        if (!is_dir($this->directory)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }

        rmdir($this->directory);
    }
}
