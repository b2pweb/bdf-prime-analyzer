<?php

namespace Bdf\Prime\Analyzer\Storage\Instant;

use DateTime;

/**
 * Instant implementation using a date time
 */
final class TimeInstant implements ReportInstant
{
    /**
     * @var DateTime
     */
    private $value;

    /**
     * TimeInstant constructor.
     * @param DateTime $value
     */
    public function __construct(DateTime $value)
    {
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return self::TYPE_TIME;
    }

    /**
     * {@inheritdoc}
     */
    public function value(): string
    {
        return $this->value->format(DateTime::ATOM);
    }

    /**
     * {@inheritdoc}
     */
    public function compare(ReportInstant $other): int
    {
        return $this->value <=> $other->value;
    }
}
