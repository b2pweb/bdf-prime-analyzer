<?php

namespace Bdf\Prime\Analyzer;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\BulkInsertQuery\BulkInsertQueryAnalyzer;
use Bdf\Prime\Analyzer\KeyValueQuery\KeyValueQueryAnalyzer;
use Bdf\Prime\Analyzer\Query\SqlQueryAnalyzer;
use Bdf\Prime\Analyzer\Testing\AnalyzerReportDumper;
use Bdf\Prime\ServiceLocator;
use Bdf\Web\Application;

/**
 * Class PrimeAnalyzerServiceProviderTest
 */
class PrimeAnalyzerServiceProviderTest extends AnalyzerTestCase
{
    /**
     * @var Application
     */
    private $app;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Application();
        $this->app[ServiceLocator::class] = $this->prime;
        $this->app->register(new PrimeAnalyzerServiceProvider());
    }

    /**
     *
     */
    public function test_instances()
    {
        $this->assertInstanceOf(AnalyzerService::class, $this->app[AnalyzerService::class]);
        $this->assertInstanceOf(SqlQueryAnalyzer::class, $this->app[SqlQueryAnalyzer::class]);
        $this->assertInstanceOf(KeyValueQueryAnalyzer::class, $this->app[KeyValueQueryAnalyzer::class]);
        $this->assertInstanceOf(BulkInsertQueryAnalyzer::class, $this->app[BulkInsertQueryAnalyzer::class]);
        $this->assertInstanceOf(AnalyzerReportDumper::class, $this->app[AnalyzerReportDumper::class]);
    }

    /**
     *
     */
    public function test_boot_should_configure_connections_and_reset()
    {
        $this->app->boot();
        $this->testPack->declareEntity(TestEntity::class)->initialize();

        TestEntity::where('_value', 42)->first();

        $this->assertCount(1, $this->app[AnalyzerService::class]->reports());

        $this->app->reset();
        $this->assertCount(0, $this->app[AnalyzerService::class]->reports());
        $this->assertCount(1, $this->app[AnalyzerReportDumper::class]->reports());
    }
}
