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
     * @var array
     */
    private $parameters;

    /**
     * PrimeAnalyzerServiceProvider constructor.
     *
     * @param array $parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters + [
            'ignoredPath' => [],
            'ignoredAnalysis' => [],
            'dumpFormats' => null,
            'register' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Application $app)
    {
        $app->set(AnalyzerService::class, function (Application $app) {
            return new AnalyzerService(
                $app->get(AnalyzerInterface::class.'[]'),
                (array) $this->parameters['ignoredPath'],
                (array) $this->parameters['ignoredAnalysis']
            );
        });

        $app->set(AnalyzerInterface::class.'[]', function (Application $app) {
            return [
                Query::class => $app->get(SqlQueryAnalyzer::class),
                KeyValueQuery::class => $app->get(KeyValueQueryAnalyzer::class),
            ];
        });

        $app->set(SqlQueryAnalyzer::class, function (Application $app) {
            return new SqlQueryAnalyzer($app->get(ServiceLocator::class));
        });

        $app->set(KeyValueQueryAnalyzer::class, function (Application $app) {
            return new KeyValueQueryAnalyzer($app->get(ServiceLocator::class));
        });

        $app->set(AnalyzerReportDumper::class, function () {
            return new AnalyzerReportDumper($this->parameters['dumpFormats']);
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

        if ($this->parameters['register'] === true) {
            $app->get(AnalyzerReportDumper::class)->register();
        }
    }
}
