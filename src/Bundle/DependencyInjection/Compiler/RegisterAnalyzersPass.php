<?php

namespace Bdf\Prime\Analyzer\Bundle\DependencyInjection\Compiler;

use Bdf\Prime\Analyzer\AnalyzerService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 *
 */
final class RegisterAnalyzersPass implements CompilerPassInterface
{
    public const TAG = 'prime_analyzer.query_analyzer';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $analyzers = [];

        foreach ($container->findTaggedServiceIds(self::TAG) as $id => $tags) {
            $analyzers[$tags[0]['query']] = new Reference($id);
        }

        $container->getDefinition(AnalyzerService::class)->replaceArgument(0, $analyzers);
    }
}
