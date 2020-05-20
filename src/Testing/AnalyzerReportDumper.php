<?php

namespace Bdf\Prime\Analyzer\Testing;

use Bdf\Prime\Analyzer\Report;

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
     * @var AnalyzerReportDumper
     */
    private static $instance;

    /**
     * @var Report[]
     */
    private $reports = [];


    /**
     * Register the dumper at the shutdown
     * Note: this method will change the self::instance() value
     */
    public function register(): void
    {
        if (!self::$isRegistered) {
            register_shutdown_function(function () {
                // Use closure to ensure that the value of self::$instance is used, in case of change
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
        if (empty($this->reports)) {
            $this->stdout("No prime reports");
            return;
        }

        $count = count($this->reports);
        $this->stdout("Prime reports ({$count}):", 'warn');

        foreach ($this->reports as $report) {
            echo PHP_EOL, $report->file(), ':', $report->line();

            if ($report->entity()) {
                echo ' on ', $report->entity();
            }

            echo ' (called ', $report->calls(), ' times)', PHP_EOL;

            foreach ($report->errors() as $error) {
                echo "\t", $error, PHP_EOL;
            }
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
     * Get the report dumper instance
     * Note: The instance must be register()ed manually
     *
     * @return AnalyzerReportDumper
     */
    public static function instance(): AnalyzerReportDumper
    {
        if (self::$instance) {
            return self::$instance;
        }

        return self::$instance = new static();
    }

    /**
     * Print on stdout
     *
     * @param string $message
     * @param string $level
     */
    private function stdout($message, $level = 'info')
    {
        if ($level === 'info') {
            $level = '42';
        } else {
            $level = '43';
        }

        if (function_exists('posix_isatty') && @posix_isatty(STDOUT)) {
            echo "\n\x1B[{$level};30m{$message}\x1B[0m\n";
        } else {
            echo "\n{$message}\n";
        }
    }
}
