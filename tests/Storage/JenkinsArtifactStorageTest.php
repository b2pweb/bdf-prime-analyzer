<?php

namespace Storage;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\AnalyzerService;
use Bdf\Prime\Analyzer\Query\SqlQueryAnalyzer;
use Bdf\Prime\Analyzer\Storage\Instant\DummyInstant;
use Bdf\Prime\Analyzer\Storage\Instant\DummyInstantFactory;
use Bdf\Prime\Analyzer\Storage\Instant\IncrementInstantFactory;
use Bdf\Prime\Analyzer\Storage\JenkinsArtifactStorage;
use Bdf\Prime\Query\Query;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class JenkinsArtifactStorageTest
 */
class JenkinsArtifactStorageTest extends AnalyzerTestCase
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @var JenkinsArtifactStorage
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

    protected function setUp()
    {
        parent::setUp();

        $this->directory = '/tmp/test_storage';
        $this->clear();

        $this->storage = new JenkinsArtifactStorage($this->directory, 'my_project', 'my_branch', 'my_report', 'foo', 'bar');
        $this->instantFactory = new DummyInstantFactory();

        $this->testPack->declareEntity([TestEntity::class])->initialize();
        $this->service = new AnalyzerService([Query::class => new SqlQueryAnalyzer($this->prime)]);
        $this->service->configure($this->prime->connection('test'));
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->clear();
    }

    /**
     *
     */
    public function test_last_no_report()
    {
        $this->assertNull($this->storage->last($this->instantFactory));
    }

    /**
     *
     */
    public function test_last_success()
    {
        TestEntity::where('_value', 2)->first();
        TestEntity::where('_key', 'foo')->first();

        $file = $this->directory.'/job/my_project/job/my_branch/lastSuccessfulBuild/artifact/my_report';
        mkdir(dirname($file), 0777, true);
        file_put_contents($file, serialize($this->service->reports()));

        $this->assertEquals($this->service->reports(), $this->storage->last($this->instantFactory));
    }

    /**
     *
     */
    public function test_push_unsupported()
    {
        $this->expectException(\BadMethodCallException::class);

        $this->storage->push(new DummyInstant(), []);
    }

    /**
     *
     */
    public function test_get_unsupported()
    {
        $this->expectException(\BadMethodCallException::class);

        $this->storage->get(new DummyInstant());
    }

    /**
     *
     */
    public function test_instants_unsupported()
    {
        $this->expectException(\BadMethodCallException::class);

        $this->storage->instants($this->instantFactory);
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
