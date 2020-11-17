<?php

namespace Bdf\Prime\Analyzer\Storage\Instant;

use Bdf\Collection\Stream\Accumulator\Accumulators;
use Bdf\Collection\Stream\Streams;
use Bdf\Collection\Util\Functor\Transformer\Getter;
use Bdf\Prime\Analyzer\Storage\ReportStorageInterface;

/**
 * Factory for IncrementInstant
 *
 * @implements ReportInstantFactory<IncrementInstant>
 */
final class IncrementInstantFactory implements ReportInstantFactory
{
    /**
     * {@inheritdoc}
     */
    public function parse(string $value): ReportInstant
    {
        return new IncrementInstant((int) $value);
    }

    /**
     * {@inheritdoc}
     */
    public function next(ReportStorageInterface $storage): ReportInstant
    {
        return new IncrementInstant((int) Streams::wrap($storage->instants($this))->map(new Getter('value'))->reduce(Accumulators::max()) + 1);
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return ReportInstant::TYPE_INCREMENT;
    }
}
