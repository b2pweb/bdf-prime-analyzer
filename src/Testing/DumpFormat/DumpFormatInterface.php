<?php

namespace Bdf\Prime\Analyzer\Testing\DumpFormat;

use Bdf\Prime\Analyzer\Report;

/**
 * Interface DumpFormatInterface
 */
interface DumpFormatInterface
{
    /**
     * @param Report[] $reports
     */
    public function dump(array $reports): void;
}
