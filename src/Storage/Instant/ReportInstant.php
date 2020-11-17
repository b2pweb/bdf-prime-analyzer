<?php

namespace Bdf\Prime\Analyzer\Storage\Instant;

/**
 * Represent the instant when the report is generated
 *
 * An instant have a type (i.e. generation method) and a value (the real "date")
 * It's use as storage key for an analysis report and it's comparable for check of which two instant is the last one
 */
interface ReportInstant
{
    const TYPE_INCREMENT = 'increment';
    const TYPE_TIME = 'time';

    /**
     * The report type
     * Should be one the ReportInstance::TYPE_* constant
     *
     * @return string
     */
    public function type(): string;

    /**
     * The report value
     *
     * @return string
     */
    public function value(): string;

    /**
     * Compare two instants
     * Note: The other instant must be of the same type as the current instant
     *
     * @param static $other The instant to compare
     *
     * @return int <0 when current < $other, 0 when current == $other, >0 when current > $other
     */
    public function compare(ReportInstant $other): int;
}
