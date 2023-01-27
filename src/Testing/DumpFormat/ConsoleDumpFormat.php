<?php

namespace Bdf\Prime\Analyzer\Testing\DumpFormat;

use Bdf\Prime\Analyzer\Report;

use function array_filter;
use function count;
use function function_exists;
use function posix_isatty;

/**
 * Dump the report in the console
 */
final class ConsoleDumpFormat implements DumpFormatInterface
{
    /**
     * {@inheritdoc}
     */
    public function dump(array $reports): void
    {
        $reports = array_filter($reports, fn (Report $report) => !empty($report->errors()));

        if (empty($reports)) {
            $this->stdout("No prime reports");
            return;
        }

        $count = count($reports);
        $this->stdout("Prime reports ({$count}):", 'warn');

        foreach ($reports as $report) {
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
     * Print on stdout
     *
     * @param string $message
     * @param string $level
     */
    private function stdout(string $message, string $level = 'info'): void
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
