<?php

namespace Testing\DumpFormat;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\AnalyzerService;
use Bdf\Prime\Analyzer\Metadata\AnalyzerMetadata;
use Bdf\Prime\Analyzer\Query\SqlQueryAnalyzer;
use Bdf\Prime\Analyzer\Storage\FileReportStorage;
use Bdf\Prime\Analyzer\Storage\Instant\IncrementInstantFactory;
use Bdf\Prime\Analyzer\Testing\DumpFormat\DiffDumpFormat;
use Bdf\Prime\Analyzer\Testing\DumpFormat\StorageDumpFormat;
use Bdf\Prime\Query\Query;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class StorageDumpFormatTest
 */
class StorageDumpFormatTest extends AnalyzerTestCase
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @var DiffDumpFormat
     */
    private $dump;

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

        $this->dump = new StorageDumpFormat($this->storage, $this->instantFactory);

        $this->testPack->declareEntity([TestEntity::class])->initialize();
        $this->service = new AnalyzerService($meta = new AnalyzerMetadata($this->prime), [Query::class => new SqlQueryAnalyzer($this->prime, $meta)]);
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
        TestEntity::where('_value', 2)->first();

        $this->dump->dump($this->service->reports());
        $this->assertEquals($this->service->reports(), $this->storage->last($this->instantFactory));
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
