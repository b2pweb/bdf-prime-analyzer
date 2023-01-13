<?php

namespace Bdf\Prime\Analyzer\Metadata;

use Bdf\Prime\Analyzer\IgnoreTagParser;
use Bdf\Prime\Analyzer\Metadata\Attribute\AnalysisOptions;
use Bdf\Prime\Analyzer\Metadata\Attribute\IgnoreAnalysis;
use Bdf\Prime\Analyzer\Report;
use Bdf\Prime\ServiceLocator;
use ReflectionClass;

use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

use function array_values;
use function get_class;
use function is_object;

/**
 * Loads useful metadata for the analyzer
 */
final class AnalyzerMetadata
{
    /**
     * Map entity class name to its global analysis options
     *
     * @var array<class-string, list<AnalysisOptions>>
     */
    private array $analysisOptionsByEntity = [];

    /**
     * Store AnalysisOptions by function name
     * In case of method, the key is in the form "ServiceClass::methodName"
     * In case of function, the key is the function name
     *
     * @var array<string, list<AnalysisOptions>>
     */
    private array $analysisOptionsByFunction = [];

    public function __construct(
        private ServiceLocator $prime,
    ) {
    }

    /**
     * Get list of analysis to ignore for the given report
     *
     * @param Report $report
     *
     * @return list<string>
     */
    public function ignoredAnalysis(Report $report): array
    {
        $options = $this->analysisOptions($report);

        $ignored = [];

        foreach ($options as $option) {
            if ($option->ignore()) {
                $ignored[] = $option->analysis();
            }
        }

        return $ignored;
    }

    /**
     * Get list of analysis options for the given report
     * Options will be filtered for the matching entity
     *
     * @param Report $report
     *
     * @return array<string, AnalysisOptions> Analysis options indexed by the analysis type
     */
    public function analysisOptions(Report $report): array
    {
        $entityName = (string) $report->entity();
        $entityOptions = $entityName ? $this->analysisOptionsForEntity($entityName) : [];
        $callerOptions = isset($report->stackTrace()[1]) ? $this->loadFromStackTraceEntry($report->stackTrace()[1]) : [];
        $lineOption = $this->loadAnalysisOptionsFromLine($report->file(), $report->line());

        /** @var list<AnalysisOptions> $options */
        $options = [
            ...$entityOptions,
            ...$callerOptions,
            ...$lineOption,
        ];

        $reportOptions = [];

        foreach ($options as $option) {
            if ($option->entity() !== null && $option->entity() !== $entityName) {
                continue;
            }

            $previousOption = $reportOptions[$option->analysis()] ?? null;
            $reportOptions[$option->analysis()] = $previousOption ? $previousOption->merge($option) : $option;
        }

        return $reportOptions;
    }

    /**
     * Get list of analysis to ignore for the given entity
     *
     * @param class-string $entity
     *
     * @return list<AnalysisOptions>
     */
    public function analysisOptionsForEntity(string $entity): array
    {
        $options = $this->analysisOptionsByEntity[$entity] ?? null;

        if ($options !== null) {
            return $options;
        }

        if (!($repository = $this->prime->repository($entity))) {
            return [];
        }

        $reflection = new ReflectionClass($repository->mapper());
        $docblock = $reflection->getDocComment();
        $options = [];

        foreach (IgnoreTagParser::parseDocBlock($docblock) as $tag) {
            $type = array_shift($tag);

            $options[$type] = new AnalysisOptions(
                analysis: $type,
                options: $tag,
                ignore: empty($tag),
                entity: $entity,
            );
        }

        foreach ($this->loadAnalysisOptionsFromReflection($reflection, $entity) as $option) {
            $options[$option->analysis()] = $option;
        }

        return $this->analysisOptionsByEntity[$entity] = array_values($options);
    }

    /**
     * Get analysis options for the given method
     * Options are loaded from the method attributes and service class attributes.
     *
     * @param object|class-string $object Service object or class name
     * @param string $method Method name
     *
     * @return list<AnalysisOptions>
     */
    public function analysisOptionsForMethod(object|string $object, string $method): array
    {
        $cacheKey = (is_object($object) ? get_class($object) : $object) . '::' . $method;

        if (isset($this->analysisOptionsByFunction[$cacheKey])) {
            return $this->analysisOptionsByFunction[$cacheKey];
        }

        /** @var list<AnalysisOptions> $options */
        $options = [
            ...$this->loadAnalysisOptionsFromReflection(new ReflectionClass($object)),
            ...$this->loadAnalysisOptionsFromReflection(new ReflectionMethod($object, $method)),
        ];

        $mergedOptions = [];

        foreach ($options as $option) {
            $key = $option->analysis() . '-' . $option->entity();
            $previous = $mergedOptions[$key] ?? null;
            $mergedOptions[$key] = $previous ? $previous->merge($option) : $option;
        }

        return $this->analysisOptionsByFunction[$cacheKey] = array_values($mergedOptions);
    }

    /**
     * Load {@see AnalysisOptions} from the given reflection
     *
     * @param ReflectionFunctionAbstract|ReflectionClass $reflection Reflection object to load analysis options from
     * @param class-string|null $entity Entity class to set to AnalysisOptions object
     *
     * @return list<AnalysisOptions>
     */
    private function loadAnalysisOptionsFromReflection(ReflectionFunctionAbstract|ReflectionClass $reflection, ?string $entity = null): array
    {
        $options = [];

        foreach ($reflection->getAttributes(AnalysisOptions::class) as $attribute) {
            /** @var AnalysisOptions $analysisOptions */
            $analysisOptions = $attribute->newInstance();

            if ($entity !== null) {
                $analysisOptions = $analysisOptions->withEntity($entity);
            }

            $options[$analysisOptions->analysis() . '-' . $analysisOptions->entity()] = $analysisOptions;
        }

        foreach ($reflection->getAttributes(IgnoreAnalysis::class) as $attribute) {
            /** @var IgnoreAnalysis $analysisOptions */
            $ignored = $attribute->newInstance();

            foreach ($ignored->analysis() as $analysis) {
                $options[$analysis . '-'] = new AnalysisOptions(
                    analysis: $analysis,
                    ignore: true,
                    entity: $entity,
                );
            }
        }

        return array_values($options);
    }

    /**
     * Load ignored analysis from the code, using the @prime-analyzer-ignore tag
     *
     * @param string $filename Source file
     * @param int $lineNumber Line number
     *
     * @return list<AnalysisOptions>
     */
    private function loadAnalysisOptionsFromLine(string $filename, int $lineNumber): array
    {
        if (!file_exists($filename)) {
            return [];
        }

        $line = file($filename)[$lineNumber - 1];

        $options = [];

        foreach (IgnoreTagParser::parseLine($line) as $analysis) {
            $options[$analysis] = new AnalysisOptions(
                analysis: $analysis,
                ignore: true,
            );
        }

        return array_values($options);
    }

    /**
     * Get analysis options for the function
     * Options are loaded from the function attributes.
     *
     * @param callable-string $function Function name
     * @return list<AnalysisOptions>
     */
    private function loadAnalysisOptionsFromFunction(string $function): array
    {
        return $this->analysisOptionsByFunction[$function] ??= $this->loadAnalysisOptionsFromReflection(new ReflectionFunction($function));
    }

    /**
     * Load analysis options from a single stack trace
     *
     * @param array{class?: class-string, function?: string, ...} $entry
     * @return list<AnalysisOptions>
     */
    private function loadFromStackTraceEntry(array $entry): array
    {
        if (isset($entry['class'], $entry['function']) && method_exists($entry['class'], $entry['function'])) {
            return $this->analysisOptionsForMethod($entry['class'], $entry['function']);
        }

        if (isset($entry['function']) && function_exists($entry['function'])) {
            return $this->loadAnalysisOptionsFromFunction($entry['function']);
        }

        return [];
    }
}
