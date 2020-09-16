<?php

namespace Bdf\Prime\Analyzer\Testing;

use Bdf\Prime\Analyzer\Report;
use Bdf\Prime\Analyzer\Testing\DumpFormat\ConsoleDumpFormat;
use Bdf\Prime\Analyzer\Testing\DumpFormat\DumpFormatInterface;

/**
 * Dump the analyzer reports on the stdout
 */
class AnalyzerReportDumper
{
    /**
     * Flag for registration
     *
     * @var bool
     */
    private static $isRegistered = false;

    /**
     * @var AnalyzerReportDumper|null
     */
    private static $instance;

    /**
     * @var Report[]
     */
    private $reports = [];

    /**
     * @var DumpFormatInterface[]
     */
    private $formats;

    /**
     * AnalyzerReportDumper constructor.
     *
     * @param DumpFormatInterface[] $formats
     */
    public function __construct(?array $formats = null)
    {
        $this->formats = $formats ?? [new ConsoleDumpFormat()];
    }

    /**
     * Register the dumper at the shutdown
     * Note: this method will change the self::instance() value
     */
    public function register(): void
    {
        if (!self::$isRegistered) {
            register_shutdown_function(function () {
                // Use closure to ensure that the value of self::$instance is used, in case of change
                /** @psalm-suppress PossiblyNullReference */
                self::$instance->dump();
            });

            self::$isRegistered = true;
        }

        self::$instance = $this;
    }

    /**
     * Dump the analyzer reports
     */
    public function dump(): void
    {
        foreach ($this->formats as $format) {
            $format->dump($this->reports);
        }
    }

    /**
     * Push reports to dump
     *
     * @param Report[] $reports
     */
    public function push(array $reports): void
    {
        foreach ($reports as $report) {
            if (empty($report->errors())) {
                continue;
            }

            $key = $report->file().':'.$report->line().':'.$report->entity();

            if (isset($this->reports[$key])) {
                $this->reports[$key]->merge($report);
            } else {
                $this->reports[$key] = $report;
            }
        }
    }

    /**
     * Get all reports
     *
     * @return Report[]
     */
    public function reports(): array
    {
        return array_values($this->reports);
    }

    /**
     * Register a new dump format
     *
     * @param DumpFormatInterface $format
     */
    public function addFormat(DumpFormatInterface $format): void
    {
        $this->formats[] = $format;
    }

    /**
     * Get the report dumper instance
     * Note: The instance must be register()ed manually
     *
     * @return AnalyzerReportDumper
     * @psalm-suppress UnsafeInstantiation
     */
    public static function instance(): AnalyzerReportDumper
    {
        if (self::$instance) {
            return self::$instance;
        }

        return self::$instance = new static();
    }
}
