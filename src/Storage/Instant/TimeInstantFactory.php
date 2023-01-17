<?php

namespace Bdf\Prime\Analyzer\Storage\Instant;

use Bdf\Prime\Analyzer\Storage\ReportStorageInterface;
use DateTime;

/**
 * Factory for the TimeInstant implementation
 *
 * @implements ReportInstantFactory<TimeInstant>
 */
final class TimeInstantFactory implements ReportInstantFactory
{
    /**
     * {@inheritdoc}
     */
    public function parse(string $value): ReportInstant
    {
        return new TimeInstant(DateTime::createFromFormat(DateTime::ATOM, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function next(ReportStorageInterface $storage): ReportInstant
    {
        return new TimeInstant(new DateTime());
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return ReportInstant::TYPE_TIME;
    }
}
