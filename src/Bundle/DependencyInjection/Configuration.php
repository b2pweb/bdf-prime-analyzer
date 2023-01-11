<?php

namespace Bdf\Prime\Analyzer\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @psalm-suppress PossiblyUndefinedMethod
 */
final class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('prime_analyzer');
        $node = $treeBuilder->getRootNode();
        $node
            ->children()
                ->booleanNode('enabled')->defaultFalse()->end()
                ->arrayNode('ignored_analysis')->defaultValue([])->scalarPrototype()->end()->end()
                ->arrayNode('ignored_paths')->defaultValue(['%kernel.project_dir%/tests'])->scalarPrototype()->end()->end()
                ->append($this->getDumpFormatsNode())
            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * @return NodeDefinition
     */
    private function getDumpFormatsNode(): NodeDefinition
    {
        $root = (new TreeBuilder('dump_formats'))->getRootNode();

        $dumpFormatsNode = $root
            ->arrayPrototype()
            ->beforeNormalization()
            ->ifString()
            ->then(static fn(string $v) => ['type' => $v])
            ->end()
        ;

        $dumpFormatsNode
            ->children()
            ->enumNode('type')->values(['html', 'storage', 'console'])->defaultNull()->end()
            ->booleanNode('diff')->defaultFalse()->end()
            ->scalarNode('html')->defaultNull()->end()
            ->append($this->getStorageDumpNode())
            ->end()
        ;

        return $root;
    }

    private function getStorageDumpNode(): NodeDefinition
    {
        $root = (new TreeBuilder('storage'))->getRootNode();

        $root
            ->children()
                ->enumNode('instant')->values(['dummy', 'time', 'increment'])->defaultValue('time')->end()
                ->scalarNode('dsn')->defaultValue('file://%kernel.project_dir%/var/prime_analyzer/')->end()
            ->end()
        ;

        return $root;
    }
}
