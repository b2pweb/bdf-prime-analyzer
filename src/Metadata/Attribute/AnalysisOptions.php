<?php

namespace Bdf\Prime\Analyzer\Metadata\Attribute;

use Attribute;
use Bdf\Prime\Analyzer\AnalysisTypes;

use function array_merge;

/**
 * Define single analysis options
 * Add this attribute on a class, method to define the scope.
 *
 * When added on a Mapper class, the attribute will be applied on all queries of the repository.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE | Attribute::TARGET_FUNCTION)]
class AnalysisOptions
{
    public function __construct(
        /**
         * Analysis type
         *
         * @var string
         * @see AnalysisTypes
         */
        private string $analysis,

        /**
         * Options passed to the analysis
         * Those options can be used to filter errors, like ignored fields
         *
         * @var mixed[]
         */
        private array $options = [],

        /**
         * Ignore the analysis
         *
         * @var bool
         */
        private bool $ignore = false,

        /**
         * Apply those options only on the given entity
         * If not set, the options will be applied on all entities
         *
         * @var class-string|null
         */
        private ?string $entity = null,
    ) {
    }

    /**
     * Analysis type
     *
     * @return string
     */
    public function analysis(): string
    {
        return $this->analysis;
    }

    /**
     * Options passed to the analysis
     * Those options can be used to filter errors, like ignored fields
     *
     * @return array
     */
    public function options(): array
    {
        return $this->options;
    }

    /**
     * Apply those options only on the given entity
     * If not set, the options will be applied on all entities
     *
     * @return class-string|null
     */
    public function entity(): ?string
    {
        return $this->entity;
    }

    /**
     * Ignore the analysis
     *
     * @return bool
     */
    public function ignore(): bool
    {
        return $this->ignore;
    }

    /**
     * Link the options to the given entity
     * A new instance will be returned
     *
     * @param class-string $entity
     * @return self
     */
    public function withEntity(string $entity): self
    {
        $self = clone $this;
        $self->entity = $entity;

        return $self;
    }

    /**
     * Merge the two analysis options objects
     * A new instance will be returned
     *
     * @param AnalysisOptions $other
     * @return self
     */
    public function merge(self $other): self
    {
        $self = clone $this;
        $self->options = array_merge($self->options, $other->options);
        $self->ignore = $self->ignore || $other->ignore;

        return $self;
    }
}
