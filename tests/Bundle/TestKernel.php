<?php

namespace AnalyzerTest\Bundle;

use Bdf\Prime\Analyzer\Bundle\PrimeAnalyzerBundle;
use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Mapper;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class TestKernel extends \Symfony\Component\HttpKernel\Kernel
{
    use \Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Bdf\PrimeBundle\PrimeBundle(),
            new PrimeAnalyzerBundle(),
        ];
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('index', '/')->controller([$this, 'indexAction']);
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/conf.yaml');
    }

    public function indexAction()
    {
        TestEntity::repository()->schema()->migrate();
        TestEntity::findById(5);

        return new \Symfony\Component\HttpFoundation\Response(<<<HTML
<!DOCTYPE html>
<html>
    <body>Hello World !</body>
</html>
HTML
        );
    }
}
