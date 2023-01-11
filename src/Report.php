<?php

namespace Bdf\Prime\Analyzer;

use Bdf\Collection\Util\Hashable;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Query\CommandInterface;
use Bdf\Prime\Query\QueryRepositoryExtension;
use Bdf\Prime\Relations\RelationInterface;
use Bdf\Prime\ServiceLocator;
use LogicException;
use ReflectionClass;

use function array_slice;
use function debug_backtrace;
use function dirname;
use function str_starts_with;

/**
 * Store a query analysis report
 */
final class Report implements Hashable
{
    /**
     * @var string|null
     */
    private static $primeDirectory;

    /**
     * @var class-string|null
     */
    private ?string $entity;

    /**
     * @var list<array{args?: list<mixed>, class?: class-string, file: string, function: string, line: int, object?: object, type?: string}>
     */
    private array $stackTrace;

    private string $file;
    private int $line;
    private array $errors = [];
    private int $calls = 1;
    private bool $loadQuery = false;
    private bool $postProcess = false;

    /**
     * @var array<string, bool>
     */
    private array $ignored = [];

    /**
     * Report constructor.
     *
     * @param class-string|null $entity
     * @param bool $loadStackTrace
     */
    public function __construct(?string $entity, bool $loadStackTrace = true)
    {
        $this->entity = $entity;

        if ($loadStackTrace) {
            $this->initializeStackTrace();
            $this->loadIgnored();
        } else {
            $this->line = 0;
            $this->file = '';
            $this->stackTrace = [];
        }
    }

    /**
     * Parse the stacktrace
     *
     * @throws \ReflectionException
     */
    private function initializeStackTrace(): void
    {
        $this->stackTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $this->stackTrace = array_slice($this->stackTrace, $this->findQueryExecuteCall());

        $this->file = $this->stackTrace[0]['file'];
        $this->line = $this->stackTrace[0]['line'];
    }

    /**
     * Load the analyzer-ignore tag
     */
    private function loadIgnored(): void
    {
        if (!file_exists($this->file)) {
            return;
        }

        $line = file($this->file)[$this->line - 1];

        foreach (IgnoreTagParser::parseLine($line) as $ignored) {
            $this->ignore($ignored);
        }
    }

    /**
     * Get the filename where the query is executed
     *
     * @return string
     */
    public function file(): string
    {
        return $this->file;
    }

    /**
     * Get the line where the query is executed
     *
     * @return int
     */
    public function line(): int
    {
        return $this->line;
    }

    /**
     * Get the calling stack trace for the query execution
     * The return format is the format of debug_backtrace()
     *
     * @return list<array{args?: list<mixed>, class?: class-string, file: string, function: string, line: int, object?: object, type?: string}>
     *
     * @see debug_backtrace()
     */
    public function stackTrace(): array
    {
        return $this->stackTrace;
    }

    /**
     * List the reported errors
     *
     * @return string[]
     */
    public function errors(): array
    {
        return array_values($this->errors);
    }

    /**
     * Get the number of calls
     *
     * @return int
     */
    public function calls(): int
    {
        return $this->calls;
    }

    /**
     * Check if the reported query is caused by a relation load
     *
     * @return bool
     */
    public function isLoad(): bool
    {
        return $this->loadQuery;
    }

    /**
     * Check if the reported query is caused by a "with" relation load
     *
     * @return bool
     */
    public function isWith(): bool
    {
        return $this->loadQuery && $this->postProcess;
    }

    /**
     * Get the queried entity
     *
     * @return class-string|null
     */
    public function entity(): ?string
    {
        return $this->entity;
    }

    /**
     * Add a new error into the report
     * If the error is already set, it'll be ignored
     *
     * @param string $error
     */
    public function addError(string $error): void
    {
        $this->errors[$error] = $error;
    }

    /**
     * Merge a report result into the current one
     *
     * @param Report $report The report to merge
     */
    public function merge(Report $report): void
    {
        $this->errors += $report->errors;
        $this->calls += $report->calls;
    }

    /**
     * {@inheritdoc}
     */
    public function hash(): string
    {
        return json_encode([$this->entity, $this->stackTrace]);
    }

    /**
     * Add an analysis to ignore
     *
     * @param string $toIgnore
     */
    public function ignore(string $toIgnore): void
    {
        $this->ignored[$toIgnore] = true;
    }

    /**
     * Check if the analysis is ignored
     *
     * @param string $analysis
     *
     * @return bool
     */
    public function isIgnored(string $analysis): bool
    {
        return !empty($this->ignored[$analysis]);
    }

    /**
     * Try to find the query execution call from the application source
     * i.e. Return the first stack trace index located outside prime package, following the $query->execute() call
     *
     * @return int
     * @throws \ReflectionException
     */
    private function findQueryExecuteCall(): int
    {
        $executeFound = false;

        foreach ($this->stackTrace as $index => $trace) {
            // Search the CommandInterface->execute() call
            if (
                !$executeFound
                && $trace['function'] === 'execute'
                && isset($trace['class'])
                && is_subclass_of($trace['class'], CommandInterface::class)
            ) {
                $executeFound = true;
            }

            // Search the ConnectionInterface->execute() call
            if (
                !$executeFound
                && $trace['function'] === 'execute'
                && isset($trace['class'])
                && is_subclass_of($trace['class'], ConnectionInterface::class)
            ) {
                $executeFound = true;
            }

            if (in_array($trace['function'], ['load', 'loadIfNotLoaded'])) {
                if (
                    isset($trace['class'])
                    && is_subclass_of($trace['class'], RelationInterface::class)
                ) {
                    $this->loadQuery = true;
                }
            }

            if (
                $trace['function'] === 'processEntities'
                && isset($trace['class'])
                && $trace['class'] == QueryRepositoryExtension::class
            ) {
                $this->postProcess = true;
            }

            // Ignore all internal prime calls
            if ($executeFound && isset($trace['file']) && !str_starts_with($trace['file'], self::primeDirectory())) {
                return $index;
            }
        }

        throw new LogicException('Cannot found any valid Prime Query call');
    }

    /**
     * Get the base directory for prime package
     *
     * @return string
     * @throws \ReflectionException
     */
    private static function primeDirectory(): string
    {
        if (self::$primeDirectory) {
            return self::$primeDirectory;
        }

        $reflection = new ReflectionClass(ServiceLocator::class);

        return self::$primeDirectory = dirname($reflection->getFileName());
    }
}
