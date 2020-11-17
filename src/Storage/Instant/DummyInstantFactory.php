<?php

namespace Bdf\Prime\Analyzer\Storage\Instant;

use Bdf\Prime\Analyzer\Storage\ReportStorageInterface;

/**
 * Factory for DummyInstant
 *
 * @implements ReportInstantFactory<DummyInstant>
 */
final class DummyInstantFactory implements ReportInstantFactory
{
    /**
     * {@inheritdoc}
     */
    public function parse(string $value): ReportInstant
    {
        return new DummyInstant();
    }

    /**
     * {@inheritdoc}
     */
    public function next(ReportStorageInterface $storage): ReportInstant
    {
        return new DummyInstant();
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return DummyInstant::TYPE;
    }
}
