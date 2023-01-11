<?php

namespace Bdf\Prime\Analyzer\Bundle;

use Bdf\Prime\Analyzer\AnalyzerService;
use Bdf\Prime\Analyzer\Bundle\DependencyInjection\Compiler\RegisterAnalyzersPass;
use Bdf\Prime\Analyzer\Bundle\DependencyInjection\Compiler\RegisterDumpFormatsPass;
use Bdf\Prime\Analyzer\Testing\AnalyzerReportDumper;
use Bdf\Prime\ServiceLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PrimeAnalyzerBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterAnalyzersPass());
        $container->addCompilerPass(new RegisterDumpFormatsPass());
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        if (!$this->container->getParameter('prime_analyzer.enabled')) {
            return;
        }

        /** @var ServiceLocator $prime */
        $prime = $this->container->get(ServiceLocator::class);
        /** @var AnalyzerService $service */
        $service = $this->container->get(AnalyzerService::class);

        foreach ($prime->connections()->getConnectionNames() as $name) {
            $service->configure($prime->connection($name));
        }

        // Retrieve reports from the previous registered dumper
        $previousReports = AnalyzerReportDumper::instance()->reports();

        /** @var AnalyzerReportDumper $dumper */
        $dumper = $this->container->get(AnalyzerReportDumper::class);
        $dumper->push($previousReports);

        // @todo register parameter ?
        $dumper->register();
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown(): void
    {
        if (!$this->container->getParameter('prime_analyzer.enabled')) {
            return;
        }

        /** @var AnalyzerService $service */
        $service = $this->container->get(AnalyzerService::class);

        /** @psalm-suppress PossiblyNullReference */
        $this->container->get(AnalyzerReportDumper::class)->push($service->reports());
        $service->reset();
    }
}
