<?php

namespace Bdf\Prime\Analyzer\Bundle\DependencyInjection\Compiler;

use Bdf\Prime\Analyzer\Testing\AnalyzerReportDumper;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Register dump formats tagged with "prime_analyzer.dump_format" into {@see AnalyzerReportDumper} constructor
 */
class RegisterDumpFormatsPass implements CompilerPassInterface
{
    const TAG = 'prime_analyzer.dump_format';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $formats = [];

        foreach ($container->findTaggedServiceIds(self::TAG) as $id => $tags) {
            $formats[] = new Reference($id);
        }

        $container->getDefinition(AnalyzerReportDumper::class)->replaceArgument(0, $formats);
    }
}
