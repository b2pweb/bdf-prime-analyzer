<?php

namespace Bdf\Prime\Analyzer\Query;

use ArrayIterator;
use Bdf\Collection\Stream\IteratorStream;
use Bdf\Collection\Stream\Streamable;
use Bdf\Collection\Stream\StreamInterface;
use Bdf\Prime\Query\Clause;
use RecursiveIterator;

/**
 * Iterate over nested query clause
 */
final class RecursiveClauseIterator extends ArrayIterator implements RecursiveIterator, Streamable
{
    /**
     * {@inheritdoc}
     */
    public function hasChildren()
    {
        return !empty($this->current()['nested']);
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren()
    {
        return new static($this->current()['nested']);
    }

    /**
     * {@inheritdoc}
     */
    public function stream(): StreamInterface
    {
        return new IteratorStream(new \RecursiveIteratorIterator($this));
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
