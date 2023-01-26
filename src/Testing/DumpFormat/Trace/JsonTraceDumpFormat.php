<?php

namespace Bdf\Prime\Analyzer\Testing\DumpFormat\Trace;

use Bdf\Prime\Analyzer\Testing\DumpFormat\DumpFormatInterface;

use function file_put_contents;
use function json_encode;

/**
 * Dump call trace in json format
 */
final class JsonTraceDumpFormat implements DumpFormatInterface
{
    public function __construct(private string $filename)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function dump(array $reports): void
    {
        $trace = new Trace();

        foreach ($reports as $report) {
            $trace->push($report);
        }

        file_put_contents($this->filename, json_encode($trace, JSON_PRETTY_PRINT));
    }
}
