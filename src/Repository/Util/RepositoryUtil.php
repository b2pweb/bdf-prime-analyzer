<?php

namespace Bdf\Prime\Analyzer\Repository\Util;

use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Relations\Exceptions\RelationNotFoundException;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Class RepositoryUtil
 */
class RepositoryUtil
{
    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * RepositoryUtil constructor.
     * @param RepositoryInterface $repository
     */
    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
        $this->metadata = $repository->metadata();
    }

    /**
     * Check if the attribute exists on the current repository or in relation
     *
     * @param string $attribute
     *
     * @return bool
     */
    public function hasAttribute(string $attribute): bool
    {
        if ($this->metadata->attributeExists($attribute)) {
            return true;
        }

        $parts = explode('.', $attribute, 2);
        $relation = $this->relation($parts[0]);

        return $relation && $relation->hasAttribute($parts[1]);
    }

    /**
     * Check if the field is in index
     *
     * @param string $field
     *
     * @return bool
     *
     * @psalm-suppress UndefinedDocblockClass
     */
    public function isIndexed(string $field): bool
    {
        if ($this->metadata->isPrimary($field)) {
            return true;
        }

        if ($this->metadata->attributeExists($field)) {
            $field = $this->metadata->fieldFrom($field);
        }

        foreach ($this->metadata->indexes() as $index) {
            if (isset($index['fields'][$field])) {
                return true;
            }
        }

        $parts = explode('.', $field, 2);
        $relation = $this->relation($parts[0]);

        return $relation && $relation->isIndexed($parts[1]);
    }

    /**
     * Get the repository util related to the relation
     *
     * @param string $name
     *
     * @return RepositoryUtil|null
     */
    public function relation(string $name): ?RepositoryUtil
    {
        try {
            return (new RepositoryUtil($this->repository->relation($name)->relationRepository()));
        } catch (RelationNotFoundException $e) {
            return null;
        }
    }
}
