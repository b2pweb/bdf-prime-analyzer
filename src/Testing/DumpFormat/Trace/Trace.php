<?php

namespace Bdf\Prime\Analyzer\Testing\DumpFormat\Trace;

use Bdf\Prime\Analyzer\Report;
use JsonSerializable;

use function array_reverse;
use function array_values;

/**
 * Function call trace tree for all prime calls
 */
final class Trace implements JsonSerializable
{
    private string $function;
    private int $calls = 0;

    /**
     * @var array<class-string, int>
     */
    private array $callsByEntity = [];

    /**
     * Trace are indexed by function name
     *
     * @var array<string, Trace>
     */
    private array $calling = [];

    /**
     * Executed SQL queries
     *
     * @var array<string, string>
     */
    private array $queries = [];

    /**
     * @param string $function
     */
    public function __construct(string $function = '{main}')
    {
        $this->function = $function;
    }

    /**
     * Get the called function name
     * In case of a method, the class name is included, like "MyClass::myMethod" for static method, or "MyClass->myMethod" for instance method
     *
     * @return string
     */
    public function function(): string
    {
        return $this->function;
    }

    /**
     * Number of prime queries called by this function and its children
     *
     * @return int
     */
    public function calls(): int
    {
        return $this->calls;
    }

    /**
     * Number of prime queries called by this function and its children, grouped by entity class
     * DBAL are not counted in this array
     *
     * @return array<class-string, int>
     */
    public function callsByEntity(): array
    {
        return $this->callsByEntity;
    }

    /**
     * Get all called functions as Trace
     *
     * @return list<Trace>
     */
    public function calling(): array
    {
        return array_values($this->calling);
    }

    /**
     * Get all executed SQL queries
     *
     * @return list<string>
     */
    public function queries(): array
    {
        return array_values($this->queries);
    }

    /**
     * Parse report stack trace and push it to the trace
     *
     * @param Report $report
     * @return void
     */
    public function push(Report $report): void
    {
        $entity = $report->entity();

        $currentTrace = $this;
        $currentTrace->calls += $report->calls();

        foreach ($report->queries() as $query) {
            $this->queries[$query] = $query;
        }

        if ($entity) {
            $currentTrace->callsByEntity[$entity] = ($currentTrace->callsByEntity[$entity] ?? 0) + $report->calls();
        }

        foreach (array_reverse($report->stackTrace()) as $entry) {
            $function = $entry['function'];

            if (isset($entry['class'], $entry['type'])) {
                $function = $entry['class'].$entry['type'].$function;
            }

            $currentTrace = ($currentTrace->calling[$function] ??= new Trace($function));

            $currentTrace->calls += $report->calls();

            foreach ($report->queries() as $query) {
                $currentTrace->queries[$query] = $query;
            }

            if ($entity) {
                $currentTrace->callsByEntity[$entity] = ($currentTrace->callsByEntity[$entity] ?? 0) + $report->calls();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return [
            'function' => $this->function,
            'calls' => $this->calls,
            'callsByEntity' => $this->callsByEntity,
            'queries' => array_values($this->queries),
            'calling' => array_values($this->calling),
        ];
    }
}
