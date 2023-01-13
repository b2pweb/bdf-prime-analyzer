<?php

namespace Storage;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\AnalyzerService;
use Bdf\Prime\Analyzer\Metadata\AnalyzerMetadata;
use Bdf\Prime\Analyzer\Query\SqlQueryAnalyzer;
use Bdf\Prime\Analyzer\Storage\FileReportStorage;
use Bdf\Prime\Analyzer\Storage\Instant\IncrementInstant;
use Bdf\Prime\Analyzer\Storage\Instant\IncrementInstantFactory;
use Bdf\Prime\Query\Query;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class FileReportStorageTest
 */
class FileReportStorageTest extends AnalyzerTestCase
{
    /**
     * @var string
     */
    private $directory;

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

        $this->directory = '/tmp/test_storage';
        $this->clear();

        $this->storage = new FileReportStorage($this->directory);
        $this->instantFactory = new IncrementInstantFactory();

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
    public function test_push_get()
    {
        TestEntity::where('_value', 2)->first();
        TestEntity::where('_key', 'foo')->first();

        $instant = new IncrementInstant(3);
        $this->storage->push($instant, $this->service->reports());

        $this->assertEquals($this->service->reports(), $this->storage->get($instant));
        $this->assertFileExists('/tmp/test_storage/increment/3.report');
    }

    /**
     *
     */
    public function test_get_not_found()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Report not found');

        $this->storage->get(new IncrementInstant(1));
    }

    /**
     *
     */
    public function test_instants()
    {
        $instants = [
            new IncrementInstant(3),
            new IncrementInstant(5),
            new IncrementInstant(6),
        ];

        foreach ($instants as $instant) {
            $this->storage->push($instant, []);
        }

        $this->assertEquals($instants, $this->storage->instants($this->instantFactory));
    }

    /**
     *
     */
    public function test_last()
    {
        TestEntity::where('_value', 2)->first();
        $this->storage->push(new IncrementInstant(3), $this->service->reports());

        TestEntity::where('_key', 'foo')->first();
        $this->storage->push(new IncrementInstant(4), $this->service->reports());

        $this->assertEquals($this->service->reports(), $this->storage->last($this->instantFactory));
    }

    /**
     *
     */
    public function test_not_found()
    {
        $this->assertNull($this->storage->last($this->instantFactory));
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
