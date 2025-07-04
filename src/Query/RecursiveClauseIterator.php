<?php

namespace Bdf\Prime\Analyzer\Query;

use ArrayIterator;
use Bdf\Collection\Stream\IteratorStream;
use Bdf\Collection\Stream\Streamable;
use Bdf\Collection\Stream\StreamInterface;
use Bdf\Prime\Query\Clause;
use RecursiveIterator;
use RecursiveIteratorIterator;

/**
 * Iterate over nested query clause
 *
 * @implements Streamable<array, array-key>
 * @implements RecursiveIterator<array-key, array>
 * @extends ArrayIterator<array-key, array>
 */
final class RecursiveClauseIterator extends ArrayIterator implements RecursiveIterator, Streamable
{
    /**
     * {@inheritdoc}
     */
    public function hasChildren(): bool
    {
        return !empty($this->current()['nested']);
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren(): RecursiveClauseIterator
    {
        return new static($this->current()['nested']);
    }

    /**
     * {@inheritdoc}
     *
     * @return StreamInterface<array, array-key>
     */
    public function stream(): StreamInterface
    {
        return new IteratorStream(new RecursiveIteratorIterator($this));
    }

    /**
     * Get the iterator for the where clause
     *
     * @param Clause $query
     *
     * @return static
     */
    public static function where(Clause $query): self
    {
        return new static($query->statements['where']);
    }
}
