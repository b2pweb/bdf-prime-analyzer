<?php

namespace Bdf\Prime\Analyzer\Storage\Instant;

use Bdf\Prime\Analyzer\Storage\ReportStorageInterface;

/**
 * Factory for retrieve and create instant
 *
 * @template I as ReportInstant
 */
interface ReportInstantFactory
{
    /**
     * Parse instant value from the storage
     *
     * @param string $value The storage value
     *
     * @return I
     */
    public function parse(string $value): ReportInstant;

    /**
     * Create the next instant instance
     *
     * @param ReportStorageInterface $storage
     *
     * @return I
     */
    public function next(ReportStorageInterface $storage): ReportInstant;

    /**
     * Get the handled instant type name
     * Should be one of the ReportInstant::TYPE_* constant
     *
     * @return string
     */
    public function type(): string;
}
