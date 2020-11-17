<?php

namespace Bdf\Prime\Analyzer\Storage\Instant;

/**
 * Report instant implementation which always as the same value
 * Useful for keeping only the last report
 */
final class DummyInstant implements ReportInstant
{
    const TYPE = 'dummy';

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return self::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function value(): string
    {
        return 'dummy';
    }

    /**
     * {@inheritdoc}
     */
    public function compare(ReportInstant $other): int
    {
        return 0;
    }
}
