<?php

namespace Bdf\Prime\Analyzer\Storage\Instant;

use InvalidArgumentException;

/**
 * Instant using simple increment of the last instant id
 */
final class IncrementInstant implements ReportInstant
{
    /**
     * @var int
     */
    private $value;

    /**
     * IncrementInstant constructor.
     *
     * @param int $value
     */
    public function __construct(int $value)
    {
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return self::TYPE_INCREMENT;
    }

    /**
     * {@inheritdoc}
     */
    public function value(): string
    {
        return (string) $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function compare(ReportInstant $other): int
    {
        if (!$other instanceof static) {
            throw new InvalidArgumentException(self::class.' can only be compared with same type');
        }

        return $this->value <=> $other->value;
    }
}
