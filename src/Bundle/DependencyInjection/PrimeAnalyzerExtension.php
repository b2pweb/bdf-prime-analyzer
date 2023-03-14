<?php

namespace Bdf\Prime\Analyzer\Bundle\DependencyInjection;

use Bdf\Dsn\Dsn;
use Bdf\Prime\Analyzer\Bundle\DependencyInjection\Compiler\RegisterDumpFormatsPass;
use Bdf\Prime\Analyzer\Storage\FileReportStorage;
use Bdf\Prime\Analyzer\Testing\DumpFormat\ConsoleDumpFormat;
use Bdf\Prime\Analyzer\Testing\DumpFormat\DiffDumpFormat;
use Bdf\Prime\Analyzer\Testing\DumpFormat\HtmlDumpFormat;
use Bdf\Prime\Analyzer\Testing\DumpFormat\StorageDumpFormat;
use Bdf\Prime\Analyzer\Testing\DumpFormat\Trace\HtmlTraceDumpFormat;
use Bdf\Prime\Analyzer\Testing\DumpFormat\Trace\JsonTraceDumpFormat;
use InvalidArgumentException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class PrimeAnalyzerExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var Configuration $configuration */
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('prime_analyzer.yaml');

        $container->setParameter('prime_analyzer.enabled', $config['enabled']);
        $container->setParameter('prime_analyzer.ignored_analysis', (array) $config['ignored_analysis']);
        $container->setParameter('prime_analyzer.ignored_paths', (array) $config['ignored_paths']);
        $container->setParameter('prime_analyzer.error_analysis', (array) $config['error_analysis']);

        $this->configureDumpFormats($config['dump_formats'], $container);
    }

    private function configureDumpFormats(array $config, ContainerBuilder $container): void
    {
        foreach ($config as $key => $format) {
            $this->configureSingleDumpFormat('prime_analyzer.dump_formats.' . $key, $format, $container)
                ->addTag(RegisterDumpFormatsPass::TAG)
            ;
        }
    }

    /**
     * @param string $baseContainerId
     * @param array{
     *     type: string|null,
     *     diff: bool,
     *     html: string|null,
     *     json: string|null,
     *     html_trace: string|null,
     *     storage: null|array{
     *         instant: string,
     *         dsn: string,
     *     },
     * } $format
     * @param ContainerBuilder $container
     *
     * @return Definition The service definition of the configured dump format
     */
    private function configureSingleDumpFormat(string $baseContainerId, array $format, ContainerBuilder $container): Definition
    {
        $definitionId = $baseContainerId . '.format';

        if (!($type = $format['type'])) {
            foreach ($format as $key => $value) {
                if ($value !== null && !is_bool($value)) {
                    $type = $key;
                    break;
                }
            }
        }

        switch ($type) {
            case 'console':
                $definition = $container->register($definitionId, ConsoleDumpFormat::class);
                break;

            case 'html':
                $definition = $container
                    ->register($definitionId, HtmlDumpFormat::class)
                    ->addArgument($format['html'])
                ;
                break;

            case 'json':
                $definition = $container
                    ->register($definitionId, JsonTraceDumpFormat::class)
                    ->addArgument($format['json'])
                ;
                break;

            case 'html_trace':
                $definition = $container
                    ->register($definitionId, HtmlTraceDumpFormat::class)
                    ->addArgument($format['html_trace'])
                ;
                break;

            case 'storage':
                if (!$format['storage']) {
                    throw new InvalidArgumentException('Missing storage configuration for dump format.');
                }

                $container->setDefinition($baseContainerId . '.storage', $this->createStorage($format['storage']));
                $definition = $container
                    ->register($definitionId, StorageDumpFormat::class)
                    ->addArgument(new Reference($baseContainerId . '.storage'))
                    ->addArgument(new Reference('prime_analyzer.instant_factory.' . $format['storage']['instant']))
                ;
                break;

            default:
                throw new InvalidArgumentException('Invalid dump format type: ' . $type);
        }

        if (!$format['diff']) {
            return $definition;
        }

        if (!$format['storage']) {
            throw new InvalidArgumentException('Missing storage configuration for dump format.');
        }

        $container->setDefinition($baseContainerId . '.storage', $this->createStorage($format['storage']));

        return $container
            ->register($baseContainerId . '.diff', DiffDumpFormat::class)
            ->addArgument(new Reference($baseContainerId . '.storage'))
            ->addArgument(new Reference('prime_analyzer.instant_factory.' . $format['storage']['instant']))
            ->addArgument([new Reference($definitionId)])
        ;
    }

    /**
     * @param array{instant: string, dsn: string} $config
     *
     * @return Definition
     */
    private function createStorage(array $config): Definition
    {
        $dsn = Dsn::parse($config['dsn']);

        switch ($dsn->getScheme()) {
            case 'file':
                return new Definition(FileReportStorage::class, [$dsn->getPath()]);

            default:
                throw new InvalidArgumentException('Unknown storage type: ' . $dsn->getScheme());
        }
    }
}
