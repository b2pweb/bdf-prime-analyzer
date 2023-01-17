<?php

namespace Bdf\Prime\Analyzer\Repository\Util;

use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Relations\Exceptions\RelationNotFoundException;
use Bdf\Prime\Repository\RepositoryInterface;

use function count;
use function explode;

/**
 * Class RepositoryUtil
 */
class RepositoryUtil
{
    private Metadata $metadata;

    public function __construct(
        private RepositoryInterface $repository,
    ) {
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

        if (count($parts) !== 2) {
            return false;
        }

        $relation = $this->relation($parts[0]);

        return $relation && $relation->hasAttribute($parts[1]);
    }

    /**
     * Check if the field is in index
     *
     * @param string $field
     *
     * @return bool
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

        if (count($parts) !== 2) {
            return false;
        }

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
