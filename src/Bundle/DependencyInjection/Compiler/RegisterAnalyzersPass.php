<?php

namespace Bdf\Prime\Analyzer\Bundle\DependencyInjection\Compiler;

use Bdf\Prime\Analyzer\AnalyzerService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Register analyzers tagged with "prime_analyzer.analyzer" into {@see AnalyzerService} constructor
 */
final class RegisterAnalyzersPass implements CompilerPassInterface
{
    public const TAG = 'prime_analyzer.query_analyzer';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $analyzers = [];

        foreach ($container->findTaggedServiceIds(self::TAG) as $id => $tags) {
            $analyzers[$tags[0]['query']] = new Reference($id);
        }

        $container->getDefinition(AnalyzerService::class)->replaceArgument(2, $analyzers);
    }
}
