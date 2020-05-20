<?php

namespace Bdf\Prime\Analyzer;

use Bdf\Prime\Analyzer\KeyValueQuery\KeyValueQueryAnalyzer;
use Bdf\Prime\Analyzer\Query\SqlQueryAnalyzer;
use Bdf\Prime\Analyzer\Testing\AnalyzerReportDumper;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;
use Bdf\Prime\Query\Query;
use Bdf\Prime\ServiceLocator;
use Bdf\Web\Application;
use Bdf\Web\Providers\BootableProviderInterface;
use Bdf\Web\Providers\ServiceProviderInterface;

/**
 * Class PrimeAnalyzerServiceProvider
 */
class PrimeAnalyzerServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(Application $app)
    {
        $app->set(AnalyzerService::class, function (Application $app) {
            return new AnalyzerService([
                Query::class => $app->get(SqlQueryAnalyzer::class),
                KeyValueQuery::class => $app->get(KeyValueQueryAnalyzer::class),
            ]);
        });

        $app->set(SqlQueryAnalyzer::class, function (Application $app) {
            return new SqlQueryAnalyzer($app->get(ServiceLocator::class));
        });

        $app->set(KeyValueQueryAnalyzer::class, function (Application $app) {
            return new KeyValueQueryAnalyzer($app->get(ServiceLocator::class));
        });

        $app->set(AnalyzerReportDumper::class, function () {
            return new AnalyzerReportDumper();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        /** @var ServiceLocator $prime */
        $prime = $app->get(ServiceLocator::class);
        /** @var AnalyzerService $service */
        $service = $app->get(AnalyzerService::class);

        foreach ($prime->connections()->allConnectionNames() as $name) {
            $service->configure($prime->connection($name));
        }

        $app->onReset(function (Application $app) {
            $app->get(AnalyzerReportDumper::class)->push($app->get(AnalyzerService::class)->reports());
            $app->get(AnalyzerService::class)->reset();
        });
    }
}
